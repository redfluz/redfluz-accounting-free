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

declare(strict_types=1);

namespace App\Http\Controllers\Bank;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Http\Controllers\Bank\Concerns\HandlesTransfers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\Transfer\UpdateCashTransferRequest;
use App\Models\Bank\CashAccount;
use App\Models\Bank\Transfer;
use App\Support\Posting\PaymentLedgerPoster;
use App\Support\Posting\PostingLeg;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Cash transfers out: a drawer hands its surplus to cash in transit.
 *
 * This is the first half of a banker's deposit. The money is counted out of the
 * drawer and is in nobody's account: the drawer is already short of it and no
 * bank has credited anything. It sits in cash in transit until a transfer in
 * says a bank has taken it, and until then it is right that no bank ledger
 * mentions it at all.
 *
 * One transfer names one drawer. A sweep across every drawer would produce a
 * single entry that no individual drawer agrees with, so a count of any one of
 * them could not be reconciled against it. Every drawer is banked on its own.
 *
 * The amount defaults to what the drawer holds above its float, because that is
 * the usual amount to bank and typing it each time invites the wrong figure. It
 * can be set lower — some days the till wants more cash in it — but never above
 * the surplus, since a drawer cannot hand over what it has not got.
 */
class CashTransferController extends Controller
{
    use GeneratesDocumentNumber, HandlesTransfers;

    public function __construct(private readonly PaymentLedgerPoster $poster) {}

    /**
     * Display the transfers waiting to be approved, or another status.
     *
     * @param  Request  $request  The request containing list filters.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::CASH_TRANSFERS);

        $status = $this->statusFrom($request);

        $searchInput = $request->input('search', '');
        $search = is_string($searchInput) ? trim($searchInput) : '';

        $transfers = Transfer::query()
            ->with(['cashAccount.chart', 'transitChart'])
            ->where('direction', Transfer::DIRECTION_OUT)
            ->where('status', $status)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('document_number', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            }))
            ->latest('document_date')
            ->paginate(static::PER_PAGE_GRID)
            ->withQueryString();

        return view('bank.cash-transfers.index', compact('transfers', 'search', 'status'));
    }

    /**
     * Show the form for a new transfer.
     *
     *
     * @throws Throwable
     */
    public function create(): View
    {
        $this->allows(FileResources::CASH_TRANSFERS, FileAction::ADD);

        $transit = $this->transitChart();

        return view('bank.cash-transfers.create', [
            'accounts' => $this->drawers(),
            'balances' => $this->balancesFor($this->drawers()),
            'transit' => $transit,
            'transitBalance' => $this->transitBalance($transit),
            'documentNumber' => $this->generateDocumentNumber(LedgerSubType::TRANSFER),
        ]);
    }

    /**
     * Display a transfer.
     *
     * @param  string  $documentNumber  The transfer's document number.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::CASH_TRANSFERS);

        return view('bank.cash-transfers.show', [
            'transfer' => $this->findOutbound($documentNumber),
        ]);
    }

    /**
     * Show the form for editing a transfer that has not been posted.
     *
     * @param  string  $documentNumber  The transfer's document number.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::CASH_TRANSFERS, FileAction::UPDATE);

        $transfer = $this->findOutbound($documentNumber);

        abort_if(! $transfer->isEditable(), 409);

        $transit = $this->transitChart();

        return view('bank.cash-transfers.edit', [
            'transfer' => $transfer,
            'accounts' => $this->drawers(),
            'balances' => $this->balancesFor($this->drawers()),
            'transit' => $transit,
            'transitBalance' => $this->transitBalance($transit),
        ]);
    }

    /**
     * Record a new transfer, pending.
     *
     * @param  Request  $request  The submitted transfer.
     *
     * @throws Throwable
     */
    public function store(Request $request): RedirectResponse
    {
        $this->allows(FileResources::CASH_TRANSFERS, FileAction::ADD);

        /** @var array<string, mixed> $validated */
        $validated = $request->validate($this->rules());

        $fields = $this->fields($validated);
        $account = $this->resolveDrawer($fields['cashAccountId']);

        $transfer = Transfer::query()->create([
            'id' => (string) Str::uuid(),
            'document_number' => $this->generateDocumentNumber(LedgerSubType::TRANSFER),
            'direction' => Transfer::DIRECTION_OUT,
            'cash_account_id' => $account->id,
            'bank_account_id' => null,
            'transit_chart_id' => $this->transitChart()->id,
            'document_date' => $fields['documentDate'],
            'amount' => $this->toAmount($fields['amount']),
            'reference_number' => $fields['referenceNumber'],
            'description' => $fields['description'],
            'notes' => $fields['notes'],
            'status' => LedgerStatus::PENDING,
        ]);

        return redirect()
            ->route('bank.cash-transfers.show', $transfer->document_number)
            ->with('success', FlashMessage::created(FileResources::CASH_TRANSFERS));
    }

