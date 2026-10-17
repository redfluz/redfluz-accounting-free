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
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Enums\PurchaseOrderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseInvoice\StorePurchaseInvoiceRequest;
use App\Http\Requests\Purchase\PurchaseInvoice\UpdatePurchaseInvoiceRequest;
use App\Models\Bank\PaymentVoucher;
use App\Models\Bank\PaymentVoucherChart;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryChart;
use App\Models\Finance\Ledger;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Models\Purchase\PurchaseOrder;
use App\Models\System\Setting;
use App\Support\Data\Purchase\PurchaseInvoiceData;
use App\Support\Posting\Ledger as LedgerPoster;
use App\Support\Posting\Payable;
use App\Support\Posting\PaymentLedgerPoster;
use App\Support\Posting\PostingLeg;
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
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class PurchaseInvoiceController
 *
 * Creates fixed-row purchase invoices, posting each completed line
 * as a balanced debit and credit ledger pair.
 */
class PurchaseInvoiceController extends Controller
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
        private readonly Payable $payable,
        private readonly LedgerPoster $ledger,
        // The on-account payment raised with an invoice is a payment voucher, so
        // it is posted by the payment poster rather than the invoice one.
        private readonly PaymentLedgerPoster $paymentPoster,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $subtype = LedgerSubType::PURCHASE_INVOICE;
        $indexRoute = 'purchase.invoices.index';
        $createRoute = 'purchase.invoices.create';
        $this->allows(FileResources::PURCHASE_INVOICES);
        $search = $request->string('search')->value();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();

        $entries = PurchaseInvoice::query()
            ->where('status', LedgerStatus::PENDING)
            ->addSelect([
                'paid_amount' => PaymentVoucher::query()
                    ->selectRaw('COALESCE(SUM(total_amount), 0)')
                    ->whereColumn('reference_number', 'purchase_invoices.document_number')
                    ->where('status', '!=', 'cancelled'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhereHas('lines.product', fn ($productQuery) => $productQuery->where('name', 'like', "%$search%"));
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        $entries->getCollection()->each(function (PurchaseInvoice $entry): void {
            $entry->setAttribute('is_posted', $entry->status === LedgerStatus::POSTED);
        });

        return view('purchase.invoices.index', compact(
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
     * @param  string  $documentNumber  The invoice number value.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $invoice = PurchaseInvoice::query()
            ->with(['company', 'contact', 'lines.product'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);

        /** @var Collection<int, PurchaseInvoiceLine> $lines */
        $lines = $invoice->lines;

        /** @var Collection<int, PaymentVoucher> $payments */
        $payments = PaymentVoucher::query()
            ->with('lines')
            ->where('reference_number', $documentNumber)
            ->orderBy('date')
            ->get();

        $isPosted = $invoice->isPosted();

        $documentType = LedgerSubType::PURCHASE_INVOICE->value;
        $indexRoute = 'purchase.invoices.index';

        return view('purchase.invoices.show', compact(
            'documentNumber',
            'invoice',
            'lines',
            'payments',
            'isPosted',
            'indexRoute'
        ) + ['postingDateMin' => null, 'postingDateMax' => null]);
    }

    /**
     * Stream a real invoice document using the selected or default PDF design.
     *
     * @param  string  $documentNumber  The invoice number.
     * @return Response The streamed invoice PDF.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $invoice = PurchaseInvoice::query()
            ->with(['company', 'contact', 'lines.product'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);

        /** @var Collection<int, PurchaseInvoiceLine> $lines */
        $lines = $invoice->lines;

        $design = null;

        $setting = Setting::query()->first();

        return Pdf::loadView('purchase.invoices.pdf', [
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
        $this->allows(FileResources::PURCHASE_INVOICES);

        // The on-account payment is a payment voucher, so its printable document
        // is read from there rather than from wherever it was once stored.
        $payment = PaymentVoucher::query()
            ->with(['company', 'lines'])
            ->where('document_number', $paymentDocumentNumber)
            ->first();

        abort_if($payment === null, 404);

        $paymentEntries = $payment->lines;

        return Pdf::loadView('purchase.invoices.payment-pdf', [
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
        $subtype = LedgerSubType::PURCHASE_INVOICE;
        $indexRoute = 'purchase.invoices.index';
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::ADD);
        $canQuickCreateCompany = auth()->user()?->hasFilePermission(FileResources::COMPANIES, FileAction::ADD) ?? false;

        /** @var Collection<int, Company> $companies */
        $companies = Company::query()->where('is_creditor', true)->orderBy('name')->get();
        /** @var Collection<int, Contact> $contacts */
        $contacts = Contact::query()->whereHas('company', fn ($query) => $query->where('is_creditor', true))->orderBy('name')->get();
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->with('chartType')->orderBy('code')->get();
        $sourcePurchaseOrder = null;

        $cloneData = session('clone_data');
        if ($cloneData) {
            session()->forget('clone_data');
        }

        return view(
            'purchase.invoices.create',
            compact('companies', 'contacts', 'charts', 'subtype', 'indexRoute', 'sourcePurchaseOrder', 'canQuickCreateCompany', 'cloneData')
            + ['postingDateMin' => null, 'postingDateMax' => null]
        );

    }

    /**
     * Create a supplier without leaving the Purchase Invoice form.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return JsonResponse Returns the new supplier for the form selector.
     */
    public function storeCompany(Request $request): JsonResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::ADD);
        /** @var array{name: string} $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $company = Company::query()->create([
            'name' => trim($validated['name']),
            'is_creditor' => true,
        ]);

        return response()->json(['id' => $company->id, 'name' => $company->name], 201);
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  string  $documentNumber  The invoice number.
     * @return View Returns the rendered Blade view.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::UPDATE);
        $canQuickCreateCompany = auth()->user()?->hasFilePermission(FileResources::COMPANIES, FileAction::ADD) ?? false;

        $invoice = PurchaseInvoice::query()
            ->with(['lines'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);

        abort_if($invoice->isPosted(), 409);

        /** @var Collection<int, PurchaseInvoiceLine> $invoiceLines */
        $invoiceLines = $invoice->lines;

        /** @var Collection<int, PaymentVoucher> $paymentEntries */
        $paymentEntries = PaymentVoucher::query()
            ->with('lines')
            ->where('reference_number', $documentNumber)
            ->get();

        abort_if(
            Ledger::query()
                ->withTrashed()
                ->whereIn('document_number', $paymentEntries->pluck('document_number'))
                ->exists(),
            409
        );

        /** @var PaymentVoucher|null $paymentDebit */
        $paymentDebit = $paymentEntries->firstWhere('status', LedgerStatus::POSTED);
        /** @var PaymentVoucher|null $paymentCredit */
        $paymentCredit = $paymentDebit;

        session()->flashInput([
            'date' => $invoice->date->format('Y-m-d'),
            'purchase_type' => ($invoice->purchase_type ?? PurchaseOrderType::BILL)->value,
            'credit_term_days' => $invoice->due_date === null
                ? 30
                : $invoice->date->diffInDays($invoice->due_date),
            'company_id' => $invoice->company_id,
            'contact_id' => $invoice->contact_id,
            'invoice_notes' => $invoice->notes,
            'amount' => $paymentDebit->total_amount ?? $paymentCredit?->total_amount,
            'payment_notes' => $paymentDebit->notes ?? $paymentCredit?->notes,
            'lines' => $invoiceLines
                ->values()
                ->map(fn (PurchaseInvoiceLine $line): array => [
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_amount' => $line->discount_amount,
                    'tax_amount' => $line->tax_amount,
                ])
                ->all(),
        ]);

        $subtype = LedgerSubType::PURCHASE_INVOICE;
        $indexRoute = 'purchase.invoices.index';
        /** @var Collection<int, Company> $companies */
        $companies = Company::query()->where('is_creditor', true)->orderBy('name')->get();
        /** @var Collection<int, Contact> $contacts */
        $contacts = Contact::query()->whereHas('company', fn ($query) => $query->where('is_creditor', true))->orderBy('name')->get();
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->with('chartType')->orderBy('code')->get();
        $sourcePurchaseOrder = $invoice->purchase_order_id !== null
            ? PurchaseOrder::query()->whereKey($invoice->purchase_order_id)->first()
            : null;

        return view(
            'purchase.invoices.edit',
            compact('companies', 'contacts', 'charts', 'subtype', 'indexRoute', 'documentNumber', 'sourcePurchaseOrder', 'canQuickCreateCompany')
            + ['postingDateMin' => null, 'postingDateMax' => null]
        );
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StorePurchaseInvoiceRequest  $request  The validated request containing invoice data.
     * @return RedirectResponse Returns a redirect response.
     *
     * @throws Throwable
     */
    public function store(StorePurchaseInvoiceRequest $request): RedirectResponse
    {
        $data = PurchaseInvoiceData::from($request->validated());

        /** @var array<int, array<string, mixed>> $submittedLines */
        $submittedLines = $request->validated('lines', []);
        $lines = collect($submittedLines)
            ->filter(fn (array $line): bool => filled($line['description'] ?? null)
                || filled($line['quantity'] ?? null)
                || filled($line['unit_price'] ?? null));

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('ledgers.validation.invoice_line_required')]);
        }

        foreach ($lines as $lineNumber => $line) {
            if (! filled($line['description'] ?? null)) {
                throw ValidationException::withMessages(["lines.$lineNumber.description" => __('ledgers.validation.invoice_line_description_required')]);
            }

            if (! filled($line['quantity'] ?? null)) {
                throw ValidationException::withMessages(["lines.$lineNumber.quantity" => __('ledgers.validation.invoice_line_quantity_required')]);
            }

            if (! array_key_exists('unit_price', $line) || $line['unit_price'] === null || $line['unit_price'] === '') {
                throw ValidationException::withMessages(["lines.$lineNumber.unit_price" => __('ledgers.validation.invoice_line_unit_price_required')]);
            }
        }

        $chartSetting = ChartSetting::query()->first();
        $lineCommercialTerms = $lines->map(
            fn (array $line, int $lineNumber): array => $this->resolveLineCommercialTerms($line, $lineNumber)
        );
        $debitAccountId = $request->debitId() ?? $chartSetting?->cogs_chart_uuid;
        $creditAccountId = $request->creditId() ?? $chartSetting?->vendor_chart_uuid;

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

        if ($lineCommercialTerms->contains(fn (array $terms): bool => (float) $terms['tax_amount'] > 0)) {
            $taxAccountId = $chartSetting?->input_tax_chart_uuid;

            if (empty($taxAccountId)) {
                throw ValidationException::withMessages([
                    'tax_rate' => __('ledgers.validation.input_tax_account_required'),
                ]);
            }

            $taxAccount = $this->taxAccountSnapshot($taxAccountId);
        }

        $hasPayment = filled($request->amount());
        $paymentSubtype = LedgerSubType::PAYMENT_VOUCHER->value;
        $paymentDebitAccountId = null;
        $paymentCreditAccountId = null;
        $paymentDebitAccount = null;
        $paymentCreditAccount = null;

        if ($hasPayment) {
            $paymentDebitAccountId = $request->paymentDebitId()
                ?? $chartSetting?->vendor_chart_uuid;
            $paymentCreditAccountId = $request->paymentCreditId()
                ?? $chartSetting?->bank_chart_uuid;

            if (empty($paymentDebitAccountId) || empty($paymentCreditAccountId)) {
                throw ValidationException::withMessages([
                    'payment_debit_id' => __('ledgers.validation.debit_account_or_default_required'),
                    'payment_credit_id' => __('ledgers.validation.credit_account_or_default_required'),
                ]);
            }

            $paymentDebitAccount = $this->accountSnapshot($paymentDebitAccountId, 'payment_debit_id');
            $paymentCreditAccount = $this->accountSnapshot($paymentCreditAccountId, 'payment_credit_id');
        }

        $companyName = filled($request->companyId())
            ? Company::query()->whereKey($request->companyId())->value('name')
            : null;

        /** @var string|null $invoiceAttachmentPath */
        $invoiceAttachmentPath = $request->hasFile('invoice_attachment')
            ? $this->storeUpload($request->file('invoice_attachment'), 'ledger_attachments')
            : null;
        $docNumber = $this->generateDocumentNumber(LedgerSubType::PURCHASE_INVOICE);
        $invoiceReferenceNumber = $docNumber;
        $invoiceDueDate = Carbon::parse($request->dateValue())
            ->addDays($request->creditTermDays())
            ->toDateString();
        $paymentDocumentNumber = $hasPayment
            ? (
                $this->generateDocumentNumber(LedgerSubType::from($paymentSubtype))
            )
            : null;
        $paymentAmount = $hasPayment
            ? number_format($request->amount(), 2, '.', '')
            : null;

        DB::transaction(function () use (

            $companyName,
            $data,
            $docNumber,
            $hasPayment,
            $invoiceAttachmentPath,
            $invoiceDueDate,
            $invoiceReferenceNumber,
            $lines,
            $paymentAmount,
            $paymentCreditAccount,
            $paymentCreditAccountId,
            $paymentDebitAccount,
            $paymentDebitAccountId,
            $paymentDocumentNumber,
            $lineCommercialTerms,
            $taxAccount

        ): void {

            $now = now();
            $invoiceDate = $data->date?->format('Y-m-d');
            $purchaseTypeValue = $data->purchaseType?->value;

            $invoice = new PurchaseInvoice;
            $invoice->forceFill([
                'company_id' => $data->companyId,
                'contact_id' => $data->contactId,
                'purchase_type' => $purchaseTypeValue,
                'status' => LedgerStatus::PENDING->value,
                'document_number' => $docNumber,
                'reference_number' => $invoiceReferenceNumber,
                'company_name' => $companyName,
                'date' => $invoiceDate,
                'due_date' => $invoiceDueDate,
                'attachment_path' => $invoiceAttachmentPath,
                'notes' => $data->invoiceNotes,
            ]);
            $invoice->save();

            $lineRows = [];
            $invoiceSubtotal = '0.00';
            $invoiceTotal = '0.00';

            foreach ($lines as $lineNumber => $line) {
                $terms = $lineCommercialTerms->get($lineNumber);
                if ($terms === null) {
                    throw new LogicException('Invoice line commercial terms are missing.');
                }
                $amounts = $this->calculateInvoiceLine(
                    is_numeric($line['quantity'] ?? 0) ? (int) ($line['quantity'] ?? 0) : 0,
                    is_numeric($line['unit_price'] ?? null) ? (string) $line['unit_price'] : '',
                    $terms['discount_amount'],
                    $terms['tax_amount']
                );

                $lineRows[] = [
                    'id' => (string) Str::uuid7(),
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => null,
                    'product_name' => null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal_amount' => $amounts['subtotal'],
                    'discount_value' => $terms['discount_amount'],
                    'discount_amount' => $amounts['discount'],
                    'tax_amount' => $amounts['tax'],
                    'line_total_amount' => $amounts['total'],
                    'total_amount' => $amounts['total'],
                    'description' => $line['description'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $invoiceSubtotal = bcadd($invoiceSubtotal, $amounts['subtotal'], 2);
                $invoiceTotal = bcadd($invoiceTotal, $amounts['total'], 2);

                if ((float) $amounts['tax'] > 0) {
                    if ($taxAccount === null) {
                        throw ValidationException::withMessages([
                            'tax_rate' => __('ledgers.validation.input_tax_account_required'),
                        ]);
                    }
                    $lineRows[] = [
                        'id' => (string) Str::uuid7(),
                        'purchase_invoice_id' => $invoice->id,
                        'product_id' => null,
                        'product_name' => null,
                        'quantity' => 1,
                        'unit_price' => $amounts['tax'],
                        'subtotal_amount' => '0.00',
                        'discount_value' => '0.0000',
                        'discount_amount' => '0.00',
                        'tax_amount' => '0.00',
                        'line_total_amount' => $amounts['tax'],
                        'total_amount' => $amounts['tax'],
                        'description' => __('ledgers.tax_line_description'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $invoiceTotal = bcadd($invoiceTotal, $amounts['tax'], 2);
                }
            }

            PurchaseInvoiceLine::query()->insert($lineRows);

            $invoice->forceFill([
                'subtotal' => $invoiceSubtotal,
                'total_amount' => $invoiceTotal,
            ]);
            $invoice->save();

            if ($hasPayment) {
                // The on-account payment raised with a purchase invoice is a
                // payment voucher, so it is stored as one rather than as rows
                // borrowed from wherever the invoice's own rows live. It carries
                // its own number and posts on its own approval, exactly as a
                // voucher raised on its own would.
                $voucher = PaymentVoucher::query()->create([
                    'document_number' => $paymentDocumentNumber,
                    'company_id' => $data->companyId,
                    'company_name' => $companyName,
                    'contact_id' => $data->contactId,
                    'date' => $invoiceDate,
                    'status' => LedgerStatus::PENDING,
                    'payment_transfer_type' => null,
                    'reference_number' => $docNumber,
                    'attachment_path' => null,
                    'notes' => $data->paymentNotes,
                    'total_amount' => $paymentAmount,
                ]);

                $voucher->lines()->create([
                    'amount' => $paymentAmount,
                    'description' => $data->paymentNotes,
                ]);

                $voucher->lines()->create([
                    'amount' => $paymentAmount,
                    'description' => $data->paymentNotes,
                ]);

                $voucher->charts()->create([
                    'debit_id' => $paymentDebitAccountId,
                    'debit_acc' => $paymentDebitAccount['code'],
                    'debit_acc_desc' => $paymentDebitAccount['description'],
                    'document_number' => $voucher->document_number,
                    'reference_number' => $voucher->reference_number,
                    'date' => $voucher->date->format('Y-m-d'),
                    'description' => $data->paymentNotes,
                    'debit_amount' => $paymentAmount,
                    'credit_amount' => '0.00',
                    'total_amount' => $paymentAmount,
                ]);

                $voucher->charts()->create([
                    'credit_id' => $paymentCreditAccountId,
                    'credit_acc' => $paymentCreditAccount['code'],
                    'credit_acc_desc' => $paymentCreditAccount['description'],
                    'document_number' => $voucher->document_number,
                    'reference_number' => $voucher->reference_number,
                    'date' => $voucher->date->format('Y-m-d'),
                    'description' => $data->paymentNotes,
                    'debit_amount' => '0.00',
                    'credit_amount' => $paymentAmount,
                    'total_amount' => $paymentAmount,
                ]);
            }

        });

        return redirect()
            ->route('purchase.invoices.show', $docNumber)
            ->with('success', FlashMessage::created(FileResources::PURCHASE_INVOICES));

    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdatePurchaseInvoiceRequest  $request  The validated request containing invoice data.
     * @param  string  $documentNumber  The invoice number.
     * @return RedirectResponse Returns a redirect response.
     *
     * @throws Throwable
     */
    public function update(UpdatePurchaseInvoiceRequest $request, string $documentNumber): RedirectResponse
    {
        $data = PurchaseInvoiceData::from($request->validated());

        $invoice = PurchaseInvoice::query()
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);

        /** @var Collection<int, PaymentVoucher> $existingPaymentEntries */
        $existingPaymentEntries = PaymentVoucher::query()
            ->with('lines')
            ->where('reference_number', $documentNumber)
            ->get();

        abort_if($invoice->isPosted(), 409);
        abort_if(
            Ledger::query()->withTrashed()
                ->whereIn('document_number', $existingPaymentEntries->pluck('document_number'))
                ->exists(),
            409
        );

        $existingPaymentEntry = $existingPaymentEntries->first();

        $sourcePurchaseOrder = $invoice->purchase_order_id !== null
            ? PurchaseOrder::query()->whereKey($invoice->purchase_order_id)->first()
            : null;
        if ($sourcePurchaseOrder !== null
            && $request->companyId() !== $sourcePurchaseOrder->company_id) {
            throw ValidationException::withMessages([
                'company_id' => __('purchase_orders.validation.generated_invoice_identity'),
            ]);
        }

        /** @var array<int, array<string, mixed>> $submittedLines */
        $submittedLines = $request->validated('lines');
        $lines = collect($submittedLines)
            ->filter(fn (array $line): bool => filled($line['description'] ?? null)
                || filled($line['quantity'] ?? null)
                || filled($line['unit_price'] ?? null));

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('ledgers.validation.invoice_line_required')]);
        }

        foreach ($lines as $lineNumber => $line) {
            if (! filled($line['description'] ?? null)) {
                throw ValidationException::withMessages(["lines.$lineNumber.description" => __('ledgers.validation.invoice_line_description_required')]);
            }

            if (! filled($line['quantity'] ?? null)) {
                throw ValidationException::withMessages(["lines.$lineNumber.quantity" => __('ledgers.validation.invoice_line_quantity_required')]);
            }

            if (! array_key_exists('unit_price', $line) || $line['unit_price'] === null || $line['unit_price'] === '') {
                throw ValidationException::withMessages(["lines.$lineNumber.unit_price" => __('ledgers.validation.invoice_line_unit_price_required')]);
            }
        }

        $chartSetting = ChartSetting::query()->first();
        $lineCommercialTerms = $lines->map(
            fn (array $line, int $lineNumber): array => $this->resolveLineCommercialTerms($line, $lineNumber)
        );
        $debitAccountId = $request->debitId() ?? $chartSetting?->cogs_chart_uuid;
        $creditAccountId = $request->creditId() ?? $chartSetting?->vendor_chart_uuid;

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

        if ($lineCommercialTerms->contains(fn (array $terms): bool => (float) $terms['tax_amount'] > 0)) {
            $taxAccountId = $chartSetting?->input_tax_chart_uuid;

            if (empty($taxAccountId)) {
                throw ValidationException::withMessages([
                    'tax_rate' => __('ledgers.validation.input_tax_account_required'),
                ]);
            }

            $taxAccount = $this->taxAccountSnapshot($taxAccountId);
        }

        $hasPayment = filled($request->amount());
        $paymentSubtype = LedgerSubType::PAYMENT_VOUCHER->value;
        $paymentDebitAccountId = null;
        $paymentCreditAccountId = null;
        $paymentDebitAccount = null;
        $paymentCreditAccount = null;

        if ($hasPayment) {
            $paymentDebitAccountId = $request->paymentDebitId()
                ?? $chartSetting?->vendor_chart_uuid;
            $paymentCreditAccountId = $request->paymentCreditId()
                ?? $chartSetting?->bank_chart_uuid;

            if (empty($paymentDebitAccountId) || empty($paymentCreditAccountId)) {
                throw ValidationException::withMessages([
                    'payment_debit_id' => __('ledgers.validation.debit_account_or_default_required'),
                    'payment_credit_id' => __('ledgers.validation.credit_account_or_default_required'),
                ]);
            }

            $paymentDebitAccount = $this->accountSnapshot($paymentDebitAccountId, 'payment_debit_id');
            $paymentCreditAccount = $this->accountSnapshot($paymentCreditAccountId, 'payment_credit_id');
        }

        $companyName = filled($request->companyId())
            ? Company::query()->whereKey($request->companyId())->value('name')
            : null;

        /** @var string|null $invoiceAttachmentPath */
        $invoiceAttachmentPath = $request->hasFile('invoice_attachment')
            ? $this->storeUpload($request->file('invoice_attachment'), 'ledger_attachments')
            : $invoice->attachment_path;
        $docNumber = $documentNumber;
        $invoiceReferenceNumber = $invoice->reference_number ?? $docNumber;
        $invoiceDueDate = Carbon::parse($request->dateValue())
            ->addDays($request->creditTermDays())
            ->toDateString();
        $paymentDocumentNumber = $hasPayment
            ? (
                $existingPaymentEntry->document_number
                ?? $this->generateDocumentNumber(LedgerSubType::from($paymentSubtype))
            )
            : null;
        $paymentAmount = $hasPayment
            ? number_format($request->amount(), 2, '.', '')
            : null;

        DB::transaction(function () use (

            $companyName,
            $data,
            $docNumber,
            $hasPayment,
            $invoice,
            $invoiceAttachmentPath,
            $invoiceDueDate,
            $invoiceReferenceNumber,
            $lines,
            $paymentAmount,
            $paymentCreditAccount,
            $paymentCreditAccountId,
            $paymentDebitAccount,
            $paymentDebitAccountId,
            $paymentDocumentNumber,
            $lineCommercialTerms,
            $taxAccount

        ): void {

            $now = now();
            $invoiceDate = $data->date?->format('Y-m-d');
            $purchaseTypeValue = $data->purchaseType?->value;

            PurchaseInvoiceLine::query()
                ->where('purchase_invoice_id', $invoice->id)
                ->delete();

            PaymentVoucher::query()
                ->where('reference_number', $docNumber)
                ->where('status', '!=', LedgerStatus::POSTED->value)
                ->get()
                ->each(function (PaymentVoucher $voucher): void {
                    $voucher->lines()->delete();
                    $voucher->charts()->delete();
                    $voucher->forceDelete();
                });

            $invoice->forceFill([
                'company_id' => $data->companyId,
                'contact_id' => $data->contactId,
                'purchase_type' => $purchaseTypeValue,
                'reference_number' => $invoiceReferenceNumber,
                'company_name' => $companyName,
                'date' => $invoiceDate,
                'due_date' => $invoiceDueDate,
                'attachment_path' => $invoiceAttachmentPath,
                'notes' => $data->invoiceNotes,
            ]);

            $lineRows = [];
            $invoiceSubtotal = '0.00';
            $invoiceTotal = '0.00';

            foreach ($lines as $lineNumber => $line) {
                $terms = $lineCommercialTerms->get($lineNumber);
                if ($terms === null) {
                    throw new LogicException('Invoice line commercial terms are missing.');
                }
                $amounts = $this->calculateInvoiceLine(
                    is_numeric($line['quantity'] ?? 0) ? (int) ($line['quantity'] ?? 0) : 0,
                    is_numeric($line['unit_price'] ?? null) ? (string) $line['unit_price'] : '',
                    $terms['discount_amount'],
                    $terms['tax_amount']
                );

                $lineRows[] = [
                    'id' => (string) Str::uuid7(),
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => null,
                    'product_name' => null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal_amount' => $amounts['subtotal'],
                    'discount_value' => $terms['discount_amount'],
                    'discount_amount' => $amounts['discount'],
                    'tax_amount' => $amounts['tax'],
                    'line_total_amount' => $amounts['total'],
                    'total_amount' => $amounts['total'],
                    'description' => $line['description'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $invoiceSubtotal = bcadd($invoiceSubtotal, $amounts['subtotal'], 2);
                $invoiceTotal = bcadd($invoiceTotal, $amounts['total'], 2);

                if ((float) $amounts['tax'] > 0) {
                    if ($taxAccount === null) {
                        throw ValidationException::withMessages([
                            'tax_rate' => __('ledgers.validation.input_tax_account_required'),
                        ]);
                    }
                    $lineRows[] = [
                        'id' => (string) Str::uuid7(),
                        'purchase_invoice_id' => $invoice->id,
                        'product_id' => null,
                        'product_name' => null,
                        'quantity' => 1,
                        'unit_price' => $amounts['tax'],
                        'subtotal_amount' => '0.00',
                        'discount_value' => '0.0000',
                        'discount_amount' => '0.00',
                        'tax_amount' => '0.00',
                        'line_total_amount' => $amounts['tax'],
                        'total_amount' => $amounts['tax'],
                        'description' => __('ledgers.tax_line_description'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $invoiceTotal = bcadd($invoiceTotal, $amounts['tax'], 2);
                }
            }

            PurchaseInvoiceLine::query()->insert($lineRows);

            $invoice->forceFill([
                'subtotal' => $invoiceSubtotal,
                'total_amount' => $invoiceTotal,
            ]);
            $invoice->save();

            if ($hasPayment) {
                // The on-account payment is a payment voucher. It is replaced
                // rather than amended, so a voucher that has already posted is
                // never quietly rewritten by saving the invoice again.
                PaymentVoucher::query()
                    ->where('reference_number', $docNumber)
                    ->where('status', LedgerStatus::PENDING)
                    ->get()
                    ->each(function (PaymentVoucher $voucher): void {
                        $voucher->lines()->delete();
                        $voucher->charts()->delete();
                        $voucher->forceDelete();
                    });

                $voucher = PaymentVoucher::query()->create([
                    'document_number' => $paymentDocumentNumber,
                    'company_id' => $data->companyId,
                    'company_name' => $companyName,
                    'contact_id' => $data->contactId,
                    'date' => $invoiceDate,
                    'status' => LedgerStatus::PENDING,
                    'payment_transfer_type' => null,
                    'reference_number' => $docNumber,
                    'attachment_path' => null,
                    'notes' => $data->paymentNotes,
                    'total_amount' => $paymentAmount,
                ]);

                $voucher->lines()->create([
                    'amount' => $paymentAmount,
                    'description' => $data->paymentNotes,
                ]);

                $voucher->lines()->create([
                    'amount' => $paymentAmount,
                    'description' => $data->paymentNotes,
                ]);

                $voucher->charts()->create([
                    'debit_id' => $paymentDebitAccountId,
                    'debit_acc' => $paymentDebitAccount['code'],
                    'debit_acc_desc' => $paymentDebitAccount['description'],
                    'document_number' => $voucher->document_number,
                    'reference_number' => $voucher->reference_number,
                    'date' => $voucher->date->format('Y-m-d'),
                    'description' => $data->paymentNotes,
                    'debit_amount' => $paymentAmount,
                    'credit_amount' => '0.00',
                    'total_amount' => $paymentAmount,
                ]);

                $voucher->charts()->create([
                    'credit_id' => $paymentCreditAccountId,
                    'credit_acc' => $paymentCreditAccount['code'],
                    'credit_acc_desc' => $paymentCreditAccount['description'],
                    'document_number' => $voucher->document_number,
                    'reference_number' => $voucher->reference_number,
                    'date' => $voucher->date->format('Y-m-d'),
                    'description' => $data->paymentNotes,
                    'debit_amount' => '0.00',
                    'credit_amount' => $paymentAmount,
                    'total_amount' => $paymentAmount,
                ]);
            }

        });

        return redirect()
            ->route('purchase.invoices.show', $docNumber)
            ->with('success', FlashMessage::updated(FileResources::PURCHASE_INVOICES));

    }

    /**
     * Remove a resource by its object.
     * Remove an unposted invoice, its linked payment, and pending stock movements.
     *
     * @param  string  $documentNumber  The unposted invoice number.
     * @return RedirectResponse Redirect response to the multi-invoice index.
     *
     * @throws Throwable
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::REMOVE);

        DB::transaction(function () use ($documentNumber): void {
            $invoice = PurchaseInvoice::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            if ($invoice->purchase_order_id !== null) {
                throw ValidationException::withMessages([
                    'document_number' => __('purchase_orders.validation.generated_invoice_delete'),
                ]);
            }

            abort_if($invoice->isPosted(), 403);

            /** @var Collection<int, PaymentVoucher> $paymentEntries */
            $paymentEntries = PaymentVoucher::query()
                ->with('lines')
                ->where('reference_number', $documentNumber)
                ->lockForUpdate()
                ->get();

            $paymentEntryIds = $paymentEntries->pluck('document_number')->all();

            if (Ledger::query()->withTrashed()->whereIn('id', $paymentEntryIds)->exists()) {
                throw ValidationException::withMessages([
                    'document_number' => __('ledgers.validation.posted_invoice_delete_forbidden'),
                ]);
            }

            $invoice->delete();

            if ($paymentEntryIds !== []) {
                PaymentVoucher::query()
                    ->whereIn('document_number', $paymentEntryIds)
                    ->get()
                    ->each(function (PaymentVoucher $voucher): void {
                        $voucher->lines()->delete();
                        $voucher->charts()->delete();
                        $voucher->forceDelete();
                    });
            }
        });

        return redirect()
            ->route('purchase.invoices.index')
            ->with('success', FlashMessage::deleted(FileResources::PURCHASE_INVOICES));
    }

    /**
     * Approve a multi-invoice and its optional payment into the General Ledger.
     *
     * @param  string  $documentNumber  The invoice number.
     * @return RedirectResponse Redirect response to the multi-invoice detail page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::APPROVE);

        DB::transaction(function () use ($documentNumber): void {
            $invoice = PurchaseInvoice::query()
                ->with(['lines'])
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            abort_if($invoice->isPosted(), 409);

            /** @var Collection<int, PurchaseInvoiceLine> $invoiceLines */
            $invoiceLines = $invoice->lines;

            abort_if($invoiceLines->isEmpty(), 404);

            /** @var Collection<int, PaymentVoucher> $paymentEntries */
            $paymentEntries = PaymentVoucher::query()
                ->with('charts')
                ->where('reference_number', $documentNumber)
                ->lockForUpdate()
                ->get();

            $linesTotal = $invoiceLines->reduce(
                fn (string $total, PurchaseInvoiceLine $line): string => bcadd(
                    $total,
                    (string) ($line->line_total_amount ?? '0.00'),
                    2
                ),
                '0.00'
            );

            // Every invoice line posts as both a debit and a credit for its own
            // figure, so the lines agree by construction and what has to balance
            // is the payment raised against the invoice, read from its chart
            // rows where the accounts actually live.
            $voucherDebit = $paymentEntries->reduce(
                fn (string $total, PaymentVoucher $entry): string => bcadd(
                    $total,
                    $entry->charts->reduce(
                        fn (string $chartTotal, PaymentVoucherChart $chart): string => bcadd($chartTotal, $chart->debit_amount, 2),
                        '0.00'
                    ),
                    2
                ),
                '0.00'
            );
            $voucherCredit = $paymentEntries->reduce(
                fn (string $total, PaymentVoucher $entry): string => bcadd(
                    $total,
                    $entry->charts->reduce(
                        fn (string $chartTotal, PaymentVoucherChart $chart): string => bcadd($chartTotal, $chart->credit_amount, 2),
                        '0.00'
                    ),
                    2
                ),
                '0.00'
            );

            $totalDebit = bcadd($linesTotal, $voucherDebit, 2);
            $totalCredit = bcadd($linesTotal, $voucherCredit, 2);

            if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
                throw ValidationException::withMessages([
                    'document_number' => __('journal_entries.unbalanced_entry_error'),
                ]);
            }

            // Post to both ledgers - services handle chart queries and DTO building
            $this->payable->post($invoice, $invoiceLines);
            $this->ledger->postPurchaseInvoice($invoice, $invoiceLines);

            $invoice->forceFill(['status' => LedgerStatus::POSTED]);
            $this->attributeApproved($invoice);

            if ($paymentEntries->isNotEmpty()) {
                $this->postPaymentEntriesForEach($paymentEntries);
                $paymentEntries->each(
                    fn (PaymentVoucher $voucher) => $voucher->update(['status' => LedgerStatus::POSTED])
                );
            }

        });

        return redirect()
            ->route('purchase.invoices.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::PURCHASE_INVOICES));
    }

    /**
     * Reverse a posted purchase invoice by creating a new reversing journal entry.
     *
     * @param  Request  $request  The HTTP request instance containing reversal data.
     * @param  string  $documentNumber  The purchase invoice number.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function reverse(Request $request, string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::APPROVE);

        /** @var array{reversal_date: string, reason: string} $validated */
        $validated = $request->validate([
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($documentNumber, $validated): void {
            $invoice = PurchaseInvoice::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            abort_if($invoice->undone_at !== null, 409, __('journal_entries.reversal_already_exists'));

            $postedLedgers = Ledger::query()
                ->where('document_number', $documentNumber)
                ->where('status', LedgerStatus::POSTED)
                ->lockForUpdate()
                ->get();

            abort_if($postedLedgers->isEmpty(), 404);

            $creditSum = $postedLedgers->sum('credit_amount');
            $debitSum = $postedLedgers->sum('debit_amount');
            $totalDebit = number_format(is_numeric($creditSum) ? (float) $creditSum : 0.0, 2, '.', '');
            $totalCredit = number_format(is_numeric($debitSum) ? (float) $debitSum : 0.0, 2, '.', '');

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
            $itemRows = $postedLedgers->values()->map(function (Ledger $ledger) use ($reversalEntry, $documentNumber, $now): array {
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

            Ledger::query()->insert($ledgerRows);

            $user = auth()->user();
            $invoice->update([
                'undone_by' => $user?->id,
                'undone_name' => $user?->name,
                'undone_at' => now(),
            ]);
        });

        return redirect()
            ->route('purchase.invoices.show', $documentNumber)
            ->with('success', __('journal_entries.reversal_success'));
    }

    /**
     * Approve multiple purchase invoices and post them to the general ledger.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function batchApprove(Request $request): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::APPROVE);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        $count = 0;

        /** @var array<int, mixed> $documentNumbers */
        $documentNumbers = $request->input('document_numbers', []);

        DB::transaction(function () use ($documentNumbers, &$count): void {
            foreach ($documentNumbers as $documentNumber) {
                $documentNumber = is_string($documentNumber) ? $documentNumber : '';

                $invoice = PurchaseInvoice::query()
                    ->with(['lines'])
                    ->where('document_number', $documentNumber)
                    ->lockForUpdate()
                    ->first();

                if ($invoice === null) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.no_entries', ['number' => $documentNumber]),
                    ]);
                }

                if ($invoice->isPosted()) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.already_posted', ['number' => $documentNumber]),
                    ]);
                }

                /** @var Collection<int, PurchaseInvoiceLine> $invoiceLines */
                $invoiceLines = $invoice->lines;

                /** @var Collection<int, PaymentVoucher> $paymentEntries */
                $paymentEntries = PaymentVoucher::query()
                    ->with('charts')
                    ->where('reference_number', $documentNumber)
                    ->lockForUpdate()
                    ->get();

                $linesTotal = $invoiceLines->reduce(
                    fn (string $total, PurchaseInvoiceLine $line): string => bcadd(
                        $total,
                        (string) ($line->line_total_amount ?? '0.00'),
                        2
                    ),
                    '0.00'
                );

                // Every invoice line posts as both a debit and a credit for its
                // own figure, so the lines agree by construction and what has to
                // balance is the payment raised against the invoice, read from
                // its chart rows where the accounts actually live.
                $voucherDebit = $paymentEntries->reduce(
                    fn (string $total, PaymentVoucher $entry): string => bcadd(
                        $total,
                        $entry->charts->reduce(
                            fn (string $chartTotal, PaymentVoucherChart $chart): string => bcadd($chartTotal, $chart->debit_amount, 2),
                            '0.00'
                        ),
                        2
                    ),
                    '0.00'
                );
                $voucherCredit = $paymentEntries->reduce(
                    fn (string $total, PaymentVoucher $entry): string => bcadd(
                        $total,
                        $entry->charts->reduce(
                            fn (string $chartTotal, PaymentVoucherChart $chart): string => bcadd($chartTotal, $chart->credit_amount, 2),
                            '0.00'
                        ),
                        2
                    ),
                    '0.00'
                );

                $totalDebit = bcadd($linesTotal, $voucherDebit, 2);
                $totalCredit = bcadd($linesTotal, $voucherCredit, 2);

                if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.unbalanced', ['number' => $documentNumber]),
                    ]);
                }

                // Post to both ledgers - services handle chart queries and DTO building
                $this->payable->post($invoice, $invoiceLines);
                $this->ledger->postPurchaseInvoice($invoice, $invoiceLines);

                $invoice->forceFill(['status' => LedgerStatus::POSTED]);
                $this->attributeApproved($invoice);

                if ($paymentEntries->isNotEmpty()) {
                    $this->postPaymentEntriesForEach($paymentEntries);
                    $paymentEntries->each(
                        fn (PaymentVoucher $voucher) => $voucher->update(['status' => LedgerStatus::POSTED])
                    );
                }

                $count++;
            }
        });

        return redirect()
            ->route('purchase.invoices.history.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending purchase invoices.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function batchCancel(Request $request): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::CANCEL);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        /** @var array<int, mixed> $documentNumbers */
        $documentNumbers = $request->input('document_numbers', []);

        $affected = 0;
        foreach ($documentNumbers as $documentNumber) {
            if (! is_string($documentNumber)) {
                continue;
            }
            $affected += PurchaseInvoice::query()
                ->where('document_number', $documentNumber)
                ->where('status', LedgerStatus::PENDING)
                ->update(['status' => LedgerStatus::CANCELLED]);
        }

        return redirect()
            ->route('purchase.invoices.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $affected]));
    }

    /**
     * Post an invoice's optional payment to the ledgers as its own document.
     *
     * A payment made against an invoice carries its own document number and
     * date, so its rows are posted as a payment voucher rather than folded into
     * the invoice's rows: the money ledger then shows the voucher where the
     * printed ledger a reader reconciles against says it belongs.
     *
     * @param  Collection<int, PaymentVoucher>  $vouchers  The vouchers being posted.
     */
    private function postPaymentEntriesForEach(Collection $vouchers): void
    {
        foreach ($vouchers as $voucher) {
            $this->postPaymentEntries($voucher);
        }
    }

    /**
     * Post one payment voucher's legs to the ledgers it belongs in.
     *
     * @param  PaymentVoucher  $voucher  The voucher being posted.
     */
    private function postPaymentEntries(PaymentVoucher $voucher): void
    {
        if ($voucher->charts->isEmpty()) {
            return;
        }

        // A voucher is one figure crossing two accounts, so the poster is given
        // the legs its chart rows carry rather than built rows: there is no
        // line detail of its own to keep.
        $this->paymentPoster->post(
            $voucher,
            (string) $voucher->document_number,
            $voucher->date,
            LedgerType::PAYMENT,
            LedgerSubType::PAYMENT_VOUCHER,
            array_values($voucher->charts->map(function (PaymentVoucherChart $chart) use ($voucher): PostingLeg {
                if ($chart->debit_id !== null) {
                    return PostingLeg::movement(
                        $chart->id,
                        $chart->debit_id,
                        true,
                        $chart->debit_amount,
                        '0.00',
                        $chart->description,
                        $voucher->reference_number,
                    );
                }

                return PostingLeg::movement(
                    $chart->id,
                    (string) $chart->credit_id,
                    false,
                    '0.00',
                    $chart->credit_amount,
                    $chart->description,
                    $voucher->reference_number,
                );
            })->all()),
            companyId: $voucher->company_id,
            contactId: $voucher->contact_id,
            companyName: $voucher->company_name,
            referenceNumber: $voucher->reference_number,
        );
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
     * Resolve and validate the input-tax control account used by purchase invoice posting.
     *
     * @param  mixed  $chartId  The chart id value.
     * @return array{code: string, description: string|null}
     */
    private function taxAccountSnapshot(mixed $chartId): array
    {
        $chart = Chart::query()->with('chartType')->whereKey($chartId)->first();

        if ($chart?->chartType?->isAsset !== true) {
            throw ValidationException::withMessages([
                'tax_rate' => __('ledgers.validation.input_tax_must_be_asset'),
            ]);
        }

        return $this->accountSnapshot($chartId, 'tax_rate');
    }

    /**
     * Resolve the supplier-entered discount and tax amounts for one invoice line.
     *
     * @param  array<string, mixed>  $line  The submitted line values.
     * @param  int  $lineNumber  The submitted line index.
     * @return array{discount_amount: string, tax_amount: string}
     */
    private function resolveLineCommercialTerms(array $line, int $lineNumber): array
    {
        $quantityRaw = $line['quantity'] ?? 0;
        $quantity = is_numeric($quantityRaw) ? (int) $quantityRaw : 0;
        $unitPriceRaw = $line['unit_price'] ?? 0;
        $unitPrice = is_numeric($unitPriceRaw) ? (float) $unitPriceRaw : 0.0;
        $subtotal = round($quantity * $unitPrice, 2);
        $discountRaw = $line['discount_amount'] ?? 0;
        $discountAmount = round(is_numeric($discountRaw) ? (float) $discountRaw : 0.0, 2);

        if ($discountAmount > $subtotal) {
            throw ValidationException::withMessages([
                "lines.$lineNumber.discount_amount" => __('discounts.messages.amount_exceeds_subtotal'),
            ]);
        }

        $taxRaw = $line['tax_amount'] ?? 0;

        return [
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'tax_amount' => number_format(is_numeric($taxRaw) ? (float) $taxRaw : 0.0, 2, '.', ''),
        ];
    }

    /**
     * Calculate one commercial invoice line after discount and tax.
     *
     * @param  int  $quantity  The quantity value.
     * @param  string  $unitPrice  The unit price value.
     * @param  string  $discountAmount  The allocated discount amount.
     * @param  string  $taxAmount  The supplier-entered tax amount.
     * @return array{subtotal: numeric-string, discount: numeric-string, net: numeric-string, tax: numeric-string, total: numeric-string}
     */
    private function calculateInvoiceLine(
        int $quantity,
        string $unitPrice,
        string $discountAmount,
        string $taxAmount
    ): array {
        $subtotal = round($quantity * (float) $unitPrice, 2);
        $discount = round((float) $discountAmount, 2);
        $net = round($subtotal - $discount, 2);
        $tax = round((float) $taxAmount, 2);
        $total = round($net + $tax, 2);

        return [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'discount' => number_format($discount, 2, '.', ''),
            'net' => number_format($net, 2, '.', ''),
            'tax' => number_format($tax, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
        ];
    }
}
