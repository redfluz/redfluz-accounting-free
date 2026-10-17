<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace App\Http\Controllers\Purchase;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\GoodsReceiptCompletion;
use App\Enums\GoodsReceiptLineState;
use App\Enums\LedgerStatus;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\GoodsReceipt\StoreGoodsReceiptRequest;
use App\Http\Requests\Purchase\GoodsReceipt\UpdateGoodsReceiptRequest;
use App\Models\Inventory\GoodsReceiptAttachment;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use App\Models\Inventory\StockLocation;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderLine;
use App\Services\DecimalService;
use App\Support\Data\Purchase\GoodsReceiptData;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Maintain goods received notes against approved purchase orders.
 */
class GoodsReceiptController extends Controller
{
    use GeneratesDocumentNumber;

    /**
     * The completion buckets offered by the goods receipt filter.
     *
     * @var array<string, string>
     */
    private const array COMPLETION_OPTIONS = [
        '' => 'goods_receipts.completion.all',
        'complete' => 'goods_receipts.completion.complete',
        'partial' => 'goods_receipts.completion.partial',
        'none' => 'goods_receipts.completion.none',
    ];

    /**
     * Display a listing of the resource.
     * Display one row per goods received note.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::GOODS_RECEIPTS);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $completion = $this->completionFilter($request);

        $completionOptions = collect(self::COMPLETION_OPTIONS)
            ->map(fn (string $translationKey): string => __($translationKey))
            ->all();

        $goodsReceiptQuery = ProductLedger::query()
            ->selectRaw(
                'MIN(product_ledgers.id) as id, product_ledgers.document_number, '
                .'MAX(product_ledgers.reference_number) as reference_number, '
                .'MAX(product_ledgers.company_name) as company_name, MAX(product_ledgers.date) as date, '
                .'MAX(product_ledgers.status) as status, '
                .'MAX(product_ledgers.stock_location_name) as stock_location_name, '
                .'MAX(purchase_orders.is_received) as purchase_order_is_received, '
                .'MAX(purchase_orders.is_partial_received) as purchase_order_is_partial_received, '
                .'COUNT(*) as line_count, SUM(product_ledgers.quantity) as total_quantity, '
                .'SUM(product_ledgers.total_cost) as total_cost'
            )
            ->where('product_ledgers.source_type', ProductMovementSource::GOODS_RECEIPT->value)
            // Every column is qualified because product_ledgers and
            // purchase_orders both own id, company_name, date and status.
            // This is an inner join, not a left join, so the list shows only
            // receipts that belong to a real purchase order. Seeded opening stock
            // carries a placeholder reference such as DEMO-OPENING-STOCK that
            // resolves to no order; those rows are stock balances, not receipts,
            // and listing them made "Not received" read as an unfinished document.
            ->join('purchase_orders', function (JoinClause $join): void {
                $join->on('purchase_orders.document_number', '=', 'product_ledgers.reference_number')
                    ->whereNull('purchase_orders.deleted_at');
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('product_ledgers.document_number', 'like', "%$search%")
                        ->orWhere('product_ledgers.reference_number', 'like', "%$search%")
                        ->orWhere('product_ledgers.company_name', 'like', "%$search%")
                        ->orWhere('product_ledgers.product_sku', 'like', "%$search%")
                        ->orWhere('product_ledgers.product_name', 'like', "%$search%");
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('product_ledgers.date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('product_ledgers.date', '<=', $endDate));

        if ($completion instanceof GoodsReceiptCompletion) {
            $this->applyCompletionFilter($goodsReceiptQuery, $completion);
        }

        $goodsReceipts = $goodsReceiptQuery
            ->groupBy('product_ledgers.document_number')
            ->orderByDesc('product_ledgers.date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('purchase.goods-receipts.index', compact(
            'goodsReceipts',
            'search',
            'startDate',
            'endDate',
            'completion',
            'completionOptions'
        ));
    }

    /**
     * Read the completion filter from the request.
     *
     * An unrecognised value is treated as no filter rather than as an error, so a
     * hand edited query string cannot make the list return nothing.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return GoodsReceiptCompletion|null The requested bucket, or null for all.
     */
    private function completionFilter(Request $request): ?GoodsReceiptCompletion
    {
        $raw = $request->string('completion')->toString();

        return $raw === '' ? null : GoodsReceiptCompletion::tryFrom($raw);
    }

    /**
     * Restrict the listing to one completion bucket.
     *
     * Only receipts joined to a real purchase order reach this query, so every
     * bucket is reached through purchase_orders.status.
     *
     * @param  Builder<ProductLedger>  $query  The goods receipt query.
     * @param  GoodsReceiptCompletion  $completion  The bucket to keep.
     */
    private function applyCompletionFilter(Builder $query, GoodsReceiptCompletion $completion): void
    {
        match ($completion) {
            GoodsReceiptCompletion::COMPLETE => $query->where('purchase_orders.is_received', true),
            GoodsReceiptCompletion::PARTIAL => $query->where('purchase_orders.is_partial_received', true),
            // The order exists but carries neither a received nor a partially
            // received status. Since the join is an inner one the status can no
            // longer be null, so a pending receipt awaiting its first posting is
            // what lands here.
            GoodsReceiptCompletion::NONE => $query->where('purchase_orders.is_received', false)
                ->where('purchase_orders.is_partial_received', false),
        };
    }

