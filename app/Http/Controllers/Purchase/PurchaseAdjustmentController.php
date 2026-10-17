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
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\Adjustment\StoreAdjustmentRequest;
use App\Http\Requests\Purchase\Adjustment\UpdateAdjustmentRequest;
use App\Models\ChartOfAccount\Chart;
use App\Models\Crm\Company;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryChart;
use App\Models\Finance\Ledger;
use App\Models\Purchase\PurchaseAdjustment;
use App\Models\Purchase\PurchaseAdjustmentLine;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Purchase\PurchaseAdjustmentData;
use App\Support\Posting\GeneralLedgerData;
use App\Support\Posting\InvoiceLedgerPoster;
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
class PurchaseAdjustmentController extends Controller
{
    use GeneratesDocumentNumber;

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
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS);

        $search = $request->string('search')->value();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();

        // Notes are read from the notes table, where each note is already one row.
        // The document is the table now rather than a grouping over a shared ledger,
        // so there is nothing to group by and no per-line column to MAX out.
        $adjustments = PurchaseAdjustment::query()
            ->where('status', LedgerStatus::PENDING)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%");
                });
            })
            ->when(
                $startDate !== '',
                fn ($query) => $query->whereDate('date', '>=', $startDate)
            )
            ->when(
                $endDate !== '',
                fn ($query) => $query->whereDate('date', '<=', $endDate)
            )
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        // A note is posted or it is not; the flag comes from its own status
        // rather than from asking whether a ledger row happens to exist.
        $adjustments->getCollection()->each(function (PurchaseAdjustment $entry): void {
            $entry->setAttribute('is_posted', $entry->status === LedgerStatus::POSTED);
        });

        return view('purchase.adjustments.index', compact(
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
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS, FileAction::ADD);

        /** @var Collection<int, Company> $companies */
        $companies = Company::query()->where('is_creditor', true)->orderBy('name')->get();
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->with('chartType')->orderBy('code')->get();

        return view(
            'purchase.adjustments.create',
            compact('companies', 'charts') + ['postingDateMin' => null, 'postingDateMax' => null]
        );
    }

    /**
     * Return searchable posted purchase invoices for the mandatory adjustment reference.
     *
     * @param  Request  $request  The request containing optional search text.
     * @return JsonResponse Matching invoice options for the combobox.
     */
    public function invoiceOptions(Request $request): JsonResponse
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS, FileAction::ADD);

        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('is_creditor', true)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim(is_string($validated['search'] ?? null) ? (string) $validated['search'] : '');

        // Invoices are read from the purchase invoice table, where each invoice is
        // already one row. The shared ledger table this used to read from held
        // them only because they had nowhere else to live.
        $results = PurchaseInvoice::query()
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
            ->map(function (PurchaseInvoice $invoice): array {
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
     * Display one debit note or credit note.
     *
     * @param  string  $documentNumber  The adjustment document number.
     * @return View The rendered adjustment detail view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS);

        $adjustment = $this->adjustment($documentNumber);
        $entries = $adjustment->lines;
        $isPosted = Ledger::query()->withTrashed()
            ->where('document_number', $documentNumber)
            ->exists();

        return view('purchase.adjustments.show', compact(
            'documentNumber',
            'entries',
            'adjustment',
            'isPosted'
        ) + ['postingDateMin' => null, 'postingDateMax' => null]);
    }

    /**
     * Stream a debit note or credit note using a purchase-invoice PDF design.
     *
     * @param  string  $documentNumber  The adjustment document number.
     * @return Response The streamed adjustment PDF.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS);

        $adjustment = $this->adjustment($documentNumber);
        $entries = $adjustment->lines;

        return Pdf::loadView('purchase.adjustments.pdf', [
            'adjustment' => $adjustment,
            'entries' => $entries,
            'setting' => Setting::query()->first(),
        ])->setPaper('a4')->stream($documentNumber.'.pdf');
    }

    /**
     * Handle the incoming request to create a new resource.
     * Store a balanced debit-note or credit-note ledger pair.
     *
     * @param  StoreAdjustmentRequest  $request  The validated request containing adjustment data.
     * @return RedirectResponse Redirect response to the adjustment detail page.
     *
     * @throws Throwable
     */
    public function store(StoreAdjustmentRequest $request): RedirectResponse
    {
        $data = PurchaseAdjustmentData::from($request->validated());

        $linkedInvoice = PurchaseInvoice::query()
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
        $this->validateAccounts($data->subtype ?? '', $debitChart, $creditChart);

        $debitAccount = $this->accountSnapshot($debitChart, 'debit_id');
        $creditAccount = $this->accountSnapshot($creditChart, 'credit_id');
        $companyName = $linkedInvoice->company_name
            ?? Company::query()->whereKey($data->companyId)->value('name');
        $totalAmount = bcmul(DecimalService::normalize((string) $data->quantity), DecimalService::normalize($data->unitPrice ?? ''), 2);
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'ledger_attachments')
            : null;
        $documentNumber = $this->generateDocumentNumber(
            LedgerSubType::from($data->subtype ?? '')
        );

        DB::transaction(function () use (
            $creditAccount,
            $creditChart,
            $debitAccount,
            $debitChart,
            $data,
            $documentNumber,
            $linkedInvoice,
            $totalAmount,
            $attachmentPath
        ): void {
            $adjustment = PurchaseAdjustment::query()->create([
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

            $adjustment->lines()->create([
                'debit_id' => $debitChart->id,
                'debit_acc' => $debitAccount['code'],
                'debit_acc_desc' => $debitAccount['description'],
                'quantity' => $data->quantity,
                'unit_price' => $data->unitPrice,
                'subtotal_amount' => $totalAmount,
                'line_total_amount' => $totalAmount,
                'debit_amount' => $totalAmount,
                'credit_amount' => '0.00',
                'total_amount' => $totalAmount,
                'description' => $data->notes,
            ]);

            $adjustment->lines()->create([
                'credit_id' => $creditChart->id,
                'credit_acc' => $creditAccount['code'],
                'credit_acc_desc' => $creditAccount['description'],
                'quantity' => $data->quantity,
                'unit_price' => $data->unitPrice,
                'subtotal_amount' => $totalAmount,
                'line_total_amount' => $totalAmount,
                'debit_amount' => '0.00',
                'credit_amount' => $totalAmount,
                'total_amount' => $totalAmount,
                'description' => $data->notes,
            ]);
        });

        return redirect()
            ->route('purchase.adjustments.show', $documentNumber)
            ->with(
                'success',
                FlashMessage::created(FileResources::PURCHASE_ADJUSTMENTS)
            );
    }

    /**
     * Update an existing resource by its object.
     * Update one unposted debit note or credit note.
     *
     * @param  UpdateAdjustmentRequest  $request  The validated request containing adjustment data.
     * @param  string  $documentNumber  The adjustment document number.
     * @return RedirectResponse Redirect response to the adjustment detail page.
     *
     * @throws Throwable
     */
    public function update(UpdateAdjustmentRequest $request, string $documentNumber): RedirectResponse
    {
        $existingEntry = PurchaseAdjustment::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if(
            Ledger::query()->withTrashed()->where('document_number', $documentNumber)->exists(),
            409
        );
        abort_if($existingEntry->isPosted(), 409);

        $data = PurchaseAdjustmentData::from($request->validated());

        $linkedInvoice = PurchaseInvoice::query()
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
        $this->validateAccounts($data->subtype ?? '', $debitChart, $creditChart);

        $debitAccount = $this->accountSnapshot($debitChart, 'debit_id');
        $creditAccount = $this->accountSnapshot($creditChart, 'credit_id');
        $companyName = $linkedInvoice->company_name
            ?? Company::query()->whereKey($data->companyId)->value('name');
        $totalAmount = bcmul(DecimalService::normalize((string) $data->quantity), DecimalService::normalize($data->unitPrice ?? ''), 2);
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'ledger_attachments')
            : $existingEntry->attachment_path;

        $entry = [
            'subtype' => $data->subtype,
            'company_id' => $data->companyId,
            'company_name' => $companyName,
            'contact_id' => null,
            'contact_name' => null,
            'reference_number' => $data->referenceNumber,
            'date' => $request->dateValue(),
            'quantity' => $data->quantity,
            'unit_price' => $data->unitPrice,
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
            /** @var Collection<int, PurchaseAdjustment> $lockedEntries */
            $lockedEntries = PurchaseAdjustment::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()->get();
            abort_if($lockedEntries->isEmpty(), 404);
            abort_if($lockedEntries->contains(fn (PurchaseAdjustment $row): bool => $row->status !== LedgerStatus::PENDING), 409);
            abort_if(Ledger::query()->withTrashed()->where('document_number', $documentNumber)->exists(), 409);

            // The lines are replaced rather than amended. A note carries one
            // amount across two accounts, so editing one side without the other
            // is the change that would leave it unbalanceable.
            $lockedEntries->each(function (PurchaseAdjustment $note): void {
                $note->lines()->delete();
            });
            PurchaseAdjustment::query()->whereKey($lockedEntries->modelKeys())->forceDelete();

            $adjustment = PurchaseAdjustment::query()->create([
                'document_number' => $documentNumber,
                'subtype' => $entry['subtype'],
                'reference_number' => $entry['reference_number'],
                'company_id' => $entry['company_id'],
                'contact_id' => $entry['contact_id'],
                'company_name' => $entry['company_name'],
                'contact_name' => $entry['contact_name'],
                'date' => $entry['date'],
                'status' => LedgerStatus::PENDING,
                'attachment_path' => $entry['attachment_path'],
                'subtotal' => $totalAmount,
                'total_amount' => $totalAmount,
                'notes' => $entry['notes'],
                'description' => $entry['description'],
            ]);

            $adjustment->lines()->create([
                'debit_id' => $debitChart->id,
                'debit_acc' => $debitAccount['code'],
                'debit_acc_desc' => $debitAccount['description'],
                'quantity' => $entry['quantity'],
                'unit_price' => $entry['unit_price'],
                'subtotal_amount' => $totalAmount,
                'line_total_amount' => $totalAmount,
                'debit_amount' => $totalAmount,
                'credit_amount' => '0.00',
                'total_amount' => $totalAmount,
                'description' => $entry['description'],
            ]);

            $adjustment->lines()->create([
                'credit_id' => $creditChart->id,
                'credit_acc' => $creditAccount['code'],
                'credit_acc_desc' => $creditAccount['description'],
                'quantity' => $entry['quantity'],
                'unit_price' => $entry['unit_price'],
                'subtotal_amount' => $totalAmount,
                'line_total_amount' => $totalAmount,
                'debit_amount' => '0.00',
                'credit_amount' => $totalAmount,
                'total_amount' => $totalAmount,
                'description' => $entry['description'],
            ]);
        });

        return redirect()
            ->route('purchase.adjustments.show', $documentNumber)
            ->with(
                'success',
                FlashMessage::updated(FileResources::PURCHASE_ADJUSTMENTS)
            );
    }

    /**
     * Approve an adjustment and post its client-ledger rows to the General Ledger.
     *
     * @param  string  $documentNumber  The adjustment document number.
     * @return RedirectResponse Redirect response to the adjustment history page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS, FileAction::APPROVE);

        DB::transaction(function () use ($documentNumber): void {
            $adjustment = PurchaseAdjustment::query()
                ->with('lines')
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($adjustment->status !== LedgerStatus::PENDING, 409);
            abort_if(
                Ledger::query()->withTrashed()->where('document_number', $documentNumber)->exists(),
                409
            );
            abort_if($adjustment->lines->isEmpty(), 422);

            PurchaseInvoice::query()
                ->where('document_number', $adjustment->reference_number)
                ->where('company_id', $adjustment->company_id)
                ->where('status', LedgerStatus::POSTED)
                ->lockForUpdate()->firstOrFail();

            $totalDebit = $adjustment->lines->reduce(
                fn (string $total, PurchaseAdjustmentLine $line): string => bcadd(
                    $total,
                    self::lineAmount($line->getAttribute('debit_amount')),
                    2
                ),
                '0.00'
            );
            $totalCredit = $adjustment->lines->reduce(
                fn (string $total, PurchaseAdjustmentLine $line): string => bcadd(
                    $total,
                    self::lineAmount($line->getAttribute('credit_amount')),
                    2
                ),
                '0.00'
            );

            if (bccomp($totalDebit, '0.00', 2) <= 0 || bccomp($totalDebit, $totalCredit, 2) !== 0) {
                throw ValidationException::withMessages([
                    'document_number' => __('journal_entries.unbalanced_entry_error'),
                ]);
            }

            $this->postAdjustmentEntries($adjustment);

            $adjustment->update(['status' => LedgerStatus::POSTED]);
        });

        return redirect()
            ->route('purchase.adjustments.history.index')
            ->with('success', FlashMessage::approved(FileResources::PURCHASE_ADJUSTMENTS));
    }

    /**
     * Post an adjustment's entries to the general and sub ledgers through the poster.
     *
     * An adjustment is a debit note or a credit note: its rows are built as full
     * general ledger rows from the client ledger lines and handed to the poster
     * with the legs left empty to be derived, so the row the document was
     * approved into and the leg a sub ledger was filed under cannot disagree.
     */
    private function postAdjustmentEntries(PurchaseAdjustment $adjustment): void
    {
        /** @var \Illuminate\Support\Collection<string, string> $accountDescriptions */
        $accountDescriptions = Chart::query()
            ->whereIn('id', $adjustment->lines->flatMap(
                fn (PurchaseAdjustmentLine $line): array => [
                    $line->getAttribute('debit_id'),
                    $line->getAttribute('credit_id'),
                ]
            )->filter()->unique())
            ->get()
            ->reduce(
                fn (\Illuminate\Support\Collection $carry, Chart $chart): \Illuminate\Support\Collection => $carry->put((string) $chart->id, (string) $chart->name),
                collect()
            );

        /** @var list<GeneralLedgerData> $generalRows */
        $generalRows = [];
        foreach ($adjustment->lines as $line) {
            foreach (GeneralLedgerData::fromPurchaseAdjustmentLine($adjustment, $line, $accountDescriptions) as $row) {
                $generalRows[] = $row;
            }
        }

        $this->poster->post(
            $adjustment,
            (string) $adjustment->document_number,
            $adjustment->date,
            LedgerType::INVOICE,
            $adjustment->subtype,
            $generalRows,
            companyId: $adjustment->company_id,
            contactId: $adjustment->contact_id,
            companyName: $adjustment->company_name,
            referenceNumber: $adjustment->reference_number,
        );
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
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS, FileAction::APPROVE);

        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($documentNumber, $validated): void {
            $adjustment = PurchaseAdjustment::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->first();

            abort_if($adjustment === null, 404);
            abort_if($adjustment->undone_at !== null, 409, __('journal_entries.reversal_already_exists'));

            $postedLedgers = Ledger::query()
                ->where('document_number', $documentNumber)
                ->where('status', LedgerStatus::POSTED)
                ->lockForUpdate()
                ->get();

            abort_if($postedLedgers->isEmpty(), 404);

            $sumCredit = $postedLedgers->sum('credit_amount');
            $sumDebit = $postedLedgers->sum('debit_amount');
            $totalDebit = number_format(is_numeric($sumCredit) ? (float) $sumCredit : 0.0, 2, '.', '');
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
            $adjustment->update([
                'undone_by' => $user?->id,
                'undone_name' => $user?->name,
                'undone_at' => now(),
            ]);
        });

        return redirect()
            ->route('purchase.adjustments.show', $documentNumber)
            ->with('success', __('journal_entries.reversal_success'));
    }

    /**
     * Approve multiple pending purchase adjustments.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function batchApprove(Request $request): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS, FileAction::APPROVE);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        $count = 0;

        DB::transaction(function () use ($request, &$count): void {
            /** @var array<int, string> $documentNumbers */
            $documentNumbers = $request->input('document_numbers', []);
            foreach ($documentNumbers as $documentNumber) {
                $note = PurchaseAdjustment::query()
                    ->with('lines')
                    ->where('document_number', $documentNumber)
                    ->lockForUpdate()
                    ->first();

                if ($note === null) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.no_entries', ['number' => $documentNumber]),
                    ]);
                }

                if ($note->status !== LedgerStatus::PENDING) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.not_pending', ['number' => $documentNumber]),
                    ]);
                }

                if (Ledger::query()->withTrashed()->where('document_number', $documentNumber)->exists()) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.already_posted', ['number' => $documentNumber]),
                    ]);
                }

                $linkedInvoice = PurchaseInvoice::query()
                    ->where('document_number', $note->reference_number)
                    ->where('company_id', $note->company_id)
                    ->where('status', LedgerStatus::POSTED)
                    ->lockForUpdate()
                    ->first();

                if ($linkedInvoice === null) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.no_linked_invoice', ['number' => $documentNumber]),
                    ]);
                }

                $totalDebit = $note->lines->reduce(
                    fn (string $total, PurchaseAdjustmentLine $line): string => bcadd($total, self::lineAmount($line->getAttribute('debit_amount')), 2),
                    '0.00'
                );
                $totalCredit = $note->lines->reduce(
                    fn (string $total, PurchaseAdjustmentLine $line): string => bcadd($total, self::lineAmount($line->getAttribute('credit_amount')), 2),
                    '0.00'
                );

                if (bccomp($totalDebit, '0.00', 2) <= 0 || bccomp($totalDebit, $totalCredit, 2) !== 0) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.unbalanced', ['number' => $documentNumber]),
                    ]);
                }

                if ($note->lines->isEmpty()) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.no_lines', ['number' => $documentNumber]),
                    ]);
                }

                $this->postAdjustmentEntries($note);
                $note->update(['status' => LedgerStatus::POSTED]);

                $count++;
            }
        });

        return redirect()
            ->route('purchase.adjustments.history.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending purchase adjustments.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function batchCancel(Request $request): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS, FileAction::CANCEL);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        $affected = PurchaseAdjustment::query()
            ->whereIn('document_number', $request->input('document_numbers', []))
            ->where('status', LedgerStatus::PENDING)
            ->update(['status' => LedgerStatus::CANCELLED]);

        return redirect()
            ->route('purchase.adjustments.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $affected]));
    }

    /**
     * Validate debit and credit roles for debit notes and credit notes.
     *
     * @param  string  $subtype  The selected adjustment subtype.
     * @param  Chart  $debitChart  The selected debit chart.
     * @param  Chart  $creditChart  The selected credit chart.
     * @return void Returns nothing.
     *
     * @throws ValidationException If either account is invalid.
     */
    private function validateAccounts(string $subtype, Chart $debitChart, Chart $creditChart): void
    {
        $payableDebit = $debitChart->chartType?->isLiability === true && $debitChart->isCreditor && ! $debitChart->isBank;
        $payableCredit = $creditChart->chartType?->isLiability === true && $creditChart->isCreditor && ! $creditChart->isBank;
        $purchaseDebit = ! $debitChart->isBank && ! $debitChart->isDebtor
            && ($debitChart->chartType?->isExpenses === true || $debitChart->chartType?->isAsset === true);
        $purchaseCredit = ! $creditChart->isBank && ! $creditChart->isDebtor
            && ($creditChart->chartType?->isExpenses === true || $creditChart->chartType?->isAsset === true);

        $debitIsValid = $subtype === LedgerSubType::PURCHASE_DEBIT_NOTE->value ? $purchaseDebit : $payableDebit;
        $creditIsValid = $subtype === LedgerSubType::PURCHASE_DEBIT_NOTE->value ? $payableCredit : $purchaseCredit;

        if (! $debitIsValid) {
            throw ValidationException::withMessages([
                'debit_id' => __('purchase_adjustments.validation.debit_account_invalid'),
            ]);
        }

        if (! $creditIsValid) {
            throw ValidationException::withMessages([
                'credit_id' => __('purchase_adjustments.validation.credit_account_invalid'),
            ]);
        }
    }

    /**
     * Retrieve the adjustment belonging to one document number.
     *
     * @param  string  $documentNumber  The adjustment document number.
     */
    private function adjustment(string $documentNumber): PurchaseAdjustment
    {
        return PurchaseAdjustment::query()
            ->with('lines')
            ->where('document_number', $documentNumber)
            ->firstOrFail();
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

    /**
     * A side amount carried on an adjustment line outside its declared columns.
     *
     * The debit and credit figures travel on the line as undeclared attributes
     * written at creation time, so they are read back through getAttribute with
     * the same numeric guard the posting layer uses rather than as properties.
     *
     * @param  mixed  $value  The raw attribute value.
     * @return numeric-string The exact decimal value, zero when not numeric.
     */
    private static function lineAmount(mixed $value): string
    {
        return is_string($value) && is_numeric($value) ? $value : '0.00';
    }
}