    /**
     * Change a transfer that has not been posted.
     *
     * @param  UpdateCashTransferRequest  $request  The submitted transfer.
     * @param  string  $documentNumber  The transfer's document number.
     *
     * @throws Throwable
     */
    public function update(UpdateCashTransferRequest $request, string $documentNumber): RedirectResponse
    {
        $transfer = $this->findOutbound($documentNumber);

        abort_if(! $transfer->isEditable(), 409);

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $fields = $this->fields($validated);

        $account = $this->resolveDrawer($fields['cashAccountId']);

        $transfer->update([
            'cash_account_id' => $account->id,
            'document_date' => $fields['documentDate'],
            'amount' => $this->toAmount($fields['amount']),
            'reference_number' => $fields['referenceNumber'],
            'description' => $fields['description'],
            'notes' => $fields['notes'],
        ]);

        return redirect()
            ->route('bank.cash-transfers.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::CASH_TRANSFERS));
    }

    /**
     * Approve a transfer and post it to the ledgers.
     *
     * @param  string  $documentNumber  The transfer's document number.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::CASH_TRANSFERS, FileAction::APPROVE);

        DB::transaction(function () use ($documentNumber): void {
            $transfer = Transfer::query()
                ->where('document_number', $documentNumber)
                ->where('direction', Transfer::DIRECTION_OUT)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($transfer->status !== LedgerStatus::PENDING, 409);

            $account = $transfer->cashAccount;

            abort_if(! $account instanceof CashAccount, 422);

            $rawAmount = $transfer->getAttribute('amount');
            abort_if(! is_numeric($rawAmount), 422);

            // Normalised with decimal arithmetic rather than cast to a float, so
            // what is posted is what was asked for and the two sides of the entry
            // add to the same figure to the cent.
            $amount = $this->toAmount((string) $rawAmount);

            // The drawer cannot hand over more than it holds above its float.
            // Read inside the lock from the same ledger the poster writes to, so
            // two transfers approved together cannot both take the same surplus.
            $surplus = $this->surplusFor($account);

            if (bccomp($surplus, '0', 2) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => __('cash_transfers.validation.no_surplus'),
                ]);
            }

            if (bccomp($amount, $surplus, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => __('cash_transfers.validation.exceeds_surplus', ['surplus' => $surplus]),
                ]);
            }

            $transitChartId = $transfer->transit_chart_id;
            $moneyChartId = $account->chart_id;

            if ($moneyChartId === '') {
                abort(422);
            }

            // The drawer is credited, transit debited: the money has left the
            // drawer and has not reached a bank.
            $legs = [
                PostingLeg::debit($transitChartId, $amount, $transfer->description, $transfer->reference_number),
                PostingLeg::credit($moneyChartId, $amount, $transfer->description, $transfer->reference_number),
            ];

            if (bccomp(PostingLeg::sumDebit($legs), PostingLeg::sumCredit($legs), 2) !== 0) {
                throw ValidationException::withMessages([
                    'document_number' => __('journal_entries.unbalanced_entry_error'),
                ]);
            }

            $this->poster->post(
                $transfer,
                $transfer->document_number,
                $transfer->document_date,
                LedgerType::PAYMENT,
                LedgerSubType::TRANSFER,
                $legs,
                referenceNumber: $transfer->reference_number,
            );

            $transfer->update(['status' => LedgerStatus::POSTED]);

            // Recorded after the posting so an approver is never recorded against
            // a transfer that did not post.
            $this->attributeApproved($transfer);
        });

        return redirect()
            ->route('bank.cash-transfers.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::CASH_TRANSFERS));
    }

    /**
     * Cancel a pending transfer.
     *
     * A cancelled transfer is kept rather than removed. Only a pending transfer
     * can be cancelled: it has moved no money, so the cancellation is a flag.
     *
     * @param  string  $documentNumber  The transfer's document number.
     *
     * @throws Throwable
     */
    public function cancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::CASH_TRANSFERS, FileAction::CANCEL);

        DB::transaction(function () use ($documentNumber): void {
            $transfer = Transfer::query()
                ->where('document_number', $documentNumber)
                ->where('direction', Transfer::DIRECTION_OUT)
                ->where('status', LedgerStatus::PENDING)
                ->lockForUpdate()
                ->first();

            // A second click matches nothing, because the transfer is no longer
            // pending. Reporting that is better than claiming a cancellation that
            // did not happen.
            abort_if($transfer === null, 404);

            $transfer->update(['status' => LedgerStatus::CANCELLED]);

            $this->attributeCancelled($transfer);
        });

