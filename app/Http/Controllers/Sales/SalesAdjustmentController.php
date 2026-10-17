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
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Adjustment\StoreAdjustmentRequest;
use App\Http\Requests\Sales\Adjustment\UpdateAdjustmentRequest;
use App\Models\ChartOfAccount\Chart;
use App\Models\Crm\ArLedger;
use App\Models\Crm\Company;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryChart;
use App\Models\Finance\Ledger;
use App\Models\Sales\SalesAdjustment;
use App\Models\Sales\SalesAdjustmentChart;
use App\Models\Sales\SalesInvoice;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Sales\SalesAdjustmentData;
use App\Support\Posting\GeneralLedgerData;
use App\Support\Posting\InvoiceLedgerPoster;
use App\Support\Traits\AttributesDocuments;
use App\Traits\GeneratesDocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class AdjustmentController
 *
 * Handles linked debit-note and credit-note ledger adjustments.
 */
class SalesAdjustmentController extends Controller
{
    use AttributesDocuments, GeneratesDocumentNumber;

    /**
     * The poster that writes a document's double entry to every ledger it
     * belongs in.
     *
     * Injected rather than resolved through the container at the point of use, so
     * what this controller depends on is declared where it can be read and so a
     * test can hand it a poster of its own.
     */
    public function __construct(private readonly InvoiceLedgerPoster $poster) {}

