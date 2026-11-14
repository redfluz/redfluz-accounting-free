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

namespace App\Http\Controllers\Purchase\Invoice;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\GeneralLedgerEntryType;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Enums\Purchase\PurchaseOrderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseInvoice\BatchApprovePurchaseInvoiceRequest;
use App\Http\Requests\Purchase\PurchaseInvoice\BatchCancelPurchaseInvoiceRequest;
use App\Http\Requests\Purchase\PurchaseInvoice\ReversePurchaseInvoiceRequest;
use App\Http\Requests\Purchase\PurchaseInvoice\StorePurchaseInvoiceRequest;
use App\Http\Requests\Purchase\PurchaseInvoice\UpdatePurchaseInvoiceRequest;
use App\Models\Bank\PaymentVoucher;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\ApLedger;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryChart;
use App\Models\Finance\Ledger;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Models\Purchase\PurchaseOrder;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Purchase\PurchaseInvoiceData;
use App\Support\Posting\ApLedgerData;
use App\Support\Posting\GeneralLedgerData;
use App\Support\Posting\LedgerData;
use App\Support\Posting\Posting;
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
     * The posting service that writes ledger rows.
     *
     * Injected rather than resolved through the container at the point of use, so
     * what this controller depends on is declared where it can be read and so a
     * test can hand it a poster of its own.
     */
    public function __construct(private readonly Posting $posting) {}

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
            ->where('status', DocumentStatus::PENDING)
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
            $entry->setAttribute('is_posted', $entry->status === DocumentStatus::POSTED);
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
            ->with('apLedgers')
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

        // Posted invoices are historical documents and print through their own
        // view so the pending and posted designs stay separate.
        if ($invoice->status === DocumentStatus::POSTED) {
            return redirect()->route('purchase.invoices.history.pdf', $documentNumber);
        }

        /** @var Collection<int, PurchaseInvoiceLine> $lines */
        $lines = $invoice->lines;

        // Payable-side payment rows for this invoice (payment vouchers debit
        // AP, supplier refunds credit AP and reduce net paid / increase balance).
        /** @var Collection<int, ApLedger> $payments */
        $payments = ApLedger::query()->againstPayableAccount()
            ->where('type', LedgerType::PAYMENT->value)
            ->where('reference_number', $documentNumber)
            ->orderBy('date')
            ->get();

        $amountPaid = $payments->reduce(
            /**
             * @param  numeric-string  $carry
             * @return numeric-string
             */
            static function (string $carry, ApLedger $payment): string {
                $diff = bcsub((string) $payment->debit_amount, (string) $payment->credit_amount, 2);

                return bcadd($carry, $diff, 2);
            },
            '0.00'
        );
        $amountPaid = bccomp($amountPaid, '0.00', 2) < 0 ? '0.00' : $amountPaid;

        /** @var numeric-string $invoiceTotal */
        $invoiceTotal = DecimalService::normalize((string) $invoice->total_amount);
        $balanceDue = bcsub(bcadd($invoiceTotal, '0.00', 2), $amountPaid, 2);
        $balanceDue = bccomp($balanceDue, '0.00', 2) < 0 ? '0.00' : $balanceDue;

        $setting = Setting::query()->first();

        return Pdf::loadView('purchase.invoices.pdf', [
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
        $this->allows(FileResources::PURCHASE_INVOICES);

        // The on-account payment is a payment voucher, so its printable document
        // is read from there rather than from wherever it was once stored.
        $payment = PaymentVoucher::query()
            ->with(['company', 'apLedgers'])
            ->where('document_number', $paymentDocumentNumber)
            ->first();

        abort_if($payment === null, 404);

        $paymentEntries = $payment->apLedgers;

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
        $sourcePurchaseOrder = null;

        $cloneData = session('clone_data');
        if ($cloneData) {
            session()->forget('clone_data');
        }

        return view(
            'purchase.invoices.create',
            compact('companies', 'contacts', 'subtype', 'indexRoute', 'sourcePurchaseOrder', 'canQuickCreateCompany', 'cloneData')
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
            ->with('apLedgers')
            ->where('reference_number', $documentNumber)
            ->get();

        abort_if(
            Ledger::query()
                ->whereIn('document_number', $paymentEntries->pluck('document_number'))
                ->exists(),
            409
        );

        /** @var PaymentVoucher|null $paymentDebit */
        $paymentDebit = $paymentEntries->firstWhere('status', DocumentStatus::POSTED);
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
        $sourcePurchaseOrder = $invoice->purchase_order_id !== null
            ? PurchaseOrder::query()->whereKey($invoice->purchase_order_id)->first()
            : null;

        return view(
            'purchase.invoices.edit',
            compact('companies', 'contacts', 'subtype', 'indexRoute', 'documentNumber', 'sourcePurchaseOrder', 'canQuickCreateCompany')
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

        DB::transaction(function () use (
            $companyName,
            $data,
            $docNumber,
            $invoiceAttachmentPath,
            $invoiceDueDate,
            $invoiceReferenceNumber,
            $lines,
            $lineCommercialTerms,
        ): void {

            $now = now();
            $invoiceDate = $data->date?->format('Y-m-d');
            $purchaseTypeValue = $data->purchaseType?->value;

            $invoice = new PurchaseInvoice;
            $invoice->forceFill([
                'company_id' => $data->companyId,
                'contact_id' => $data->contactId,
                'purchase_type' => $purchaseTypeValue,
                'status' => DocumentStatus::PENDING->value,
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
        abort_if($invoice->isPosted(), 409);

        $sourcePurchaseOrder = $invoice->purchase_order_id !== null
            ? PurchaseOrder::query()->whereKey($invoice->purchase_order_id)->first()
            : null;
        if (
            $sourcePurchaseOrder !== null
            && $request->companyId() !== $sourcePurchaseOrder->company_id
        ) {
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

        DB::transaction(function () use (
            $companyName,
            $data,
            $invoice,
            $invoiceAttachmentPath,
            $invoiceDueDate,
            $invoiceReferenceNumber,
            $lines,
            $lineCommercialTerms,
        ): void {

            $now = now();
            $invoiceDate = $data->date?->format('Y-m-d');
            $purchaseTypeValue = $data->purchaseType?->value;

            PurchaseInvoiceLine::query()
                ->where('purchase_invoice_id', $invoice->id)
                ->delete();

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

            $invoice->delete();
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
            /** @var PurchaseInvoice|null $invoice */
            $invoice = PurchaseInvoice::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            abort_if($invoice->isPosted(), 409);

            $this->postInvoice($invoice);

            $invoice->forceFill(['status' => DocumentStatus::POSTED]);
            $this->attributeApproved($invoice);
        });

        return redirect()
            ->route('purchase.invoices.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::PURCHASE_INVOICES));
    }

    /**
     * Reverse a posted purchase invoice by creating a new reversing journal entry.
     *
     * @param  ReversePurchaseInvoiceRequest  $request  The request containing the reversal reason.
     * @param  string  $documentNumber  The purchase invoice document number.
     * @return RedirectResponse Redirects to the invoice detail page.
     *
     * @throws Throwable
     */
    public function reverse(ReversePurchaseInvoiceRequest $request, string $documentNumber): RedirectResponse
    {
        /** @var array{reason: string} $validated */
        $validated = $request->validated();
        $reversalDate = now()->toDateString();

        DB::transaction(function () use ($documentNumber, $validated, $reversalDate): void {
            $invoice = PurchaseInvoice::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($invoice === null, 404);
            abort_if($invoice->undone_at !== null, 409, __('journal_entries.reversal_already_exists'));

            /** @var Collection<int, PurchaseInvoiceLine> $lines */
            $lines = $invoice->lines()->get();

            abort_if($lines->isEmpty(), 404);

            // Load chart configuration once
            $chartConfig = ChartSetting::query()->first();
            abort_if($chartConfig === null, 500, __('posting.chart_config_missing'));

            // Load all needed charts once
            $chartIds = array_filter([
                $chartConfig->vendor_chart_uuid,
                $chartConfig->cogs_chart_uuid,
                $chartConfig->input_tax_chart_uuid,
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
                /** @var PurchaseInvoiceLine $line */
                $grossTotal = bcadd($grossTotal, $line->subtotal_amount ?? '0.00', 2);
                $taxTotal = bcadd($taxTotal, $line->tax_amount ?? '0.00', 2);
                $discountTotal = bcadd($discountTotal, $line->discount_amount ?? '0.00', 2);
            }

            // What the supplier was owed, and the cost that was charged to the
            // purchase account - the same two gated figures approval wrote.
            $invoiceTotal = bcsub(bcadd($grossTotal, $taxTotal, 2), $discountTotal, 2);

            $expenseTotal = $chartConfig->is_tax_enabled
                ? bcsub($invoiceTotal, $taxTotal, 2)
                : $invoiceTotal;

            // Build GL reversal DTOs with swapped amounts (CR→DR, DR→CR)
            /** @var list<GeneralLedgerData> $glDtos */
            $glDtos = [];
            $firstLine = $lines->first();

            // DR Accounts Payable (reverses the CR from approve)
            if ($chartConfig->vendor_chart_uuid !== null) {
                $payableChart = $charts->get($chartConfig->vendor_chart_uuid);
                $glDtos[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                    $invoice,
                    $firstLine,
                    $chartConfig,
                    GeneralLedgerEntryType::PAYABLE,
                    $payableChart,
                    $invoiceTotal,
                    '0.00'
                );
            }

            // CR COGS (reverses the DR from approve)
            if ($chartConfig->cogs_chart_uuid !== null && bccomp($expenseTotal, '0.00', 2) !== 0) {
                $cogsChart = $charts->get($chartConfig->cogs_chart_uuid);
                $glDtos[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                    $invoice,
                    $firstLine,
                    $chartConfig,
                    GeneralLedgerEntryType::COGS,
                    $cogsChart,
                    '0.00',
                    $expenseTotal
                );
            }

            // CR Input Tax - only while GST/VAT is enabled, because that is the
            // only state in which approve() debited it
            if ($chartConfig->is_tax_enabled && $chartConfig->input_tax_chart_uuid !== null && bccomp($taxTotal, '0.00', 2) !== 0) {
                $inputTaxChart = $charts->get($chartConfig->input_tax_chart_uuid);
                $glDtos[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                    $invoice,
                    $firstLine,
                    $chartConfig,
                    GeneralLedgerEntryType::TAX,
                    $inputTaxChart,
                    '0.00',
                    $taxTotal
                );
            }

            // Calculate totals for the reversal journal entry. The tax is credited on
            // its own row only while GST/VAT is enabled, and both sides then equal the
            // payable approval credited.
            $totalDebit = $invoiceTotal;
            $totalCredit = $chartConfig->is_tax_enabled
                ? bcadd($expenseTotal, $taxTotal, 2)
                : $expenseTotal;

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
                        'document_number' => $dto->documentNumber,
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

            // Reverse AP ledger: create new rows with swapped amounts (ledgers are immutable)
            /** @var Collection<int, ApLedger> $apEntries */
            $apEntries = ApLedger::query()
                ->where('document_number', $documentNumber)
                ->get();

            if ($apEntries->isNotEmpty()) {
                /** @var list<ApLedgerData> $apReversalDtos */
                $apReversalDtos = $apEntries
                    ->map(function (ApLedger $ap) use ($reversalEntry, $documentNumber, $currencyId, $now): ApLedgerData {
                        return new ApLedgerData(
                            id: (string) Str::uuid7(),
                            chartId: $ap->chart_id,
                            companyId: $ap->company_id,
                            currencyId: $currencyId,
                            type: LedgerType::JOURNAL->value,
                            subtype: LedgerSubType::JOURNAL->value,
                            documentNumber: $reversalEntry->document_number,
                            date: $reversalEntry->date->format('Y-m-d'),
                            referenceNumber: $documentNumber,
                            chartCode: $ap->chart_code,
                            chartName: $ap->chart_name,
                            chartDescription: $ap->chart_description,
                            companyName: $ap->company_name,
                            description: __('journal_entries.reversal_line_description', [
                                'document_number' => $ap->document_number,
                                'description' => $ap->description ?? '-',
                            ]),
                            debitAmount: (string) $ap->credit_amount,
                            creditAmount: (string) $ap->debit_amount,
                            createdAt: $now,
                            updatedAt: $now,
                            deletedAt: null,
                        );
                    })
                    ->values()
                    ->all();

                $this->posting->accountPayableLedger($apReversalDtos);
            }

            $this->attributeUndone($invoice);
        });

        return redirect()
            ->route('purchase.invoices.show', $documentNumber)
            ->with('success', __('journal_entries.reversal_success'));
    }

    /**
     * Approve multiple purchase invoices and post them to the general ledger.
     *
     * @param  BatchApprovePurchaseInvoiceRequest  $request  The validated request containing document numbers.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function batchApprove(BatchApprovePurchaseInvoiceRequest $request): RedirectResponse
    {
        /** @var array{document_numbers: array<int, string>} $validated */
        $validated = $request->validated();

        $count = 0;

        DB::transaction(function () use ($validated, &$count): void {
            foreach ($validated['document_numbers'] as $documentNumber) {
                /** @var PurchaseInvoice|null $invoice */
                $invoice = PurchaseInvoice::query()
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

                /** @var Collection<int, PurchaseInvoiceLine> $lines */
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
            ->route('purchase.invoices.history.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending purchase invoices.
     *
     * @param  BatchCancelPurchaseInvoiceRequest  $request  The validated request containing document numbers.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function batchCancel(BatchCancelPurchaseInvoiceRequest $request): RedirectResponse
    {
        /** @var array{document_numbers: array<int, string>} $validated */
        $validated = $request->validated();

        $affected = PurchaseInvoice::query()
            ->whereIn('document_number', $validated['document_numbers'])
            ->where('status', DocumentStatus::PENDING)
            ->update(['status' => DocumentStatus::CANCELLED]);

        return redirect()
            ->route('purchase.invoices.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $affected]));
    }

    /**
     * Post one purchase invoice to its payable sub-ledger and the general ledger.
     *
     * The rows are written per invoice, the way a sales invoice writes them: one
     * debit to the purchase account, one debit to the input tax account when
     * GST/VAT is enabled, and one credit to the supplier for what is owed. Every
     * general ledger row has a payable ledger row beside it carrying the same
     * figure against the same account, so the payable ledger holds exactly the
     * rows the general ledger holds for this invoice - the cost row reads as the
     * cost account and only the payable row reads as the supplier's. The supplier
     * discount is never written to its own account; it stays inside the cost of
     * the purchase.
     *
     * @param  PurchaseInvoice  $invoice  The invoice being posted.
     *
     * @throws Throwable
     */
    private function postInvoice(PurchaseInvoice $invoice): void
    {
        /** @var list<GeneralLedgerData> $glDtos */
        $glDtos = [];

        /** @var list<ApLedgerData> $apDtos */
        $apDtos = [];

        // Aggregate per invoice - one pass, no per-line rows
        $grossTotal = '0.00';
        $taxTotal = '0.00';
        $discountTotal = '0.00';

        [$chartConfig, $vendorChart, $cogsChart, $inputTaxChart, $currencyId] = $this->getPurchaseInvoiceConfig();

        /** @var Collection<int, PurchaseInvoiceLine> $lines */
        $lines = $invoice->lines;
        abort_if($lines->isEmpty(), 404);

        foreach ($lines as $line) {
            /** @var PurchaseInvoiceLine $line */
            $grossTotal = bcadd($grossTotal, $line->subtotal_amount ?? '0.00', 2);
            $taxTotal = bcadd($taxTotal, $line->tax_amount ?? '0.00', 2);
            $discountTotal = bcadd($discountTotal, $line->discount_amount ?? '0.00', 2);
        }

        // What the supplier is owed: the price, plus the tax the supplier charged,
        // less the discount the supplier gave.
        $invoiceTotal = bcsub(bcadd($grossTotal, $taxTotal, 2), $discountTotal, 2);

        // The purchase account carries the cost. With GST/VAT enabled the tax is
        // claimed back through its own account, so the cost is what is left of the
        // payable; with it disabled no tax is claimed and the tax is part of the cost.
        $expenseTotal = $chartConfig->is_tax_enabled
            ? bcsub($invoiceTotal, $taxTotal, 2)
            : $invoiceTotal;

        // $firstLine is only for the DTO's line reference - amounts are invoice-level
        /** @var PurchaseInvoiceLine $firstLine */
        $firstLine = $lines->first();

        // DR Purchase Expense
        if ($cogsChart instanceof Chart && bccomp($expenseTotal, '0.00', 2) !== 0) {
            $glDtos[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $firstLine,
                $chartConfig,
                GeneralLedgerEntryType::COGS,
                $cogsChart,
                $expenseTotal,
                '0.00'
            );
            $apDtos[] = ApLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $firstLine,
                $cogsChart,
                $currencyId,
                $expenseTotal,
                '0.00'
            );
        }

        // DR Input Tax - only while GST/VAT is enabled
        if ($chartConfig->is_tax_enabled && $inputTaxChart instanceof Chart && bccomp($taxTotal, '0.00', 2) !== 0) {
            $glDtos[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $firstLine,
                $chartConfig,
                GeneralLedgerEntryType::TAX,
                $inputTaxChart,
                $taxTotal,
                '0.00'
            );
            $apDtos[] = ApLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $firstLine,
                $inputTaxChart,
                $currencyId,
                $taxTotal,
                '0.00'
            );
        }

        // CR Payable = invoice total
        if (bccomp($invoiceTotal, '0.00', 2) !== 0) {
            $glDtos[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $firstLine,
                $chartConfig,
                GeneralLedgerEntryType::PAYABLE,
                $vendorChart,
                '0.00',
                $invoiceTotal
            );
            $apDtos[] = ApLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $firstLine,
                $vendorChart,
                $currencyId,
                '0.00',
                $invoiceTotal
            );
        }

        if (! empty($apDtos)) {
            $this->posting->accountPayableLedger($apDtos);
        }
        if (! empty($glDtos)) {
            $this->posting->generalLedger($glDtos);
        }
    }

    /**
     * The configuration a purchase invoice posts against.
     *
     * The chart accounts and the posting currency are read here once, so posting
     * only ever sees the accounts and the currency it writes. A missing
     * configuration or a missing supplier account is a setup failure, not a
     * posting with a blank payable, so both stop the request.
     *
     * @return array{0: ChartSetting, 1: Chart, 2: Chart|null, 3: Chart|null, 4: string} The chart configuration, the vendor, COGS and input tax accounts, then the currency id.
     */
    private function getPurchaseInvoiceConfig(): array
    {
        /** @var ChartSetting|null $chartConfig */
        $chartConfig = ChartSetting::query()->first();
        abort_if($chartConfig === null, 500, __('posting.chart_config_missing'));

        $chartIds = array_filter([
            $chartConfig->vendor_chart_uuid,
            $chartConfig->cogs_chart_uuid,
            $chartConfig->input_tax_chart_uuid,
        ]);

        /** @var Collection<string, Chart> $charts */
        $charts = Chart::query()
            ->whereIn('id', $chartIds)
            ->get()
            ->keyBy(fn (Chart $chart): string => (string) $chart->id);

        /** @var Chart|null $vendorChart */
        $vendorChart = $charts->get((string) $chartConfig->vendor_chart_uuid);
        abort_if(! $vendorChart instanceof Chart, 500, __('posting.chart_config_missing'));

        /** @var Chart|null $cogsChart */
        $cogsChart = $charts->get((string) $chartConfig->cogs_chart_uuid);

        /** @var Chart|null $inputTaxChart */
        $inputTaxChart = $charts->get((string) $chartConfig->input_tax_chart_uuid);

        /** @var string $currencyId */
        $currencyId = Setting::query()->value('currency_id') ?? '';

        return [$chartConfig, $vendorChart, $cogsChart, $inputTaxChart, $currencyId];
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
