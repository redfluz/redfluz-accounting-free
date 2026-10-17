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

namespace App\Http\Controllers\Finance;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\JournalEntry\StoreJournalEntryRequest;
use App\Http\Requests\Finance\JournalEntry\UpdateJournalEntryRequest;
use App\Models\ChartOfAccount\Chart;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryChart;
use App\Models\Finance\Ledger;
use App\Models\System\Setting;
use App\Support\Data\Finance\JournalEntryData;
use App\Traits\GeneratesDocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class JournalEntryController
 *
 * Handles journal entry record viewing, creation, editing, and approval.
 */
class JournalEntryController extends Controller
{
    use GeneratesDocumentNumber;

    /**
     * Display a listing of the resource.
     * Display a paginated listing of journal entry records.
     *
     * @param  Request  $request  The incoming HTTP request instance containing query filters.
     * @return View The rendered journal entry index Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);

        /** @var string|null $search */
        $search = $request->input('search');

        /** @var string|null $startDate */
        $startDate = $request->input('start_date');

        /** @var string|null $endDate */
        $endDate = $request->input('end_date');

        $entries = JournalEntry::query()
            ->with(['charts', 'reversal'])
            ->where('status', LedgerStatus::PENDING)
            ->when(is_string($search) && $search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%")
                        ->orWhereHas('charts', fn ($iq) => $iq
                            ->where('debit_acc', 'like', "%$search%")
                            ->orWhere('credit_acc', 'like', "%$search%")
                            ->orWhere('debit_acc_desc', 'like', "%$search%")
                            ->orWhere('credit_acc_desc', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%"));
                });
            })
            ->when(is_string($startDate) && $startDate !== '', fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when(is_string($endDate) && $endDate !== '', fn ($q) => $q->whereDate('date', '<=', $endDate))
            ->latest('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('finance.journal-entries.index', compact('entries', 'search', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     * Show the form for creating a new journal entry.
     *
     * @return View The rendered journal entry creation form Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::ADD);

        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()
            ->whereNotNull('code')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $prefillItems = [];
        $prefillDate = today()->format('Y-m-d');
        $prefillReference = null;
        $prefillNotes = null;

        return view(
            'finance.journal-entries.create',
            compact('charts', 'prefillItems', 'prefillDate', 'prefillReference', 'prefillNotes')
                + ['postingDateMin' => null, 'postingDateMax' => null]
        );
    }

    /**
     * Handle the incoming request to create a new resource.
     * Store a newly created journal entry with 10 fixed line items into the database table.
     *
     * @param  StoreJournalEntryRequest  $request  The validated journal entry being created.
     * @return RedirectResponse Redirect response to the journal entry index route.
     *
     * @throws Throwable
     */
    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $data = JournalEntryData::from($request->validated());
        [$lines, $totalDebit, $totalCredit] = $this->prepareJournalLines($request->validated());

        DB::transaction(function () use ($data, $lines, $totalDebit, $totalCredit): void {
            /** @var JournalEntry $entry */
            $entry = JournalEntry::query()->create([
                'document_number' => $this->generateDocumentNumber(LedgerSubType::JOURNAL),
                'reference_number' => $data->referenceNumber,
                'date' => $data->date,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => LedgerStatus::PENDING,
                'notes' => $data->notes,
            ]);

            $now = now();
            $itemRows = collect($lines)
                ->map(fn (array $line, int $index): array => $line + [
                    'id' => (string) Str::uuid7(),
                    'journal_entry_id' => $entry->id,
                    'document_number' => $entry->document_number,
                    'reference_number' => $entry->reference_number ?: $entry->document_number,
                    'date' => $entry->date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();
            JournalEntryChart::query()->insert($itemRows);
        });

        return redirect()
            ->route('finance.journal-entries.index')
            ->with('success', FlashMessage::created(FileResources::JOURNAL_ENTRIES));
    }

    /**
     * Display the specified resource.
     * Display one journal entry and its line items.
     *
     * @param  JournalEntry  $journalEntry  The journal entry resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(JournalEntry $journalEntry): View
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);
        $journalEntry->load(['charts', 'reversal', 'reversalOf']);

        return view(
            'finance.journal-entries.show',
            compact('journalEntry') + ['postingDateMin' => null, 'postingDateMax' => null]
        );
    }

    /**
     * Stream one journal entry as a PDF document.
     *
     * @param  JournalEntry  $journalEntry  The journal entry resource instance.
     * @return Response Returns the HTTP response.
     */
    public function pdf(JournalEntry $journalEntry): Response
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);
        $journalEntry->load(['charts', 'reversal', 'reversalOf']);
        $setting = Setting::query()->first();

        return Pdf::loadView(
            'finance.journal-entries.pdf',
            compact('journalEntry', 'setting')
        )
            ->setPaper('a4')
            ->stream($journalEntry->document_number.'.pdf');
    }

    /**
     * Show the form for editing an open resource.
     * Show the form for editing an existing journal entry.
     *
     * @param  JournalEntry  $journalEntry  The journal entry instance to edit.
     * @return View The rendered edit view.
     */
    public function edit(JournalEntry $journalEntry): View
    {
        $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::UPDATE);
        $this->abortIfApproved($journalEntry);

        $journalEntry->load('charts');

        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()
            ->whereNotNull('code')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('finance.journal-entries.edit', compact('journalEntry', 'charts'));
    }

    /**
     * Update an existing resource by its object.
     * Update an existing journal entry record and its line items.
     *
     * @param  UpdateJournalEntryRequest  $request  The validated journal entry being saved.
     * @param  JournalEntry  $journalEntry  The journal entry instance to update.
     * @return RedirectResponse Redirect response back to the index view.
     *
     * @throws Throwable
     */
    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): RedirectResponse
    {
        $data = JournalEntryData::from($request->validated());
        [$lines, $totalDebit, $totalCredit] = $this->prepareJournalLines($request->validated());

        DB::transaction(function () use ($journalEntry, $data, $lines, $totalDebit, $totalCredit): void {
            $lockedEntry = JournalEntry::query()
                ->whereKey($journalEntry->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->abortIfApproved($lockedEntry);

            $lockedEntry->update([
                'reference_number' => $data->referenceNumber,
                'date' => $data->date,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'notes' => $data->notes,
            ]);

            JournalEntryChart::query()
                ->where('journal_entry_id', $lockedEntry->id)
                ->delete();

            $now = now();
            $itemRows = collect($lines)
                ->map(fn (array $line, int $index): array => $line + [
                    'id' => (string) Str::uuid7(),
                    'journal_entry_id' => $lockedEntry->id,
                    'document_number' => $lockedEntry->document_number,
                    'reference_number' => $lockedEntry->reference_number ?: $lockedEntry->document_number,
                    'date' => $lockedEntry->date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();
            JournalEntryChart::query()->insert($itemRows);
        });

        return redirect()
            ->route('finance.journal-entries.index')
            ->with('success', FlashMessage::updated(FileResources::JOURNAL_ENTRIES));
    }

    /**
     * Approve a pending journal entry and post its lines to the General Ledger.
     *
     * Each line is validated again during approval because pending journal data may
     * originate from an older application version, an import, or a direct database
     * correction. The posting transaction enforces these accounting rules:
     *
     * 1. Debit and credit cannot both be zero because a zero-value line has no
     *    financial effect and must not become part of the General Ledger history.
     * 2. Debit and credit cannot both contain positive amounts because one journal
     *    line represents only one side of the double-entry transaction.
     * 3. The selected account side must match the amount side: a debit account
     *    requires a debit amount, while a credit account requires a credit amount.
     *
     * Approval is atomic: either every valid line is posted and the entry becomes
     * posted, or the complete transaction is rolled back without changing the ledger.
     *
     * @param  JournalEntry  $journalEntry  The journal entry to approve.
     * @return RedirectResponse Redirect response to the index route.
     *
     * @throws Throwable
     * @throws Throwable
     */
    public function approve(JournalEntry $journalEntry): RedirectResponse
    {
        $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);

        DB::transaction(function () use ($journalEntry): void {
            $lockedEntry = JournalEntry::query()
                ->with('charts')
                ->whereKey($journalEntry->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->abortIfApproved($lockedEntry);

            $itemIds = $lockedEntry->charts->modelKeys();
            abort_if($itemIds === [], 422, __('journal_entries.lines_required'));

            foreach ($lockedEntry->charts as $index => $item) {
                $hasDebitAccount = $item->debit_id !== null;
                $hasCreditAccount = $item->credit_id !== null;
                $hasDebitAmount = bccomp((string) $item->debit_amount, '0.00', 2) === 1;
                $hasCreditAmount = bccomp((string) $item->credit_amount, '0.00', 2) === 1;

                if ($hasDebitAccount === $hasCreditAccount) {
                    throw ValidationException::withMessages([
                        'items.'.$index.'.chart_id' => __('journal_entries.account_required'),
                    ]);
                }

                if (! $hasDebitAmount && ! $hasCreditAmount) {
                    throw ValidationException::withMessages([
                        'items.'.$index.'.debit_amount' => __('journal_entries.amount_required'),
                    ]);
                }

                if ($hasDebitAmount && $hasCreditAmount) {
                    throw ValidationException::withMessages([
                        'items.'.$index.'.debit_amount' => __('journal_entries.one_side_only'),
                    ]);
                }

                if ($hasDebitAccount !== $hasDebitAmount || $hasCreditAccount !== $hasCreditAmount) {
                    throw ValidationException::withMessages([
                        'items.'.$index.'.chart_id' => __('journal_entries.account_amount_mismatch'),
                    ]);
                }
            }

            abort_if(
                Ledger::query()->withTrashed()->whereIn('id', $itemIds)->exists(),
                409,
                __('journal_entries.already_posted')
            );

            $now = now();
            $ledgerRows = $lockedEntry->charts
                ->values()
                ->map(fn (JournalEntryChart $item): array => [
                    'id' => $item->id,
                    'source_id' => $item->id,
                    'source_type' => null,
                    'company_id' => null,
                    'contact_id' => null,
                    'debit_id' => $item->debit_id,
                    'credit_id' => $item->credit_id,
                    'debit_acc' => $item->debit_acc,
                    'credit_acc' => $item->credit_acc,
                    'debit_acc_desc' => $item->debit_acc_desc,
                    'credit_acc_desc' => $item->credit_acc_desc,
                    'company_name' => null,
                    'document_number' => $item->document_number,
                    'reference_number' => $item->reference_number,
                    'type' => LedgerType::JOURNAL->value,
                    'subtype' => LedgerSubType::JOURNAL->value,
                    'date' => $item->date,
                    'document_date' => $item->date,
                    'debit_amount' => $item->debit_amount,
                    'credit_amount' => $item->credit_amount,
                    'total_amount' => $item->total_amount,
                    'status' => LedgerStatus::POSTED->value,
                    'notes' => $item->description,
                    'description' => $item->description,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ])
                ->all();

            Ledger::query()->insert($ledgerRows);

            $lockedEntry->update(['status' => LedgerStatus::POSTED]);
            $this->attributeApproved($lockedEntry);
        });

        return redirect()
            ->route('finance.journal-entries.history.index')
            ->with('success', __('journal_entries.approved_success'));
    }

    /**
     * Reverse a posted journal entry without deleting or modifying its history.
     *
     * Reversal is available only for an original posted entry. A pending entry must
     * be edited instead, a reversal entry cannot itself be undone through this action,
     * and an original entry can be reversed only once. The posted General Ledger rows
     * are used as the authoritative source. A new posted journal is created with every
     * debit and credit swapped, while the original journal and ledger rows remain intact.
     * The reversal date must belong to an open posting period and a reason is mandatory
     * so the audit history explains why the correction was made.
     *
     * @param  Request  $request  Request containing the reversal date and reason.
     * @param  JournalEntry  $journalEntry  Original posted journal entry to reverse.
     * @return RedirectResponse Redirect response to the original journal entry.
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function reverse(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);

        /** @var array{reversal_date: string, reason: string} $validated */
        $validated = $request->validate([
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($journalEntry, $validated): void {
            $lockedEntry = JournalEntry::query()
                ->with('charts')
                ->whereKey($journalEntry->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $lockedEntry->status !== LedgerStatus::POSTED,
                409,
                __('journal_entries.reversal_posted_only')
            );
            abort_if(
                $lockedEntry->reversal_of_id !== null,
                409,
                __('journal_entries.reversal_cannot_reverse')
            );
            abort_if(
                $lockedEntry->reversal()->exists(),
                409,
                __('journal_entries.reversal_already_exists')
            );

            $itemIds = $lockedEntry->charts->modelKeys();
            abort_if($itemIds === [], 422, __('journal_entries.lines_required'));

            /** @var Collection<string, Ledger> $postedLedgers */
            $postedLedgers = Ledger::query()
                ->whereIn('id', $itemIds)
                ->where('status', LedgerStatus::POSTED)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            abort_if(
                $postedLedgers->count() !== count($itemIds),
                409,
                __('journal_entries.reversal_ledger_mismatch')
            );

            $sumCredit = $postedLedgers->sum('credit_amount');
            $totalDebit = number_format(
                is_numeric($sumCredit) ? (float) $sumCredit : 0.0,
                2,
                '.',
                ''
            );
            $sumDebit = $postedLedgers->sum('debit_amount');
            $totalCredit = number_format(
                is_numeric($sumDebit) ? (float) $sumDebit : 0.0,
                2,
                '.',
                ''
            );

            abort_if(
                bccomp($totalDebit, '0.00', 2) !== 1 || bccomp($totalDebit, $totalCredit, 2) !== 0,
                422,
                __('journal_entries.unbalanced_entry_error')
            );

            $reversalEntry = JournalEntry::query()->create([
                'reversal_of_id' => $lockedEntry->id,
                'document_number' => $this->generateDocumentNumber(LedgerSubType::JOURNAL),
                'reference_number' => $lockedEntry->reference_number,
                'date' => $validated['reversal_date'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => LedgerStatus::POSTED,
                'notes' => $validated['reason'],
            ]);

            $now = now();
            $itemRows = collect($itemIds)
                ->values()
                ->map(function (string $itemId) use ($postedLedgers, $reversalEntry, $now): array {
                    /** @var Ledger $postedLedger */
                    $postedLedger = $postedLedgers->get($itemId);

                    return [
                        'id' => (string) Str::uuid7(),
                        'journal_entry_id' => $reversalEntry->id,
                        'debit_id' => $postedLedger->credit_id,
                        'credit_id' => $postedLedger->debit_id,
                        'debit_acc' => $postedLedger->credit_acc,
                        'credit_acc' => $postedLedger->debit_acc,
                        'debit_acc_desc' => $postedLedger->credit_acc_desc,
                        'credit_acc_desc' => $postedLedger->debit_acc_desc,
                        'document_number' => $reversalEntry->document_number,
                        'reference_number' => $reversalEntry->reference_number ?: $reversalEntry->document_number,
                        'date' => $reversalEntry->date,
                        'description' => __('journal_entries.reversal_line_description', [
                            'document_number' => $postedLedger->document_number,
                            'description' => $postedLedger->description ?? '-',
                        ]),
                        'debit_amount' => $postedLedger->credit_amount,
                        'credit_amount' => $postedLedger->debit_amount,
                        'total_amount' => $postedLedger->total_amount,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                });

            JournalEntryChart::query()->insert($itemRows->all());

            $sourceLedger = $postedLedgers->first();
            abort_if($sourceLedger === null, 422, __('journal_entries.reversal_source_missing'));
            $sourceLedgerType = $sourceLedger->type;
            $ledgerRows = $itemRows->map(fn (array $item): array => [
                'id' => $item['id'],
                'source_id' => $item['id'],
                'source_type' => null,
                'company_id' => null,
                'contact_id' => null,
                'debit_id' => $item['debit_id'],
                'credit_id' => $item['credit_id'],
                'debit_acc' => $item['debit_acc'],
                'credit_acc' => $item['credit_acc'],
                'debit_acc_desc' => $item['debit_acc_desc'],
                'credit_acc_desc' => $item['credit_acc_desc'],
                'company_name' => null,
                'document_number' => $item['document_number'],
                'reference_number' => $item['reference_number'],
                'type' => $sourceLedgerType->value,
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
            ])->all();

            Ledger::query()->insert($ledgerRows);
        });

        return redirect()
            ->route('finance.journal-entries.show', $journalEntry)
            ->with('success', __('journal_entries.reversal_success'));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  JournalEntry  $journalEntry  The journal entry instance to remove.
     * @return RedirectResponse Redirect response to the index route.
     *
     * @throws Throwable
     */
    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::REMOVE);

        DB::transaction(function () use ($journalEntry): void {
            $lockedEntry = JournalEntry::query()
                ->whereKey($journalEntry->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->abortIfApproved($lockedEntry);
            JournalEntryChart::query()
                ->where('journal_entry_id', $lockedEntry->id)
                ->delete();
            JournalEntry::query()->whereKey($lockedEntry->id)->delete();
        });

        return redirect()
            ->route('finance.journal-entries.index')
            ->with('success', FlashMessage::deleted(FileResources::JOURNAL_ENTRIES));
    }

    /**
     * Convert validated rows into ledger-compatible journal lines.
     *
     * @param  array<string, mixed>  $validated  The validated journal-entry data.
     * @return array{0: array<int, array<string, mixed>>, 1: string, 2: string}
     */
    private function prepareJournalLines(array $validated): array
    {
        /** @var Collection<string, Chart> $charts */
        $charts = Chart::query()
            ->whereNotNull('code')
            ->get(['id', 'code', 'name'])
            ->keyBy(fn (Chart $chart): string => (string) $chart->id);
        $lines = [];
        $totalDebit = '0.00';
        $totalCredit = '0.00';

        /** @var array<int, array<string, mixed>> $items */
        $items = $validated['items'];
        foreach ($items as $index => $item) {
            /** @var string $chartId */
            $chartId = $item['chart_id'] ?? '';
            $debitRaw = $item['debit_amount'] ?? 0;
            $creditRaw = $item['credit_amount'] ?? 0;
            $debit = number_format(is_numeric($debitRaw) ? (float) $debitRaw : 0.0, 2, '.', '');
            $credit = number_format(is_numeric($creditRaw) ? (float) $creditRaw : 0.0, 2, '.', '');
            $hasDebit = bccomp($debit, '0.00', 2) === 1;
            $hasCredit = bccomp($credit, '0.00', 2) === 1;

            if ($chartId === '' && ! $hasDebit && ! $hasCredit) {
                continue;
            }

            $chart = $charts->get($chartId);

            if (! $chart) {
                throw ValidationException::withMessages([
                    'items.'.$index.'.chart_id' => __('journal_entries.account_required'),
                ]);
            }

            if ($hasDebit === $hasCredit) {
                throw ValidationException::withMessages([
                    'items.'.$index.'.debit_amount' => __('journal_entries.one_side_only'),
                ]);
            }

            $amount = $hasDebit ? $debit : $credit;
            $lines[] = [
                'debit_id' => $hasDebit ? $chart->id : null,
                'credit_id' => $hasDebit ? null : $chart->id,
                'debit_acc' => $hasDebit ? $chart->code : null,
                'credit_acc' => $hasDebit ? null : $chart->code,
                'debit_acc_desc' => $hasDebit ? $chart->name : null,
                'credit_acc_desc' => $hasDebit ? null : $chart->name,
                'description' => $item['description'] ?? null,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'total_amount' => $amount,
            ];
            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalCredit = bcadd($totalCredit, $credit, 2);
        }

        if ($lines === [] || bccomp($totalDebit, '0.00', 2) !== 1) {
            throw ValidationException::withMessages([
                'items' => __('journal_entries.lines_required'),
            ]);
        }

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw ValidationException::withMessages([
                'total_debit' => __('journal_entries.unbalanced_entry_error'),
            ]);
        }

        return [$lines, $totalDebit, $totalCredit];
    }

    /**
     * Give every posted ledger row a stable line reference.
     *
     * @param  JournalEntry  $entry  The entry resource instance.
     * @param  int  $position  The position value.
     * @return string Returns the resulting string value.
     */
    /**
     * Approve multiple journal entries and post them to the general ledger.
     *
     * @throws Throwable
     */
    public function batchApprove(Request $request): RedirectResponse
    {
        $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);

        $request->validate([
            'entry_ids' => 'required|array|min:1',
            'entry_ids.*' => 'required|string|exists:journal_entries,id',
        ]);

        $count = 0;

        DB::transaction(function () use ($request, &$count): void {
            /** @var array<string> $entryIds */
            $entryIds = $request->input('entry_ids');
            foreach ($entryIds as $entryId) {
                $lockedEntry = JournalEntry::query()
                    ->with('charts')
                    ->whereKey($entryId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedEntry->status === LedgerStatus::POSTED) {
                    throw ValidationException::withMessages([
                        'entry_ids' => __('common.batch_rejected.already_posted', ['number' => $entryId]),
                    ]);
                }

                $itemIds = $lockedEntry->charts->modelKeys();
                if ($itemIds === []) {
                    throw ValidationException::withMessages([
                        'entry_ids' => __('common.batch_rejected.no_lines', ['number' => $entryId]),
                    ]);
                }

                if (Ledger::query()->withTrashed()->whereIn('id', $itemIds)->exists()) {
                    throw ValidationException::withMessages([
                        'entry_ids' => __('common.batch_rejected.already_posted', ['number' => $entryId]),
                    ]);
                }

                $now = now();
                $ledgerRows = $lockedEntry->charts
                    ->values()
                    ->map(fn (JournalEntryChart $item): array => [
                        'id' => $item->id,
                        'source_id' => $item->id,
                        'source_type' => null,
                        'company_id' => null,
                        'contact_id' => null,
                        'debit_id' => $item->debit_id,
                        'credit_id' => $item->credit_id,
                        'debit_acc' => $item->debit_acc,
                        'credit_acc' => $item->credit_acc,
                        'debit_acc_desc' => $item->debit_acc_desc,
                        'credit_acc_desc' => $item->credit_acc_desc,
                        'company_name' => null,
                        'document_number' => $item->document_number,
                        'reference_number' => $item->reference_number,
                        'type' => LedgerType::JOURNAL->value,
                        'subtype' => LedgerSubType::JOURNAL->value,
                        'date' => $item->date,
                        'document_date' => $item->date,
                        'debit_amount' => $item->debit_amount,
                        'credit_amount' => $item->credit_amount,
                        'total_amount' => $item->total_amount,
                        'status' => LedgerStatus::POSTED->value,
                        'notes' => $item->description,
                        'description' => $item->description,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ])
                    ->all();

                Ledger::query()->insert($ledgerRows);
                $lockedEntry->update(['status' => LedgerStatus::POSTED]);
                $this->attributeApproved($lockedEntry);

                $count++;
            }
        });

        return redirect()
            ->route('finance.journal-entries.history.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending journal entries.
     *
     * @param  Request  $request  The HTTP request instance containing entry IDs.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function batchCancel(Request $request): RedirectResponse
    {
        $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::CANCEL);

        $request->validate([
            'entry_ids' => 'required|array|min:1',
            'entry_ids.*' => 'required|string|exists:journal_entries,id',
        ]);

        /** @var array<string> $entryIds */
        $entryIds = $request->input('entry_ids');

        $count = 0;

        /** @var JournalEntry $entry */
        foreach (JournalEntry::query()->whereIn('id', $entryIds)->where('status', LedgerStatus::PENDING)->get() as $entry) {
            $entry->update(['status' => LedgerStatus::CANCELLED]);
            $this->attributeCancelled($entry);
            $count++;
        }

        return redirect()
            ->route('finance.journal-entries.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $count]));
    }

    /**
     * Prevent approved entries from being edited, removed, or approved twice.
     *
     * @param  JournalEntry  $journalEntry  The journal entry resource instance.
     * @return void Returns nothing.
     */
    private function abortIfApproved(JournalEntry $journalEntry): void
    {
        abort_if(
            $journalEntry->status === LedgerStatus::POSTED,
            409,
            __('journal_entries.approved_locked')
        );
    }
}