    /**
     * Display a listing of the resource.
     * Display one adjustment row per document number.
     *
     * @param  Request  $request  The request containing list filters.
     * @return View The rendered adjustment index view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        // A note is one row in the notes table, where each note is already one
        // row, so there is nothing to group by.
        $adjustments = SalesAdjustment::query()
            ->where('status', LedgerStatus::PENDING)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%");
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        // A note is posted or it is not; the flag comes from its own status
        // rather than from asking whether a ledger row happens to exist.
        $adjustments->getCollection()->each(function (SalesAdjustment $entry): void {
            $entry->setAttribute('is_posted', $entry->status === LedgerStatus::POSTED);
        });

        return view('sales.adjustments.index', compact(
            'adjustments',
            'search',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * Show the form for creating a linked adjustment entry.
     *
     * @return View The rendered adjustment creation form.
     */
    public function create(): View
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS, FileAction::ADD);

        /** @var Collection<int, Company> $companies */
        $companies = Company::query()->where('is_debtor', true)->orderBy('name')->get();
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->with('chartType')->orderBy('code')->get();

        return view(
            'sales.adjustments.create',
            compact('companies', 'charts') + ['postingDateMin' => null, 'postingDateMax' => null]
        );
    }

    /**
     * Return searchable sales invoices for the mandatory adjustment reference.
     *
     * @param  Request  $request  The request containing optional search text.
     * @return JsonResponse Matching invoice options for the combobox.
     */
    public function invoiceOptions(Request $request): JsonResponse
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS, FileAction::ADD);

        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('is_debtor', true)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim(is_string($validated['search'] ?? null) ? (string) $validated['search'] : '');

        $results = SalesInvoice::query()
            ->where('status', LedgerStatus::POSTED)
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
                    'value' => $invoice->document_number,
                    'text' => "$invoice->document_number$company - $invoice->date - (RM $amount)$notes",
                ];
            })
            ->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Display the specified resource.
     * Display one debit note or credit note and its PDF designs.
     *
     * @param  string  $documentNumber  The adjustment document number.
     * @return View The rendered adjustment detail view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS);

        /** @var SalesAdjustment|null $adjustment */
        $adjustment = SalesAdjustment::query()
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($adjustment === null, 404);

        $adjustment->load(['charts', 'company']);
        $isPosted = $adjustment->status === LedgerStatus::POSTED;

        return view('sales.adjustments.show', [
            'documentNumber' => $documentNumber,
            'entries' => $adjustment->charts,
            'adjustment' => $adjustment,
            'isPosted' => $isPosted,
        ] + ['postingDateMin' => null, 'postingDateMax' => null]);
    }

    /**
     * Stream a debit note or credit note as a PDF.
     *
     * @param  string  $documentNumber  The adjustment document number.
     * @return Response The streamed adjustment PDF.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS);

        /** @var SalesAdjustment|null $adjustment */
        $adjustment = SalesAdjustment::query()
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($adjustment === null, 404);
        $adjustment->load(['charts', 'lines']);

        return Pdf::loadView('sales.adjustments.pdf', [
            'adjustment' => $adjustment,
            'entries' => $adjustment->charts,
            'firstLine' => $adjustment->lines->first(),
            'setting' => Setting::query()->first(),
        ])->setPaper('a4')->stream($documentNumber.'.pdf');
    }

    /**
     * Handle the incoming request to create a new resource.
     * Store a balanced debit-note or credit-note ledger pair.
     *
     * @param  StoreAdjustmentRequest  $request  The request containing adjustment data.
     * @return RedirectResponse Redirect response to the adjustment detail page.
     *
     * @throws ValidationException If validation or linked-invoice checks fail.
     * @throws Throwable
     */
    public function store(StoreAdjustmentRequest $request): RedirectResponse
    {
        $data = SalesAdjustmentData::from($request->validated());

        if ($data->subtype === null) {
            throw ValidationException::withMessages([
                'subtype' => __('adjustments.validation.subtype_required'),
            ]);
        }

        $linkedInvoice = SalesInvoice::query()
            ->where('status', LedgerStatus::POSTED)
            ->where('company_id', $data->companyId)
            ->where('document_number', $data->referenceNumber)
            ->first();

        if ($linkedInvoice === null) {
            throw ValidationException::withMessages([
                'reference_number' => __('common.errors.linked_invoice_invalid'),
            ]);
        }

        $debitChart = Chart::query()->with('chartType')->whereKey($data->debitId)->firstOrFail();
        $creditChart = Chart::query()->with('chartType')->whereKey($data->creditId)->firstOrFail();
        $this->validateAccounts($data->subtype->value, $debitChart, $creditChart);

        $debitAccount = $this->accountSnapshot($debitChart, 'debit_id');
        $creditAccount = $this->accountSnapshot($creditChart, 'credit_id');
        $companyName = $linkedInvoice->company_name
            ?? Company::query()->whereKey($data->companyId)->value('name');
        $totalAmount = bcmul(DecimalService::normalize((string) $data->quantity), DecimalService::normalize((string) $data->unitPrice), 2);
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'ledger_attachments')
            : null;
        $documentNumber = $this->generateDocumentNumber(
            $data->subtype
        );

        DB::transaction(function () use (
            $creditAccount,
            $creditChart,
            $data,
            $debitAccount,
            $debitChart,
            $documentNumber,
            $linkedInvoice,
            $totalAmount,
            $attachmentPath
        ): void {
            $note = SalesAdjustment::query()->create([
                'document_number' => $documentNumber,
                'subtype' => $data->subtype,
                'reference_number' => $data->referenceNumber,
                'company_id' => $data->companyId,
                'contact_id' => null,
                'company_name' => $linkedInvoice->company_name,
                'contact_name' => null,
                'date' => $data->date?->format('Y-m-d'),
                'status' => LedgerStatus::PENDING,
                'attachment_path' => $attachmentPath,
                'subtotal' => $totalAmount,
                'total_amount' => $totalAmount,
                'notes' => $data->notes,
                'description' => $data->notes,
            ]);

            $note->lines()->create([
                'quantity' => $data->quantity,
                'unit_price' => $data->unitPrice,
                'subtotal_amount' => $totalAmount,
                'line_total_amount' => $totalAmount,
                'total_amount' => $totalAmount,
                'description' => $data->notes,
            ]);

            $note->lines()->create([
                'quantity' => $data->quantity,
                'unit_price' => $data->unitPrice,
                'subtotal_amount' => $totalAmount,
                'line_total_amount' => $totalAmount,
                'total_amount' => $totalAmount,
                'description' => $data->notes,
            ]);

            $note->charts()->create([
                'debit_id' => $debitChart->id,
                'debit_acc' => $debitAccount['code'],
                'debit_acc_desc' => $debitAccount['description'],
                'document_number' => $documentNumber,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => $totalAmount,
                'credit_amount' => '0.00',
                'total_amount' => $totalAmount,
            ]);

            $note->charts()->create([
                'credit_id' => $creditChart->id,
                'credit_acc' => $creditAccount['code'],
                'credit_acc_desc' => $creditAccount['description'],
                'document_number' => $documentNumber,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => '0.00',
                'credit_amount' => $totalAmount,
                'total_amount' => $totalAmount,
            ]);
        });

        return redirect()
            ->route('sales.adjustments.show', $documentNumber)
            ->with(
                'success',
                FlashMessage::created(FileResources::SALES_ADJUSTMENTS)
            );
    }

    /**
     * Update an existing resource by its object.
     * Update one unposted debit note or credit note.
     *
     * @param  UpdateAdjustmentRequest  $request  The request containing adjustment data.
     * @param  string  $documentNumber  The adjustment document number.
     * @return RedirectResponse Redirect response to the adjustment detail page.
     *
     * @throws Throwable
     */
    public function update(UpdateAdjustmentRequest $request, string $documentNumber): RedirectResponse
    {
        $data = SalesAdjustmentData::from($request->validated());

        if ($data->subtype === null) {
            throw ValidationException::withMessages([
                'subtype' => __('adjustments.validation.subtype_required'),
            ]);
        }

        $existingNote = SalesAdjustment::query()
            ->where('document_number', $documentNumber)
            ->first();
        abort_if($existingNote === null, 404);
        abort_if($existingNote->status !== LedgerStatus::PENDING, 409);
        abort_if(
            Ledger::query()->withTrashed()->where('document_number', $documentNumber)->exists(),
            409
        );

        $linkedInvoice = SalesInvoice::query()
            ->where('status', LedgerStatus::POSTED)
            ->where('company_id', $data->companyId)
            ->where('document_number', $data->referenceNumber)
            ->first();

        if ($linkedInvoice === null) {
            throw ValidationException::withMessages([
                'reference_number' => __('common.errors.linked_invoice_invalid'),
            ]);
        }

        $debitChart = Chart::query()->with('chartType')->whereKey($data->debitId)->firstOrFail();
        $creditChart = Chart::query()->with('chartType')->whereKey($data->creditId)->firstOrFail();
        $this->validateAccounts($data->subtype->value, $debitChart, $creditChart);

        $debitAccount = $this->accountSnapshot($debitChart, 'debit_id');
        $creditAccount = $this->accountSnapshot($creditChart, 'credit_id');
        $companyName = $linkedInvoice->company_name
            ?? Company::query()->whereKey($data->companyId)->value('name');
        $totalAmount = bcmul(DecimalService::normalize((string) $data->quantity), DecimalService::normalize((string) $data->unitPrice), 2);
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'ledger_attachments')
            : $existingNote->attachment_path;

        $entry = [
            'status' => LedgerStatus::PENDING->value,
            'company_id' => $data->companyId,
            'company_name' => $companyName,
            'product_id' => null,
            'product_name' => null,
            'contact_id' => null,
            'type' => LedgerType::INVOICE->value,
            'subtype' => $data->subtype->value,
            'document_number' => $documentNumber,
            'reference_number' => $data->referenceNumber,
            'date' => $data->date,
            'quantity' => $data->quantity,
            'unit_price' => $data->unitPrice,
            'total_amount' => $totalAmount,
            'attachment_path' => $attachmentPath,
            'notes' => $data->notes,
            'description' => $data->notes,
        ];

        DB::transaction(function () use (
            $creditAccount,
            $creditChart,
            $debitAccount,
            $debitChart,
            $entry,
            $documentNumber,
            $totalAmount
        ): void {
            /** @var Collection<int, ArLedger> $lockedEntries */
            /** @var Collection<int, SalesAdjustment> $lockedEntries */
            $lockedEntries = SalesAdjustment::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()->get();
            abort_if($lockedEntries->isEmpty(), 404);
            abort_if($lockedEntries->contains(fn (SalesAdjustment $row): bool => $row->status !== LedgerStatus::PENDING), 409);
            abort_if(Ledger::query()->withTrashed()->where('document_number', $documentNumber)->exists(), 409);

            // The lines are replaced rather than amended: a note carries one
            // amount across two accounts, so editing one side without the other
            // is the change that would leave it unbalanceable.
            $lockedEntries->each(function (SalesAdjustment $note): void {
                $note->lines()->delete();
                $note->charts()->delete();
            });
            SalesAdjustment::query()->whereKey($lockedEntries->modelKeys())->forceDelete();

            $note = SalesAdjustment::query()->create([
                'document_number' => $documentNumber,
                'subtype' => $entry['subtype'],
                'reference_number' => $entry['reference_number'],
                'company_id' => $entry['company_id'],
                'contact_id' => null,
                'company_name' => $entry['company_name'],
                'contact_name' => null,
                'date' => $entry['date'],
                'status' => LedgerStatus::PENDING,
                'attachment_path' => $entry['attachment_path'],
                'subtotal' => $totalAmount,
                'total_amount' => $totalAmount,
                'notes' => $entry['notes'],
                'description' => $entry['description'],
            ]);

            $note->lines()->create([
                'quantity' => $entry['quantity'],
                'unit_price' => $entry['unit_price'],
                'subtotal_amount' => $totalAmount,
                'line_total_amount' => $totalAmount,
                'total_amount' => $totalAmount,
                'description' => $entry['description'],
            ]);

            $note->lines()->create([
                'quantity' => $entry['quantity'],
                'unit_price' => $entry['unit_price'],
                'subtotal_amount' => $totalAmount,
                'line_total_amount' => $totalAmount,
                'total_amount' => $totalAmount,
                'description' => $entry['description'],
            ]);

            $note->charts()->create([
                'debit_id' => $debitChart->id,
                'debit_acc' => $debitAccount['code'],
                'debit_acc_desc' => $debitAccount['description'],
                'document_number' => $documentNumber,
                'reference_number' => $entry['reference_number'],
                'date' => $entry['date'],
                'description' => $entry['description'],
                'debit_amount' => $totalAmount,
                'credit_amount' => '0.00',
                'total_amount' => $totalAmount,
            ]);

            $note->charts()->create([
                'credit_id' => $creditChart->id,
                'credit_acc' => $creditAccount['code'],
                'credit_acc_desc' => $creditAccount['description'],
                'document_number' => $documentNumber,
                'reference_number' => $entry['reference_number'],
                'date' => $entry['date'],
                'description' => $entry['description'],
                'debit_amount' => '0.00',
                'credit_amount' => $totalAmount,
                'total_amount' => $totalAmount,
            ]);
        });

        return redirect()
            ->route('sales.adjustments.show', $documentNumber)
            ->with(
                'success',
                FlashMessage::updated(FileResources::SALES_ADJUSTMENTS)
            );
    }

    /**
     * Approve an adjustment and post its client-ledger rows to the General Ledger.
     *
     * @param  string  $documentNumber  The adjustment document number.
     * @return RedirectResponse Redirect response to the adjustment detail page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS, FileAction::APPROVE);

        DB::transaction(function () use ($documentNumber): void {
            /** @var SalesAdjustment|null $adjustment */
            $adjustment = SalesAdjustment::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($adjustment === null, 404);
            abort_if($adjustment->status === LedgerStatus::POSTED, 409);

            /** @var Collection<int, SalesAdjustmentChart> $charts */
            $charts = $adjustment->charts()->get();

            abort_if($charts->isEmpty(), 404);

            /** @var \Illuminate\Support\Collection<string, string> $accountDescriptions */
            $accountDescriptions = Chart::query()
                ->whereIn('id', $charts->flatMap(fn (SalesAdjustmentChart $chart): array => [
                    $chart->debit_id,
                    $chart->credit_id,
                ])->filter()->unique())
                ->get()
                ->reduce(function (\Illuminate\Support\Collection $carry, Chart $chart): \Illuminate\Support\Collection {
                    return $carry->put((string) $chart->id, (string) $chart->name);
                }, collect());

            /** @var list<GeneralLedgerData> $generalRows */
            $generalRows = [];
            foreach ($charts as $chart) {
                $generalRows[] = GeneralLedgerData::fromSalesAdjustmentLine($adjustment, $chart, $accountDescriptions);
            }

            $this->poster->post(
                $adjustment,
                $adjustment->document_number,
                $adjustment->date,
                LedgerType::INVOICE,
                $adjustment->subtype,
                $generalRows,
                companyId: $adjustment->company_id,
                contactId: $adjustment->contact_id,
                companyName: $adjustment->company_name,
                referenceNumber: $adjustment->reference_number,
            );

            $adjustment->update(['status' => LedgerStatus::POSTED]);

            $this->attributeApproved($adjustment);
        });

        return redirect()
            ->route('sales.adjustments.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::SALES_ADJUSTMENTS));
    }

    /**
     * Reverse a posted adjustment by creating a new reversing journal entry.
     *
     * @param  Request  $request  The HTTP request instance containing the reversal date and reason.
     * @param  string  $documentNumber  The adjustment document number.
     * @return RedirectResponse Redirects to the adjustment detail page.
     *
     * @throws Throwable
     */
    public function reverse(Request $request, string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS, FileAction::APPROVE);

        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($documentNumber, $validated): void {
            $adjustment = SalesAdjustment::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($adjustment === null, 404);
            abort_if($adjustment->reversal_journal_id !== null, 409, __('journal_entries.reversal_already_exists'));
            abort_if($adjustment->undone_at !== null, 409, __('journal_entries.reversal_already_exists'));

            $postedLedgers = Ledger::query()
                ->where('document_number', $documentNumber)
                ->where('status', LedgerStatus::POSTED)
                ->lockForUpdate()
                ->get();

            abort_if($postedLedgers->isEmpty(), 404);

            $totalDebitSum = $postedLedgers->sum('credit_amount');
            $totalCreditSum = $postedLedgers->sum('debit_amount');
            $totalDebit = number_format(is_numeric($totalDebitSum) ? (float) $totalDebitSum : 0.0, 2, '.', '');
            $totalCredit = number_format(is_numeric($totalCreditSum) ? (float) $totalCreditSum : 0.0, 2, '.', '');

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

            $adjustment->update(['reversal_journal_id' => $reversalEntry->id]);
            $this->attributeUndone($adjustment);
        });

        return redirect()
            ->route('sales.adjustments.show', $documentNumber)
            ->with('success', __('journal_entries.reversal_success'));
    }

    /**
     * Approve multiple pending sales adjustments.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers to approve.
     * @return RedirectResponse Redirects to the sales adjustments history page.
     *
     * @throws Throwable
     */
    public function batchApprove(Request $request): RedirectResponse
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS, FileAction::APPROVE);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        $count = 0;

        DB::transaction(function () use ($request, &$count): void {
            $documentNumbers = $request->input('document_numbers');
            foreach ((is_array($documentNumbers) ? $documentNumbers : []) as $documentNumber) {
                $documentNumber = is_string($documentNumber) ? $documentNumber : '';
                /** @var SalesAdjustment|null $adjustment */
                $adjustment = SalesAdjustment::query()
                    ->where('document_number', $documentNumber)
                    ->lockForUpdate()
                    ->first();

                if ($adjustment === null) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.no_entries', ['number' => $documentNumber]),
                    ]);
                }

                if ($adjustment->status !== LedgerStatus::PENDING) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.not_pending', ['number' => $documentNumber]),
                    ]);
                }

                if (Ledger::query()->withTrashed()->where('document_number', $documentNumber)->exists()) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.already_posted', ['number' => $documentNumber]),
                    ]);
                }

                /** @var Collection<int, SalesAdjustmentChart> $charts */
                $charts = $adjustment->charts()->get();

                if ($charts->isEmpty()) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.no_lines', ['number' => $documentNumber]),
                    ]);
                }

                $linkedInvoice = SalesInvoice::query()
                    ->where('document_number', $adjustment->reference_number)
                    ->where('company_id', $adjustment->company_id)
                    ->where('status', LedgerStatus::POSTED)
                    ->lockForUpdate()
                    ->first();

                if ($linkedInvoice === null) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.no_linked_invoice', ['number' => $documentNumber]),
                    ]);
                }

                $totalDebit = $charts->reduce(
                    fn (string $total, SalesAdjustmentChart $chart): string => bcadd($total, $chart->debit_amount, 2),
                    '0.00'
                );
                $totalCredit = $charts->reduce(
                    fn (string $total, SalesAdjustmentChart $chart): string => bcadd($total, $chart->credit_amount, 2),
                    '0.00'
                );

                if (bccomp($totalDebit, '0.00', 2) <= 0) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.non_positive', ['number' => $documentNumber]),
                    ]);
                }

                if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.unbalanced', ['number' => $documentNumber]),
                    ]);
                }

                /** @var \Illuminate\Support\Collection<string, string> $accountDescriptions */
                $accountDescriptions = Chart::query()
                    ->whereIn('id', $charts->flatMap(fn (SalesAdjustmentChart $chart): array => [
                        $chart->debit_id,
                        $chart->credit_id,
                    ])->filter()->unique())
                    ->get()
                    ->reduce(function (\Illuminate\Support\Collection $carry, Chart $chart): \Illuminate\Support\Collection {
                        return $carry->put((string) $chart->id, (string) $chart->name);
                    }, collect());

                /** @var list<GeneralLedgerData> $generalRows */
                $generalRows = [];
                foreach ($charts as $chart) {
                    $generalRows[] = GeneralLedgerData::fromSalesAdjustmentLine($adjustment, $chart, $accountDescriptions);
                }

                $this->poster->post(
                    $adjustment,
                    $adjustment->document_number,
                    $adjustment->date,
                    LedgerType::INVOICE,
                    $adjustment->subtype,
                    $generalRows,
                    companyId: $adjustment->company_id,
                    contactId: $adjustment->contact_id,
                    companyName: $adjustment->company_name,
                    referenceNumber: $adjustment->reference_number,
                );

                $adjustment->update(['status' => LedgerStatus::POSTED]);
                $this->attributeApproved($adjustment);

                $count++;
            }
        });

        return redirect()
            ->route('sales.adjustments.history.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending sales adjustments.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers to cancel.
     * @return RedirectResponse Redirects to the sales adjustments index page.
     */
    public function batchCancel(Request $request): RedirectResponse
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS, FileAction::CANCEL);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        $documentNumbers = is_array($request->input('document_numbers')) ? $request->input('document_numbers') : [];

        ArLedger::query()
            ->where('type', LedgerType::INVOICE)
            ->whereIn('subtype', [
                LedgerSubType::INCREASE_ADJUSTMENT->value,
                LedgerSubType::DECREASE_ADJUSTMENT->value,
            ])
            ->whereIn('document_number', $documentNumbers)
            ->where('status', LedgerStatus::PENDING)
            ->update(['status' => LedgerStatus::CANCELLED]);

        /** @var Collection<int, SalesAdjustment> $adjustments */
        $adjustments = SalesAdjustment::query()
            ->whereIn('document_number', $documentNumbers)
            ->get();

        /** @var SalesAdjustment $adjustment */
        foreach ($adjustments as $adjustment) {
            $this->attributeCancelled($adjustment);
        }

        $affected = $adjustments->count();

        return redirect()
            ->route('sales.adjustments.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $affected]));
    }

    /**
     * Validate debit and credit roles for debit notes and credit notes.
     *
     * @param  string  $subtype  The selected adjustment subtype.
     * @param  Chart  $debitChart  The selected debit chart.
     * @param  Chart  $creditChart  The selected credit chart.
     *
     * @throws ValidationException If either account is invalid.
     */
    private function validateAccounts(string $subtype, Chart $debitChart, Chart $creditChart): void
    {
        $debitIsValid = false;
        $creditIsValid = false;

        if ($subtype === LedgerSubType::INCREASE_ADJUSTMENT->value) {
            $debitIsValid = $debitChart->chartType?->isAsset === true
                && ! $debitChart->isBank
                && $debitChart->isDebtor;
            $creditIsValid = $creditChart->chartType?->isIncome === true;
        }

        if ($subtype === LedgerSubType::DECREASE_ADJUSTMENT->value) {
            $debitIsValid = $debitChart->chartType?->isIncome === true;
            $creditIsValid = $creditChart->chartType?->isAsset === true
                && ! $creditChart->isBank
                && $creditChart->isDebtor;
        }

        if (! $debitIsValid) {
            throw ValidationException::withMessages([
                'debit_id' => __('ledgers.validation.adjustment_debit_account_invalid'),
            ]);
        }

        if (! $creditIsValid) {
            throw ValidationException::withMessages([
                'credit_id' => __('ledgers.validation.adjustment_credit_account_invalid'),
            ]);
        }
    }

    /**
     * Build immutable account history fields from a selected chart.
     *
     * @param  Chart  $chart  The selected chart.
     * @param  string  $attribute  The form field receiving validation errors.
     * @return array{code: string, description: string|null} Account snapshot values.
     *
     * @throws ValidationException If the chart has no account code.
     */
    private function accountSnapshot(Chart $chart, string $attribute): array
    {
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
}
