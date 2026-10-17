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
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\GoodsReceiptAdjustment\StoreGoodsReceiptAdjustmentRequest;
use App\Models\Inventory\ProductLedger;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderLine;
use App\Services\DecimalService;
use App\Support\Data\Purchase\GoodsReceiptAdjustmentData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Correct the quantities on a posted goods received note.
 *
 * A posted receipt cannot be edited or deleted, because its rows are active
 * stock movements. Rather than rewriting history, this screen books a signed
 * correction: each line states the quantity that should actually have been
 * received, and the difference is written back to the original row as a new
 * pending movement of the opposite direction.
 *
 * Nothing here touches the General Ledger. A goods receipt only ever writes
 * ProductLedger rows and the purchase order status, so there is no ledger or
 * account to reverse. The purchase order status is recomputed from the
 * corrected active quantities, which is what makes a fully received order fall
 * back to partially received once its receipt is reduced.
 */
class GoodsReceiptAdjustmentController extends Controller
{
    /**
     * List corrections that are booked but not yet posted.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::GOODS_RECEIPT_ADJUSTMENTS);

        return view('purchase.goods-receipt-adjustments.index', [
            'entries' => $this->adjustmentQuery($request, ProductMovementStatus::PENDING->value),
            'search' => $request->string('search')->toString(),
            'startDate' => $request->string('start_date')->value(),
            'endDate' => $request->string('end_date')->value(),
        ] + ['postingDateMin' => null, 'postingDateMax' => null]);
    }

    /**
     * List posted corrections.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function history(Request $request): View
    {
        $this->allows(FileResources::GOODS_RECEIPT_ADJUSTMENTS_HISTORY);

        return view('purchase.goods-receipt-adjustments.history.index', [
            'entries' => $this->adjustmentQuery($request, ProductMovementStatus::ACTIVE->value),
            'search' => $request->string('search')->toString(),
            'startDate' => $request->string('start_date')->value(),
            'endDate' => $request->string('end_date')->value(),
        ] + ['postingDateMin' => null, 'postingDateMax' => null]);
    }

    /**
     * List cancelled corrections.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function cancelled(Request $request): View
    {
        $this->allows(FileResources::GOODS_RECEIPT_ADJUSTMENTS_CANCELLED);

        return view('purchase.goods-receipt-adjustments.cancelled.index', [
            'entries' => $this->adjustmentQuery($request, ProductMovementStatus::CANCELLED->value),
            'search' => $request->string('search')->toString(),
            'startDate' => $request->string('start_date')->value(),
            'endDate' => $request->string('end_date')->value(),
        ] + ['postingDateMin' => null, 'postingDateMax' => null]);
    }

    /**
     * Cancel one posted correction and take its effect off stock.
     *
     * Stock reads only count active movements, so moving the row to cancelled is
     * what reverts the quantity. The row itself is kept for the audit trail.
     *
     * @param  string  $entryId  The correction row to cancel.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function cancel(string $entryId): RedirectResponse
    {
        $this->allows(FileResources::GOODS_RECEIPT_ADJUSTMENTS, FileAction::CANCEL);

        DB::transaction(function () use ($entryId): void {
            /** @var ProductLedger|null $correction */
            $correction = ProductLedger::query()
                ->whereKey($entryId)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->whereNotNull('source_entry_id')
                ->where('status', ProductMovementStatus::ACTIVE->value)
                ->lockForUpdate()
                ->first();

            abort_if($correction === null, 404);

            ProductLedger::query()
                ->whereKey($correction->id)
                ->update([
                    'status' => ProductMovementStatus::CANCELLED->value,
                    'updated_at' => now(),
                ]);

