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

namespace App\Http\Controllers\Sales;

use App\Constants\FlashMessage;
use App\Enums\DiscountType;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\SalesType;
use App\Enums\TaxMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SalesInvoice\StoreSalesInvoiceRequest;
use App\Http\Requests\Sales\SalesInvoice\UpdateSalesInvoiceRequest;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\ArLedger;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Finance\Discount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryChart;
use App\Models\Finance\Ledger as FinanceLedger;
use App\Models\Finance\Tax;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesAdjustment;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceChart;
use App\Models\Sales\SalesInvoiceLine;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Sales\SalesInvoiceData;
use App\Support\Posting\GeneralLedgerData;
use App\Support\Posting\InvoiceLedgerPoster;
use App\Support\Posting\Ledger;
use App\Support\Posting\Receivable;
use App\Traits\GeneratesDocumentNumber;
use App\Traits\HandlesInvoiceProductMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class SalesInvoiceController
 *
 * Creates fixed-row sales invoices, posting each completed line
 * as a balanced debit and credit ledger pair.
 */
class SalesInvoiceController extends Controller
{
    use GeneratesDocumentNumber, HandlesInvoiceProductMovement;

    /**
     * The poster that writes a document's double entry to every ledger it
     * belongs in.
     *
     * Injected rather than resolved through the container at the point of use, so
     * what this controller depends on is declared where it can be read and so a
     * test can hand it a poster of its own.
     */
    public function __construct(
        private readonly Receivable $receivable,
        private readonly Ledger $ledger,
        private readonly InvoiceLedgerPoster $invoicePoster
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $subtype = LedgerSubType::SALES_INVOICE;
        $indexRoute = 'sales.invoices.index';
        $createRoute = 'sales.invoices.create';
        $this->allows(FileResources::SALES_INVOICES);
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $entries = SalesInvoice::query()
            ->with(['company', 'contact'])
            ->where('status', LedgerStatus::PENDING)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', "%$search%"))
                        ->orWhereHas('lines.product', fn ($productQuery) => $productQuery->where('name', 'like', "%$search%"));
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        $entries->getCollection()->each(function (SalesInvoice $entry): void {
            $entry->setAttribute('is_posted', false);
        });

        return view('sales.invoices.index', compact(
            'entries',
            'search',
            'startDate',
            'endDate',
            'subtype',
            'indexRoute',
            'createRoute'
        ));

    }

    /**
     * Display the specified resource.
     * Display the shared details, lines, and linked payments for one invoice document.
     *
     * @param  string  $documentNumber  The document number value.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::SALES_INVOICES);

        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with(['company', 'contact', 'lines.product', 'quotation', 'salesOrder'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines;

        // Per-line return audit: what was invoiced, how much already came
        // back, and what is still returnable for every product line.
        /** @var array<string, array{id: string, product_name: string|null, quantity: string, returned: string, remaining: numeric-string, unit_price: string}> $lineReturns */
        $lineReturns = [];

        foreach ($lines as $line) {
            /** @var SalesInvoiceLine $line */
            if ($line->product_id === null) {
                continue;
            }

            $returnedIn = (string) ProductLedger::query()
                ->whereIn('source_type', [ProductMovementSource::SALES_INVOICE, ProductMovementSource::SALES_RETURN])
                ->where('direction', ProductMovementDirection::IN)
                ->whereNull('deleted_at')
                ->where('source_entry_id', $line->id)
                ->where('status', ProductMovementStatus::ACTIVE->value)
                ->sum('quantity');

            $quantity = DecimalService::normalize((string) ($line->quantity ?? 0));
            $returned = DecimalService::normalize($returnedIn);

            $lineReturns[$line->id] = [
                'id' => $line->id,
                'product_name' => $line->product_name,
                'quantity' => $quantity,
                'returned' => $returned,
                'remaining' => bcsub($quantity, $returned, 4),
                'unit_price' => DecimalService::normalize((string) ($line->unit_price ?? 0)),
            ];
        }

        // What each product line still has left to return: invoiced less what
        // already came back through the return action.
        /** @var list<array{id: string, product_name: string|null, quantity: string, returned: string, remaining: numeric-string, unit_price: string}> $returnableItems */
        $returnableItems = [];

        foreach ($lineReturns as $lineReturn) {
            if (bccomp($lineReturn['remaining'], '0.0000', 4) === 1) {
                $returnableItems[] = $lineReturn;
            }
        }

        /** @var \Illuminate\Support\Collection<int, array{id: string, product_name: string|null, quantity: string, returned: string, remaining: numeric-string, unit_price: string}> $returnableLines */
        $returnableLines = new \Illuminate\Support\Collection($returnableItems);

        /** @var Collection<int, ArLedger> $payments */
        $payments = ArLedger::query()->where('type', LedgerType::PAYMENT)
            ->where('reference_number', $documentNumber)->whereNotNull('debit_acc')->orderBy('date')->get();

        $isPosted = $invoice->status === LedgerStatus::POSTED;

        $indexRoute = 'sales.invoices.index';

