<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

namespace App\Http\Controllers\Bank;

use App\Constants\FlashMessage;
use App\Enums\BankAccountKind;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\BankJournal\StoreBankJournalRequest;
use App\Http\Requests\Bank\BankJournal\UpdateBankJournalRequest;
use App\Models\Bank\Bank;
use App\Models\Bank\BankJournal;
use App\Models\Bank\CashAccount;
use App\Models\Bank\EWalletAccount;
use App\Models\ChartOfAccount\Chart;
use App\Services\DecimalService;
use App\Support\Posting\PaymentLedgerPoster;
use App\Support\Posting\PostingLeg;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Bank journals: money moved in or out of one money account.
 *
 * Bank journals are for first-time setup only (e.g. opening balances, initial
 * data migration). They are not intended for ongoing daily transactions. Regular
 * money movements come from posted documents: receipts, payment vouchers,
 * invoices, and other operational documents that write to the ledgers directly.
 *
 * A bank journal is a document with its own number, its own approval and its
 * own history. It is not a pair of rows that happen to share a document number,
 * and it is not a general journal entry: the general Journal is
 * ledgers-table-only by design and is never written from here. That distinction
 * is the whole reason this controller exists separately from receipts and
 * payment vouchers: each of those is a different document with different rules
 * about which accounts may face each other.
 *
 * What a bank journal writes on approval is decided by {@see PaymentLedgerPoster}
 * from the accounts it names, not from anything here. A journal into a drawer
 * and one into a bank account are the same document to this controller; the
 * money ledger each produces follows from the kind of the account the money
 * actually moved through.
 */
