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
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\ManualAdjustment\ManualSalesAdjustmentInvoiceOptionsRequest;
use App\Http\Requests\Sales\ManualAdjustment\ReverseManualSalesAdjustmentRequest;
use App\Http\Requests\Sales\ManualAdjustment\StoreManualSalesAdjustmentRequest;
use App\Http\Requests\Sales\ManualAdjustment\UpdateManualSalesAdjustmentRequest;
use App\Http\Requests\Sales\ManualSalesAdjustment\BatchApproveManualSalesAdjustmentRequest;
use App\Http\Requests\Sales\ManualSalesAdjustment\BatchCancelManualSalesAdjustmentRequest;
use App\Models\ChartOfAccount\Chart;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Finance\Currency;
use App\Models\Sales\ManualSalesAdjustment;
use App\Models\Sales\ManualSalesAdjustmentChart;
use App\Models\Sales\SalesInvoice;
use App\Models\System\Setting;
use App\Support\Data\Sales\ManualSalesAdjustmentData;
use App\Support\Posting\ArLedgerData;
use App\Support\Posting\GeneralLedgerData;
use App\Support\Posting\Posting;
use App\Support\Traits\AttributesDocuments;
use App\Traits\ChecksFinancialPostingDate;
use App\Traits\GeneratesDocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ManualSalesAdjustmentController extends Controller
{
    use AttributesDocuments, ChecksFinancialPostingDate, GeneratesDocumentNumber;

    /**
     * How many manual adjustments the index shows per page.
     */
    private const int PER_PAGE = 20;

    /**
     * The posting services the controller writes through.
     *
     * Injected rather than resolved at the point of use, so what this controller
     * depends on is declared where it can be read: one writes the general
     * ledger rows, one writes the receivable rows.
     */
    public function __construct(
        private readonly Posting $posting,
    ) {}

    /**
     * Display a listing of manual sales adjustments.
     *
     * @param  Request  $request  The incoming HTTP request carrying the search and date filters.
     * @return View The index view with paginated manual sales adjustments.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS);

        $indexRoute = 'sales.manual-adjustments.index';
        $createRoute = 'sales.manual-adjustments.create';
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString() ?: null;
        $endDate = $request->string('end_date')->toString() ?: null;

        $adjustments = ManualSalesAdjustment::query()
            ->with('company')
            ->withCount('charts')
            ->withSum('charts as total_debit', 'debit_amount')
            ->withSum('charts as total_credit', 'credit_amount')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%")
                        ->orWhereHas('charts', fn ($chartQuery) => $chartQuery->where('description', 'like', "%$search%"));
                });
            })
            ->when($startDate !== null, fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== null, fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('sales.manual-adjustments.index', compact(
            'adjustments',
            'search',
            'startDate',
            'endDate',
            'indexRoute',
            'createRoute'
        ));
    }

    /**
     * Display the details of a manual sales adjustment.
     *
     * @param  string  $documentNumber  The manual adjustment document number.
     * @return View The show view with the adjustment and its chart lines.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS);

        /** @var ManualSalesAdjustment|null $adjustment */
        $adjustment = ManualSalesAdjustment::query()
            ->with(['company', 'charts'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($adjustment === null, 404);

        $isPosted = $adjustment->isPosted();
        $indexRoute = 'sales.manual-adjustments.index';

        $reversalNumber = ManualSalesAdjustment::query()
            ->where('document_original', $adjustment->document_number)
            ->value('document_number');

        return view('sales.manual-adjustments.show', compact(
            'adjustment',
            'isPosted',
            'indexRoute',
            'reversalNumber'
        ) + $this->financialPostingDateBounds());
    }

    /**
     * Print one manual sales adjustment.
     *
     * @param  string  $documentNumber  The manual adjustment document number.
     * @return Response The PDF download response for the adjustment.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS);

        /** @var ManualSalesAdjustment|null $adjustment */
        $adjustment = ManualSalesAdjustment::query()
            ->with(['company', 'charts'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($adjustment === null, 404);

        return Pdf::loadView('sales.manual-adjustments.pdf', [
            'adjustment' => $adjustment,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->stream("manual_sales_adjustment_$documentNumber.pdf");
    }

    /**
     * Show the form for creating a new manual sales adjustment.
     *
     * @return View The create view with companies, contacts, and charts.
     */
    public function create(): View
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::ADD);

        $companies = Company::query()
            ->where('is_debtor', true)
            ->orderBy('name')
            ->get();

        $contacts = Contact::query()
            ->whereHas('company', fn ($q) => $q->where('is_debtor', true))
            ->orderBy('name')
            ->get();

        $charts = Chart::query()
            ->with('chartType')
            ->orderBy('code')
            ->get();

        $selectedInvoice = $this->selectedInvoice(old('sales_invoice_id'));

        return view('sales.manual-adjustments.create', compact(
            'companies',
            'contacts',
            'charts',
            'selectedInvoice'
        ) + $this->financialPostingDateBounds());
    }

    /**
     * Return searchable sales invoices for the mandatory adjustment reference.
     *
     * @param  ManualSalesAdjustmentInvoiceOptionsRequest  $request  The request containing the company filter and optional search text.
     * @return JsonResponse Matching posted sales invoice options for the combobox.
     */
    public function invoiceOptions(ManualSalesAdjustmentInvoiceOptionsRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $search = trim(is_string($validated['search'] ?? null) ? (string) $validated['search'] : '');

        $results = SalesInvoice::query()
            ->where('status', DocumentStatus::POSTED)
            ->where('company_id', is_string($validated['company_id'] ?? null) ? $validated['company_id'] : '')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%");
                });
            })
            ->orderByDesc('date')
            ->limit(50)
            ->get()
            ->map(function (SalesInvoice $invoice): array {
                $amount = number_format((float) $invoice->total_amount, 2, '.', '');
                $company = filled($invoice->company_name) ? " - $invoice->company_name" : '';
                $notes = filled($invoice->notes) ? " - $invoice->notes" : '';

                return [
                    'value' => $invoice->id,
                    'text' => "$invoice->document_number$company - $invoice->date - (RM $amount)$notes",
                ];
            })
            ->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Store a newly created manual sales adjustment.
     *
     * The header is one manual_sales_adjustments row and its accounting
     * entries are manual_sales_adjustment_charts rows, one per debit or
     * credit the user keyed. The sales invoice the note corrects is linked
     * by foreign key; the reference number keeps the invoice's document
     * number as the snapshot the grids print.
     *
     * @param  StoreManualSalesAdjustmentRequest  $request  The incoming HTTP request carrying the form.
     * @return RedirectResponse Redirect response to the manual adjustment detail page.
     *
     * @throws Throwable
     */
    public function store(StoreManualSalesAdjustmentRequest $request): RedirectResponse
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::ADD);

        $data = ManualSalesAdjustmentData::from($request->validated());

        $date = $data->date;
        if (! $data->subtype instanceof LedgerSubType || ! $date instanceof CarbonImmutable) {
            abort(422);
        }
        $subtype = $data->subtype;

        $adjustmentDate = $date->format('Y-m-d');
        /** @var array<int, array<string, mixed>> $lineInputs */
        $lineInputs = array_values(is_array($lines = $request->validated('lines')) ? $lines : []);
        $this->ensureFinancialPostingDate($adjustmentDate);
        $this->linkInvoice($data);

        $preparedLines = $this->prepareLines($lineInputs, $subtype);

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('ledger_attachments', 'public')
            : null;
        $data->attachmentPath = is_string($attachmentPath) ? $attachmentPath : null;

        $docNumber = $this->generateDocumentNumber($subtype);
        $total = $preparedLines['total'];
        $data->subtotal = $total;
        $data->totalAmount = $total;

        DB::transaction(function () use ($preparedLines, $data, $docNumber, $adjustmentDate): void {
            /** @var ManualSalesAdjustment $adjustment */
            $adjustment = ManualSalesAdjustment::query()->create([
                'document_number' => $docNumber,
                'status' => DocumentStatus::PENDING,
            ] + $data->toRow());

            /** @var array<string, mixed> $line */
            foreach ($preparedLines['lines'] as $line) {
                $adjustment->charts()->create([
                    'chart_id' => $line['chart_id'],
                    'company_id' => $line['company_id'],
                    'currency_id' => $line['currency_id'],
                    'type' => $line['type'],
                    'subtype' => $line['subtype'],
                    'document_number' => $docNumber,
                    'date' => $adjustmentDate,
                    'chart_code' => $line['chart_code'],
                    'chart_name' => $line['chart_name'],
                    'company_name' => $line['company_name'],
                    'description' => $line['description'],
                    'line_description' => $line['line_description'],
                    'debit_amount' => $line['debit_amount'],
                    'credit_amount' => $line['credit_amount'],
                ]);
            }
        });

        return redirect()
            ->route('sales.manual-adjustments.show', $docNumber)
            ->with('success', FlashMessage::created(FileResources::MANUAL_SALES_ADJUSTMENTS));
    }

    /**
     * Show the form for editing a manual sales adjustment.
     *
     * @param  string  $documentNumber  The manual adjustment document number.
     * @return View The edit view with the adjustment and its chart lines.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::UPDATE);

        /** @var ManualSalesAdjustment|null $adjustment */
        $adjustment = ManualSalesAdjustment::query()
            ->with(['charts', 'company'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($adjustment === null, 404);
        abort_if(! $adjustment->isEditable(), 409);

        $companies = Company::query()
            ->where('is_debtor', true)
            ->orderBy('name')
            ->get();

        $contacts = Contact::query()
            ->whereHas('company', fn ($q) => $q->where('is_debtor', true))
            ->orderBy('name')
            ->get();

        $charts = Chart::query()
            ->with('chartType')
            ->orderBy('code')
            ->get();

        $selectedInvoice = $this->selectedInvoice(old('sales_invoice_id', $adjustment->sales_invoice_id));

        return view('sales.manual-adjustments.edit', compact(
            'adjustment',
            'companies',
            'contacts',
            'charts',
            'selectedInvoice'
        ) + $this->financialPostingDateBounds());
    }

    /**
     * Update the specified manual sales adjustment.
     *
     * The header is updated in place and its chart lines are replaced with what
     * the form submitted, so the document and its entries cannot disagree.
     *
     * @param  UpdateManualSalesAdjustmentRequest  $request  The incoming HTTP request carrying the form.
     * @param  string  $documentNumber  The manual adjustment document number.
     * @return RedirectResponse Redirect response to the manual adjustment detail page.
     *
     * @throws Throwable
     */
    public function update(UpdateManualSalesAdjustmentRequest $request, string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::UPDATE);

        /** @var ManualSalesAdjustment|null $adjustment */
        $adjustment = ManualSalesAdjustment::query()
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($adjustment === null, 404);
        abort_if(! $adjustment->isEditable(), 409);

        $data = ManualSalesAdjustmentData::from($request->validated());

        $date = $data->date;
        if (! $data->subtype instanceof LedgerSubType || ! $date instanceof CarbonImmutable) {
            abort(422);
        }
        $subtype = $data->subtype;

        $this->ensureFinancialPostingDate($date->format('Y-m-d'));
        $this->linkInvoice($data);

        /** @var array<int, array<string, mixed>> $lineInputs */
        $lineInputs = array_values(is_array($lines = $request->validated('lines')) ? $lines : []);
        $preparedLines = $this->prepareLines($lineInputs, $subtype);

        $adjustmentDate = $date->format('Y-m-d');
        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('ledger_attachments', 'public')
            : $adjustment->attachment_path;
        $data->attachmentPath = is_string($attachmentPath) ? $attachmentPath : $adjustment->attachment_path;

        $total = $preparedLines['total'];
        $data->subtotal = $total;
        $data->totalAmount = $total;

        DB::transaction(function () use ($adjustment, $preparedLines, $data, $adjustmentDate): void {
            $adjustment->update($data->toRow());

            $adjustment->charts()->delete();

            /** @var array<string, mixed> $line */
            foreach ($preparedLines['lines'] as $line) {
                $adjustment->charts()->create([
                    'chart_id' => $line['chart_id'],
                    'company_id' => $line['company_id'],
                    'currency_id' => $line['currency_id'],
                    'type' => $line['type'],
                    'subtype' => $line['subtype'],
                    'document_number' => $adjustment->document_number,
                    'date' => $adjustmentDate,
                    'chart_code' => $line['chart_code'],
                    'chart_name' => $line['chart_name'],
                    'company_name' => $line['company_name'],
                    'description' => $line['description'],
                    'line_description' => $line['line_description'],
                    'debit_amount' => $line['debit_amount'],
                    'credit_amount' => $line['credit_amount'],
                ]);
            }
        });

        return redirect()
            ->route('sales.manual-adjustments.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::MANUAL_SALES_ADJUSTMENTS));
    }

    /**
     * Remove the specified manual sales adjustment from storage.
     *
     * @param  string  $documentNumber  The manual adjustment document number.
     * @return RedirectResponse Redirect response to the manual adjustment index.
     *
     * @throws Throwable
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::REMOVE);

        DB::transaction(function () use ($documentNumber): void {
            /** @var ManualSalesAdjustment|null $adjustment */
            $adjustment = ManualSalesAdjustment::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($adjustment === null, 404);

            if ($adjustment->status === DocumentStatus::POSTED) {
                throw ValidationException::withMessages([
                    'document_number' => __('manual_sales_adjustments.validation.posted_delete_forbidden'),
                ]);
            }

            $adjustment->charts()->delete();
            $adjustment->delete();
        });

        return redirect()
            ->route('sales.manual-adjustments.index')
            ->with('success', FlashMessage::deleted(FileResources::MANUAL_SALES_ADJUSTMENTS));
    }

    /**
     * Approve a manual sales adjustment and post its chart lines into the ledgers.
     *
     * The general ledger receives every line as keyed, and the receivable
     * sub-ledger receives those same lines against those same accounts, so the
     * customer's statement moves with the note the same way a product
     * invoice's does. Nothing else is written: no stock moves and no money
     * ledger row is raised by approving a manual adjustment.
     *
     * @param  string  $documentNumber  The manual adjustment document number.
     * @return RedirectResponse Redirect response to the manual adjustment detail page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::APPROVE);

        DB::transaction(function () use ($documentNumber): void {
            /** @var ManualSalesAdjustment|null $adjustment */
            $adjustment = ManualSalesAdjustment::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($adjustment === null, 404);
            abort_if($adjustment->status === DocumentStatus::POSTED, 409);

            $this->postToLedgers($adjustment);

            $adjustment->update(['status' => DocumentStatus::POSTED]);
            $this->attributeApproved($adjustment);
        });

        return redirect()
            ->route('sales.manual-adjustments.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::MANUAL_SALES_ADJUSTMENTS));
    }

    /**
     * Undo a posted manual sales adjustment by raising the opposite note.
     *
     * The undo is a document of its own, never a journal: a customer credit
     * note when the original raised the receivable, a customer debit note when
     * it reduced it. The note carries the original's document number in
     * document_original, repeats the original's lines with the sides swapped,
     * and is posted on creation. The original keeps its ledger rows, so the
     * customer's statement shows the note and its opposite netting to zero,
     * and the original is marked undone.
     *
     * @param  ReverseManualSalesAdjustmentRequest  $request  The incoming HTTP request carrying the reversal reason.
     * @param  string  $documentNumber  The manual adjustment document number.
     * @return RedirectResponse Redirect response to the manual adjustment detail page.
     *
     * @throws Throwable
     */
    public function reverse(ReverseManualSalesAdjustmentRequest $request, string $documentNumber): RedirectResponse
    {
        /** @var array{reason: string} $validated */
        $validated = $request->validated();
        $reversalDate = now()->toDateString();

        $this->ensureFinancialPostingDate($reversalDate, 'reversal_date');

        DB::transaction(function () use ($documentNumber, $validated, $reversalDate): void {
            /** @var ManualSalesAdjustment|null $adjustment */
            $adjustment = ManualSalesAdjustment::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($adjustment === null, 404);
            abort_if($adjustment->status !== DocumentStatus::POSTED, 409);
            abort_if($adjustment->document_original !== null, 409, __('manual_sales_adjustments.validation.reversal_cannot_reverse'));
            abort_if($adjustment->undone_at !== null, 409, __('manual_sales_adjustments.validation.reversal_already_exists'));

            /** @var Collection<int, ManualSalesAdjustmentChart> $charts */
            $charts = $adjustment->charts()->get();

            abort_if($charts->isEmpty(), 404);

            // The opposite note: an increase is undone by a credit note, a
            // decrease by a debit note.
            $noteSubtype = match ($adjustment->subtype) {
                LedgerSubType::DECREASE_ADJUSTMENT, LedgerSubType::SALES_CREDIT_NOTE => LedgerSubType::SALES_DEBIT_NOTE,
                default => LedgerSubType::SALES_CREDIT_NOTE,
            };

            $noteNumber = $this->generateDocumentNumber($noteSubtype);

            /** @var ManualSalesAdjustment $note */
            $note = ManualSalesAdjustment::query()->create([
                'company_id' => $adjustment->company_id,
                'contact_id' => $adjustment->contact_id,
                'document_number' => $noteNumber,
                'subtype' => $noteSubtype,
                'reference_number' => $adjustment->reference_number,
                'document_original' => $documentNumber,
                'sales_invoice_id' => $adjustment->sales_invoice_id,
                'company_name' => $adjustment->company_name,
                'contact_name' => $adjustment->contact_name,
                'date' => $reversalDate,
                'status' => DocumentStatus::POSTED,
                'subtotal' => $adjustment->subtotal,
                'total_amount' => $adjustment->total_amount,
                'notes' => $validated['reason'],
            ]);

            /** @var ManualSalesAdjustmentChart $chart */
            foreach ($charts as $chart) {
                $note->charts()->create([
                    'chart_id' => $chart->chart_id,
                    'company_id' => $chart->company_id,
                    'currency_id' => $chart->currency_id,
                    'type' => $chart->type,
                    'subtype' => $noteSubtype,
                    'document_number' => $noteNumber,
                    'reference_number' => $chart->reference_number ?? $adjustment->reference_number,
                    'date' => $reversalDate,
                    'chart_code' => $chart->chart_code,
                    'chart_name' => $chart->chart_name,
                    'company_name' => $chart->company_name,
                    'description' => $chart->description,
                    'line_description' => __('manual_sales_adjustments.reversal_line_description', [
                        'entry_number' => $documentNumber,
                        'description' => $chart->line_description ?? $chart->description ?? '-',
                    ]),
                    'debit_amount' => $chart->credit_amount,
                    'credit_amount' => $chart->debit_amount,
                ]);
            }

            $this->postToLedgers($note);
            $this->attributeApproved($note);
            $this->attributeUndone($adjustment);
        });

        return redirect()
            ->route('sales.manual-adjustments.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::MANUAL_SALES_ADJUSTMENTS));
    }

    /**
     * Post an adjustment's chart lines into the general and receivable ledgers.
     *
     * The general ledger receives every line as keyed, and the receivable
     * sub-ledger receives those same lines against those same accounts, so the
     * customer's statement moves with the note the same way a product
     * invoice's does. Shared by approval and by the opposite note an undo
     * raises, so both write the ledgers the same way.
     *
     * @param  ManualSalesAdjustment  $adjustment  The adjustment whose chart lines are posted.
     *
     * @throws ValidationException When the lines do not balance.
     */
    private function postToLedgers(ManualSalesAdjustment $adjustment): void
    {
        /** @var Collection<int, ManualSalesAdjustmentChart> $charts */
        $charts = $adjustment->charts()->get();

        abort_if($charts->isEmpty(), 404);

        /** @var numeric-string $totalDebit */
        $totalDebit = $charts->reduce(
            /** @param numeric-string $total */
            fn (string $total, ManualSalesAdjustmentChart $chart): string => bcadd($total, (string) $chart->debit_amount, 2),
            '0.00'
        );
        /** @var numeric-string $totalCredit */
        $totalCredit = $charts->reduce(
            /** @param numeric-string $total */
            fn (string $total, ManualSalesAdjustmentChart $chart): string => bcadd($total, (string) $chart->credit_amount, 2),
            '0.00'
        );

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw ValidationException::withMessages([
                'document_number' => __('manual_sales_adjustments.validation.unbalanced_entry'),
            ]);
        }

        // GL: direct from manual_sales_adjustment_charts
        /** @var Collection<string, Chart> $accounts */
        $accounts = Chart::query()
            ->whereIn('id', $charts->map(fn (ManualSalesAdjustmentChart $c): ?string => $c->chart_id)->filter()->unique())
            ->get()
            ->keyBy('id');

        /** @var Collection<string, string> $accountDescriptions */
        $accountDescriptions = $accounts->pluck('name', 'id');

        $glDtos = [];
        /** @var ManualSalesAdjustmentChart $chart */
        foreach ($charts as $chart) {
            $glDtos[] = GeneralLedgerData::fromManualSalesAdjustmentLine($adjustment, $chart, $accountDescriptions);
        }

        // AR: every chart line, each against the account it was booked to, so
        // the receivable ledger holds exactly the rows the general ledger holds
        // for this adjustment - the control-account line among them, not only it.
        $arDtos = [];
        /** @var string|null $defaultCurrencyId */
        $defaultCurrencyId = Currency::query()->where('is_default', true)->value('id');
        $currencyId = is_string($defaultCurrencyId) ? $defaultCurrencyId : '';

        /** @var ManualSalesAdjustmentChart $chart */
        foreach ($charts as $chart) {
            $account = $accounts->get((string) $chart->chart_id);

            if ($account === null) {
                continue;
            }

            $debit = (string) ($chart->debit_amount ?? '0.00');
            $credit = (string) ($chart->credit_amount ?? '0.00');

            if (bccomp($debit, '0.00', 2) !== 0 || bccomp($credit, '0.00', 2) !== 0) {
                $arDtos[] = ArLedgerData::fromManualSalesAdjustmentChartLine(
                    $adjustment,
                    $chart,
                    $account,
                    $currencyId,
                    $debit,
                    $credit,
                );
            }
        }

        $this->posting->generalLedger($glDtos);
        $this->posting->accountReceivableLedger($arDtos);
    }

    /**
     * Approve multiple manual sales adjustments in batch.
     *
     * @param  BatchApproveManualSalesAdjustmentRequest  $request  The incoming HTTP request carrying the document numbers to approve.
     * @return RedirectResponse Redirect response to the manual adjustment index.
     */
    public function batchApprove(BatchApproveManualSalesAdjustmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $documentNumbers = $validated['document_numbers'] ?? null;
        if (! is_array($documentNumbers)) {
            abort(422);
        }

        foreach ($documentNumbers as $docNumber) {
            if (! is_string($docNumber)) {
                continue;
            }
            try {
                $this->approve($docNumber);
            } catch (Throwable $e) {
                return redirect()
                    ->route('sales.manual-adjustments.index')
                    ->withErrors(['document_numbers' => __('manual_sales_adjustments.validation.batch_approve_failed', ['document' => $docNumber])]);
            }
        }

        return redirect()
            ->route('sales.manual-adjustments.index')
            ->with('success', FlashMessage::approved(FileResources::MANUAL_SALES_ADJUSTMENTS));
    }

    /**
     * Cancel multiple pending manual sales adjustments in batch.
     *
     * Cancelling keeps the document for the record: the header moves to the
     * cancelled status so the cancelled page can list and restore it, instead
     * of the row being deleted outright.
     *
     * @param  BatchCancelManualSalesAdjustmentRequest  $request  The incoming HTTP request carrying the document numbers to cancel.
     * @return RedirectResponse Redirect response to the cancelled manual adjustments index.
     */
    public function batchCancel(BatchCancelManualSalesAdjustmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $affected = ManualSalesAdjustment::query()
            ->whereIn('document_number', $validated['document_numbers'] ?? [])
            ->where('status', DocumentStatus::PENDING)
            ->update(['status' => DocumentStatus::CANCELLED]);

        return redirect()
            ->route('sales.manual-adjustments.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $affected]));
    }

    /**
     * Search charts of accounts for the manual sales adjustment account picker.
     *
     * @param  Request  $request  The HTTP request instance with the search query and optional chart type filter.
     * @return JsonResponse JSON response with matching charts for the picker table.
     */
    public function searchCharts(Request $request): JsonResponse
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS);

        $query = $request->string('q')->toString();
        $typeId = $request->string('type_id')->toString();

        $charts = Chart::query()
            ->with('chartType')
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($subQuery) use ($query) {
                    $subQuery->where('code', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%")
                        ->orWhereHas('chartType', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$query}%"));
                });
            })
            ->when($typeId !== '', fn ($q) => $q->where('chart_type_id', $typeId))
            ->orderBy('code')
            ->limit(50)
            ->get()
            ->map(fn (Chart $chart) => [
                'id' => $chart->id,
                'code' => $chart->code,
                'name' => $chart->name,
                'type' => $chart->chartType->name ?? '',
            ]);

        return response()->json($charts);
    }

    /**
     * Link the data object to the posted sales invoice of the selected company.
     *
     * The sales invoice the note corrects is posted with the note; the form
     * posts the invoice's id and the reference number is taken from the
     * invoice itself, so the two cannot disagree.
     *
     * @param  ManualSalesAdjustmentData  $data  The validated header data; the linked invoice is set on it.
     *
     * @throws ValidationException When no matching posted invoice exists for the company.
     */
    private function linkInvoice(ManualSalesAdjustmentData $data): void
    {
        $linkedInvoice = SalesInvoice::query()
            ->where('status', DocumentStatus::POSTED)
            ->where('company_id', $data->companyId)
            ->whereKey($data->salesInvoiceId)
            ->first();

        if ($linkedInvoice === null) {
            throw ValidationException::withMessages([
                'sales_invoice_id' => __('adjustments.validation.linked_invoice_invalid'),
            ]);
        }

        $companyName = filled($data->companyId)
            ? Company::query()->whereKey($data->companyId)->value('name')
            : null;

        $data->linkInvoice($linkedInvoice, is_string($companyName) ? $companyName : null);
    }

    /**
     * The invoice to preselect in the linked-invoice select, if any.
     *
     * @param  mixed  $id  The submitted or stored sales invoice id.
     */
    private function selectedInvoice(mixed $id): ?SalesInvoice
    {
        return is_string($id) && $id !== '' ? SalesInvoice::query()->find($id) : null;
    }

    /**
     * Validate and normalize the submitted chart lines.
     *
     * Every kept line names exactly one side — a debit or a credit, never both —
     * and resolves to a real chart account. The sides must balance, so the entry
     * leaves this method already double-entry clean: the lines ready for storage
     * and the balanced total alongside them. Each line carries the header's
     * subtype, so the ledgers name the note the same way the document does.
     *
     * @param  array<int, array<string, mixed>>  $lineInputs  The raw submitted lines.
     * @param  LedgerSubType  $subtype  The header subtype the lines are posted under.
     * @return array{lines: list<array<string, mixed>>, total: string} The normalized lines and the balanced total.
     *
     * @throws ValidationException When a line is missing, one-sided rules are broken, or the entry is unbalanced.
     */
    private function prepareLines(array $lineInputs, LedgerSubType $subtype): array
    {
        /** @var array<int, array<string, mixed>> $rawLines */
        $rawLines = collect($lineInputs)
            ->filter(function (mixed $line): bool {
                $debitAmount = $line['debit_amount'] ?? null;
                $creditAmount = $line['credit_amount'] ?? null;

                return filled($line['chart_id'] ?? null)
                    || (is_numeric($debitAmount) && (float) $debitAmount > 0)
                    || (is_numeric($creditAmount) && (float) $creditAmount > 0);
            })
            ->all();

        if ($rawLines === []) {
            throw ValidationException::withMessages([
                'lines' => __('manual_sales_adjustments.validation.lines_required'),
            ]);
        }

        $chartIds = collect($rawLines)->pluck('chart_id')->unique();
        $charts = Chart::query()
            ->with('chartType')
            ->whereIn('id', $chartIds)
            ->get()
            ->keyBy('id');

        /** @var string|null $currencyId */
        $currencyId = Setting::query()->value('currency_id')
            ?? Currency::query()->where('is_default', true)->value('id');

        $prepared = [];

        /** @var array<string, mixed> $line */
        foreach ($rawLines as $index => $line) {
            $chartId = $line['chart_id'] ?? null;
            $chart = is_int($chartId) || is_string($chartId) ? $charts->get($chartId) : null;
            if ($chart === null) {
                throw ValidationException::withMessages([
                    "lines.$index.chart_id" => __('manual_sales_adjustments.validation.chart_not_found'),
                ]);
            }

            $debitAmount = $line['debit_amount'] ?? null;
            $creditAmount = $line['credit_amount'] ?? null;
            $hasDebit = is_numeric($debitAmount) && (float) $debitAmount > 0;
            $hasCredit = is_numeric($creditAmount) && (float) $creditAmount > 0;

            if ($hasDebit === $hasCredit) {
                throw ValidationException::withMessages([
                    "lines.$index.debit_amount" => __('manual_sales_adjustments.validation.one_side_only'),
                ]);
            }

            $prepared[] = [
                'chart_id' => $chart->id,
                'company_id' => null,
                'currency_id' => $currencyId,
                'type' => LedgerType::INVOICE->value,
                'subtype' => $subtype->value,
                'chart_code' => $chart->code,
                'chart_name' => $chart->name,
                'company_name' => null,
                'description' => null,
                'line_description' => $line['description'] ?? null,
                'debit_amount' => $hasDebit ? $debitAmount : 0,
                'credit_amount' => $hasCredit ? $creditAmount : 0,
            ];
        }

        /** @var numeric-string $totalDebit */
        $totalDebit = collect($prepared)->reduce(
            /** @param numeric-string $carry */
            function (string $carry, array $line): string {
                $amount = is_numeric($line['debit_amount']) ? (string) $line['debit_amount'] : '0';

                return bcadd($carry, $amount, 2);
            },
            '0.00'
        );

        /** @var numeric-string $totalCredit */
        $totalCredit = collect($prepared)->reduce(
            /** @param numeric-string $carry */
            function (string $carry, array $line): string {
                $amount = is_numeric($line['credit_amount']) ? (string) $line['credit_amount'] : '0';

                return bcadd($carry, $amount, 2);
            },
            '0.00'
        );

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw ValidationException::withMessages([
                'lines' => __('manual_sales_adjustments.validation.unbalanced_entry'),
            ]);
        }

        return ['lines' => $prepared, 'total' => $totalDebit];
    }
}