        return view('sales.invoices.show', compact(
            'documentNumber',
            'invoice',
            'lines',
            'payments',
            'isPosted',
            'indexRoute',
            'lineReturns',
            'returnableLines'
        ) + [
            'canDeductInventory' => $this->canDeductInventory($documentNumber),
            'inventoryDeducted' => $this->inventoryDeducted($documentNumber),
        ]);
    }

    /**
     * Determine whether this invoice still owes a stock deduction.
     *
     * A stock deduction is only offered for a product sale whose lines still
     * carry deduct_inventory = false. The flag is the single record of who owns
     * the movement: this button sets it, and a delivery order that reduced stock
     * also sets it, so the same units are never deducted twice.
     *
     * sales_type is checked as well so that a document whose lines are not
     * product sales never offers a stock deduction, even if the data is edited
     * outside the application.
     *
     * @param  string  $documentNumber  The sales invoice document number.
     * @return bool True when the deduct-inventory action should be offered.
     */
    private function canDeductInventory(string $documentNumber): bool
    {
        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->where('document_number', $documentNumber)
            ->first();

        if ($invoice === null) {
            return false;
        }

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines()->get();

        return $lines->isNotEmpty()
            && $invoice->sales_type === SalesType::PRODUCT
            && $lines->every(fn (SalesInvoiceLine $line): bool => ! $line->deduct_inventory)
            && ! $this->inventoryDeducted($documentNumber);
    }

    /**
     * Determine whether stock has already been moved for this document.
     *
     * @param  string  $documentNumber  The sales invoice document number.
     * @return bool True when a movement already exists for the document.
     */
    private function inventoryDeducted(string $documentNumber): bool
    {
        return ProductLedger::query()
            ->where('document_number', $documentNumber)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->exists();
    }

    /**
     * Reduce stock for a posted sales invoice that never went through a delivery order.
     *
     * @param  string  $documentNumber  The sales invoice document number.
     * @return RedirectResponse Redirects back to the invoice.
     *
     * @throws ValidationException|Throwable If the invoice is not eligible for a deduction.
     */
    public function deductInventory(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::UPDATE);

        abort_unless($this->canDeductInventory($documentNumber), 409);

        DB::transaction(function () use ($documentNumber): void {
            /** @var SalesInvoice|null $invoice */
            $invoice = SalesInvoice::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);

            $invoice->lines()->update(['deduct_inventory' => true]);

            $this->syncPendingSalesProductMovements($documentNumber);
            $this->postSalesProductMovements($documentNumber);
        });

        return redirect()->route('sales.invoices.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::SALES_INVOICES));
    }

    /**
     * Receive part of a posted sale back from the customer.
     *
     * A return is the reverse of a sale: every returned quantity goes back on
     * the shelf as an active stock movement, and one decrease adjustment (credit
     * note) covering all of them is raised against the invoice and posted at
     * once, so the customer owes less. Both halves happen in one transaction
     * because a return with only one half is either missing stock or missing
     * money.
     *
     * One submission is one return. A return may span several invoice lines and
     * all of them share the same credit note number, so returning nine units
     * across three products raises one cn100xx document, not three.
     *
     * @param  Request  $request  The returned lines and quantities.
     * @param  string  $documentNumber  The sales invoice document number.
     * @return RedirectResponse Redirects to the invoice detail page.
     *
     * @throws Throwable
     */
    public function receiveReturn(Request $request, string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::UPDATE);

        // The grid posts returns[<line id>][quantity]. The older single line
        // payload (return_line_id + return_quantity) is still accepted so any
        // saved form or bookmarked URL keeps working.
        /** @var array{returns?: list<array{line_id: string, quantity?: numeric-string|int|float|null}>, return_line_id?: string, return_quantity?: numeric-string|int|float|null, return_notes?: string|null} $validated */
        $validated = $request->validate([
            'returns' => ['sometimes', 'array'],
            'returns.*' => ['array'],
            'returns.*.line_id' => ['required', 'uuid'],
            'returns.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'return_line_id' => ['required_without:returns', 'uuid'],
            'return_quantity' => ['required_without:returns', 'numeric', 'min:0.0001'],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var array<string, numeric-string> $requested */
        $requested = [];

        // Blank quantity boxes mean "not returning this line", so skip them and
        // fold repeats of the same line into one quantity.
        foreach ($validated['returns'] ?? [] as $entry) {
            $lineId = $entry['line_id'];
            $rawQuantity = $entry['quantity'] ?? null;

            // A blank quantity box means "not returning this line".
            if ($rawQuantity === null) {
                continue;
            }

            $quantity = DecimalService::normalize((string) $rawQuantity);

            if (bccomp($quantity, '0.0000', 4) !== 1) {
                continue;
            }

            $requested[$lineId] = bcadd($requested[$lineId] ?? '0', $quantity, 4);
        }

        if ($requested === [] && isset($validated['return_line_id'], $validated['return_quantity'])) {
            $requested[$validated['return_line_id']] = DecimalService::normalize(
                (string) $validated['return_quantity']
            );
        }

        if ($requested === []) {
            throw ValidationException::withMessages([
                'returns' => __('sales_returns.select_at_least_one_line'),
            ]);
        }

        $invoice = SalesInvoice::query()
            ->where('document_number', $documentNumber)
            ->lockForUpdate()
            ->first();
        abort_if($invoice === null, 404);
        abort_if($invoice->status !== LedgerStatus::POSTED, 409);

        /** @var array<string, array{line: SalesInvoiceLine, product: Product, quantity: numeric-string, credit: numeric-string, cost: numeric-string}> $returnRows */
        $returnRows = [];
        $totalCredit = '0';

        foreach ($requested as $lineId => $quantity) {
            /** @var SalesInvoiceLine|null $line */
            $line = $invoice->lines()
                ->whereKey($lineId)
                ->whereNotNull('product_id')
                ->first();
            abort_if($line === null, 404);

            // Use the line's returned_quantity to track what has been returned.
            // This works even after a full undo, unlike querying product_ledger.
            $returnedIn = DecimalService::normalize((string) ($line->returned_quantity ?? '0'));
            $remaining = bcsub(
                DecimalService::normalize((string) ($line->quantity ?? 0)),
                $returnedIn,
                4
            );

            if (bccomp($quantity, $remaining, 4) === 1) {
                throw ValidationException::withMessages([
                    'returns' => __('sales_returns.quantity_exceeds_remaining', [
                        'product' => $line->product_name ?? $line->id,
                        'remaining' => $remaining,
                    ]),
                ]);
            }

            $product = Product::query()->whereKey($line->product_id)->first();
            abort_if($product === null, 404);

            $credit = bcmul($quantity, DecimalService::normalize((string) $line->unit_price), 2);
            $cost = bcmul($quantity, DecimalService::normalize((string) $product->base_price), 2);

            $returnRows[$lineId] = [
                'line' => $line,
                'product' => $product,
                'quantity' => $quantity,
                'credit' => $credit,
                'cost' => $cost,
            ];

            $totalCredit = bcadd($totalCredit, $credit, 2);
        }

        $chartConfig = SalesInvoiceChart::query()
            ->where('company_id', $invoice->company_id)
            ->first()
            ?? SalesInvoiceChart::query()->whereNull('company_id')->first();
        abort_if($chartConfig === null || $chartConfig->receivable_id === null || $chartConfig->revenue_id === null, 422);
        $receivableChart = Chart::query()->find($chartConfig->receivable_id);
        $revenueChart = Chart::query()->find($chartConfig->revenue_id);
        abort_if($receivableChart === null || $revenueChart === null, 422);

        $chartSetting = ChartSetting::query()->first();
        $cogsChart = filled($chartSetting?->cogs_chart_uuid)
            ? Chart::query()->whereKey($chartSetting->cogs_chart_uuid)->first()
            : null;
        $inventoryChart = filled($chartSetting?->inventory_chart_uuid)
            ? Chart::query()->whereKey($chartSetting->inventory_chart_uuid)->first()
            : null;
        abort_if($cogsChart === null || $inventoryChart === null, 422);

        $location = $this->resolveStockLocation(null);
        $notes = is_string($validated['return_notes'] ?? null) && trim($validated['return_notes']) !== ''
            ? trim($validated['return_notes'])
            : __('sales_returns.default_note', ['document' => $documentNumber]);
        $now = now();
        // The return movement carries the credit note number, so the sequence
        // is drawn once, before any movement row is written.
        $adjustmentNumber = $this->generateDocumentNumber(LedgerSubType::DECREASE_ADJUSTMENT);

        DB::transaction(function () use (
            $adjustmentNumber, $cogsChart, $documentNumber, $inventoryChart, $invoice,
            $location, $notes, $now, $receivableChart, $revenueChart, $returnRows, $totalCredit
        ): void {
            $movementRows = [];
            $financeRows = [];

            foreach ($returnRows as $returnRow) {
                /** @var SalesInvoiceLine $line */
                $line = $returnRow['line'];
                /** @var Product $product */
                $product = $returnRow['product'];
                $quantity = $returnRow['quantity'];
                $costAmount = $returnRow['cost'];
                $movementId = (string) Str::uuid7();
                $cogsLedgerId = (string) Str::uuid7();
                $inventoryLedgerId = (string) Str::uuid7();

                // The goods are physically back, so the movement is active at once.
                // Every returned line shares the one credit note number, which is what
                // keeps a multi line return on a single document.
                $movementRows[] = [
                    'id' => $movementId,
                    'product_id' => $product->id,
                    'stock_location_id' => $location->id,
                    'stock_location_name' => $location->name,
                    'company_id' => $invoice->company_id,
                    'source_entry_id' => $line->id,
                    'cogs_ledger_id' => $cogsLedgerId,
                    'inventory_ledger_id' => $inventoryLedgerId,
                    'product_sku' => $product->sku,
                    'product_name' => $line->product_name ?? $product->name,
                    'company_name' => $invoice->company_name,
                    'document_number' => $adjustmentNumber,
                    'reference_number' => $documentNumber,
                    'source_type' => ProductMovementSource::SALES_RETURN->value,
                    'direction' => ProductMovementDirection::IN->value,
                    'date' => today()->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => DecimalService::normalize((string) $product->base_price),
                    'total_cost' => $costAmount,
                    'status' => ProductMovementStatus::ACTIVE->value,
                    'activated_at' => $now,
                    'notes' => $notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Reverse the cost of what came back: stock up, cost of sales down.
                $financeRows[] = [
                    'id' => $inventoryLedgerId,
                    'source_id' => $movementId,
                    'source_type' => null,
                    'company_id' => $invoice->company_id,
                    'company_name' => $invoice->company_name,
                    'contact_id' => $invoice->contact_id,
                    'document_number' => $adjustmentNumber,
                    'reference_number' => $documentNumber,
                    'type' => LedgerType::JOURNAL->value,
                    'subtype' => LedgerSubType::JOURNAL->value,
                    'date' => today()->toDateString(),
                    'document_date' => today()->toDateString(),
                    'debit_id' => $inventoryChart->id,
                    'credit_id' => null,
                    'debit_acc' => $inventoryChart->code,
                    'credit_acc' => null,
                    'debit_acc_desc' => $inventoryChart->name,
                    'credit_acc_desc' => null,
                    'debit_amount' => $costAmount,
                    'credit_amount' => '0.00',
                    'total_amount' => $costAmount,
                    'status' => LedgerStatus::POSTED->value,
                    'notes' => $notes,
                    'description' => $notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];

                $financeRows[] = [
                    'id' => $cogsLedgerId,
                    'source_id' => $movementId,
                    'source_type' => null,
                    'company_id' => $invoice->company_id,
                    'company_name' => $invoice->company_name,
                    'contact_id' => $invoice->contact_id,
                    'document_number' => $adjustmentNumber,
                    'reference_number' => $documentNumber,
                    'type' => LedgerType::JOURNAL->value,
                    'subtype' => LedgerSubType::JOURNAL->value,
                    'date' => today()->toDateString(),
                    'document_date' => today()->toDateString(),
                    'debit_id' => null,
                    'credit_id' => $cogsChart->id,
                    'debit_acc' => null,
                    'credit_acc' => $cogsChart->code,
                    'debit_acc_desc' => null,
                    'credit_acc_desc' => $cogsChart->name,
                    'debit_amount' => '0.00',
                    'credit_amount' => $costAmount,
                    'total_amount' => $costAmount,
                    'status' => LedgerStatus::POSTED->value,
                    'notes' => $notes,
                    'description' => $notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            ProductLedger::query()->insert($movementRows);

            $adjustment = SalesAdjustment::query()->create([
                'id' => (string) Str::uuid7(),
                'company_id' => $invoice->company_id,
                'contact_id' => $invoice->contact_id,
                'subtype' => LedgerSubType::DECREASE_ADJUSTMENT,
                'document_number' => $adjustmentNumber,
                'reference_number' => $documentNumber,
                'company_name' => $invoice->company_name,
                'contact_name' => $invoice->contact_name,
                'date' => today()->toDateString(),
                'status' => LedgerStatus::PENDING,
                'subtotal' => $totalCredit,
                'total_amount' => $totalCredit,
                'notes' => $notes,
            ]);

            // One line per returned invoice line, all on this single credit note.
            foreach ($returnRows as $returnRow) {
                /** @var SalesInvoiceLine $line */
                $line = $returnRow['line'];
                /** @var Product $product */
                $product = $returnRow['product'];
                $creditAmount = $returnRow['credit'];

                $adjustment->lines()->create([
                    'product_id' => $product->id,
                    'product_name' => $line->product_name ?? $product->name,
                    'quantity' => $returnRow['quantity'],
                    'unit_price' => DecimalService::normalize((string) $line->unit_price),
                    'subtotal_amount' => $creditAmount,
                    'line_total_amount' => $creditAmount,
                    'total_amount' => $creditAmount,
                    'description' => $notes,
                ]);

                $adjustment->charts()->create([
                    'debit_id' => $revenueChart->id,
                    'debit_acc' => $revenueChart->code,
                    'debit_acc_desc' => $revenueChart->name,
                    'document_number' => $adjustmentNumber,
                    'reference_number' => $documentNumber,
                    'date' => today()->toDateString(),
                    'description' => $notes,
                    'debit_amount' => $creditAmount,
                    'credit_amount' => '0.00',
                    'total_amount' => $creditAmount,
                ]);

                $adjustment->charts()->create([
                    'credit_id' => $receivableChart->id,
                    'credit_acc' => $receivableChart->code,
                    'credit_acc_desc' => $receivableChart->name,
                    'document_number' => $adjustmentNumber,
                    'reference_number' => $documentNumber,
                    'date' => today()->toDateString(),
                    'description' => $notes,
                    'debit_amount' => '0.00',
                    'credit_amount' => $creditAmount,
                    'total_amount' => $creditAmount,
                ]);
            }

            $chartsById = Chart::query()
                ->whereIn('id', [$revenueChart->id, $receivableChart->id])
                ->get(['id', 'name'])
                ->mapWithKeys(fn (Chart $chart): array => [(string) $chart->id => (string) $chart->name]);
            $generalRows = [];
            foreach ($adjustment->charts()->get() as $chart) {
                $generalRows[] = GeneralLedgerData::fromSalesAdjustmentLine($adjustment, $chart, $chartsById);
            }

            $this->invoicePoster->post(
                $adjustment,
                $adjustmentNumber,
                today(),
                LedgerType::INVOICE,
                LedgerSubType::DECREASE_ADJUSTMENT,
                $generalRows,
                companyId: $adjustment->company_id,
                contactId: $adjustment->contact_id,
                companyName: $adjustment->company_name,
                referenceNumber: $documentNumber,
            );

            $adjustment->update(['status' => LedgerStatus::POSTED]);
            $this->attributeApproved($adjustment);

            FinanceLedger::query()->insert($financeRows);

            // Track how much has been returned on each returned line.
            foreach ($returnRows as $returnRow) {
                $returnRow['line']->increment('returned_quantity', (float) $returnRow['quantity']);
            }
        });

        return redirect()->route('sales.invoices.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::SALES_INVOICES));
    }

    /**
     * Stream a real invoice document using the selected or default PDF design.
     *
     * @param  string  $documentNumber  The invoice document number.
     * @return Response The streamed invoice PDF.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::SALES_INVOICES);

        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with(['company', 'contact', 'lines.product', 'quotation', 'salesOrder'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines;

        $design = null;

        $setting = Setting::query()->firstOrNew();

        return Pdf::loadView('sales.invoices.pdf', [
            'documentNumber' => $documentNumber,
            'invoice' => $invoice,
            'lines' => $lines,
            'setting' => $setting,
        ])->setPaper('a4')->stream($documentNumber.'.pdf');
    }

    /**
     * Stream one receipt or payment voucher using a receipt PDF design.
     *
     * @param  string  $paymentDocumentNumber  The payment document number.
     * @return Response The streamed receipt or payment voucher PDF.
     */
    public function paymentPdf(string $paymentDocumentNumber): Response
    {
        $this->allows(FileResources::SALES_INVOICES);

        /** @var Collection<int, ArLedger> $paymentEntries */
        $paymentEntries = ArLedger::query()
            ->with(['company'])
            ->where('type', LedgerType::PAYMENT)
            ->where('document_number', $paymentDocumentNumber)
            ->orderBy('created_at')
            ->get();

        abort_if($paymentEntries->isEmpty(), 404);

        $payment = $paymentEntries->firstWhere('debit_acc', '!=', null) ?? $paymentEntries->first();

        return Pdf::loadView('sales.invoices.payment-pdf', [
            'payment' => $payment,
            'paymentEntries' => $paymentEntries,
            'setting' => Setting::query()->first(),
        ])->setPaper('a4')->stream($paymentDocumentNumber.'.pdf');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $subtype = LedgerSubType::SALES_INVOICE;
        $indexRoute = 'sales.invoices.index';
        $this->allows(FileResources::SALES_INVOICES, FileAction::ADD);
        $deliveryOrder = null;

        /** @var Collection<int, Company> $companies */
        $companies = Company::query()->where('is_debtor', true)->orderBy('name')->get();
        /** @var Collection<int, Product> $products */
        $products = Product::query()->orderBy('name')->get();
        /** @var Collection<int, Contact> $contacts */
        $contacts = Contact::query()->whereHas('company', fn ($query) => $query->where('is_debtor', true))->orderBy('name')->get();
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->with('chartType')->orderBy('code')->get();
        /** @var Collection<int, Tax> $taxes */
        $taxes = Tax::query()->where('is_active', true)->orderBy('code')->get();
        /** @var Collection<int, Discount> $discounts */
        $discounts = Discount::query()->where('is_active', true)->orderBy('code')->get();
        $canCreateCompany = auth()->user()?->hasFilePermission(FileResources::COMPANIES, FileAction::ADD) ?? false;

        $cloneData = session('clone_data');
        if ($cloneData) {
            session()->forget('clone_data');
        }

        return view(
            'sales.invoices.create',
            compact('companies', 'products', 'contacts', 'charts', 'taxes', 'discounts', 'subtype', 'indexRoute', 'deliveryOrder', 'canCreateCompany', 'cloneData')
        );

    }

    /**
     * Create a customer without leaving the Sales Invoice form.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return JsonResponse Returns the new customer for the form selector.
     */
    public function storeCompany(Request $request): JsonResponse
    {
        $this->allows(FileResources::COMPANIES, FileAction::ADD);
        /** @var array{name: string} $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $company = Company::query()->create([
            'name' => trim($validated['name']),
            'is_debtor' => true,
        ]);

        return response()->json(['id' => $company->id, 'name' => $company->name], 201);
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  string  $documentNumber  The invoice document number.
     * @return View Returns the rendered Blade view.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::UPDATE);

        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with(['company', 'contact', 'lines'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);
        abort_if($invoice->status === LedgerStatus::POSTED, 409);

        /** @var Collection<int, SalesInvoiceLine> $invoiceLines */
        $invoiceLines = $invoice->lines;

        /** @var Collection<int, ArLedger> $paymentEntries */
        $paymentEntries = ArLedger::query()
            ->where('type', LedgerType::PAYMENT)
            ->where('reference_number', $documentNumber)
            ->get();

        abort_if(
            FinanceLedger::query()
                ->withTrashed()
                ->where('reference_number', $documentNumber)
                ->where('type', LedgerType::INVOICE)
                ->exists(),
            409
        );

        /** @var ArLedger|null $paymentDebit */
        $paymentDebit = $paymentEntries->first(fn (ArLedger $entry): bool => bccomp($entry->debit_amount, '0.00', 2) !== 0);
        /** @var ArLedger|null $paymentCredit */
        $paymentCredit = $paymentEntries->first(fn (ArLedger $entry): bool => bccomp($entry->credit_amount, '0.00', 2) !== 0);

        session()->flashInput([
            'subtype' => LedgerSubType::SALES_INVOICE->value,
            'date' => $invoice->date->format('Y-m-d'),
            'company_id' => $invoice->company_id,
            'contact_id' => $invoice->contact_id,
            'sales_type' => $invoice->sales_type->value,
            'invoice_notes' => $invoice->notes,
            'amount' => $paymentDebit->debit_amount ?? $paymentCredit?->credit_amount,
            'payment_notes' => $paymentDebit->description ?? $paymentCredit?->description,
            'payment_debit_id' => $paymentDebit?->chart_id,
            'payment_credit_id' => $paymentCredit?->chart_id,
            'lines' => $invoiceLines
                ->values()
                ->map(fn (SalesInvoiceLine $line): array => [
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_option' => $line->discount_id ?? ((float) $line->discount_amount > 0 ? 'manual' : 'none'),
                    'discount_type' => $line->discount_type ?? DiscountType::PERCENT->value,
                    'discount_value' => (float) $line->discount_value > 0 ? $line->discount_value : $line->discount_rate,
                    'tax_option' => $line->tax_id ?? ((float) $line->tax_rate > 0 ? 'manual' : 'none'),
                    'tax_rate' => $line->tax_rate,
                    'tax_mode' => $line->tax_mode ?? TaxMode::EXCLUSIVE->value,
                ])
                ->all(),
        ]);

        $subtype = LedgerSubType::SALES_INVOICE;
        $indexRoute = 'sales.invoices.index';
        /** @var Collection<int, Company> $companies */
        $companies = Company::query()->where('is_debtor', true)->orderBy('name')->get();
        /** @var Collection<int, Product> $products */
        $products = Product::query()->orderBy('name')->get();
        /** @var Collection<int, Contact> $contacts */
        $contacts = Contact::query()->whereHas('company', fn ($query) => $query->where('is_debtor', true))->orderBy('name')->get();
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->with('chartType')->orderBy('code')->get();
        /** @var Collection<int, Tax> $taxes */
        $taxes = Tax::query()->where('is_active', true)->orderBy('code')->get();
        /** @var Collection<int, Discount> $discounts */
        $discounts = Discount::query()->where('is_active', true)->orderBy('code')->get();
        $deliveryOrder = DeliveryOrder::query()
            ->where('document_number', $invoice->reference_number)
            ->when(
                $invoice->sales_order_id !== null,
                fn ($query) => $query->orWhere('sales_order_id', $invoice->sales_order_id)
            )
            ->first();

        $canCreateCompany = auth()->user()?->hasFilePermission(FileResources::COMPANIES, FileAction::ADD) ?? false;

        return view(
            'sales.invoices.edit',
            compact('companies', 'products', 'contacts', 'charts', 'taxes', 'discounts', 'subtype', 'indexRoute', 'documentNumber', 'deliveryOrder', 'canCreateCompany')
        );
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreSalesInvoiceRequest  $request  The incoming request containing invoice data.
     * @return RedirectResponse Returns a redirect response.
     *
     * @throws ValidationException If validation fails.
     * @throws Throwable
     */
    public function store(StoreSalesInvoiceRequest $request): RedirectResponse
    {
        $data = SalesInvoiceData::from($request->validated());

        /** @var array<int, array<string, mixed>> $submittedLines */
        $submittedLines = $request->validated('lines');
        $lines = collect($submittedLines)
            ->filter(fn (array $line): bool => filled($line['product_id'] ?? null)
                || filled($line['description'] ?? null)
                || filled($line['quantity'] ?? null)
                || filled($line['unit_price'] ?? null));

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('ledgers.validation.invoice_line_required')]);
        }

        foreach ($lines as $lineNumber => $line) {
            /** @var int $lineNumber */
            /** @var array<string, mixed> $line */
            if (! filled($line['product_id'] ?? null) && ! filled($line['description'] ?? null)) {
                throw ValidationException::withMessages(["lines.$lineNumber.description" => __('ledgers.validation.invoice_line_product_or_description_required')]);
            }

            if (! filled($line['quantity'] ?? null)) {
                throw ValidationException::withMessages(["lines.$lineNumber.quantity" => __('ledgers.validation.invoice_line_quantity_required')]);
            }

            if (! array_key_exists('unit_price', $line) || $line['unit_price'] === null || $line['unit_price'] === '') {
                throw ValidationException::withMessages(["lines.$lineNumber.unit_price" => __('ledgers.validation.invoice_line_unit_price_required')]);
            }
        }

        $chartSetting = ChartSetting::query()->first();
        /** @var Collection<string, Product> $lineProducts */
        $lineProducts = Product::query()
            ->whereIn('id', $lines->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id');
        $lineCommercialTerms = $lines->map(
            function (array $line, int $lineNumber) use ($lineProducts, $data): array {
                $productId = $line['product_id'] ?? '';
                $productKey = is_string($productId) ? $productId : (is_numeric($productId) ? (string) $productId : '');

                return $this->resolveLineCommercialTerms(
                    $line,
                    $lineProducts->get($productKey),
                    (string) $data->date,
                    $lineNumber
                );
            }
        );
        $debitAccountId = $data->debitId ?? $chartSetting?->client_chart_uuid;
        $creditAccountId = $data->creditId ?? $chartSetting?->income_chart_uuid;

        if (empty($debitAccountId) || empty($creditAccountId)) {
            throw ValidationException::withMessages([
                'debit_id' => __('ledgers.validation.debit_account_or_default_required'),
                'credit_id' => __('ledgers.validation.credit_account_or_default_required'),
            ]);
        }

        $debitAccount = $this->accountSnapshot($debitAccountId, 'debit_id');
        $creditAccount = $this->accountSnapshot($creditAccountId, 'credit_id');
        $taxAccountId = null;
        $taxAccount = null;

        if ($lineCommercialTerms->contains(fn (array $terms): bool => (float) $terms['tax_rate'] > 0)) {
            $taxAccountId = $chartSetting?->output_tax_chart_uuid;

            if (empty($taxAccountId)) {
                throw ValidationException::withMessages([
                    'tax_rate' => __('ledgers.validation.output_tax_account_required'),
                ]);
            }

            $taxAccount = $this->taxAccountSnapshot($taxAccountId);
        }

        $hasPayment = filled($data->amount ?? null);
        $paymentSubtype = LedgerSubType::RECEIPT->value;
        $paymentDebitAccountId = null;
        $paymentCreditAccountId = null;
        $paymentDebitAccount = null;
        $paymentCreditAccount = null;

        if ($hasPayment) {
            $paymentDebitAccountId = $data->paymentDebitId
                ?? $chartSetting?->bank_chart_uuid;
            $paymentCreditAccountId = $data->paymentCreditId
                ?? $chartSetting?->client_chart_uuid;

            if (empty($paymentDebitAccountId) || empty($paymentCreditAccountId)) {
                throw ValidationException::withMessages([
                    'payment_debit_id' => __('ledgers.validation.debit_account_or_default_required'),
                    'payment_credit_id' => __('ledgers.validation.credit_account_or_default_required'),
                ]);
            }

            $paymentDebitAccount = $this->accountSnapshot($paymentDebitAccountId, 'payment_debit_id');
            $paymentCreditAccount = $this->accountSnapshot($paymentCreditAccountId, 'payment_credit_id');
        }

        $companyName = filled($data->companyId ?? null)
            ? Company::query()->whereKey($data->companyId)->value('name')
            : null;

        /** @var \Illuminate\Support\Collection<int|string, string> $productNames */
        $productNames = $lineProducts->pluck('name', 'id');
        /** @var \Illuminate\Support\Collection<int|string, int|null> $productWarranties */
        $productWarranties = $lineProducts->pluck('warranty_months', 'id');

        /** @var string|null $invoiceAttachmentPath */
        $invoiceAttachmentPath = $request->hasFile('invoice_attachment')
            ? $this->storeUpload($request->file('invoice_attachment'), 'ledger_attachments')
            : null;
        $docNumber = $this->generateDocumentNumber(LedgerSubType::SALES_INVOICE);
        $invoiceReferenceNumber = $docNumber;
        $invoiceDueDate = null;
        $invoiceSalesOrderNumber = null;
        $paymentDocumentNumber = $hasPayment
            ? (
                $this->generateDocumentNumber(LedgerSubType::from($paymentSubtype))
            )
            : null;
        $paymentAmount = $hasPayment
            ? number_format((float) $data->amount, 2, '.', '')
            : null;

        DB::transaction(function () use (

            $companyName,
            $docNumber,
            $invoiceAttachmentPath,
            $invoiceDueDate,
            $invoiceReferenceNumber,
            $lines,
            $productNames,
            $productWarranties,
            $lineCommercialTerms,
            $data
        ): void {
            // Drafts save freely: stock availability is enforced when stock
            // actually moves (deduct-inventory button, delivery order), never
            // as a condition for writing the document itself.
            // Create the sales invoice header
            $invoice = SalesInvoice::query()->create([
                'id' => (string) Str::uuid7(),
                'company_id' => $data->companyId,
                'contact_id' => $data->contactId,
                'document_number' => $docNumber,
                'sales_type' => SalesType::PRODUCT,
                'reference_number' => $invoiceReferenceNumber,
                'sales_order_id' => null,
                'company_name' => $companyName,
                'date' => $data->date,
                'due_date' => $invoiceDueDate,
                'status' => LedgerStatus::PENDING,
                'attachment_path' => $invoiceAttachmentPath,
                'notes' => $data->invoiceNotes,
                'subtotal' => '0.00',
                'total_amount' => '0.00',
            ]);

            $subtotal = '0.00';
            $totalAmount = '0.00';

            // Create invoice lines
            foreach ($lines as $lineNumber => $line) {
                /** @var int $lineNumber */
                /** @var array<string, mixed> $line */
                $terms = $lineCommercialTerms->get($lineNumber);
                /** @var array<string, mixed> $terms */
                $quantityRaw = $line['quantity'] ?? 0;
                $quantity = is_numeric($quantityRaw) ? (int) $quantityRaw : 0;
                $unitPriceRaw = $line['unit_price'] ?? '';
                $unitPrice = is_string($unitPriceRaw) ? $unitPriceRaw : (is_numeric($unitPriceRaw) ? (string) $unitPriceRaw : '');
                $discountAmount = is_string($terms['discount_amount']) ? $terms['discount_amount'] : (is_numeric($terms['discount_amount']) ? (string) $terms['discount_amount'] : '0');
                $taxRate = is_string($terms['tax_rate']) ? $terms['tax_rate'] : (is_numeric($terms['tax_rate']) ? (string) $terms['tax_rate'] : '0');
                $taxMode = $terms['tax_mode'] instanceof TaxMode ? $terms['tax_mode'] : TaxMode::EXCLUSIVE;
                $amounts = $this->calculateInvoiceLine(
                    $quantity,
                    $unitPrice,
                    $discountAmount,
                    $taxRate,
                    $taxMode
                );
                $productIdRaw = $line['product_id'] ?? null;
                $productKey = is_string($productIdRaw) ? $productIdRaw : (is_numeric($productIdRaw) ? (string) $productIdRaw : null);

                SalesInvoiceLine::query()->create([
                    'id' => (string) Str::uuid7(),
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $line['product_id'] ?? null,
                    'product_name' => filled($line['product_id'] ?? null)
                        ? (string) ($productNames->get($productKey) ?? '')
                        : null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal_amount' => $amounts['subtotal'],
                    'discount_id' => $terms['discount_id'],
                    'discount_name' => $terms['discount_name'],
                    'discount_type' => $terms['discount_type'],
                    'discount_value' => $terms['discount_value'],
                    'discount_rate' => $terms['discount_rate'],
                    'discount_amount' => $amounts['discount'],
                    'tax_id' => $terms['tax_id'],
                    'tax_name' => $terms['tax_name'],
                    'tax_rate' => $terms['tax_rate'],
                    'tax_amount' => $amounts['tax'],
                    'tax_mode' => ($terms['tax_mode'] instanceof TaxMode ? $terms['tax_mode']->value : ''),
                    'line_total_amount' => $amounts['total'],
                    'total_amount' => $amounts['total'],
                    'warranty_months' => filled($line['product_id'] ?? null)
                        ? (int) ($productWarranties->get($productKey) ?? 0)
                        : 0,
                    'deduct_inventory' => false,
                    'description' => $line['description'] ?? null,
                ]);

                $subtotal = bcadd($subtotal, $amounts['subtotal'], 2);
                $totalAmount = bcadd($totalAmount, $amounts['total'], 2);
            }

            // Update invoice totals
            $invoice->update([
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
            ]);

            $this->syncPendingSalesProductMovements($docNumber);
        });

        return redirect()
            ->route('sales.invoices.show', $docNumber)
            ->with('success', FlashMessage::created(FileResources::SALES_INVOICES));

    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateSalesInvoiceRequest  $request  The incoming request containing invoice data.
     * @param  string  $documentNumber  The invoice document number.
     * @return RedirectResponse Returns a redirect response.
     *
     * @throws ValidationException If validation fails.
     * @throws Throwable
     */
    public function update(UpdateSalesInvoiceRequest $request, string $documentNumber): RedirectResponse
    {
        $data = SalesInvoiceData::from($request->validated());

        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);
        abort_if($invoice->status === LedgerStatus::POSTED, 409);

        /** @var array<int, array<string, mixed>> $submittedLines */
        $submittedLines = $request->validated('lines');
        $lines = collect($submittedLines)
            ->filter(fn (array $line): bool => filled($line['product_id'] ?? null)
                || filled($line['description'] ?? null)
                || filled($line['quantity'] ?? null)
                || filled($line['unit_price'] ?? null));

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('ledgers.validation.invoice_line_required')]);
        }

        foreach ($lines as $lineNumber => $line) {
            /** @var int $lineNumber */
            /** @var array<string, mixed> $line */
            if (! filled($line['product_id'] ?? null) && ! filled($line['description'] ?? null)) {
                throw ValidationException::withMessages(["lines.$lineNumber.description" => __('ledgers.validation.invoice_line_product_or_description_required')]);
            }

            if (! filled($line['quantity'] ?? null)) {
                throw ValidationException::withMessages(["lines.$lineNumber.quantity" => __('ledgers.validation.invoice_line_quantity_required')]);
            }

            if (! array_key_exists('unit_price', $line) || $line['unit_price'] === null || $line['unit_price'] === '') {
                throw ValidationException::withMessages(["lines.$lineNumber.unit_price" => __('ledgers.validation.invoice_line_unit_price_required')]);
            }
        }

        $chartSetting = ChartSetting::query()->first();
        /** @var Collection<string, Product> $lineProducts */
        $lineProducts = Product::query()
            ->whereIn('id', $lines->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id');
        $lineCommercialTerms = $lines->map(
            function (array $line, int $lineNumber) use ($lineProducts, $data): array {
                $productId = $line['product_id'] ?? '';
                $productKey = is_string($productId) ? $productId : (is_numeric($productId) ? (string) $productId : '');

                return $this->resolveLineCommercialTerms(
                    $line,
                    $lineProducts->get($productKey),
                    (string) $data->date,
                    $lineNumber
                );
            }
        );
        $debitAccountId = $data->debitId ?? $chartSetting?->client_chart_uuid;
        $creditAccountId = $data->creditId ?? $chartSetting?->income_chart_uuid;

        if (empty($debitAccountId) || empty($creditAccountId)) {
            throw ValidationException::withMessages([
                'debit_id' => __('ledgers.validation.debit_account_or_default_required'),
                'credit_id' => __('ledgers.validation.credit_account_or_default_required'),
            ]);
        }

        $debitAccount = $this->accountSnapshot($debitAccountId, 'debit_id');
        $creditAccount = $this->accountSnapshot($creditAccountId, 'credit_id');

        $companyName = filled($data->companyId ?? null)
            ? Company::query()->whereKey($data->companyId)->value('name')
            : null;

        /** @var \Illuminate\Support\Collection<int|string, string> $productNames */
        $productNames = $lineProducts->pluck('name', 'id');
        /** @var \Illuminate\Support\Collection<int|string, int|null> $productWarranties */
        $productWarranties = $lineProducts->pluck('warranty_months', 'id');

        /** @var string|null $invoiceAttachmentPath */
        $invoiceAttachmentPath = $request->hasFile('invoice_attachment')
            ? $this->storeUpload($request->file('invoice_attachment'), 'ledger_attachments')
            : $invoice->attachment_path;

        DB::transaction(function () use (
            $invoice,
            $companyName,
            $invoiceAttachmentPath,
            $lines,
            $productNames,
            $productWarranties,
            $lineCommercialTerms,
            $data
        ): void {
            // Same as store(): drafts save freely, stock is enforced where it moves.
            // Delete existing lines
            $invoice->lines()->delete();

            $subtotal = '0.00';
            $totalAmount = '0.00';

            // Recreate invoice lines
            foreach ($lines as $lineNumber => $line) {
                /** @var int $lineNumber */
                /** @var array<string, mixed> $line */
                $terms = $lineCommercialTerms->get($lineNumber);
                /** @var array<string, mixed> $terms */
                $quantityRaw = $line['quantity'] ?? 0;
                $quantity = is_numeric($quantityRaw) ? (int) $quantityRaw : 0;
                $unitPriceRaw = $line['unit_price'] ?? '';
                $unitPrice = is_string($unitPriceRaw) ? $unitPriceRaw : (is_numeric($unitPriceRaw) ? (string) $unitPriceRaw : '');
                $discountAmount = is_string($terms['discount_amount']) ? $terms['discount_amount'] : (is_numeric($terms['discount_amount']) ? (string) $terms['discount_amount'] : '0');
                $taxRate = is_string($terms['tax_rate']) ? $terms['tax_rate'] : (is_numeric($terms['tax_rate']) ? (string) $terms['tax_rate'] : '0');
                $taxMode = $terms['tax_mode'] instanceof TaxMode ? $terms['tax_mode'] : TaxMode::EXCLUSIVE;
                $amounts = $this->calculateInvoiceLine(
                    $quantity,
                    $unitPrice,
                    $discountAmount,
                    $taxRate,
                    $taxMode
                );
                $productIdRaw = $line['product_id'] ?? null;
                $productKey = is_string($productIdRaw) ? $productIdRaw : (is_numeric($productIdRaw) ? (string) $productIdRaw : null);

                SalesInvoiceLine::query()->create([
                    'id' => (string) Str::uuid7(),
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $line['product_id'] ?? null,
                    'product_name' => filled($line['product_id'] ?? null)
                        ? (string) ($productNames->get($productKey) ?? '')
                        : null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal_amount' => $amounts['subtotal'],
                    'discount_id' => $terms['discount_id'],
                    'discount_name' => $terms['discount_name'],
                    'discount_type' => $terms['discount_type'],
                    'discount_value' => $terms['discount_value'],
                    'discount_rate' => $terms['discount_rate'],
                    'discount_amount' => $amounts['discount'],
                    'tax_id' => $terms['tax_id'],
                    'tax_name' => $terms['tax_name'],
                    'tax_rate' => $terms['tax_rate'],
                    'tax_amount' => $amounts['tax'],
                    'tax_mode' => ($terms['tax_mode'] instanceof TaxMode ? $terms['tax_mode']->value : ''),
                    'line_total_amount' => $amounts['total'],
                    'warranty_months' => filled($line['product_id'] ?? null)
                        ? (int) ($productWarranties->get($productKey) ?? 0)
                        : 0,
                    'deduct_inventory' => false,
                    'description' => $line['description'] ?? null,
                ]);

                $subtotal = bcadd($subtotal, $amounts['subtotal'], 2);
                $totalAmount = bcadd($totalAmount, $amounts['total'], 2);
            }

            // Update invoice
            $invoice->update([
                'company_id' => $data->companyId,
                'contact_id' => $data->contactId,
                'company_name' => $companyName,
                'date' => $data->date,
                'attachment_path' => $invoiceAttachmentPath,
                'notes' => $data->invoiceNotes,
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
            ]);

            $this->syncPendingSalesProductMovements($invoice->document_number);
        });

        return redirect()
            ->route('sales.invoices.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::SALES_INVOICES));

    }

    /**
     * Remove a resource by its object.
     * Remove an unposted invoice, its linked payment, and pending stock movements.
     *
     * @param  string  $documentNumber  The unposted invoice document number.
     * @return RedirectResponse Redirect response to the multi-invoice index.
     *
     * @throws Throwable
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::REMOVE);

        DB::transaction(function () use ($documentNumber): void {
            /** @var SalesInvoice|null $invoice */
            $invoice = SalesInvoice::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            abort_if($invoice->status === LedgerStatus::POSTED, 409);

            /** @var Collection<int, ProductLedger> $productMovements */
            $productMovements = ProductLedger::query()
                ->where('document_number', $documentNumber)
                ->where('source_type', ProductMovementSource::SALES_INVOICE)
                ->lockForUpdate()
                ->get();

            if ($productMovements->contains(
                fn (ProductLedger $movement): bool => $movement->status !== ProductMovementStatus::PENDING
            )) {
                throw ValidationException::withMessages([
                    'document_number' => __('ledgers.validation.active_stock_delete_forbidden'),
                ]);
            }

            ProductLedger::query()
                ->whereKey($productMovements->modelKeys())
                ->forceDelete();

            $invoice->lines()->delete();
            $invoice->delete();
        });

        return redirect()
            ->route('sales.invoices.index')
            ->with('success', FlashMessage::deleted(FileResources::SALES_INVOICES));
    }

    /**
     * Approve a sales invoice into the General Ledger.
     *
     * @param  string  $documentNumber  The invoice document number.
     * @return RedirectResponse Redirect response to the invoice detail page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::APPROVE);

        DB::transaction(function () use ($documentNumber): void {
            /** @var SalesInvoice|null $invoice */
            $invoice = SalesInvoice::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            abort_if($invoice->status === LedgerStatus::POSTED, 409);

            /** @var Collection<int, SalesInvoiceLine> $lines */
            $lines = $invoice->lines()->get();

            abort_if($lines->isEmpty(), 404);

            // Post to both ledgers - services handle chart queries and DTO building
            $this->receivable->post($invoice, $lines);
            $this->ledger->postSalesInvoice($invoice, $lines);

            // Update invoice status to POSTED. Stock is not moved here: it moves
            // when the deduct-inventory button is pressed or when a delivery
            // order posts, so approving a document never minuses stock by itself.
            $invoice->update(['status' => LedgerStatus::POSTED]);
        });

        return redirect()
            ->route('sales.invoices.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::SALES_INVOICES));
    }

    /**
     * Reverse a posted sales invoice by creating a new reversing journal entry.
     *
     * @param  Request  $request  The HTTP request instance containing the reversal date and reason.
     * @param  string  $documentNumber  The invoice document number.
     * @return RedirectResponse Redirects to the invoice detail page.
     *
     * @throws Throwable
     */
    public function reverse(Request $request, string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::APPROVE);

        /** @var array{reversal_date: string, reason: string} $validated */
        $validated = $request->validate([
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($documentNumber, $validated): void {
            $invoice = SalesInvoice::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            abort_if($invoice->reversal_journal_id !== null, 409, __('journal_entries.reversal_already_exists'));
            abort_if($invoice->undone_at !== null, 409, __('journal_entries.reversal_already_exists'));

            $postedLedgers = FinanceLedger::query()
                ->where('document_number', $documentNumber)
                ->where('status', LedgerStatus::POSTED)
                ->lockForUpdate()
                ->get();

            abort_if($postedLedgers->isEmpty(), 404);

            $sumCredit = $postedLedgers->sum('credit_amount');
            $totalDebit = number_format(is_numeric($sumCredit) ? (float) $sumCredit : 0.0, 2, '.', '');
            $sumDebit = $postedLedgers->sum('debit_amount');
            $totalCredit = number_format(is_numeric($sumDebit) ? (float) $sumDebit : 0.0, 2, '.', '');

            abort_if(
                bccomp($totalDebit, '0.00', 2) !== 1 || bccomp($totalDebit, $totalCredit, 2) !== 0,
                422,
                __('journal_entries.unbalanced_entry_error')
            );

            $sourceLedger = $postedLedgers->first();
            $reversalEntry = JournalEntry::query()->create([
                'reversal_of_id' => null,
                'document_number' => $this->generateDocumentNumber(LedgerSubType::JOURNAL),
                'reference_number' => $documentNumber,
                'date' => $validated['reversal_date'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => LedgerStatus::POSTED,
                'notes' => $validated['reason'],
            ]);

            $now = now();
            $itemRows = $postedLedgers->values()->map(function (FinanceLedger $ledger) use ($reversalEntry, $documentNumber, $now): array {
                return [
                    'id' => (string) Str::uuid7(),
                    'journal_entry_id' => $reversalEntry->id,
                    'debit_id' => $ledger->credit_id,
                    'credit_id' => $ledger->debit_id,
                    'debit_acc' => $ledger->credit_acc,
                    'credit_acc' => $ledger->debit_acc,
                    'debit_acc_desc' => $ledger->credit_acc_desc,
                    'credit_acc_desc' => $ledger->debit_acc_desc,
                    'document_number' => $reversalEntry->document_number,
                    'reference_number' => $documentNumber,
                    'date' => $reversalEntry->date,
                    'description' => __('journal_entries.reversal_line_description', [
                        'document_number' => $ledger->document_number,
                        'description' => $ledger->description ?? '-',
                    ]),
                    'debit_amount' => $ledger->credit_amount,
                    'credit_amount' => $ledger->debit_amount,
                    'total_amount' => $ledger->total_amount,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            })->all();

            JournalEntryChart::query()->insert($itemRows);

            $sourceType = $sourceLedger->type;
            $ledgerRows = collect($itemRows)->map(function (array $item, int $index) use ($postedLedgers, $now, $sourceType, $validated): array {
                $postedLedger = $postedLedgers[$index] ?? abort(500);

                return [
                    'id' => $item['id'],
                    'source_id' => $item['id'],
                    'source_type' => null,
                    'company_id' => $postedLedger->company_id,
                    'contact_id' => $postedLedger->contact_id,
                    'debit_id' => $item['debit_id'],
                    'credit_id' => $item['credit_id'],
                    'debit_acc' => $item['debit_acc'],
                    'credit_acc' => $item['credit_acc'],
                    'debit_acc_desc' => $item['debit_acc_desc'],
                    'credit_acc_desc' => $item['credit_acc_desc'],
                    'company_name' => $postedLedger->company_name,
                    'document_number' => $item['document_number'],
                    'reference_number' => $item['reference_number'],
                    'type' => $sourceType,
                    'subtype' => LedgerSubType::JOURNAL->value,
                    'date' => $item['date'],
                    'document_date' => $item['date'],
                    'debit_amount' => $item['debit_amount'],
                    'credit_amount' => $item['credit_amount'],
                    'total_amount' => $item['total_amount'],
                    'status' => LedgerStatus::POSTED->value,
                    'notes' => $validated['reason'],
                    'description' => $item['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            })->all();

            FinanceLedger::query()->insert($ledgerRows);

            // Reverse the AR ledger entries by soft-deleting them. The reversal
            // journal already reversed the GL entries, so we just need to remove
            // the AR entries from the customer's statement.
            ArLedger::query()
                ->where('document_number', $documentNumber)
                ->where('status', LedgerStatus::POSTED)
                ->update(['deleted_at' => now()]);

            // Create credit notes and product movements for items not yet returned.
            // This ensures full undo behaves like a full return.
            $lines = $invoice->lines()->whereNotNull('product_id')->get();
            $location = $this->resolveStockLocation(null);
            $chartConfig = SalesInvoiceChart::query()
                ->where('company_id', $invoice->company_id)
                ->first()
                ?? SalesInvoiceChart::query()->whereNull('company_id')->first();

            if ($chartConfig && $chartConfig->receivable_id && $chartConfig->revenue_id) {
                $receivableChart = Chart::query()->find($chartConfig->receivable_id);
                $revenueChart = Chart::query()->find($chartConfig->revenue_id);
                $chartSetting = ChartSetting::query()->first();
                $cogsChart = filled($chartSetting?->cogs_chart_uuid)
                    ? Chart::query()->whereKey($chartSetting->cogs_chart_uuid)->first()
                    : null;
                $inventoryChart = filled($chartSetting?->inventory_chart_uuid)
                    ? Chart::query()->whereKey($chartSetting->inventory_chart_uuid)->first()
                    : null;

                foreach ($lines as $line) {
                    /** @var SalesInvoiceLine $line */
                    $remaining = bcsub(
                        DecimalService::normalize((string) ($line->quantity ?? 0)),
                        DecimalService::normalize((string) ($line->returned_quantity ?? 0)),
                        4
                    );

                    if (bccomp($remaining, '0.0000', 4) !== 1) {
                        continue;
                    }

                    $product = Product::query()->whereKey($line->product_id)->first();
                    if ($product === null || $receivableChart === null || $revenueChart === null || $cogsChart === null || $inventoryChart === null) {
                        continue;
                    }

                    $creditAmount = bcmul($remaining, DecimalService::normalize((string) $line->unit_price), 2);
                    $costAmount = bcmul($remaining, DecimalService::normalize((string) $product->base_price), 2);
                    $adjustmentNumber = $this->generateDocumentNumber(LedgerSubType::DECREASE_ADJUSTMENT);
                    $movementId = (string) Str::uuid7();

                    // Create product movement (IN) to return stock
                    ProductLedger::query()->insert([
                        'id' => $movementId,
                        'product_id' => $product->id,
                        'stock_location_id' => $location->id,
                        'stock_location_name' => $location->name,
                        'company_id' => $invoice->company_id,
                        'source_entry_id' => $line->id,
                        'cogs_ledger_id' => null,
                        'inventory_ledger_id' => null,
                        'product_sku' => $product->sku,
                        'product_name' => $line->product_name ?? $product->name,
                        'company_name' => $invoice->company_name,
                        'document_number' => $adjustmentNumber,
                        'reference_number' => $documentNumber,
                        'source_type' => ProductMovementSource::SALES_RETURN->value,
                        'direction' => ProductMovementDirection::IN->value,
                        'date' => $validated['reversal_date'],
                        'quantity' => $remaining,
                        'unit_cost' => DecimalService::normalize((string) $product->base_price),
                        'total_cost' => $costAmount,
                        'status' => ProductMovementStatus::ACTIVE->value,
                        'activated_at' => $now,
                        'notes' => __('sales_returns.undo_note', ['document' => $documentNumber]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    // Create credit note
                    $adjustment = SalesAdjustment::query()->create([
                        'id' => (string) Str::uuid7(),
                        'company_id' => $invoice->company_id,
                        'contact_id' => $invoice->contact_id,
                        'subtype' => LedgerSubType::DECREASE_ADJUSTMENT,
                        'document_number' => $adjustmentNumber,
                        'reference_number' => $documentNumber,
                        'company_name' => $invoice->company_name,
                        'contact_name' => $invoice->contact_name,
                        'date' => $validated['reversal_date'],
                        'status' => LedgerStatus::POSTED,
                        'subtotal' => $creditAmount,
                        'total_amount' => $creditAmount,
                        'notes' => __('sales_returns.undo_note', ['document' => $documentNumber]),
                    ]);

                    $adjustment->lines()->create([
                        'product_id' => $product->id,
                        'product_name' => $line->product_name ?? $product->name,
                        'quantity' => $remaining,
                        'unit_price' => DecimalService::normalize((string) $line->unit_price),
                        'subtotal_amount' => $creditAmount,
                        'line_total_amount' => $creditAmount,
                        'total_amount' => $creditAmount,
                        'description' => __('sales_returns.undo_note', ['document' => $documentNumber]),
                    ]);

                    $adjustment->charts()->create([
                        'debit_id' => $revenueChart->id,
                        'debit_acc' => $revenueChart->code,
                        'debit_acc_desc' => $revenueChart->name,
                        'document_number' => $adjustmentNumber,
                        'reference_number' => $documentNumber,
                        'date' => $validated['reversal_date'],
                        'description' => __('sales_returns.undo_note', ['document' => $documentNumber]),
                        'debit_amount' => $creditAmount,
                        'credit_amount' => '0.00',
                        'total_amount' => $creditAmount,
                    ]);

                    $adjustment->charts()->create([
                        'credit_id' => $receivableChart->id,
                        'credit_acc' => $receivableChart->code,
                        'credit_acc_desc' => $receivableChart->name,
                        'document_number' => $adjustmentNumber,
                        'reference_number' => $documentNumber,
                        'date' => $validated['reversal_date'],
                        'description' => __('sales_returns.undo_note', ['document' => $documentNumber]),
                        'debit_amount' => '0.00',
                        'credit_amount' => $creditAmount,
                        'total_amount' => $creditAmount,
                    ]);

                    // Build general ledger rows from the charts, same as receiveReturn does.
                    $chartsById = Chart::query()
                        ->whereIn('id', [$revenueChart->id, $receivableChart->id])
                        ->get(['id', 'name'])
                        ->mapWithKeys(fn (Chart $chart): array => [(string) $chart->id => (string) $chart->name]);
                    $generalRows = [];
                    foreach ($adjustment->charts()->get() as $chart) {
                        $generalRows[] = GeneralLedgerData::fromSalesAdjustmentLine($adjustment, $chart, $chartsById);
                    }

                    // Post to AR ledger
                    $this->invoicePoster->post(
                        $adjustment,
                        $adjustmentNumber,
                        Carbon::parse($validated['reversal_date']),
                        LedgerType::INVOICE,
                        LedgerSubType::DECREASE_ADJUSTMENT,
                        $generalRows,
                        companyId: $adjustment->company_id,
                        contactId: $adjustment->contact_id,
                        companyName: $adjustment->company_name,
                        referenceNumber: $documentNumber,
                    );

                    // Update line's returned_quantity
                    $line->update(['returned_quantity' => $line->quantity]);
                }
            }

            $invoice->update(['reversal_journal_id' => $reversalEntry->id]);
            $this->attributeUndone($invoice);
        });

        return redirect()
            ->route('sales.invoices.show', $documentNumber)
            ->with('success', __('journal_entries.reversal_success'));
    }

    /**
     * Approve multiple sales invoices and post them to the general ledger.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers to approve.
     * @return RedirectResponse Redirects to the sales invoices history page.
     *
     * @throws Throwable
     */
    public function batchApprove(Request $request): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::APPROVE);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        $count = 0;

        DB::transaction(function () use ($request, &$count): void {
            /** @var array<int, mixed> $documentNumbers */
            $documentNumbers = $request->input('document_numbers') ?? [];
            foreach ($documentNumbers as $documentNumber) {
                if (! is_string($documentNumber)) {
                    continue;
                }

                /** @var SalesInvoice|null $invoice */
                $invoice = SalesInvoice::query()
                    ->where('document_number', $documentNumber)
                    ->lockForUpdate()
                    ->first();

                if ($invoice === null) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.no_entries', ['number' => $documentNumber]),
                    ]);
                }

                if ($invoice->status === LedgerStatus::POSTED) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.already_posted', ['number' => $documentNumber]),
                    ]);
                }

                /** @var Collection<int, SalesInvoiceLine> $lines */
                $lines = $invoice->lines()->get();

                if ($lines->isEmpty()) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.no_entries', ['number' => $documentNumber]),
                    ]);
                }

                // Post to both ledgers - services handle chart queries and DTO building
                $this->receivable->post($invoice, $lines);
                $this->ledger->postSalesInvoice($invoice, $lines);

                $invoice->update(['status' => LedgerStatus::POSTED]);

                $count++;
            }
        });

        return redirect()
            ->route('sales.invoices.history.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending sales invoices.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers to cancel.
     * @return RedirectResponse Redirects to the sales invoices index page.
     */
    public function batchCancel(Request $request): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::CANCEL);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        $affected = SalesInvoice::query()
            ->whereIn('document_number', $request->input('document_numbers') ?? [])
            ->where('status', LedgerStatus::PENDING)
            ->update(['status' => LedgerStatus::CANCELLED]);

        return redirect()
            ->route('sales.invoices.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $affected]));
    }

    /**
     * Convert a chart UUID into immutable code and description history fields.
     *
     * @param  mixed  $chartId  The selected or configured chart UUID.
     * @param  string  $attribute  The form field receiving a validation error.
     * @return array{code: string, description: string|null} The account history snapshot.
     *
     * @throws ValidationException If the configured chart no longer exists.
     */
    private function accountSnapshot(mixed $chartId, string $attribute): array
    {
        $chart = Chart::query()->whereKey($chartId)->first();

        if ($chart === null) {
            throw ValidationException::withMessages([
                $attribute => __('common.labels.not_found_record'),
            ]);
        }

        $accountCode = trim((string) $chart->code);

        if ($accountCode === '') {
            throw ValidationException::withMessages([
                $attribute => __('common.labels.not_found_record'),
            ]);
        }

        return [
            'code' => $accountCode,
            'description' => filled($chart->name) ? (string) $chart->name : null,
        ];
    }

    /**
     * Resolve and validate the output-tax control account used by sales invoice posting.
     *
     * @param  mixed  $chartId  The chart id value.
     * @return array{code: string, description: string|null}
     */
    private function taxAccountSnapshot(mixed $chartId): array
    {
        $chart = Chart::query()->with('chartType')->whereKey($chartId)->first();

        if ($chart?->chartType?->isLiability !== true) {
            throw ValidationException::withMessages([
                'tax_rate' => __('ledgers.validation.output_tax_must_be_liability'),
            ]);
        }

        return $this->accountSnapshot($chartId, 'tax_rate');
    }

    /**
     * Resolve the product defaults or manual commercial terms for one invoice line.
     * The resolved values are snapshots and do not change when the product master changes later.
     *
     * @param  array<string, mixed>  $line  The submitted line values.
     * @param  Product|null  $product  The selected product resource instance.
     * @param  string  $invoiceDate  The invoice date.
     * @param  int  $lineNumber  The submitted line index.
     * @return array{discount_id: string|null, discount_name: string|null, discount_type: string|null, discount_value: string, discount_rate: string, discount_amount: string, tax_id: string|null, tax_name: string|null, tax_rate: string, tax_mode: TaxMode}
     */
    private function resolveLineCommercialTerms(
        array $line,
        ?Product $product,
        string $invoiceDate,
        int $lineNumber
    ): array {
        $defaultDiscountId = $product instanceof Product ? $product->default_discount_id : null;
        $discountOptionRaw = $line['discount_option'] ?? $defaultDiscountId ?? 'none';
        $discountOption = is_string($discountOptionRaw) ? $discountOptionRaw : (is_numeric($discountOptionRaw) ? (string) $discountOptionRaw : 'none');
        $discount = in_array($discountOption, ['none', 'manual'], true)
            ? null
            : Discount::query()->whereKey($discountOption)->where('is_active', true)->first();

        if (! in_array($discountOption, ['none', 'manual'], true) && $discount === null) {
            throw ValidationException::withMessages([
                "lines.$lineNumber.discount_option" => __('discounts.messages.inactive'),
            ]);
        }
        if ($discount instanceof Discount
            && (($discount->starts_at !== null && $invoiceDate < $discount->starts_at->format('Y-m-d'))
                || ($discount->ends_at !== null && $invoiceDate > $discount->ends_at->format('Y-m-d')))) {
            throw ValidationException::withMessages([
                "lines.$lineNumber.discount_option" => __('discounts.messages.outside_date_range'),
            ]);
        }

        $discountTypeRaw = $line['discount_type'] ?? DiscountType::PERCENT->value;
        $discountTypeStr = is_string($discountTypeRaw) ? $discountTypeRaw : (is_numeric($discountTypeRaw) ? (string) $discountTypeRaw : DiscountType::PERCENT->value);
        $discountType = $discount instanceof Discount
            ? $discount->type
            : DiscountType::from($discountTypeStr);
        $discountValueRaw = $discount instanceof Discount ? $discount->value : ($line['discount_value'] ?? 0);
        $discountValue = $discountOption === 'none'
            ? 0.0
            : (is_numeric($discountValueRaw) ? (float) $discountValueRaw : 0.0);
        if ($discountType === DiscountType::PERCENT && $discountValue > 100) {
            throw ValidationException::withMessages([
                "lines.$lineNumber.discount_value" => __('validation.max.numeric', [
                    'attribute' => __('discounts.form.value'),
                    'max' => 100,
                ]),
            ]);
        }

        $quantityRaw = $line['quantity'] ?? 0;
        $quantityInt = is_numeric($quantityRaw) ? (int) $quantityRaw : 0;
        $unitPriceRaw = $line['unit_price'] ?? 0;
        $unitPriceFloat = is_numeric($unitPriceRaw) ? (float) $unitPriceRaw : 0.0;
        $subtotal = round($quantityInt * $unitPriceFloat, 2);
        $discountAmount = $discountType === DiscountType::PERCENT
            ? round($subtotal * $discountValue / 100, 2)
            : round($discountValue, 2);
        if ($discountAmount > $subtotal) {
            throw ValidationException::withMessages([
                "lines.$lineNumber.discount_value" => __('discounts.messages.amount_exceeds_subtotal'),
            ]);
        }

        $defaultTaxId = $product instanceof Product ? $product->default_tax_id : null;
        $taxOptionRaw = $line['tax_option'] ?? $defaultTaxId ?? 'none';
        $taxOption = is_string($taxOptionRaw) ? $taxOptionRaw : (is_numeric($taxOptionRaw) ? (string) $taxOptionRaw : 'none');
        $tax = in_array($taxOption, ['none', 'manual'], true)
            ? null
            : Tax::query()->whereKey($taxOption)->where('is_active', true)->first();
        if (! in_array($taxOption, ['none', 'manual'], true) && $tax === null) {
            throw ValidationException::withMessages([
                "lines.$lineNumber.tax_option" => __('taxes.messages.inactive'),
            ]);
        }

        $taxRateRaw = $tax instanceof Tax ? $tax->rate : ($line['tax_rate'] ?? 0);
        $taxRate = $taxOption === 'none'
            ? 0.0
            : (is_numeric($taxRateRaw) ? (float) $taxRateRaw : 0.0);
        $taxModeRaw = $line['tax_mode'] ?? TaxMode::EXCLUSIVE->value;
        $taxModeStr = is_string($taxModeRaw) ? $taxModeRaw : (is_numeric($taxModeRaw) ? (string) $taxModeRaw : TaxMode::EXCLUSIVE->value);
        $taxMode = $tax instanceof Tax
            ? $tax->default_mode
            : TaxMode::from($taxModeStr);

        return [
            'discount_id' => $discount?->id,
            'discount_name' => $discount instanceof Discount ? $discount->name : ($discountOption === 'manual' ? (string) __('discounts.form.manual') : null),
            'discount_type' => $discountOption === 'none' ? null : $discountType->value,
            'discount_value' => number_format($discountValue, 4, '.', ''),
            'discount_rate' => $discountType === DiscountType::PERCENT ? number_format($discountValue, 4, '.', '') : '0.0000',
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'tax_id' => $tax?->id,
            'tax_name' => $tax instanceof Tax ? $tax->name : ($taxOption === 'manual' ? (string) __('taxes.form.manual') : null),
            'tax_rate' => number_format($taxRate, 4, '.', ''),
            'tax_mode' => $taxMode,
        ];
    }

    /**
     * Calculate one commercial invoice line after discount and tax.
     * Exclusive tax is added to the discounted amount; inclusive tax is
     * extracted from the discounted tax-inclusive amount.
     *
     *
     * @param  int  $quantity  The quantity value.
     * @param  string  $unitPrice  The unit price value.
     * @param  string  $discountAmount  The allocated discount amount.
     * @param  string  $taxRate  The tax rate value.
     * @param  TaxMode  $taxMode  The tax mode resource instance.
     * @return array{subtotal: numeric-string, discount: numeric-string, net: numeric-string, tax: numeric-string, total: numeric-string}
     */
    private function calculateInvoiceLine(
        int $quantity,
        string $unitPrice,
        string $discountAmount,
        string $taxRate,
        TaxMode $taxMode
    ): array {
        $subtotal = round($quantity * (float) $unitPrice, 2);
        $discount = round((float) $discountAmount, 2);
        $discountedAmount = round($subtotal - $discount, 2);

        if ($taxMode === TaxMode::INCLUSIVE) {
            $tax = (float) $taxRate > 0
                ? round($discountedAmount * (float) $taxRate / (100 + (float) $taxRate), 2)
                : 0.0;
            $net = round($discountedAmount - $tax, 2);
            $total = $discountedAmount;
        } else {
            $net = $discountedAmount;
            $tax = round($net * (float) $taxRate / 100, 2);
            $total = round($net + $tax, 2);
        }

        return [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'discount' => number_format($discount, 2, '.', ''),
            'net' => number_format($net, 2, '.', ''),
            'tax' => number_format($tax, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
        ];
    }
}