class BankJournalController extends Controller
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
    public function __construct(private readonly PaymentLedgerPoster $poster) {}

    /**
     * Display a listing of bank journals in one status.
     *
     * @param  Request  $request  The request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::BANK_JOURNALS);

        $statusInput = $request->input('status', LedgerStatus::PENDING->value);
        $status = is_string($statusInput) ? $statusInput : LedgerStatus::PENDING->value;

        $allowed = array_column(LedgerStatus::cases(), 'value');

        if (! in_array($status, $allowed, true)) {
            $status = LedgerStatus::PENDING->value;
        }

        $searchInput = $request->input('search', '');
        $search = is_string($searchInput) ? trim($searchInput) : '';

        $journals = BankJournal::query()
            ->with(['bankAccount', 'cashAccount', 'eWalletAccount', 'offsetChart'])
            ->where('status', $status)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('document_number', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            }))
            ->latest('document_date')
            ->paginate(static::PER_PAGE_GRID)
            ->withQueryString();

        return view('bank.bank-journals.index', compact('journals', 'search', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     *
     * @throws Throwable
     */
    public function create(): View
    {
        $this->allows(FileResources::BANK_JOURNALS, FileAction::ADD);

        // Only an account that can carry a money ledger row is offered, so the
        // form is not offered choices that would be refused on submit.
        return view('bank.bank-journals.create', [
            'accounts' => $this->moneyAccounts(),
            'charts' => $this->offsetCharts(),
            'documentNumber' => $this->generateDocumentNumber(LedgerSubType::BANK_JOURNAL),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $documentNumber  The document number of the bank journal.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::BANK_JOURNALS);

        $journal = BankJournal::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        $journal->load(['bankAccount', 'cashAccount', 'eWalletAccount', 'offsetChart']);

        return view('bank.bank-journals.show', compact('journal', 'documentNumber'));
    }

    /**
     * Show the form for editing a draft resource.
     *
     * @param  string  $documentNumber  The document number of the bank journal.
     * @return View Returns the rendered Blade view.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::BANK_JOURNALS, FileAction::UPDATE);

        $journal = BankJournal::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if(! $journal->isEditable(), 409);

        return view('bank.bank-journals.edit', [
            'journal' => $journal,
            'accounts' => $this->moneyAccounts(),
            'charts' => $this->offsetCharts(),
        ]);
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreBankJournalRequest  $request  The validated bank journal being created.
     * @return RedirectResponse Redirect response to the bank journal detail page.
     *
     * @throws Throwable
     */
    public function store(StoreBankJournalRequest $request): RedirectResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $fields = $this->fields($validated);

        [$account, $offsetChart, $moneyChartId, $kind] = $this->resolveAccounts(
            $fields['bankAccountId'],
            $fields['offsetChartId']
        );

        $documentNumber = $this->generateDocumentNumber(LedgerSubType::BANK_JOURNAL);

        // The amount is normalised with decimal arithmetic rather than cast to a
        // float, so what is written is what the user typed and a ledger has to
        // balance to the cent.
        $amount = bcadd(DecimalService::normalize($fields['amount']), '0', 2);

        // The correct FK column is set based on which account table the selected
        // id belongs to; the other two are left null.
        $fkColumns = $this->accountFkColumns($account->id, $kind);

        BankJournal::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'direction' => $fields['direction'],
            'document_number' => $documentNumber,
            'document_date' => $fields['documentDate'],
            'offset_chart_id' => $offsetChart->id,
            'amount' => $amount,
            'description' => $fields['description'],
            'notes' => $fields['notes'],
            'status' => LedgerStatus::PENDING,
        ], $fkColumns));

        return redirect()
            ->route('bank.bank-journals.show', $documentNumber)
            ->with('success', FlashMessage::created(FileResources::BANK_JOURNALS));
    }

    /**
     * Update an existing draft resource.
     *
     * @param  UpdateBankJournalRequest  $request  The validated bank journal being saved.
     * @param  string  $documentNumber  The bank journal document number.
     * @return RedirectResponse Redirect response to the bank journal detail page.
     *
     * @throws Throwable
     */
    public function update(UpdateBankJournalRequest $request, string $documentNumber): RedirectResponse
    {
        $journal = BankJournal::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if(! $journal->isEditable(), 409);

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $fields = $this->fields($validated);

        [$account, $offsetChart, , $kind] = $this->resolveAccounts(
            $fields['bankAccountId'],
            $fields['offsetChartId']
        );

        $amount = bcadd(DecimalService::normalize($fields['amount']), '0', 2);

        // The correct FK column is set based on which account table the selected
        // id belongs to; the other two are cleared so only one is ever set.
        $fkColumns = $this->accountFkColumns($account->id, $kind);

        $journal->update(array_merge([
            'direction' => $fields['direction'],
            'document_date' => $fields['documentDate'],
            'offset_chart_id' => $offsetChart->id,
            'amount' => $amount,
            'description' => $fields['description'],
            'notes' => $fields['notes'],
        ], $fkColumns));

        return redirect()
            ->route('bank.bank-journals.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::BANK_JOURNALS));
    }

    /**
     * Approve a bank journal and post it to the ledgers.
     *
     * @param  string  $documentNumber  The bank journal document number.
     * @return RedirectResponse Redirect response to the bank journal detail page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::BANK_JOURNALS, FileAction::APPROVE);

        DB::transaction(function () use ($documentNumber): void {
            $journal = BankJournal::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($journal->status !== LedgerStatus::PENDING, 409);
            abort_if($journal->direction !== 'in' && $journal->direction !== 'out', 422);

            $accountId = $journal->bank_account_id
                ?? $journal->cash_account_id
                ?? $journal->e_wallet_account_id;

            if (! is_string($accountId)) {
                abort(422);
            }

            [$account, , $moneyChartId, $kind] = $this->resolveAccounts($accountId, $journal->offset_chart_id);

            $rawAmount = $journal->getAttribute('amount');

            if (! is_numeric($rawAmount)) {
                abort(422);
            }

            $amount = bcadd(DecimalService::normalize((string) $rawAmount), '0', 2);

            // Money out cannot exceed what the account holds. The balance is
            // read inside the lock from the same money ledger the poster writes
            // to, so two journals approved together cannot both spend it.
            if ($journal->direction === 'out' && bccomp($this->currentBalance($account, $kind), $amount, 2) < 0) {
                throw ValidationException::withMessages([
                    'amount' => __('bank_journals.validation.insufficient_balance'),
                ]);
            }

            $legs = $this->legs($journal, $moneyChartId, $journal->offset_chart_id, $amount);

            // A journal that does not balance cannot be posted, because posting
            // it would write an entry the general ledger cannot accept.
            if (bccomp(PostingLeg::sumDebit($legs), PostingLeg::sumCredit($legs), 2) !== 0) {
                throw ValidationException::withMessages([
                    'document_number' => __('journal_entries.unbalanced_entry_error'),
                ]);
            }

            $this->poster->post(
                $journal,
                $journal->document_number,
                $journal->document_date,
                LedgerType::PAYMENT,
                LedgerSubType::BANK_JOURNAL,
                $legs,
            );

            $journal->update(['status' => LedgerStatus::POSTED]);

            // Recorded after the posting so an approver is never recorded against
            // a journal that did not post. The attribution saves the journal
            // itself, so this is the last write.
            $this->attributeApproved($journal);
        });

        return redirect()
            ->route('bank.bank-journals.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::BANK_JOURNALS));
    }

    /**
     * Cancel a pending bank journal.
     *
     * A cancelled journal is kept rather than removed. It is part of the record
     * of what was asked for and what happened to it. Only a pending journal can
     * be cancelled: it has moved no money, so there is nothing to reverse and
     * the cancellation is a flag, exactly as receipts do it.
     *
     * @param  string  $documentNumber  The bank journal document number.
     * @return RedirectResponse Redirect response to the bank journal list.
     *
     * @throws Throwable
     */
    public function cancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::BANK_JOURNALS, FileAction::CANCEL);

        DB::transaction(function () use ($documentNumber): void {
            $journal = BankJournal::query()
                ->where('document_number', $documentNumber)
                ->where('status', LedgerStatus::PENDING)
                ->lockForUpdate()
                ->first();

            // A second click matches nothing, because the journal is no longer
            // pending. Reporting that and redirecting is better than claiming a
            // cancellation that did not happen.
            abort_if($journal === null, 404);

            $journal->update(['status' => LedgerStatus::CANCELLED]);

            $this->attributeCancelled($journal);
        });

        return redirect()
            ->route('bank.bank-journals.index')
            ->with('success', FlashMessage::cancelled(FileResources::BANK_JOURNALS));
    }

    /**
     * Restore a cancelled bank journal, putting it back in the pending list.
     *
     * A cancelled journal has not moved money, so restoring it returns it to
     * pending and it can be posted as though it had never been cancelled.
     *
     * @param  string  $documentNumber  The bank journal document number.
     * @return RedirectResponse Redirect response to the bank journal list.
     *
     * @throws Throwable
     */
    public function restore(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::BANK_JOURNALS, FileAction::UPDATE);

        DB::transaction(function () use ($documentNumber): void {
            $journal = BankJournal::query()
                ->where('document_number', $documentNumber)
                ->where('status', LedgerStatus::CANCELLED)
                ->lockForUpdate()
                ->first();

            // A second click matches nothing, because the journal is no longer
            // cancelled. Reporting that and redirecting is better than claiming
            // a restore that did not happen.
            abort_if($journal === null, 404);

            $journal->update(['status' => LedgerStatus::PENDING]);

            $this->attributeUndone($journal);
        });

        return redirect()
            ->route('bank.bank-journals.index')
            ->with('success', __('common.messages.uncancelled'));
    }

    /**
     * Remove a draft resource.
     *
     * Only a pending journal can be removed: it has moved no money, so removing
     * it leaves the ledgers exactly as they were.
     *
     * @param  string  $documentNumber  The bank journal document number.
     * @return RedirectResponse Redirect response to the bank journal list.
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::BANK_JOURNALS, FileAction::REMOVE);

        $journal = BankJournal::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if($journal->status !== LedgerStatus::PENDING, 409);

        $journal->delete();

        return redirect()
            ->route('bank.bank-journals.index')
            ->with('success', FlashMessage::deleted(FileResources::BANK_JOURNALS));
    }

    /**
     * The money accounts a journal may move.
     *
     * Every active account with a chart behind it, drawn from all three account
     * tables (banks, cash accounts and e-wallet accounts) and combined into one
     * collection for the form select. Crypto accounts no longer exist in these
     * tables, so nothing needs to be excluded.
     *
     * @return SupportCollection<int, Bank|CashAccount|EWalletAccount>
     */
    private function moneyAccounts(): SupportCollection
    {
        $banks = Bank::query()
            ->with('chart')
            ->where('is_active', true)
            ->whereNotNull('chart_id')
            ->orderBy('name')
            ->get();

        $cashAccounts = CashAccount::query()
            ->with('chart')
            ->where('is_active', true)
            ->whereNotNull('chart_id')
            ->orderBy('name')
            ->get();

        $eWalletAccounts = EWalletAccount::query()
            ->with('chart')
            ->where('is_active', true)
            ->whereNotNull('chart_id')
            ->orderBy('name')
            ->get();

        /** @var SupportCollection<int, Bank|CashAccount|EWalletAccount> $accounts */
        $accounts = collect();

        return $accounts->merge($banks)->merge($cashAccounts)->merge($eWalletAccounts);
    }

    /**
     * The charts an offsetting side may be booked against.
     *
     * Opening-balance journals only face equity: the other side of "the business
     * now holds this much cash" is "the owner put it there". Liability, revenue,
     * expense and other asset accounts are not offered because none of them
     * describe where the money came from at setup time.
     *
     * @return Collection<int, Chart>
     */
    private function offsetCharts(): Collection
    {
        return Chart::query()
            ->with('chartType')
            ->whereHas('chartType', fn ($q) => $q->where('isEquity', true))
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get();
    }

    /**
     * The validated form, named.
     *
     * Everything below works from this array, and the request is not asked
     * anything again.
     *
     * @param  array<string, mixed>  $validated  The validated request input.
     * @return array{bankAccountId: string, offsetChartId: string, direction: string, documentDate: string, amount: string, description: string|null, notes: string|null}
     */
    private function fields(array $validated): array
    {
        $bankAccountId = $validated['bank_account_id'] ?? null;
        $offsetChartId = $validated['offset_chart_id'] ?? null;
        $direction = $validated['direction'] ?? null;
        $documentDate = $validated['document_date'] ?? null;
        $rawAmount = $validated['amount'] ?? null;
        $description = $validated['description'] ?? null;
        $notes = $validated['notes'] ?? null;

        if (
            ! is_string($bankAccountId) || ! is_string($offsetChartId)
            || ! is_string($direction) || ! is_string($documentDate)
            || ! is_numeric($rawAmount)
        ) {
            abort(422);
        }

        if ($direction !== 'in' && $direction !== 'out') {
            abort(422);
        }

        return [
            'bankAccountId' => $bankAccountId,
            'offsetChartId' => $offsetChartId,
            'direction' => $direction,
            'documentDate' => $documentDate,
            'amount' => (string) $rawAmount,
            'description' => is_string($description) ? $description : null,
            'notes' => is_string($notes) ? $notes : null,
        ];
    }

    /**
     * Resolve the two sides of the movement to rows.
     *
     * The account is re-fetched active rather than trusted from the form, so a
     * journal can never name an account that has since been deactivated. All
     * three account tables are searched; the first hit determines the kind. The
     * offsetting chart must differ from the account's own chart, because a
     * journal that debits and credits the same account moves nothing.
     *
     * @param  string  $bankAccountId  The money account being moved.
     * @param  string  $offsetChartId  The chart account on the other side.
     * @return array{0: Bank|CashAccount|EWalletAccount, 1: Chart, 2: string, 3: BankAccountKind} The account, the offset chart,
     *                                                                                            the account's chart id, and the account kind.
     *
     * @throws ValidationException When the pair cannot face each other.
     */
    private function resolveAccounts(string $bankAccountId, string $offsetChartId): array
    {
        $account = Bank::query()
            ->whereKey($bankAccountId)
            ->where('is_active', true)
            ->first();

        if ($account instanceof Bank) {
            $kind = BankAccountKind::BANK;
        } else {
            $account = CashAccount::query()
                ->whereKey($bankAccountId)
                ->where('is_active', true)
                ->first();

            if ($account instanceof CashAccount) {
                $kind = BankAccountKind::CASH;
            } else {
                $account = EWalletAccount::query()
                    ->whereKey($bankAccountId)
                    ->where('is_active', true)
                    ->first();

                if ($account instanceof EWalletAccount) {
                    $kind = BankAccountKind::E_WALLET;
                } else {
                    throw ValidationException::withMessages([
                        'bank_account_id' => __('bank_journals.validation.account_inactive'),
                    ]);
                }
            }
        }

        /** @var Chart|null $offsetChart */
        $offsetChart = Chart::query()->find($offsetChartId);

        if (! $offsetChart instanceof Chart) {
            throw ValidationException::withMessages([
                'offset_chart_id' => __('bank_journals.validation.offset_invalid'),
            ]);
        }

        $moneyChartId = $account->chart_id;

        if ($moneyChartId === '') {
            throw ValidationException::withMessages([
                'bank_account_id' => __('bank_journals.validation.account_inactive'),
            ]);
        }

        $offsetKey = $offsetChart->getKey();

        if (! is_string($offsetKey) || $offsetKey === $moneyChartId) {
            throw ValidationException::withMessages([
                'offset_chart_id' => __('bank_journals.validation.offset_same_as_account'),
            ]);
        }

        return [$account, $offsetChart, $moneyChartId, $kind];
    }

    /**
     * Build the three FK columns for a journal row, setting only the one that
     * matches the account kind and leaving the other two null.
     *
     * @param  string  $accountId  The id of the money account.
     * @param  BankAccountKind  $kind  The kind of money account.
     * @return array{bank_account_id: string|null, cash_account_id: string|null, e_wallet_account_id: string|null}
     */
    private function accountFkColumns(string $accountId, BankAccountKind $kind): array
    {
        return match ($kind) {
            BankAccountKind::BANK => [
                'bank_account_id' => $accountId,
                'cash_account_id' => null,
                'e_wallet_account_id' => null,
            ],
            BankAccountKind::CASH => [
                'bank_account_id' => null,
                'cash_account_id' => $accountId,
                'e_wallet_account_id' => null,
            ],
            BankAccountKind::E_WALLET => [
                'bank_account_id' => null,
                'cash_account_id' => null,
                'e_wallet_account_id' => $accountId,
            ],
            BankAccountKind::CRYPTO => throw ValidationException::withMessages([
                'bank_account_id' => __('bank_journals.validation.crypto_account'),
            ]),
        };
    }

    /**
     * Turn a journal into the double entry the poster expects.
     *
     * Money in debits the money account and credits the offset; money out is the
     * mirror. Two legs for a straight journal, one per side, which is why the
     * two sides always add to the same figure.
     *
     * @param  BankJournal  $journal  The journal being posted.
     * @param  string  $moneyChartId  The money account's chart id.
     * @param  string  $offsetChartId  The offsetting chart id.
     * @param  numeric-string  $amount  The movement, normalised to an exact decimal.
     * @return list<PostingLeg> The double entry, one leg per side.
     */
    private function legs(BankJournal $journal, string $moneyChartId, string $offsetChartId, string $amount): array
    {
        $description = $journal->description;

        if ($journal->direction === 'in') {
            return [
                PostingLeg::debit($moneyChartId, $amount, $description),
                PostingLeg::credit($offsetChartId, $amount, $description),
            ];
        }

        return [
            PostingLeg::debit($offsetChartId, $amount, $description),
            PostingLeg::credit($moneyChartId, $amount, $description),
        ];
    }

    /**
     * The balance an account holds before this posting.
     *
     * Read from the same money ledger the poster writes to, leaving soft-deleted
     * rows out: a row that was reversed out of the ledger no longer says
     * anything about the account.
     *
     * @param  Bank|CashAccount|EWalletAccount  $account  The money account being moved out of.
     * @param  BankAccountKind  $kind  The kind of money account, used to pick the ledger table.
     * @return numeric-string The balance, exact to two decimal places.
     */
    private function currentBalance(Bank|CashAccount|EWalletAccount $account, BankAccountKind $kind): string
    {
        [$table, $column] = match ($kind) {
            BankAccountKind::CASH => ['cash_ledgers', 'cash_account_id'],
            BankAccountKind::BANK => ['bank_ledgers', 'bank_account_id'],
            BankAccountKind::E_WALLET => ['e_wallet_ledgers', 'e_wallet_account_id'],
            BankAccountKind::CRYPTO => throw ValidationException::withMessages([
                'bank_account_id' => __('bank_journals.validation.crypto_account'),
            ]),
        };

        $balance = DB::table($table)
            ->where($column, $account->id)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(debit_amount) - SUM(credit_amount), 0) as balance')
            ->value('balance');

        // The SUM comes back from the database as a string, an int or a float
        // depending on the driver; whichever it is, the number itself is exact
        // enough that its string form is what bcmath expects.
        return bcadd(DecimalService::normalize(is_numeric($balance) ? (string) $balance : '0'), '0', 2);
    }
}