    /**
     * Show the form for creating a new resource.
     * Show the creation form and load lines from the selected purchase order.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function create(Request $request): View
    {
        $this->allows(FileResources::GOODS_RECEIPTS, FileAction::ADD);

        $selectedOrderNumber = $request->string('purchase_order_number')->toString();

        return view(
            'purchase.goods-receipts.create',
            $this->formData($selectedOrderNumber)
        );
    }

    /**
     * Handle the incoming request to create a new resource.
     * Store one pending goods received note.
     *
     * @param  StoreGoodsReceiptRequest  $request  The validated request instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function store(StoreGoodsReceiptRequest $request): RedirectResponse
    {
        $data = GoodsReceiptData::from($request->validated());

        $stockLocation = $this->defaultStockLocation();

        $availableLines = $this->purchaseInvoiceLines(
            (string) $data->purchaseOrderNumber
        )->keyBy('product_id');
        $orderSummary = $this->purchaseInvoiceSummary(
            (string) $data->purchaseOrderNumber
        );

        /** @var array<int, array{quantity: string|int|float}> $linesInput */
        $linesInput = is_array($request->input('lines')) ? $request->input('lines') : [];
        /** @var Collection<int, array{quantity: string|int|float}> $receivingLines */
        $receivingLines = collect($linesInput)
            ->filter(static function (array $line): bool {
                $quantity = $line['quantity'];

                return is_numeric($quantity) && (float) $quantity > 0;
            });