            $orderNumber = (string) $correction->reference_number;
            if ($orderNumber !== '') {
                $this->recomputePurchaseOrderStatus($orderNumber);
            }
        });

        return redirect()
            ->route('purchase.goods-receipt-adjustments.cancelled.index')
            ->with('success', FlashMessage::cancelled(FileResources::GOODS_RECEIPT_ADJUSTMENTS));
    }

    /**
     * Restore a cancelled correction.
     *
     * @param  string  $entryId  The correction row to restore.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function uncancel(string $entryId): RedirectResponse
    {
        $this->allows(FileResources::GOODS_RECEIPT_ADJUSTMENTS, FileAction::CANCEL);

        DB::transaction(function () use ($entryId): void {
            /** @var ProductLedger|null $correction */
            $correction = ProductLedger::query()
                ->whereKey($entryId)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->whereNotNull('source_entry_id')
                ->where('status', ProductMovementStatus::CANCELLED->value)
                ->lockForUpdate()
                ->first();

            abort_if($correction === null, 404);

            ProductLedger::query()
                ->whereKey($correction->id)
                ->update([
                    'status' => ProductMovementStatus::ACTIVE->value,
                    'updated_at' => now(),
                ]);

            $orderNumber = (string) $correction->reference_number;
            if ($orderNumber !== '') {
                $this->recomputePurchaseOrderStatus($orderNumber);
            }
        });

        return redirect()
            ->route('purchase.goods-receipt-adjustments.history.index')
            ->with('success', FlashMessage::updated(FileResources::GOODS_RECEIPT_ADJUSTMENTS));
    }

    /**
     * Show one cancelled correction on its own page.
     *
     * Every cancelled module keeps its restore action on the detail page rather
     * than the grid, so this module gets a detail page too.
     *
     * @param  string  $entryId  The cancelled correction row identifier.
     * @return View Returns the rendered Blade view.
     */
    public function showCancelled(string $entryId): View
    {
        $this->allows(FileResources::GOODS_RECEIPT_ADJUSTMENTS_CANCELLED);

        $entry = ProductLedger::query()
            ->whereKey($entryId)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->whereNotNull('source_entry_id')
            ->where('status', ProductMovementStatus::CANCELLED->value)
            ->firstOrFail();

        $entries = ProductLedger::query()
            ->where('document_number', $entry->document_number)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->whereNotNull('source_entry_id')
            ->where('status', ProductMovementStatus::CANCELLED->value)
            ->orderBy('created_at')
            ->get();

        return view('purchase.goods-receipt-adjustments.cancelled.show', [
            'documentNumber' => (string) $entry->document_number,
            'entryId' => $entryId,
            'entries' => $entries,
        ] + ['postingDateMin' => null, 'postingDateMax' => null]);
    }

    /**
     * Build one grouped correction listing.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  string  $status  The movement status to list.
     * @return LengthAwarePaginator<int, ProductLedger> The paginated result.
     */
    private function adjustmentQuery(Request $request, string $status): LengthAwarePaginator
    {
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();

        return ProductLedger::query()
            ->selectRaw(
                'MIN(id) as id, document_number, MAX(reference_number) as reference_number, '
                .'MAX(date) as date, MAX(status) as status, MAX(notes) as notes, '
                .'MAX(company_name) as company_name, '
                .'SUM(CASE WHEN direction = ? THEN quantity ELSE 0 END) as increase_quantity, '
                .'SUM(CASE WHEN direction = ? THEN quantity ELSE 0 END) as decrease_quantity, '
                .'COUNT(*) as line_count',
                [ProductMovementDirection::IN->value, ProductMovementDirection::OUT->value]
            )
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->whereNotNull('source_entry_id')
            // A receipt row carries its purchase order line here, so non-null
            // is not the test: only rows pointing back at a fellow ledger row
            // are corrections.
            ->whereExists(function (QueryBuilder $query): void {
                $query->select(DB::raw('1'))
                    ->from('product_ledgers as originals')
                    ->whereColumn('originals.id', 'product_ledgers.source_entry_id');
            })
            ->where('status', $status)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%");
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->groupBy('document_number')
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();
    }

    /**
     * Show the adjustment form for one posted goods received note.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return View Returns the rendered Blade view.
     */
    public function create(string $documentNumber): View
    {
        $this->allows(FileResources::GOODS_RECEIPTS, FileAction::UPDATE);

        $entries = $this->adjustableEntries($documentNumber);
        $posted = $this->postedCorrectionTotals($documentNumber);
        $ceilings = $this->receivingCeilings(
            (string) $entries->first()?->reference_number,
            $documentNumber
        );

        // Show what is currently received rather than the original booking, so the
        // figure the user edits is the figure the correction is measured from.
        $lines = $entries->map(function (ProductLedger $entry) use ($ceilings, $posted): array {
            $original = DecimalService::normalize($entry->quantity);
            $current = DecimalService::normalize(bcadd(
                $original,
                DecimalService::normalize((string) ($posted->get($entry->id) ?? '0.0000')),
                4
            ));
            $ceiling = DecimalService::normalize((string) ($ceilings->get((string) $entry->product_id) ?? '0.0000'));
            $headroom = bcsub($ceiling, $current, 4);

            return [
                'id' => $entry->id,
                'product_sku' => $entry->product_sku,
                'product_name' => $entry->product_name,
                'original_quantity' => $original,
                'current_quantity' => $current,
                // Max is the current quantity — you can always reduce, even if the
                // PO ceiling is lower (e.g. PO already fully received).
                'max_quantity' => $current,
                'headroom_quantity' => bccomp($headroom, '0', 4) === -1 ? '0.0000' : $headroom,
            ];
        })->values();

        return view('purchase.goods-receipts.adjust', [
            'documentNumber' => $documentNumber,
            'lines' => $lines,
            'adjusted' => $this->pendingAdjustments($documentNumber),
        ] + ['postingDateMin' => null, 'postingDateMax' => null]);
    }

    /**
     * Book the quantity corrections for one posted goods received note.
     *
     * @param  StoreGoodsReceiptAdjustmentRequest  $request  The validated request instance.
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function store(StoreGoodsReceiptAdjustmentRequest $request, string $documentNumber): RedirectResponse
    {
        $entries = $this->adjustableEntries($documentNumber)->keyBy('id');

        $data = GoodsReceiptAdjustmentData::from($request->validated());

        $now = now();
        $rows = [];
        $touchedOrders = [];

        // Cap every corrected quantity at the ordered figure. This is what stops a
        // correction from over-receiving the purchase order, since the corrected
        // total becomes the new received quantity for the line.
        $orderNumber = (string) $entries->first()?->reference_number;
        $ceilings = $this->receivingCeilings($orderNumber, $documentNumber);

        foreach (($data->lines ?? []) as $index => $line) {
            $entryId = is_string($line['entry_id'] ?? null) ? $line['entry_id'] : '';
            $entry = $entries->get($entryId);
            if ($entry === null) {
                continue;
            }

            $ceiling = DecimalService::normalize(
                (string) ($ceilings->get($entry->product_id) ?? '0.0000')
            );

            $lineQuantity = is_numeric($line['quantity'] ?? null) ? (string) $line['quantity'] : '0';
            $current = DecimalService::normalize($entry->quantity);

            // Only check ceiling when INCREASING. Reductions (corrected < current)
            // are always allowed — you can return items even if the PO is complete.
            if (bccomp(DecimalService::normalize($lineQuantity), $current, 4) === 1
                && bccomp(DecimalService::normalize($lineQuantity), $ceiling, 4) === 1) {
                throw ValidationException::withMessages([
                    "lines.{$index}.quantity" => __('goods_receipts.adjustment.validation.exceeds_ordered', [
                        'quantity' => number_format((float) $ceiling, 4),
                    ]),
                ]);
            }
        }

        // Corrections already booked and posted against each original line. The
        // entered quantity is a target, so the difference has to be measured from
        // what is currently received rather than from the original booking, or a
        // second correction would be calculated from a stale figure.
        $postedCorrections = $this->postedCorrectionTotals($documentNumber);

        foreach (($data->lines ?? []) as $line) {
            $entryId2 = is_string($line['entry_id'] ?? null) ? $line['entry_id'] : '';
            $entry = $entries->get($entryId2);

            // Ignore ids that belong to this note but are already corrected, and
            // ids that belong to another note entirely.
            if ($entry === null) {
                continue;
            }

            $current = DecimalService::normalize(bcadd(
                DecimalService::normalize($entry->quantity),
                DecimalService::normalize((string) ($postedCorrections->get($entry->id) ?? '0.0000')),
                4
            ));

            $target = DecimalService::normalize(is_numeric($line['quantity'] ?? null) ? (string) $line['quantity'] : '0');
            $delta = bcsub($target, $current, 4);

            if (bccomp($delta, '0', 4) === 0) {
                continue;
            }

            $direction = bccomp($delta, '0', 4) === 1
                ? ProductMovementDirection::IN
                : ProductMovementDirection::OUT;
            $magnitude = bccomp($delta, '0', 4) === 1
                ? $delta
                : bcsub('0.0000', $delta, 4);

            $rows[] = [
                'id' => (string) Str::uuid7(),
                'product_id' => $entry->product_id,
                'stock_location_id' => $entry->stock_location_id,
                'stock_location_name' => $entry->stock_location_name,
                'company_id' => $entry->company_id,
                'source_entry_id' => $entry->id,
                'cogs_ledger_id' => null,
                'inventory_ledger_id' => null,
                'variance_ledger_id' => null,
                'product_sku' => $entry->product_sku,
                'product_name' => $entry->product_name,
                'company_name' => $entry->company_name,
                'document_number' => $documentNumber,
                'reference_number' => $entry->reference_number,
                'source_type' => $entry->source_type,
                'direction' => $direction->value,
                'stock_bucket' => $entry->stock_bucket->value,
                'date' => $data->date,
                'quantity' => $magnitude,
                'unit_cost' => $entry->unit_cost,
                'total_cost' => bcmul($magnitude, $entry->unit_cost, 2),
                // Pending, so the reviewer can see and remove a correction before
                // it changes stock. Posting is done from the same screen.
                'status' => ProductMovementStatus::PENDING->value,
                'activated_at' => null,
                'notes' => $data->notes ?? __('goods_receipts.adjustment.default_note'),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            $touchedOrders[(string) $entry->reference_number] = true;
        }

        abort_if($rows === [], 422, __('goods_receipts.adjustment.no_change'));

        DB::transaction(function () use ($rows, $touchedOrders): void {
            ProductLedger::query()->insert($rows);

            foreach (array_keys($touchedOrders) as $purchaseOrderNumber) {
                $this->recomputePurchaseOrderStatus($purchaseOrderNumber);
            }
        });

        return redirect()
            ->route('purchase.goods-receipts.adjust', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::GOODS_RECEIPTS));
    }

    /**
     * Post every pending correction on one receipt, from the listing.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function approveDocument(string $documentNumber): RedirectResponse
    {
        return $this->approve($documentNumber);
    }

    /**
     * Post a pending quantity correction so it changes stock.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::GOODS_RECEIPTS, FileAction::APPROVE);

        $pending = $this->pendingAdjustments($documentNumber);
        abort_if($pending->isEmpty(), 409);

        DB::transaction(function () use ($documentNumber): void {
            /** @var EloquentCollection<int, ProductLedger> $locked */
            $locked = ProductLedger::query()
                ->where('document_number', $documentNumber)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->whereIn('status', [
                    ProductMovementStatus::PENDING->value,
                    ProductMovementStatus::ACTIVE->value,
                ])
                ->lockForUpdate()
                ->get();

            $corrections = $locked->filter(
                fn (ProductLedger $entry): bool => $entry->source_entry_id !== null
            );
            abort_if($corrections->isEmpty(), 409);

            ProductLedger::query()
                ->whereKey($corrections->modelKeys())
                ->update([
                    'status' => ProductMovementStatus::ACTIVE->value,
                    'activated_at' => now(),
                    'updated_at' => now(),
                ]);

            $orders = $corrections
                ->pluck('reference_number')
                ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
                ->unique()
                ->all();

            foreach ($orders as $purchaseOrderNumber) {
                $this->recomputePurchaseOrderStatus($purchaseOrderNumber);
            }
        });

        return redirect()
            ->route('purchase.goods-receipts.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::GOODS_RECEIPTS));
    }

    /**
     * Discard a pending quantity correction.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @param  string  $entryId  The pending correction row to discard.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function destroy(string $documentNumber, string $entryId): RedirectResponse
    {
        $this->allows(FileResources::GOODS_RECEIPTS, FileAction::REMOVE);

        DB::transaction(function () use ($documentNumber, $entryId): void {
            /** @var ProductLedger|null $correction */
            $correction = ProductLedger::query()
                ->where('document_number', $documentNumber)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->whereKey($entryId)
                ->whereNotNull('source_entry_id')
                ->where('status', ProductMovementStatus::PENDING->value)
                ->lockForUpdate()
                ->first();

            abort_if($correction === null, 404);
            ProductLedger::query()->whereKey($correction->id)->delete();
        });

        return redirect()
            ->route('purchase.goods-receipts.adjust', $documentNumber)
            ->with('success', FlashMessage::deleted(FileResources::GOODS_RECEIPTS));
    }

    /**
     * Fetch the posted lines of one receipt that may still be corrected.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return EloquentCollection<int, ProductLedger> The original active lines.
     */
    private function adjustableEntries(string $documentNumber): EloquentCollection
    {
        /** @var EloquentCollection<int, ProductLedger> $entries */
        $entries = ProductLedger::query()
            ->where('document_number', $documentNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->orderBy('product_sku')
            ->get()
            ->filter(
                // A receipt row carries the purchase order line it came from in
                // source_entry_id, so null is not the test: only rows pointing
                // back at fellow ledger rows are corrections, not originals.
                fn (ProductLedger $entry): bool => $entry->source_entry_id === null
                    || ! ProductLedger::query()->whereKey($entry->source_entry_id)->exists()
            )
            ->values();

        abort_if($entries->isEmpty(), 404);

        return $entries;
    }

    /**
     * Fetch the corrections already booked against one receipt.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return EloquentCollection<int, ProductLedger> The correction rows.
     */
    private function pendingAdjustments(string $documentNumber): EloquentCollection
    {
        /** @var EloquentCollection<int, ProductLedger> $corrections */
        $corrections = ProductLedger::query()
            ->where('document_number', $documentNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->whereNotNull('source_entry_id')
            ->orderBy('created_at')
            ->get();

        return $corrections;
    }

    /**
     * Highest quantity one receipt line may carry, per product.
     *
     * The ceiling is the ordered quantity less whatever other receipts on the same
     * purchase order have already booked. Without it a correction could raise a
     * line past the ordered figure and over-receive the order.
     *
     * @param  string  $purchaseOrderNumber  The linked purchase order number.
     * @param  string|null  $excludeDocumentNumber  A receipt to leave out of the total.
     * @return Collection<array-key, numeric-string> Product id to maximum.
     */
    private function receivingCeilings(string $purchaseOrderNumber, ?string $excludeDocumentNumber = null): Collection
    {
        if ($purchaseOrderNumber === '') {
            /** @var Collection<array-key, numeric-string> $empty */
            $empty = collect();

            return $empty;
        }

        $ordered = PurchaseOrderLine::query()
            ->whereHas('purchaseOrder', fn ($query) => $query->where('document_number', $purchaseOrderNumber))
            ->whereNotNull('product_id')
            ->get()
            ->groupBy('product_id')
            ->map(fn (EloquentCollection $lines): string => $lines->reduce(
                fn (string $total, PurchaseOrderLine $line): string => bcadd($total, DecimalService::normalize((string) $line->quantity), 4),
                '0.0000'
            ));

        $receivedElsewhere = ProductLedger::query()
            ->selectRaw('product_id, SUM(quantity) as received_quantity')
            ->where('reference_number', $purchaseOrderNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->when($excludeDocumentNumber !== null, fn ($query) => $query->where('document_number', '!=', $excludeDocumentNumber))
            ->groupBy('product_id')
            ->pluck('received_quantity', 'product_id');

        return $ordered->map(function (string $orderedQuantity, string $productId) use ($receivedElsewhere): string {
            $elsewhereRaw = $receivedElsewhere->get($productId);
            $elsewhere = DecimalService::normalize(is_numeric($elsewhereRaw) ? (string) $elsewhereRaw : '0.0000');
            $ceiling = bcsub($orderedQuantity, $elsewhere, 4);

            return bccomp($ceiling, '0', 4) === -1 ? '0.0000' : $ceiling;
        });
    }

    /**
     * Net posted correction per original receipt line.
     *
     * A correction is signed by its direction, so an out movement subtracts from
     * what has been received and an in movement adds to it.
     *
     * @param  string  $documentNumber  The Goods Received Note document number.
     * @return Collection<string, numeric-string> Original line id to signed total.
     */
    private function postedCorrectionTotals(string $documentNumber): Collection
    {
        return ProductLedger::query()
            ->where('document_number', $documentNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->whereNotNull('source_entry_id')
            ->get(['source_entry_id', 'direction', 'quantity'])
            ->groupBy('source_entry_id')
            ->map(fn (EloquentCollection $rows): string => $rows->reduce(
                fn (string $total, ProductLedger $row): string => $row->direction === ProductMovementDirection::IN
                    ? bcadd($total, DecimalService::normalize($row->quantity), 4)
                    : bcsub($total, DecimalService::normalize($row->quantity), 4),
                '0.0000'
            ));
    }

    /**
     * Net quantity currently received against one purchase order.
     *
     * Direction aware on purpose. A correction is stored as a movement, so a
     * reduction is an out row. Summing the quantity column would add that
     * reduction back instead of netting it off, and an order whose corrected
     * total fell below the ordered quantity would still read as fully received.
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
     * Kept in step with GoodsReceiptController::approve() so a corrected
     * receipt moves the order between partial and complete exactly as the
     * original posting did.
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
}