        return redirect()
            ->route('bank.cash-transfers.index')
            ->with('success', FlashMessage::cancelled(FileResources::CASH_TRANSFERS));
    }

    /**
     * Remove a transfer that has not been posted.
     *
     * @param  string  $documentNumber  The transfer's document number.
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::CASH_TRANSFERS, FileAction::REMOVE);

        $transfer = $this->findOutbound($documentNumber);

        abort_if($transfer->status !== LedgerStatus::PENDING, 409);

        $transfer->delete();

        return redirect()
            ->route('bank.cash-transfers.index')
            ->with('success', FlashMessage::deleted(FileResources::CASH_TRANSFERS));
    }

    /**
     * The balance and surplus of every drawer, keyed by account id.
     *
     * Sent to the form so the amount can open on the real surplus without a
     * round trip, and so a drawer that is already at its float is visibly not
     * bankable rather than quietly refusing on submit.
     *
     * @param  Collection<int, CashAccount>  $accounts  The drawers offered.
     * @return array<string, array{balance: numeric-string, float: numeric-string, surplus: numeric-string}>
     */
    private function balancesFor(Collection $accounts): array
    {
        $balances = [];

        foreach ($accounts as $account) {
            $balances[$account->id] = [
                'balance' => $this->cashBalance($account),
                'float' => $account->float_amount === null
                    ? $this->toAmount('0')
                    : $this->toAmount($account->float_amount),
                'surplus' => $this->surplusFor($account),
            ];
        }

        return $balances;
    }

    /**
     * The drawers a transfer may be made from.
     *
     * @return Collection<int, CashAccount>
     */
    private function drawers(): Collection
    {
        return CashAccount::query()
            ->with('chart')
            ->where('is_active', true)
            ->whereNotNull('chart_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Re-read a drawer rather than trust the form.
     *
     * A drawer may have been deactivated since the form was rendered, and a
     * transfer naming one would then post against an account nobody is counting.
     *
     * @throws ValidationException When the drawer cannot be used.
     */
    private function resolveDrawer(string $accountId): CashAccount
    {
        /** @var CashAccount|null $account */
        $account = CashAccount::query()
            ->whereKey($accountId)
            ->where('is_active', true)
            ->whereNotNull('chart_id')
            ->first();

        if (! $account instanceof CashAccount) {
            throw ValidationException::withMessages([
                'cash_account_id' => __('cash_transfers.validation.account_inactive'),
            ]);
        }

        return $account;
    }

    /**
     * Load a transfer, insisting it is one of this screen's.
     *
     * The direction is part of the lookup so that a bank transfer's number cannot
     * be opened, edited or approved through the cash transfer screen.
     */
    private function findOutbound(string $documentNumber): Transfer
    {
        return Transfer::query()
            ->with(['cashAccount.chart', 'transitChart'])
            ->where('document_number', $documentNumber)
            ->where('direction', Transfer::DIRECTION_OUT)
            ->firstOrFail();
    }

    /**
     * The status being listed, defaulting to pending.
     */
    private function statusFrom(Request $request): string
    {
        $statusInput = $request->input('status', LedgerStatus::PENDING->value);
        $status = is_string($statusInput) ? $statusInput : '';

        return in_array($status, array_column(LedgerStatus::cases(), 'value'), true)
            ? $status
            : LedgerStatus::PENDING->value;
    }

    /**
     * The validated form, named and typed.
     *
     * Everything below works from this array rather than from the request, so
     * each field is checked once here and read as the type it is meant to be.
     *
     * @param  array<string, mixed>  $validated  The validated request input.
     * @return array{cashAccountId: string, documentDate: string, amount: string, referenceNumber: string|null, description: string|null, notes: string|null}
     */
    private function fields(array $validated): array
    {
        $cashAccountId = $validated['cash_account_id'] ?? null;
        $documentDate = $validated['document_date'] ?? null;
        $rawAmount = $validated['amount'] ?? null;
        $referenceNumber = $validated['reference_number'] ?? null;
        $description = $validated['description'] ?? null;
        $notes = $validated['notes'] ?? null;

        if (
            ! is_string($cashAccountId)
            || ! is_string($documentDate)
            || ! is_numeric($rawAmount)
        ) {
            abort(422);
        }

        return [
            'cashAccountId' => $cashAccountId,
            'documentDate' => $documentDate,
            'amount' => (string) $rawAmount,
            'referenceNumber' => is_string($referenceNumber) ? $referenceNumber : null,
            'description' => is_string($description) ? $description : null,
            'notes' => is_string($notes) ? $notes : null,
        ];
    }

    /**
     * The validation rules for a transfer out.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'cash_account_id' => ['required', 'uuid'],
            'document_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