        if ($receivingLines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => __('goods_receipts.validation.line_required'),
            ]);
        }

        /** @var EloquentCollection<string, Product> $products */
        $products = Product::query()
            ->whereIn('id', $receivingLines->pluck('product_id')->unique())
            ->where('is_service', false)
            ->get()
            ->keyBy('id');

        if ($availableLines->isNotEmpty()) {
            foreach ($receivingLines as $lineNumber => $line) {
                /** @var array<string, string|int|float> $line */
                $lineProductId = $line['product_id'] ?? null;
                $orderLine = $availableLines->get(is_string($lineProductId) ? $lineProductId : null);

                if ($orderLine === null) {
                    throw ValidationException::withMessages([
                        "lines.$lineNumber.product_id" => __('goods_receipts.validation.product_not_on_invoice'),
                    ]);
                }

                $lineQuantityRaw = $line['quantity'] ?? null;
                $lineQuantity = is_numeric($lineQuantityRaw) ? (string) $lineQuantityRaw : '0';
                $remainingQuantityRaw = $orderLine['remaining_quantity'] ?? null;
                $remainingQuantity = is_numeric($remainingQuantityRaw) ? (string) $remainingQuantityRaw : '0';

                if (bccomp(DecimalService::normalize($lineQuantity), DecimalService::normalize($remainingQuantity), 4) === 1) {
                    throw ValidationException::withMessages([
                        "lines.$lineNumber.quantity" => __('goods_receipts.validation.quantity_exceeds_remaining', [
                            'remaining' => number_format(is_numeric($remainingQuantityRaw) ? (float) $remainingQuantityRaw : 0.0, 4),
                        ]),
                    ]);
                }
            }
        } else {
            foreach ($receivingLines as $lineNumber => $line) {
                /** @var array<string, string|int|float> $line */
                $lineProductId = $line['product_id'] ?? null;
                if (! $products->has(is_string($lineProductId) ? $lineProductId : '')) {
                    throw ValidationException::withMessages([
                        "lines.$lineNumber.product_id" => __('goods_receipts.validation.stock_product_required'),
                    ]);
                }
            }

            $totalReceivingQuantity = $receivingLines->reduce(
                static function (string $total, array $line): string {
                    return bcadd($total, DecimalService::normalize((string) $line['quantity']), 4);
                },
                '0.0000'
            );

            $summaryRemainingRaw = $orderSummary['remaining_quantity'] ?? null;
            $summaryRemaining = is_string($summaryRemainingRaw) ? $summaryRemainingRaw : '0';

            if (bccomp($totalReceivingQuantity, DecimalService::normalize($summaryRemaining), 4) === 1) {
                throw ValidationException::withMessages([
                    'lines' => __('goods_receipts.validation.quantity_exceeds_remaining', [
                        'remaining' => number_format(is_numeric($summaryRemainingRaw) ? (float) $summaryRemainingRaw : 0.0, 4),
                    ]),
                ]);
            }
        }

        $purchaseOrder = PurchaseOrder::query()
            ->where('document_number', $data->purchaseOrderNumber)
            ->whereNotNull('approved_at')
            ->firstOrFail();
        $documentNumber = $this->generateGoodsReceiptNumber();

        DB::transaction(function () use (
            $availableLines,
            $documentNumber,
            $orderSummary,
            $purchaseOrder,
            $products,
            $receivingLines,
            $stockLocation,
            $data,
            $request
        ): void {
            $now = now();
            $movementRows = $receivingLines->map(function (array $line) use (
                $availableLines,
                $documentNumber,
                $orderSummary,
                $now,
                $products,
                $purchaseOrder,
                $stockLocation,
                $data,
                $request
            ): array {
                /** @var array<string, string|int|float> $line */
                $lineProductId = $line['product_id'] ?? null;
                $orderLine = $availableLines->get(is_string($lineProductId) ? $lineProductId : null);
                $product = $products->get(is_string($lineProductId) ? $lineProductId : null);
                if ($orderLine === null && $product === null) {
                    throw ValidationException::withMessages([
                        'lines' => __('goods_receipts.validation.stock_product_required'),
                    ]);
                }
                $lineQuantityRaw = $line['quantity'] ?? null;
                $quantity = DecimalService::normalize(is_numeric($lineQuantityRaw) ? (string) $lineQuantityRaw : '0');
                $orderLineUnitCostRaw = $orderLine['unit_cost'] ?? null;
                $summaryUnitCostRaw = $orderSummary['unit_cost'] ?? null;
                $lineUnitCostRaw = $line['unit_cost'] ?? null;
                $lineUnitCost = is_string($lineUnitCostRaw) ? $lineUnitCostRaw : (is_numeric($lineUnitCostRaw) ? (string) $lineUnitCostRaw : '');
                $unitCost = $orderLine !== null
                    ? (is_string($orderLineUnitCostRaw) ? $orderLineUnitCostRaw : '0.00')
                    : ($lineUnitCost !== '' ? $lineUnitCost : (is_string($summaryUnitCostRaw) ? $summaryUnitCostRaw : '0.00'));

                return [
                    'id' => (string) Str::uuid7(),
                    'product_id' => $line['product_id'],
                    'stock_location_id' => $stockLocation->id,
                    'stock_location_name' => $stockLocation->name,
                    'company_id' => $purchaseOrder->company_id,
                    'source_entry_id' => $orderLine['source_entry_id'] ?? $orderSummary['source_entry_id'],
                    'cogs_ledger_id' => null,
                    'inventory_ledger_id' => null,
                    'product_sku' => $orderLine['product_sku'] ?? $product?->sku,
                    'product_name' => $orderLine['product_name'] ?? $product?->name,
                    'company_name' => $purchaseOrder->company_name,
                    'document_number' => $documentNumber,
                    'reference_number' => $data->purchaseOrderNumber,
                    'source_type' => ProductMovementSource::GOODS_RECEIPT->value,
                    'direction' => ProductMovementDirection::IN->value,
                    'date' => $request->dateValue(),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => bcmul(DecimalService::normalize($quantity), DecimalService::normalize($unitCost), 2),
                    'status' => ProductMovementStatus::PENDING->value,
                    'activated_at' => null,
                    'notes' => $data->notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            })->all();
            ProductLedger::query()->insert($movementRows);
        });
        $this->storeAttachments($request, $documentNumber);

        return redirect()
            ->route('purchase.goods-receipts.show', $documentNumber)
            ->with(
                'success',
                FlashMessage::created(FileResources::GOODS_RECEIPTS)
            );
    }

    /**
     * Display the specified resource.
     * Display one goods received note.
     *
     * @param  string  $documentNumber  The document number value.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::GOODS_RECEIPTS);

        $entries = $this->goodsReceiptEntries($documentNumber);
        // goodsReceiptEntries() already aborts on an unknown document, so first()
        // is guaranteed here rather than merely likely.
        $goodsReceipt = $entries->firstOrFail();
        /** @var EloquentCollection<int, GoodsReceiptAttachment> $attachments */
        $attachments = GoodsReceiptAttachment::query()
            ->where('document_number', $documentNumber)
            ->oldest()
            ->get();

        $lines = $this->receiptLines($entries, (string) $goodsReceipt->reference_number);
        $hasPending = $entries->contains(
            fn (ProductLedger $entry): bool => $entry->status === ProductMovementStatus::PENDING
        );
        $allPending = $entries->every(
            fn (ProductLedger $entry): bool => $entry->status === ProductMovementStatus::PENDING
        );

        return view('purchase.goods-receipts.show', compact(
            'attachments',
            'documentNumber',
            'lines',
            'goodsReceipt',
            'hasPending',
            'allPending'
        ));
    }

    /**
     * Show the form for editing an open resource.
     * Show the edit form for one pending goods received note.
     *
     * @param  string  $documentNumber  The document number value.
     * @return View Returns the rendered Blade view.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::GOODS_RECEIPTS, FileAction::UPDATE);

        $entries = $this->goodsReceiptEntries($documentNumber);
        abort_if(
            $entries->contains(fn (ProductLedger $entry): bool => $entry->status === ProductMovementStatus::ACTIVE),
            409
        );

        $firstEntry = $entries->first();
        abort_if($firstEntry === null, 404);
        $selectedOrderNumber = (string) $firstEntry->reference_number;

        return view(
            'purchase.goods-receipts.edit',
            $this->formData($selectedOrderNumber, $documentNumber)
            + compact('documentNumber', 'entries')
            + [
                'attachments' => GoodsReceiptAttachment::query()
                    ->where('document_number', $documentNumber)
                    ->oldest()
                    ->get(),
            ]
        );
    }

    /**
     * Update an existing resource by its object.
     * Update one pending goods received note.
     *
     * @param  UpdateGoodsReceiptRequest  $request  The validated request instance.
     * @param  string  $documentNumber  The document number value.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(UpdateGoodsReceiptRequest $request, string $documentNumber): RedirectResponse
    {
        $existingEntries = $this->goodsReceiptEntries($documentNumber);
        abort_if(
            $existingEntries->contains(
                fn (ProductLedger $entry): bool => $entry->status === ProductMovementStatus::ACTIVE
            ),
            409
        );

        $this->abortIfInvoicePostedForReceipt($documentNumber);

        $data = GoodsReceiptData::from($request->validated());
        /** @var array<int, mixed> $deleteAttachmentIds */
        $deleteAttachmentIds = is_array($request->validated('delete_attachments')) ? $request->validated('delete_attachments') : [];
        $this->validateAttachmentLimit($request, $documentNumber, $deleteAttachmentIds);

        $stockLocation = $this->defaultStockLocation();

        $availableLines = $this->purchaseInvoiceLines(
            (string) $data->purchaseOrderNumber,
            $documentNumber
        )->keyBy('product_id');
        $invoiceSummary = $this->purchaseInvoiceSummary(
            (string) $data->purchaseOrderNumber,
            $documentNumber
        );

        /** @var array<int, array{quantity: string|int|float}> $linesInput */
        $linesInput = is_array($request->input('lines')) ? $request->input('lines') : [];
        /** @var Collection<int, array{quantity: string|int|float}> $receivingLines */
        $receivingLines = collect($linesInput)
            ->filter(static function (array $line): bool {
                $quantity = $line['quantity'];

                return is_numeric($quantity) && (float) $quantity > 0;
            });

        if ($receivingLines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => __('goods_receipts.validation.line_required'),
            ]);
        }

        /** @var EloquentCollection<string, Product> $products */
        $products = Product::query()
            ->whereIn('id', $receivingLines->pluck('product_id')->unique())
            ->where('is_service', false)
            ->get()
            ->keyBy('id');

        if ($availableLines->isNotEmpty()) {
            foreach ($receivingLines as $lineNumber => $line) {
                /** @var array<string, string|int|float> $line */
                $lineProductId = $line['product_id'] ?? null;
                $invoiceLine = $availableLines->get(is_string($lineProductId) ? $lineProductId : null);

                if ($invoiceLine === null) {
                    throw ValidationException::withMessages([
                        "lines.$lineNumber.product_id" => __('goods_receipts.validation.product_not_on_invoice'),
                    ]);
                }

                $lineQuantityRaw = $line['quantity'] ?? null;
                $lineQuantity = is_numeric($lineQuantityRaw) ? (string) $lineQuantityRaw : '0';
                $remainingQuantityRaw = $invoiceLine['remaining_quantity'] ?? null;
                $remainingQuantity = is_numeric($remainingQuantityRaw) ? (string) $remainingQuantityRaw : '0';

                if (bccomp(DecimalService::normalize($lineQuantity), DecimalService::normalize($remainingQuantity), 4) === 1) {
                    throw ValidationException::withMessages([
                        "lines.$lineNumber.quantity" => __('goods_receipts.validation.quantity_exceeds_remaining', [
                            'remaining' => number_format(is_numeric($remainingQuantityRaw) ? (float) $remainingQuantityRaw : 0.0, 4),
                        ]),
                    ]);
                }
            }
        } else {
            foreach ($receivingLines as $lineNumber => $line) {
                /** @var array<string, string|int|float> $line */
                $lineProductId = $line['product_id'] ?? null;
                if (! $products->has(is_string($lineProductId) ? $lineProductId : '')) {
                    throw ValidationException::withMessages([
                        "lines.$lineNumber.product_id" => __('goods_receipts.validation.stock_product_required'),
                    ]);
                }
            }

            $totalReceivingQuantity = $receivingLines->reduce(
                static function (string $total, array $line): string {
                    return bcadd($total, DecimalService::normalize((string) $line['quantity']), 4);
                },
                '0.0000'
            );

            $summaryRemainingRaw = $invoiceSummary['remaining_quantity'] ?? null;
            $summaryRemaining = is_string($summaryRemainingRaw) ? $summaryRemainingRaw : '0';

            if (bccomp($totalReceivingQuantity, DecimalService::normalize($summaryRemaining), 4) === 1) {
                throw ValidationException::withMessages([
                    'lines' => __('goods_receipts.validation.quantity_exceeds_remaining', [
                        'remaining' => number_format(is_numeric($summaryRemainingRaw) ? (float) $summaryRemainingRaw : 0.0, 4),
                    ]),
                ]);
            }
        }

        $purchaseInvoice = PurchaseOrder::query()
            ->where('document_number', $data->purchaseOrderNumber)
            ->whereNotNull('approved_at')
            ->firstOrFail();

        DB::transaction(function () use (
            $availableLines,
            $documentNumber,
            $invoiceSummary,
            $purchaseInvoice,
            $products,
            $receivingLines,
            $stockLocation,
            $data,
            $request
        ): void {
            ProductLedger::query()
                ->where('document_number', $documentNumber)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->delete();

            $now = now();
            $movementRows = $receivingLines->map(function (array $line) use (
                $availableLines,
                $documentNumber,
                $invoiceSummary,
                $now,
                $products,
                $purchaseInvoice,
                $stockLocation,
                $data,
                $request
            ): array {
                /** @var array<string, string|int|float> $line */
                $lineProductId = $line['product_id'] ?? null;
                $invoiceLine = $availableLines->get(is_string($lineProductId) ? $lineProductId : null);
                $product = $products->get(is_string($lineProductId) ? $lineProductId : null);
                if ($invoiceLine === null && $product === null) {
                    throw ValidationException::withMessages([
                        'lines' => __('goods_receipts.validation.stock_product_required'),
                    ]);
                }
                $lineQuantityRaw = $line['quantity'] ?? null;
                $quantity = DecimalService::normalize(is_numeric($lineQuantityRaw) ? (string) $lineQuantityRaw : '0');
                $invoiceLineUnitCostRaw = $invoiceLine['unit_cost'] ?? null;
                $summaryUnitCostRaw = $invoiceSummary['unit_cost'] ?? null;
                $lineUnitCostRaw = $line['unit_cost'] ?? null;
                $lineUnitCost = is_string($lineUnitCostRaw) ? $lineUnitCostRaw : (is_numeric($lineUnitCostRaw) ? (string) $lineUnitCostRaw : '');
                $unitCost = $invoiceLine !== null
                    ? (is_string($invoiceLineUnitCostRaw) ? $invoiceLineUnitCostRaw : '0.00')
                    : ($lineUnitCost !== '' ? $lineUnitCost : (is_string($summaryUnitCostRaw) ? $summaryUnitCostRaw : '0.00'));

                return [
                    'id' => (string) Str::uuid7(),
                    'product_id' => $line['product_id'],
                    'stock_location_id' => $stockLocation->id,
                    'stock_location_name' => $stockLocation->name,
                    'company_id' => $purchaseInvoice->company_id,
                    'source_entry_id' => $invoiceLine['source_entry_id'] ?? $invoiceSummary['source_entry_id'],
                    'cogs_ledger_id' => null,
                    'inventory_ledger_id' => null,
                    'product_sku' => $invoiceLine['product_sku'] ?? $product?->sku,
                    'product_name' => $invoiceLine['product_name'] ?? $product?->name,
                    'company_name' => $purchaseInvoice->company_name,
                    'document_number' => $documentNumber,
                    'reference_number' => $data->purchaseOrderNumber,
                    'source_type' => ProductMovementSource::GOODS_RECEIPT->value,
                    'direction' => ProductMovementDirection::IN->value,
                    'date' => $request->dateValue(),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => bcmul(DecimalService::normalize($quantity), DecimalService::normalize($unitCost), 2),
                    'status' => ProductMovementStatus::PENDING->value,
                    'activated_at' => null,
                    'notes' => $data->notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            })->all();
            ProductLedger::query()->insert($movementRows);
        });
        $this->deleteAttachments($documentNumber, $deleteAttachmentIds);
        $this->storeAttachments($request, $documentNumber);

        return redirect()
            ->route('purchase.goods-receipts.show', $documentNumber)
            ->with(
                'success',
                FlashMessage::updated(FileResources::GOODS_RECEIPTS)
            );
    }

    /**
     * Activate all received quantities for one goods received note.
     *
     *
     * @param  string  $documentNumber  The document number value.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::GOODS_RECEIPTS, FileAction::APPROVE);

        DB::transaction(function () use ($documentNumber): void {
            /** @var EloquentCollection<int, ProductLedger> $entries */
            $entries = ProductLedger::query()
                ->where('document_number', $documentNumber)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->lockForUpdate()
                ->get();

            abort_if($entries->isEmpty(), 404);
            $pendingIds = $entries
                ->filter(fn (ProductLedger $entry): bool => $entry->status === ProductMovementStatus::PENDING)
                ->modelKeys();
            abort_if($pendingIds === [], 409);

            ProductLedger::query()
                ->whereKey($pendingIds)
                ->update([
                    'status' => ProductMovementStatus::ACTIVE->value,
                    'activated_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->recomputePurchaseOrderStatus((string) $entries->first()->reference_number);
        });

        return redirect()
            ->route('purchase.goods-receipts.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::GOODS_RECEIPTS));
    }

    /**
     * Net quantity currently received against one purchase order.
     *
     * Direction aware on purpose. A quantity correction is stored as a movement,
     * so reducing a receipt writes an out row. Summing the quantity column would
     * add that reduction back instead of netting it off, and an order whose
     * corrected total fell below the ordered quantity would still be marked fully
     * received.
     *
     * @param  string  $purchaseOrderNumber  The purchase order number.
     * @return numeric-string The net active received quantity.
     */
    private function receivedQuantityForOrder(string $purchaseOrderNumber): string
    {
        $receivedIn = (float) ProductLedger::query()
            ->where('reference_number', $purchaseOrderNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->where('direction', ProductMovementDirection::IN->value)
            ->sum('quantity');

        $receivedOut = (float) ProductLedger::query()
            ->where('reference_number', $purchaseOrderNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->where('direction', ProductMovementDirection::OUT->value)
            ->sum('quantity');

        return DecimalService::normalize((string) ($receivedIn - $receivedOut));
    }

    /**
     * Recompute one purchase order status from its active receipts.
     *
     * Kept in step with GoodsReceiptAdjustmentController::recomputePurchaseOrderStatus()
     * so deleting a pending receipt keeps the order status accurate even if it had
     * drifted out of sync with the actual received quantity.
     *
     * @param  string  $purchaseOrderNumber  The purchase order number.
     */
    private function recomputePurchaseOrderStatus(string $purchaseOrderNumber): void
    {
        $purchaseOrder = PurchaseOrder::query()
            ->where('document_number', $purchaseOrderNumber)
            ->lockForUpdate()
            ->first();

        if ($purchaseOrder === null) {
            return;
        }

        $orderedQuantity = $purchaseOrder->lines->reduce(
            fn (string $total, PurchaseOrderLine $line): string => bcadd($total, (string) $line->quantity, 4),
            '0.0000'
        );

        $receivedQuantity = $this->receivedQuantityForOrder($purchaseOrderNumber);

        $purchaseOrder->update(
            bccomp($receivedQuantity, $orderedQuantity, 4) >= 0
                ? ['is_received' => true, 'is_partial_received' => false]
                : ['is_received' => false, 'is_partial_received' => true]
        );
    }

    /**
     * Remove one pending goods received note.
     *
     * Only a pending receipt can be deleted. Once the quantities are active they
     * have already moved stock, so removing the rows would silently change on-hand
     * quantities; a posted receipt is corrected with the adjustment screen instead.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::GOODS_RECEIPTS, FileAction::REMOVE);

        DB::transaction(function () use ($documentNumber): void {
            /** @var EloquentCollection<int, ProductLedger> $entries */
            $entries = ProductLedger::query()
                ->where('document_number', $documentNumber)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->lockForUpdate()
                ->get();

            abort_if($entries->isEmpty(), 404);

            if ($entries->contains(
                fn (ProductLedger $entry): bool => $entry->status !== ProductMovementStatus::PENDING
            )) {
                throw ValidationException::withMessages([
                    'document_number' => __('goods_receipts.validation.posted_delete_forbidden'),
                ]);
            }

            $this->abortIfInvoicePostedForReceipt($documentNumber);

            $this->deleteAllAttachments($documentNumber);

            // Soft delete. This system never removes rows outright, and every read
            // of these tables goes through Eloquent, so the ProductLedger soft delete
            // scope keeps the receipt out of stock totals and out of the received
            // quantity maths that gates how much more can be received.
            ProductLedger::query()
                ->whereKey($entries->modelKeys())
                ->delete();
        });

        return redirect()
            ->route('purchase.goods-receipts.index')
            ->with('success', FlashMessage::deleted(FileResources::GOODS_RECEIPTS));
    }

    /**
     * Download one supporting document belonging to the Goods Received Note.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @param  GoodsReceiptAttachment  $attachment  The attachment resource instance.
     * @return StreamedResponse Returns the requested file download.
     */
    public function downloadAttachment(
        string $documentNumber,
        GoodsReceiptAttachment $attachment
    ): StreamedResponse {
        $this->allows(FileResources::GOODS_RECEIPTS);
        abort_unless($attachment->document_number === $documentNumber, 404);
        abort_unless($this->documentExists($attachment->file_path), 404);

        return $this->downloadDocument($attachment->file_path, $attachment->original_name);
    }

    /**
     * Return purchase invoices and receiving lines used by GRN forms.
     *
     *
     * @param  string  $selectedInvoiceNumber  The selected invoice number value.
     * @param  string|null  $currentDocumentNumber  The current document number value.
     * @return array<string, mixed>
     */
    private function formData(
        string $selectedInvoiceNumber = '',
        ?string $currentDocumentNumber = null
    ): array {
        $purchaseInvoices = PurchaseOrder::query()
            ->whereNotNull('approved_at')
            ->orderByDesc('date')
            ->get();

        /** @var EloquentCollection<int, StockLocation> $stockLocations */
        $stockLocations = StockLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedStockLocationId = $currentDocumentNumber !== null
            ? ProductLedger::query()
                ->where('document_number', $currentDocumentNumber)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->value('stock_location_id')
            : $stockLocations->first()?->id;

        return [
            'purchaseInvoices' => $purchaseInvoices,
            'stockLocations' => $stockLocations,
            'selectedStockLocationId' => $selectedStockLocationId,
            'products' => Product::query()
                ->where('is_service', false)
                ->orderBy('name')
                ->get(),
            'selectedInvoiceNumber' => $selectedInvoiceNumber,
            'invoiceLines' => $selectedInvoiceNumber !== ''
                ? $this->purchaseInvoiceLines($selectedInvoiceNumber, $currentDocumentNumber)
                : collect(),
            'invoiceSummary' => $selectedInvoiceNumber !== ''
                ? $this->purchaseInvoiceSummary($selectedInvoiceNumber, $currentDocumentNumber)
                : null,
        ] + ['postingDateMin' => null, 'postingDateMax' => null];
    }

    /**
     * Get the single active stock location for the free version.
     *
     * @return StockLocation Returns the default stock location.
     */
    private function defaultStockLocation(): StockLocation
    {
        return StockLocation::query()
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Store uploaded Goods Received Note files and insert their metadata in one batch.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return void Returns nothing.
     *
     * @throws Throwable
     */
    private function storeAttachments(Request $request, string $documentNumber): void
    {
        $uploadedFiles = $request->file('attachments', []);
        if (! is_array($uploadedFiles) || $uploadedFiles === []) {
            return;
        }

        $user = $request->user();
        abort_if($user === null, 403);
        $now = now();
        $attachmentRows = [];
        $storedPaths = [];
        try {
            foreach ($uploadedFiles as $uploadedFile) {
                try {
                    $filePath = $this->storeUpload($uploadedFile, "goods-receipts/$documentNumber");
                } catch (RuntimeException) {
                    throw ValidationException::withMessages([
                        'attachments' => __('goods_receipts.validation.attachment_upload_failed'),
                    ]);
                }

                $storedPaths[] = $filePath;
                $attachmentRows[] = [
                    'id' => (string) Str::uuid7(),
                    'document_number' => $documentNumber,
                    'file_path' => $filePath,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getMimeType(),
                    'file_size' => (int) $uploadedFile->getSize(),
                    'uploaded_by' => $user->id,
                    'uploaded_by_name' => $user->name,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }
            GoodsReceiptAttachment::query()->insert($attachmentRows);
        } catch (Throwable $throwable) {
            $this->deleteDocuments($storedPaths);

            throw $throwable;
        }
    }

    /**
     * Remove selected Goods Received Note attachments.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @param  array<int, mixed>  $attachmentIds  The validated attachment identifiers.
     * @return void Returns nothing.
     */
    private function deleteAttachments(string $documentNumber, array $attachmentIds): void
    {
        $ids = array_values(array_filter($attachmentIds, is_string(...)));
        if ($ids === []) {
            return;
        }

        /** @var EloquentCollection<int, GoodsReceiptAttachment> $attachments */
        $attachments = GoodsReceiptAttachment::query()
            ->where('document_number', $documentNumber)
            ->whereKey($ids)
            ->get();
        /** @var array<int, string> $filePaths */
        $filePaths = $attachments->pluck('file_path')->all();
        $this->deleteDocuments($filePaths);
        GoodsReceiptAttachment::query()->whereKey($attachments->modelKeys())->delete();
    }

    /**
     * Remove every attachment belonging to a goods received note.
     *
     * Used when the whole note is deleted, so orphaned files are not left on the
     * public disk after their metadata rows are gone.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return void Returns nothing.
     */
    private function deleteAllAttachments(string $documentNumber): void
    {
        /** @var EloquentCollection<int, GoodsReceiptAttachment> $attachments */
        $attachments = GoodsReceiptAttachment::query()
            ->where('document_number', $documentNumber)
            ->get();

        if ($attachments->isEmpty()) {
            return;
        }

        // The file itself is removed from disk; the metadata row is soft deleted, so
        // the attachment history of a deleted note is not erased outright.
        /** @var array<int, string> $filePaths */
        $filePaths = $attachments->pluck('file_path')->all();
        $this->deleteDocuments($filePaths);
        GoodsReceiptAttachment::query()->whereKey($attachments->modelKeys())->delete();
    }

    /**
     * Ensure one Goods Received Note does not retain more than ten attachments.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @param  array<int, mixed>  $deleteAttachmentIds  The attachments selected for removal.
     * @return void Returns nothing.
     */
    private function validateAttachmentLimit(
        Request $request,
        string $documentNumber,
        array $deleteAttachmentIds
    ): void {
        $ids = array_values(array_filter($deleteAttachmentIds, is_string(...)));
        $deleteCount = $ids === []
            ? 0
            : GoodsReceiptAttachment::query()
                ->where('document_number', $documentNumber)
                ->whereKey($ids)
                ->count();
        $uploadedFiles = $request->file('attachments', []);
        $newCount = is_array($uploadedFiles) ? count($uploadedFiles) : 0;
        $retainedCount = GoodsReceiptAttachment::query()
            ->where('document_number', $documentNumber)
            ->count() - $deleteCount;

        if ($retainedCount + $newCount > 10) {
            throw ValidationException::withMessages([
                'attachments' => __('goods_receipts.validation.attachment_limit'),
            ]);
        }
    }

    /**
     * Build physical product quantities and outstanding balances for one invoice.
     *
     *
     * @param  string  $purchaseInvoiceNumber  The purchase invoice number value.
     * @param  string|null  $currentDocumentNumber  The current document number value.
     * @return Collection<int, covariant array<string, mixed>>
     */
    private function purchaseInvoiceLines(
        string $purchaseInvoiceNumber,
        ?string $currentDocumentNumber = null
    ): Collection {
        $receivedQuantities = ProductLedger::query()
            ->selectRaw('product_id, SUM(quantity) as received_quantity')
            ->where('reference_number', $purchaseInvoiceNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->whereIn('status', [
                ProductMovementStatus::PENDING->value,
                ProductMovementStatus::ACTIVE->value,
            ])
            ->when(
                $currentDocumentNumber !== null,
                fn ($query) => $query->where('document_number', '!=', $currentDocumentNumber)
            )
            ->groupBy('product_id')
            ->pluck('received_quantity', 'product_id');

        return PurchaseOrderLine::query()
            ->with('product')
            ->whereHas(
                'purchaseOrder',
                fn ($query) => $query->where('document_number', $purchaseInvoiceNumber)
            )
            ->whereNotNull('product_id')
            ->whereHas('product', fn ($query) => $query->where('is_service', false))
            ->get()
            ->groupBy('product_id')
            ->map(function (Collection $entries, string $productId) use ($receivedQuantities): array {
                $firstEntry = $entries->first();
                abort_if($firstEntry === null, 422, __('goods_receipts.validation.no_physical_lines'));
                $orderedQuantity = $entries->reduce(
                    fn (string $total, PurchaseOrderLine $entry): string => bcadd($total, (string) $entry->quantity, 4),
                    '0.0000'
                );
                $orderedTotal = $entries->reduce(
                    fn (string $total, PurchaseOrderLine $entry): string => bcadd($total, (string) $entry->total_amount, 2),
                    '0.00'
                );
                $receivedQuantityRaw = $receivedQuantities->get($productId);
                $receivedQuantity = is_string($receivedQuantityRaw) ? $receivedQuantityRaw : '0.0000';
                $remainingQuantity = bcsub($orderedQuantity, DecimalService::normalize($receivedQuantity), 4);

                return [
                    'source_entry_id' => $firstEntry->id,
                    'product_id' => $productId,
                    'product_sku' => $firstEntry->product->sku ?? '-',
                    'product_name' => $firstEntry->product_name ?? $firstEntry->product->name ?? '-',
                    'ordered_quantity' => $orderedQuantity,
                    'received_quantity' => $receivedQuantity,
                    'remaining_quantity' => bccomp($remainingQuantity, '0', 4) === -1
                        ? '0.0000'
                        : $remainingQuantity,
                    'unit_cost' => bccomp($orderedQuantity, '0', 4) === 1
                        ? bcdiv($orderedTotal, $orderedQuantity, 2)
                        : '0.00',
                ];
            })
            ->values();
    }

    /**
     * Build the overall quantity balance for a description-only purchase invoice.
     *
     *
     * @param  string  $purchaseInvoiceNumber  The purchase invoice number value.
     * @param  string|null  $currentDocumentNumber  The current document number value.
     * @return array<string, string>
     */
    private function purchaseInvoiceSummary(
        string $purchaseInvoiceNumber,
        ?string $currentDocumentNumber = null
    ): array {
        $purchaseOrder = PurchaseOrder::query()
            ->with('lines')
            ->where('document_number', $purchaseInvoiceNumber)
            ->firstOrFail();
        $invoiceEntries = $purchaseOrder->lines;
        $firstEntry = $invoiceEntries->firstOrFail();
        $orderedQuantity = $invoiceEntries->reduce(
            fn (string $total, PurchaseOrderLine $entry): string => bcadd($total, (string) $entry->quantity, 4),
            '0.0000'
        );
        $orderedTotal = $invoiceEntries->reduce(
            fn (string $total, PurchaseOrderLine $entry): string => bcadd($total, $entry->total_amount, 2),
            '0.00'
        );
        $receivedQuery = ProductLedger::query()
            ->where('reference_number', $purchaseInvoiceNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->whereIn('status', [
                ProductMovementStatus::PENDING->value,
                ProductMovementStatus::ACTIVE->value,
            ])
            ->when(
                $currentDocumentNumber !== null,
                fn ($query) => $query->where('document_number', '!=', $currentDocumentNumber)
            );
        $receivedQuantity = (string) $receivedQuery->sum('quantity');
        $remainingQuantity = bcsub($orderedQuantity, DecimalService::normalize($receivedQuantity), 4);

        return [
            'source_entry_id' => $firstEntry->id,
            'ordered_quantity' => $orderedQuantity,
            'received_quantity' => $receivedQuantity,
            'remaining_quantity' => bccomp($remainingQuantity, '0', 4) === -1
                ? '0.0000'
                : $remainingQuantity,
            'unit_cost' => bccomp($orderedQuantity, '0', 4) === 1
                ? bcdiv($orderedTotal, $orderedQuantity, 2)
                : '0.00',
        ];
    }

    /**
     * Retrieve all product rows belonging to one GRN.
     *
     *
     * @param  string  $documentNumber  The document number value.
     * @return Collection<int, ProductLedger>
     */
    /**
     * Build one display row per original receipt line, with its ordered quantity.
     *
     * Corrections are folded into the booked quantity rather than shown as extra
     * rows, so a corrected receipt reads as the single figure it now represents.
     *
     * @param  Collection<int, ProductLedger>  $entries  The receipt lines.
     * @param  string  $purchaseOrderNumber  The linked purchase order number.
     * @return Collection<int, array{id: string, product_sku: string, product_name: string, unit_cost: string, total_cost: string, ordered_quantity: string, received_quantity: string, shortfall_quantity: string, state: GoodsReceiptLineState, has_pending_correction: bool}> The rows.
     */
    private function receiptLines(Collection $entries, string $purchaseOrderNumber): Collection
    {
        // Originals are the rows this document received. A receipt row carries
        // the purchase order line it came from in source_entry_id, so null is
        // not the test: corrections are the rows that point back at a fellow
        // product ledger row, and they live in adjustment documents anyway.
        $ids = $entries->pluck('id')->all();
        $originals = $entries->filter(
            fn (ProductLedger $entry): bool => $entry->source_entry_id === null
                || ! in_array($entry->source_entry_id, $ids, true)
        );
        $corrections = $entries->filter(
            fn (ProductLedger $entry): bool => $entry->source_entry_id !== null
                && in_array($entry->source_entry_id, $ids, true)
        );

        $correctionTotals = $corrections->groupBy('source_entry_id')->map(
            fn (Collection $rows): string => $rows->reduce(
                fn (string $total, ProductLedger $row): string => $row->direction === ProductMovementDirection::IN
                    ? bcadd($total, DecimalService::normalize($row->quantity), 4)
                    : bcsub($total, DecimalService::normalize($row->quantity), 4),
                '0.0000'
            )
        );

        $orderedByProduct = $purchaseOrderNumber === ''
            ? collect()
            : PurchaseOrderLine::query()
                ->whereHas(
                    'purchaseOrder',
                    fn ($query) => $query->where('document_number', $purchaseOrderNumber)
                )
                ->whereNotNull('product_id')
                ->get()
                ->groupBy('product_id')
                ->map(fn (Collection $lines): string => $lines->reduce(
                    fn (string $total, PurchaseOrderLine $line): string => bcadd($total, (string) $line->quantity, 4),
                    '0.0000'
                ));

        $pendingCorrectionIds = $corrections
            ->filter(fn (ProductLedger $row): bool => $row->status === ProductMovementStatus::PENDING)
            ->pluck('source_entry_id')
            ->all();

        return $originals->map(function (ProductLedger $entry) use (
            $correctionTotals,
            $orderedByProduct,
            $pendingCorrectionIds
        ): array {
            $productId = $entry->product_id;

            // Only posted corrections count. A pending one has not moved stock yet.
            $posted = $correctionTotals->get($entry->id, '0.0000');
            $booked = DecimalService::normalize(bcadd(
                DecimalService::normalize($entry->quantity),
                DecimalService::normalize((string) $posted),
                4
            ));
            $orderedRaw = $orderedByProduct->get($productId);
            $ordered = DecimalService::normalize(is_string($orderedRaw) ? $orderedRaw : '0.0000');
            $shortfall = bcsub($ordered, $booked, 4);

            return [
                'id' => $entry->id,
                'product_sku' => $entry->product_sku,
                'product_name' => $entry->product_name,
                'unit_cost' => $entry->unit_cost,
                'total_cost' => $entry->total_cost,
                'ordered_quantity' => $ordered,
                'received_quantity' => $booked,
                'shortfall_quantity' => bccomp($shortfall, '0', 4) === -1 ? '0.0000' : $shortfall,
                'state' => GoodsReceiptLineState::classify($ordered, $booked),
                'has_pending_correction' => in_array($entry->id, $pendingCorrectionIds, true),
            ];
        })->values();
    }

    /**
     * Load the stored lines of one goods received note.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return Collection<int, ProductLedger> Every line, including any corrections.
     */
    private function goodsReceiptEntries(string $documentNumber): Collection
    {
        /** @var EloquentCollection<int, ProductLedger> $entries */
        $entries = ProductLedger::query()
            ->where('document_number', $documentNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->orderBy('created_at')
            ->get();

        abort_if($entries->isEmpty(), 404);

        return $entries;
    }

    /**
     * Block GRN edits when a posted Purchase Invoice exists for the linked PO.
     *
     * Once the supplier invoice has been posted to the general ledger the
     * goods receipt is considered final. Stock corrections should go through
     * the GRN Adjustment workflow instead.
     *
     * @param  string  $grnDocumentNumber  The GRN document number to check.
     */
    private function abortIfInvoicePostedForReceipt(string $grnDocumentNumber): void
    {
        $poDocumentNumber = ProductLedger::query()
            ->where('document_number', $grnDocumentNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->value('reference_number');

        if ($poDocumentNumber === null) {
            return;
        }

        $postedInvoiceExists = PurchaseInvoice::query()
            ->whereHas(
                'purchaseOrder',
                fn ($query) => $query->where('document_number', $poDocumentNumber)
            )
            ->where('status', LedgerStatus::POSTED)
            ->exists();

        abort_if($postedInvoiceExists, 409, __('goods_receipts.validation.invoice_posted_lock'));
    }
}
