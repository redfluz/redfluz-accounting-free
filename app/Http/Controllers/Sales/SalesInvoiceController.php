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

namespace app\Http\Controllers\Sales;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DiscountType;
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\GeneralLedgerEntryType;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Enums\Finance\TaxMode;
use App\Enums\Inventory\ProductMovementSource;
use App\Enums\Inventory\ProductMovementStatus;
use App\Enums\Sales\SalesType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SalesInvoice\BatchApproveSalesInvoiceRequest;
use App\Http\Requests\Sales\SalesInvoice\BatchCancelSalesInvoiceRequest;
use App\Http\Requests\Sales\SalesInvoice\ReverseSalesInvoiceRequest;
use App\Http\Requests\Sales\SalesInvoice\StoreSalesInvoiceCompanyRequest;
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
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductLedger;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Models\System\Setting;
use App\Support\Data\Sales\SalesInvoiceData;
use App\Support\Posting\ArLedgerData;
use App\Support\Posting\GeneralLedgerData;
use App\Support\Posting\LedgerData;
use App\Support\Posting\Posting;
use App\Traits\GeneratesDocumentNumber;
use App\Traits\HandlesInvoiceProductMovement;
use Barryvdh\DomPDF\Facade\Pdf;
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
        private readonly Posting $posting
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
            ->where('status', DocumentStatus::PENDING)
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

        // One card per payment, so the receivable side is what is listed: the
        // ledger also holds the account the money moved, and that would print
        // the same payment a second time.
        /** @var Collection<int, ArLedger> $payments */
        $payments = ArLedger::query()->againstReceivableAccount()
            ->where('type', LedgerType::PAYMENT)
            ->where('reference_number', $documentNumber)->whereNotNull('chart_code')->orderBy('date')->get();

        $isPosted = $invoice->status === DocumentStatus::POSTED;

        $indexRoute = 'sales.invoices.index';

        return view('sales.invoices.show', compact(
            'documentNumber',
            'invoice',
            'lines',
            'payments',
            'isPosted',
            'indexRoute'
        ) + [
            'canDeductInventory' => $this->canDeductInventory($documentNumber),
            'inventoryDeducted' => $this->inventoryDeducted($documentNumber),
            'deductableLines' => $this->deductableLines($documentNumber),
        ]);
    }

    /**
     * Get invoice lines that are eligible for manual inventory deduction.
     *
     * @param  string  $documentNumber  The sales invoice document number.
     * @return Collection<int, SalesInvoiceLine>
     */
    private function deductableLines(string $documentNumber): Collection
    {
        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with(['lines.product'])
            ->where('document_number', $documentNumber)
            ->first();

        if ($invoice === null || $invoice->sales_type !== SalesType::PRODUCT) {
            return new Collection;
        }

        return $invoice->lines
            ->filter(fn (SalesInvoiceLine $line): bool => $line->product_id !== null && ! $line->deduct_inventory)
            ->values();
    }

    /**
     * Determine whether this invoice still has stock that can be deducted manually.
     *
     * @param  string  $documentNumber  The sales invoice document number.
     * @return bool True when at least one eligible line still owes a deduction.
     */
    private function canDeductInventory(string $documentNumber): bool
    {
        return $this->deductableLines($documentNumber)->isNotEmpty();
    }

    /**
     * Determine whether all eligible invoice lines have been deducted.
     *
     * @param  string  $documentNumber  The sales invoice document number.
     * @return bool True when no eligible line remains to deduct.
     */
    private function inventoryDeducted(string $documentNumber): bool
    {
        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with('lines')
            ->where('document_number', $documentNumber)
            ->first();

        if ($invoice === null || $invoice->sales_type !== SalesType::PRODUCT) {
            return false;
        }

        $eligible = $invoice->lines->filter(fn (SalesInvoiceLine $line): bool => $line->product_id !== null);

        return $eligible->isNotEmpty()
            && $eligible->every(fn (SalesInvoiceLine $line): bool => (bool) $line->deduct_inventory);
    }

    /**
     * Reduce stock for selected lines of a posted sales invoice.
     *
     * @param  Request  $request  The incoming request containing selected line IDs.
     * @param  string  $documentNumber  The sales invoice document number.
     * @return RedirectResponse Redirects back to the invoice.
     *
     * @throws ValidationException|Throwable If the invoice is not eligible for a deduction.
     */
    public function deductInventory(Request $request, string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::UPDATE);

        DB::transaction(function () use ($request, $documentNumber): void {
            /** @var SalesInvoice|null $invoice */
            $invoice = SalesInvoice::query()
                ->with('lines')
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            abort_if($invoice->sales_type !== SalesType::PRODUCT, 409);

            $eligibleLines = $invoice->lines
                ->filter(fn (SalesInvoiceLine $line): bool => $line->product_id !== null && ! $line->deduct_inventory);

            abort_if($eligibleLines->isEmpty(), 409);

            $deductAll = $request->boolean('deduct_all');
            /** @var array<int, string> $selectedIds */
            $selectedIds = array_values(array_filter((array) $request->input('line_ids', []), 'is_string'));

            $lineIds = $deductAll
                ? $eligibleLines->pluck('id')->all()
                : array_values(array_intersect($selectedIds, $eligibleLines->pluck('id')->all()));

            if ($lineIds === []) {
                throw ValidationException::withMessages([
                    'line_ids' => __('invoices.deduct_inventory.select_at_least_one'),
                ]);
            }

            $this->syncPendingSalesProductMovements($documentNumber, null, $lineIds);

            SalesInvoiceLine::query()
                ->where('sales_invoice_id', $invoice->id)
                ->whereIn('id', $lineIds)
                ->update(['deduct_inventory' => true]);

            $this->postSalesProductMovements($documentNumber, $lineIds);
        });

        return redirect()->back()
            ->with('success', __('invoices.deduct_inventory.success'));
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

        // Posted invoices are historical documents and print through their own
        // view so the pending and posted designs stay separate.
        if ($invoice->status === DocumentStatus::POSTED) {
            return redirect()->route('sales.invoices.history.pdf', $documentNumber);
        }

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines;

        // Receivable-side payment rows for this invoice (receipts credit AR,
        // customer refunds debit AR and reduce net paid / increase balance due).
        /** @var Collection<int, ArLedger> $payments */
        $payments = ArLedger::query()->againstReceivableAccount()
            ->where('type', LedgerType::PAYMENT->value)
            ->where('reference_number', $documentNumber)
            ->orderBy('date')
            ->get();

        $amountPaid = $payments->reduce(
            static fn (string $carry, ArLedger $payment): string => bcadd(
                $carry,
                bcsub((string) $payment->credit_amount, (string) $payment->debit_amount, 2),
                2
            ),
            '0.00'
        );
        $amountPaid = bccomp($amountPaid, '0.00', 2) < 0 ? '0.00' : $amountPaid;

        $balanceDue = bcsub(bcadd((string) $invoice->total_amount, '0', 2), $amountPaid, 2);
        $balanceDue = bccomp($balanceDue, '0.00', 2) < 0 ? '0.00' : $balanceDue;

        $setting = Setting::query()->firstOrNew();

        return Pdf::loadView('sales.invoices.pdf', [
            'documentNumber' => $documentNumber,
            'invoice' => $invoice,
            'lines' => $lines,
            'payments' => $payments,
            'amountPaid' => $amountPaid,
            'balanceDue' => $balanceDue,
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

        $payment = $paymentEntries->firstWhere('chart_code', '!=', null) ?? $paymentEntries->first();

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
        /** @var Collection<int, ProductCategory> $categories */
        $categories = ProductCategory::query()->with('types')->orderBy('name')->get();
        /** @var Collection<int, Contact> $contacts */
        $contacts = Contact::query()->whereHas('company', fn ($query) => $query->where('is_debtor', true))->orderBy('name')->get();
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
            compact('companies', 'products', 'categories', 'contacts', 'taxes', 'discounts', 'subtype', 'indexRoute', 'deliveryOrder', 'canCreateCompany', 'cloneData')
        );
    }

    /**
     * Create a customer without leaving the Sales Invoice form.
     *
     * @param  StoreSalesInvoiceCompanyRequest  $request  The HTTP request instance.
     * @return JsonResponse Returns the new customer for the form selector.
     */
    public function storeCompany(StoreSalesInvoiceCompanyRequest $request): JsonResponse
    {
        /** @var array{name: string} $validated */
        $validated = $request->validated();
        $company = Company::query()->create([
            'name' => trim($validated['name']),
            'is_debtor' => true,
        ]);

        return response()->json(['id' => $company->id, 'name' => $company->name], 201);
    }

    /**
     * Search products for the sales invoice creation form.
     * Returns a JSON array of products matching the search query.
     *
     * @param  Request  $request  The HTTP request instance containing the search query.
     * @return JsonResponse JSON response with matching products.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::ADD);

        $search = $request->string('search')->toString();
        $categoryId = $request->string('category_id')->toString();
        $typeId = $request->string('type_id')->toString();

        $query = Product::query()
            ->with(['category', 'type']);

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%");
            });
        }

        if ($categoryId !== '') {
            $query->where('product_category_id', $categoryId);
        }

        if ($typeId !== '') {
            $query->where('product_type_id', $typeId);
        }

        $products = $query->orderBy('name')->limit(50)->get();

        return response()->json($products->map(fn (Product $product): array => [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => $product->category->name ?? '',
            'type' => $product->type->name ?? '',
            'price' => (string) $product->selling_price,
            'description' => $product->description ?? '',
            'default_discount_id' => $product->default_discount_id,
            'default_tax_id' => $product->default_tax_id,
        ]));
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
        abort_if($invoice->status === DocumentStatus::POSTED, 409);

        /** @var Collection<int, SalesInvoiceLine> $invoiceLines */
        $invoiceLines = $invoice->lines;

        /** @var Collection<int, ArLedger> $paymentEntries */
        $paymentEntries = ArLedger::query()
            ->where('type', LedgerType::PAYMENT)
            ->where('reference_number', $documentNumber)
            ->get();

        abort_if(
            FinanceLedger::query()
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
        /** @var Collection<int, ProductCategory> $categories */
        $categories = ProductCategory::query()->with('types')->orderBy('name')->get();
        /** @var Collection<int, Contact> $contacts */
        $contacts = Contact::query()->whereHas('company', fn ($query) => $query->where('is_debtor', true))->orderBy('name')->get();
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
            compact('companies', 'products', 'categories', 'contacts', 'taxes', 'discounts', 'subtype', 'indexRoute', 'documentNumber', 'deliveryOrder', 'canCreateCompany')
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

        if ($lineCommercialTerms->contains(fn (array $terms): bool => (float) $terms['tax_rate'] > 0)) {
            if ($chartSetting === null || empty($chartSetting->output_tax_chart_uuid)) {
                throw ValidationException::withMessages([
                    'tax_rate' => __('ledgers.validation.output_tax_account_required'),
                ]);
            }
        }

        $hasPayment = filled($data->amount ?? null);
        $paymentSubtype = LedgerSubType::RECEIPT->value;

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

        /** @var string|null $paymentAttachmentPath */
        $paymentAttachmentPath = $request->hasFile('payment_attachment')
            ? $this->storeUpload($request->file('payment_attachment'), 'ledger_attachments')
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
            $paymentAttachmentPath,
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
                'status' => DocumentStatus::PENDING,
                'attachment_path' => $invoiceAttachmentPath,
                'payment_transfer_type' => $data->paymentTransferType,
                'payment_attachment_path' => $paymentAttachmentPath,
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
        abort_if($invoice->status === DocumentStatus::POSTED, 409);

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

        /** @var string|null $paymentAttachmentPath */
        $paymentAttachmentPath = $request->hasFile('payment_attachment')
            ? $this->storeUpload($request->file('payment_attachment'), 'ledger_attachments')
            : $invoice->payment_attachment_path;

        DB::transaction(function () use (
            $invoice,
            $companyName,
            $invoiceAttachmentPath,
            $paymentAttachmentPath,
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

            // Update invoice
            $invoice->update([
                'company_id' => $data->companyId,
                'contact_id' => $data->contactId,
                'company_name' => $companyName,
                'date' => $data->date,
                'attachment_path' => $invoiceAttachmentPath,
                'payment_transfer_type' => $data->paymentTransferType,
                'payment_attachment_path' => $paymentAttachmentPath,
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
            abort_if($invoice->status === DocumentStatus::POSTED, 409);

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
            abort_if($invoice->isPosted(), 409);

            $this->postInvoice($invoice);

            // Stock is not moved here: it moves when the deduct-inventory button is
            // pressed or when a delivery order posts, so approving a document never
            // minuses stock by itself.
            $invoice->forceFill(['status' => DocumentStatus::POSTED]);
            $this->attributeApproved($invoice);
        });

        return redirect()
            ->route('sales.invoices.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::SALES_INVOICES));
    }

    /**
     * Reverse a posted sales invoice by creating a new reversing journal entry.
     *
     * @param  ReverseSalesInvoiceRequest  $request  The request containing the reversal reason.
     * @param  string  $documentNumber  The invoice document number.
     * @return RedirectResponse Redirects to the invoice detail page.
     *
     * @throws Throwable
     */
    public function reverse(ReverseSalesInvoiceRequest $request, string $documentNumber): RedirectResponse
    {
        /** @var array{reason: string} $validated */
        $validated = $request->validated();
        $reversalDate = now()->toDateString();

        DB::transaction(function () use ($documentNumber, $validated, $reversalDate): void {
            $invoice = SalesInvoice::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            abort_if($invoice->undone_at !== null, 409, __('journal_entries.reversal_already_exists'));

            /** @var Collection<int, SalesInvoiceLine> $lines */
            $lines = $invoice->lines()->get();

            abort_if($lines->isEmpty(), 404);

            // Load chart configuration once
            $chartConfig = ChartSetting::query()->first();
            abort_if($chartConfig === null, 500, __('posting.chart_config_missing'));

            // Load all needed charts once
            $chartIds = array_filter([
                $chartConfig->client_chart_uuid,
                $chartConfig->income_chart_uuid,
                $chartConfig->output_tax_chart_uuid,
            ]);

            /** @var Collection<string, Chart> $charts */
            $charts = Chart::query()
                ->whereIn('id', $chartIds)
                ->get()
                ->keyBy(fn (Chart $chart): string => (string) $chart->id);

            /** @var string $currencyId */
            $currencyId = Setting::query()->value('currency_id') ?? '';

            // Aggregate per invoice exactly the way postInvoice() does, one pass over
            // the stored line columns. The reversal is built from the same figures the
            // approval was, so every reversed row nets against the row it reverses.
            $grossTotal = '0.00';
            $taxTotal = '0.00';
            $discountTotal = '0.00';

            foreach ($lines as $line) {
                /** @var SalesInvoiceLine $line */
                $grossTotal = bcadd($grossTotal, $line->subtotal_amount ?? '0.00', 2);
                $taxTotal = bcadd($taxTotal, $line->tax_amount ?? '0.00', 2);
                $discountTotal = bcadd($discountTotal, $line->discount_amount ?? '0.00', 2);
            }

            $invoiceTotal = $chartConfig->is_tax_enabled
                ? bcsub(bcadd($grossTotal, $taxTotal, 2), $discountTotal, 2)
                : bcsub($grossTotal, $discountTotal, 2);

            // Build GL reversal DTOs with swapped amounts (CR→DR, DR→CR)
            /** @var list<GeneralLedgerData> $glDtos */
            $glDtos = [];
            $firstLine = $lines->first();

            // CR Accounts Receivable (reverses the DR from approve)
            if ($chartConfig->client_chart_uuid !== null) {
                $receivableChart = $charts->get($chartConfig->client_chart_uuid);
                $glDtos[] = GeneralLedgerData::fromSalesInvoiceLine(
                    $invoice,
                    $firstLine,
                    $chartConfig,
                    GeneralLedgerEntryType::RECEIVABLE,
                    $receivableChart,
                    '0.00',
                    $invoiceTotal
                );
            }

            // DR Sales Revenue (reverses the CR from approve)
            if ($chartConfig->income_chart_uuid !== null && bccomp($grossTotal, '0.00', 2) !== 0) {
                $revenueChart = $charts->get($chartConfig->income_chart_uuid);
                $glDtos[] = GeneralLedgerData::fromSalesInvoiceLine(
                    $invoice,
                    $firstLine,
                    $chartConfig,
                    GeneralLedgerEntryType::REVENUE,
                    $revenueChart,
                    $grossTotal,
                    '0.00'
                );
            }

            // DR Tax Payable - only while GST/VAT is enabled, because that is the
            // only state in which approve() credited it
            if ($chartConfig->is_tax_enabled && $chartConfig->output_tax_chart_uuid !== null && bccomp($taxTotal, '0.00', 2) !== 0) {
                $taxChart = $charts->get($chartConfig->output_tax_chart_uuid);
                $glDtos[] = GeneralLedgerData::fromSalesInvoiceLine(
                    $invoice,
                    $firstLine,
                    $chartConfig,
                    GeneralLedgerEntryType::TAX,
                    $taxChart,
                    $taxTotal,
                    '0.00'
                );
            }

            // CR Sales Discounts (reverses the DR from approve)
            $discountChart = $this->getDiscountChart();
            if ($discountChart instanceof Chart && bccomp($discountTotal, '0.00', 2) !== 0) {
                $glDtos[] = GeneralLedgerData::fromSalesInvoiceLine(
                    $invoice,
                    $firstLine,
                    $chartConfig,
                    GeneralLedgerEntryType::DISCOUNT,
                    $discountChart,
                    '0.00',
                    $discountTotal
                );
            }

            // Calculate totals for the reversal journal entry. The tax is only part of
            // the debits while GST/VAT is enabled, which is when its row is written.
            $totalDebit = $chartConfig->is_tax_enabled
                ? bcadd($grossTotal, $taxTotal, 2)
                : $grossTotal;
            $totalCredit = bcadd($invoiceTotal, $discountTotal, 2);

            // Create the reversal journal entry header
            $reversalEntry = JournalEntry::query()->create([
                'document_number' => $this->generateDocumentNumber(LedgerSubType::JOURNAL),
                'reference_number' => $documentNumber,
                'document_original' => $documentNumber,
                'date' => $reversalDate,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => DocumentStatus::POSTED,
                'notes' => $validated['reason'],
            ]);

            $now = now();

            // Build JournalEntryChart rows from DTOs
            $chartRows = array_map(
                fn (GeneralLedgerData $dto): array => [
                    'id' => (string) Str::uuid7(),
                    'journal_entry_id' => $reversalEntry->id,
                    'chart_id' => $dto->chartId,
                    'company_id' => $dto->companyId,
                    'currency_id' => $dto->currencyId,
                    'type' => LedgerType::JOURNAL->value,
                    'subtype' => LedgerSubType::JOURNAL->value,
                    'document_number' => $dto->documentNumber,
                    'reference_number' => $dto->referenceNumber,
                    'date' => $dto->date,
                    'chart_code' => $dto->chartCode,
                    'chart_name' => $dto->chartName,
                    'company_name' => $dto->companyName,
                    'description' => null,
                    'line_description' => __('journal_entries.reversal_line_description', [
                        'document_number' => $documentNumber,
                        'description' => $dto->lineDescription ?? $dto->description ?? '-',
                    ]),
                    'debit_amount' => $dto->debitAmount,
                    'credit_amount' => $dto->creditAmount,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ],
                $glDtos,
            );

            JournalEntryChart::query()->insert($chartRows);

            // Post reversal to general ledger via Posting class
            $this->posting->generalLedger(array_map(
                fn (GeneralLedgerData $dto): LedgerData => new LedgerData(
                    id: (string) Str::uuid7(),
                    chartId: $dto->chartId,
                    companyId: $dto->companyId,
                    currencyId: $dto->currencyId,
                    type: LedgerType::JOURNAL,
                    subtype: LedgerSubType::JOURNAL,
                    documentNumber: $dto->documentNumber ?? $documentNumber,
                    referenceNumber: $dto->referenceNumber,
                    date: $dto->date,
                    chartCode: $dto->chartCode,
                    chartName: $dto->chartName,
                    companyName: $dto->companyName,
                    description: $dto->description,
                    lineDescription: $dto->lineDescription,
                    debitAmount: $dto->debitAmount,
                    creditAmount: $dto->creditAmount,
                    createdAt: $now,
                    updatedAt: $now,
                ),
                $glDtos,
            ));

            // Reverse AR ledger: create new rows with swapped amounts (ledgers are immutable)
            /** @var Collection<int, ArLedger> $arEntries */
            $arEntries = ArLedger::query()
                ->where('document_number', $documentNumber)
                ->get();

            if ($arEntries->isNotEmpty()) {
                /** @var list<ArLedgerData> $arReversalDtos */
                $arReversalDtos = $arEntries
                    ->map(function (ArLedger $ar) use ($reversalEntry, $documentNumber, $currencyId, $now): ArLedgerData {
                        return new ArLedgerData(
                            id: (string) Str::uuid7(),
                            chartId: $ar->chart_id,
                            companyId: $ar->company_id,
                            currencyId: $currencyId,
                            type: LedgerType::JOURNAL->value,
                            subtype: LedgerSubType::JOURNAL->value,
                            documentNumber: $reversalEntry->document_number,
                            date: $reversalEntry->date->format('Y-m-d'),
                            referenceNumber: $documentNumber,
                            chartCode: $ar->chart_code,
                            chartName: $ar->chart_name,
                            chartDescription: $ar->chart_description,
                            companyName: $ar->company_name,
                            description: __('journal_entries.reversal_line_description', [
                                'document_number' => $ar->document_number,
                                'description' => $ar->description ?? '-',
                            ]),
                            debitAmount: (string) $ar->credit_amount,
                            creditAmount: (string) $ar->debit_amount,
                            createdAt: $now,
                            updatedAt: $now,
                            deletedAt: null,
                        );
                    })
                    ->values()
                    ->all();

                $this->posting->accountReceivableLedger($arReversalDtos);
            }

            $this->attributeUndone($invoice);
        });

        return redirect()
            ->route('sales.invoices.show', $documentNumber)
            ->with('success', __('journal_entries.reversal_success'));
    }

    /**
     * Approve multiple sales invoices and post them to the general ledger.
     *
     * @param  BatchApproveSalesInvoiceRequest  $request  The request containing document numbers to approve.
     * @return RedirectResponse Redirects to the sales invoices history page.
     *
     * @throws Throwable
     */
    public function batchApprove(BatchApproveSalesInvoiceRequest $request): RedirectResponse
    {
        /** @var array{document_numbers: array<int, string>} $validated */
        $validated = $request->validated();

        $count = 0;

        DB::transaction(function () use ($validated, &$count): void {
            /** @var array<int, string> $documentNumbers */
            $documentNumbers = $validated['document_numbers'];
            foreach ($documentNumbers as $documentNumber) {

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

                if ($invoice->status === DocumentStatus::POSTED) {
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

                $this->postInvoice($invoice);

                $invoice->forceFill(['status' => DocumentStatus::POSTED]);
                $this->attributeApproved($invoice);

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
     * @param  BatchCancelSalesInvoiceRequest  $request  The request containing document numbers to cancel.
     * @return RedirectResponse Redirects to the sales invoices index page.
     */
    public function batchCancel(BatchCancelSalesInvoiceRequest $request): RedirectResponse
    {
        /** @var array{document_numbers: array<int, string>} $validated */
        $validated = $request->validated();

        $affected = SalesInvoice::query()
            ->whereIn('document_number', $validated['document_numbers'])
            ->where('status', DocumentStatus::PENDING)
            ->update(['status' => DocumentStatus::CANCELLED]);

        return redirect()
            ->route('sales.invoices.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $affected]));
    }

    /**
     * Post one sales invoice to its receivable sub-ledger and the general ledger.
     *
     * Every entry is written to both ledgers from the same figure and against the
     * same account, so the general ledger holds exactly what the receivable ledger
     * holds: the revenue row reads as revenue, the tax row as tax, the discount row
     * as discount, and only the net debt row reads as the customer's receivable.
     *
     * @param  SalesInvoice  $invoice  The invoice being posted.
     *
     * @throws Throwable
     */
    private function postInvoice(SalesInvoice $invoice): void
    {
        /** @var list<GeneralLedgerData> $glDtos */
        $glDtos = [];

        /** @var list<ArLedgerData> $arDtos */
        $arDtos = [];

        // Aggregate per invoice - one pass, no per-line rows
        $grossTotal = '0.00';
        $taxTotal = '0.00';
        $discountTotal = '0.00';

        [$chartConfig, $clientChart, $incomeChart, $outputTaxChart, $currencyId] = $this->getSalesInvoiceConfig();

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines;
        abort_if($lines->isEmpty(), 404);

        foreach ($lines as $line) {
            /** @var SalesInvoiceLine $line */
            $grossTotal = bcadd($grossTotal, $line->subtotal_amount ?? '0.00', 2);
            $taxTotal = bcadd($taxTotal, $line->tax_amount ?? '0.00', 2);
            $discountTotal = bcadd($discountTotal, $line->discount_amount ?? '0.00', 2);
        }

        $invoiceTotal = $chartConfig->is_tax_enabled
            ? bcsub(bcadd($grossTotal, $taxTotal, 2), $discountTotal, 2)
            : bcsub($grossTotal, $discountTotal, 2);

        // $firstLine is only for the DTO's line reference - amounts are invoice-level
        /** @var SalesInvoiceLine $firstLine */
        $firstLine = $lines->first();

        // CR Revenue
        if ($incomeChart instanceof Chart && bccomp($grossTotal, '0.00', 2) !== 0) {
            $glDtos[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $firstLine,
                $chartConfig,
                GeneralLedgerEntryType::REVENUE,
                $incomeChart,
                '0.00',
                $grossTotal
            );
            $arDtos[] = ArLedgerData::fromSalesInvoiceLine(
                $invoice,
                $firstLine,
                $incomeChart,
                $currencyId,
                '0.00',
                $grossTotal
            );
        }

        // CR Tax - only while GST/VAT is enabled
        if ($chartConfig->is_tax_enabled && $outputTaxChart instanceof Chart && bccomp($taxTotal, '0.00', 2) !== 0) {
            $glDtos[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $firstLine,
                $chartConfig,
                GeneralLedgerEntryType::TAX,
                $outputTaxChart,
                '0.00',
                $taxTotal
            );
            $arDtos[] = ArLedgerData::fromSalesInvoiceLine(
                $invoice,
                $firstLine,
                $outputTaxChart,
                $currencyId,
                '0.00',
                $taxTotal
            );
        }

        // DR Discount
        $discountChart = $this->getDiscountChart();
        if ($discountChart instanceof Chart && bccomp($discountTotal, '0.00', 2) !== 0) {
            $glDtos[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $firstLine,
                $chartConfig,
                GeneralLedgerEntryType::DISCOUNT,
                $discountChart,
                $discountTotal,
                '0.00'
            );
            $arDtos[] = ArLedgerData::fromSalesInvoiceLine(
                $invoice,
                $firstLine,
                $discountChart,
                $currencyId,
                $discountTotal,
                '0.00'
            );
        }

        // DR Receivable = invoice total
        if (bccomp($invoiceTotal, '0.00', 2) !== 0) {
            $glDtos[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $firstLine,
                $chartConfig,
                GeneralLedgerEntryType::RECEIVABLE,
                $clientChart,
                $invoiceTotal,
                '0.00'
            );
            $arDtos[] = ArLedgerData::fromSalesInvoiceLine(
                $invoice,
                $firstLine,
                $clientChart,
                $currencyId,
                $invoiceTotal,
                '0.00'
            );
        }

        if (! empty($arDtos)) {
            $this->posting->accountReceivableLedger($arDtos);
        }
        if (! empty($glDtos)) {
            $this->posting->generalLedger($glDtos);
        }
    }

    /**
     * The configuration a sales invoice posts against.
     *
     * The chart accounts and the posting currency are read here once, so posting
     * only ever sees the accounts and the currency it writes. A missing
     * configuration or a missing customer account is a setup failure, not a
     * posting with a blank receivable, so both stop the request.
     *
     * @return array{0: ChartSetting, 1: Chart, 2: Chart|null, 3: Chart|null, 4: string} The chart configuration, the client, income and output tax accounts, then the currency id.
     */
    private function getSalesInvoiceConfig(): array
    {
        /** @var ChartSetting|null $chartConfig */
        $chartConfig = ChartSetting::query()->first();
        abort_if($chartConfig === null, 500, __('posting.chart_config_missing'));

        $chartIds = array_filter([
            $chartConfig->client_chart_uuid,
            $chartConfig->income_chart_uuid,
            $chartConfig->output_tax_chart_uuid,
        ]);

        /** @var Collection<string, Chart> $charts */
        $charts = Chart::query()
            ->whereIn('id', $chartIds)
            ->get()
            ->keyBy(fn (Chart $chart): string => (string) $chart->id);

        /** @var Chart|null $clientChart */
        $clientChart = $charts->get((string) $chartConfig->client_chart_uuid);
        abort_if(! $clientChart instanceof Chart, 500, __('posting.chart_config_missing'));

        /** @var Chart|null $incomeChart */
        $incomeChart = $charts->get((string) $chartConfig->income_chart_uuid);

        /** @var Chart|null $outputTaxChart */
        $outputTaxChart = $charts->get((string) $chartConfig->output_tax_chart_uuid);

        /** @var string $currencyId */
        $currencyId = Setting::query()->value('currency_id') ?? '';

        return [$chartConfig, $clientChart, $incomeChart, $outputTaxChart, $currencyId];
    }

    /**
     * The account a sales discount is charged to.
     *
     * The discount is a contra-income, so the account chosen in the chart
     * settings comes from the income block of the chart of accounts. When none is
     * configured there is no account to debit, and the caller leaves the discount
     * row out rather than writing a debit against nothing.
     *
     * @return Chart|null The discount account, or null when chart settings holds none.
     */
    private function getDiscountChart(): ?Chart
    {
        /** @var Chart|null $discountChart */
        $discountChart = ChartSetting::query()->first()?->discountChart;

        return $discountChart;
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
        if (
            $discount instanceof Discount
            && (($discount->starts_at !== null && $invoiceDate < $discount->starts_at->format('Y-m-d'))
                || ($discount->ends_at !== null && $invoiceDate > $discount->ends_at->format('Y-m-d')))
        ) {
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
