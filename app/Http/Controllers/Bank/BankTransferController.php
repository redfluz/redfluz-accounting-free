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
use App\Http\Requests\Bank\Transfer\UpdateBankTransferRequest;
use App\Models\Bank\Bank;
use App\Models\Bank\Transfer;
use App\Support\Posting\PaymentLedgerPoster;
use App\Support\Posting\PostingLeg;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Bank transfers in: cash in transit reaches a bank account.
 *
 * This is the second half of a banker's deposit, and it is the half that makes
 * the money the business's money at the bank. It should only be recorded once a
 * bank has actually credited the deposit, which is why it is a screen of its own
 * and not the same form as the transfer out: the two are separated by however
 * long the bank takes, and are approved by whoever confirms the credit.
 *
 * The amount defaults to whatever is sitting in transit, since that is normally
 * all of it, and can be set lower when a deposit lands in part or several
 * deposits are cleared one at a time. It can never exceed transit's balance:
 * transit cannot hand to a bank what it does not hold, and a bank ledger
 * credited beyond it would leave the account permanently short.
 */
class BankTransferController extends Controller
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
        $this->allows(FileResources::BANK_TRANSFERS);

        $status = $this->statusFrom($request);

        $searchInput = $request->input('search', '');
        $search = is_string($searchInput) ? trim($searchInput) : '';

        $transfers = Transfer::query()
            ->with(['bankAccount.chart', 'transitChart'])
            ->where('direction', Transfer::DIRECTION_IN)
            ->where('status', $status)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('document_number', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            }))
            ->latest('document_date')
            ->paginate(static::PER_PAGE_GRID)
            ->withQueryString();

        return view('bank.bank-transfers.index', compact('transfers', 'search', 'status'));
    }

    /**
     * Show the form for a new transfer.
     *
     *
     * @throws Throwable
     */
    public function create(): View
    {
        $this->allows(FileResources::BANK_TRANSFERS, FileAction::ADD);

        $transit = $this->transitChart();

        return view('bank.bank-transfers.create', [
            'banks' => $this->bankAccounts(),
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
        $this->allows(FileResources::BANK_TRANSFERS);

        return view('bank.bank-transfers.show', [
            'transfer' => $this->findInbound($documentNumber),
        ]);
    }

    /**
     * Show the form for editing a transfer that has not been posted.
     *
     * @param  string  $documentNumber  The transfer's document number.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::BANK_TRANSFERS, FileAction::UPDATE);

        $transfer = $this->findInbound($documentNumber);

        abort_if(! $transfer->isEditable(), 409);

        $transit = $this->transitChart();

        return view('bank.bank-transfers.edit', [
            'transfer' => $transfer,
            'banks' => $this->bankAccounts(),
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
        $this->allows(FileResources::BANK_TRANSFERS, FileAction::ADD);

        /** @var array<string, mixed> $validated */
        $validated = $request->validate($this->rules());

        $fields = $this->fields($validated);
        $bank = $this->resolveBank($fields['bankAccountId']);

        // Stored before the row is written so the path and the name the user
        // uploaded always travel together and are never half-recorded.
        $attachment = $this->storeAttachment($request);

        $transfer = Transfer::query()->create([
            'id' => (string) Str::uuid(),
            'document_number' => $this->generateDocumentNumber(LedgerSubType::TRANSFER),
            'direction' => Transfer::DIRECTION_IN,
            'cash_account_id' => null,
            'bank_account_id' => $bank->id,
            'transit_chart_id' => $this->transitChart()->id,
            'document_date' => $fields['documentDate'],
            'amount' => $this->toAmount($fields['amount']),
            'reference_number' => $fields['referenceNumber'],
            'attachment_path' => $attachment['path'],
            'attachment_name' => $attachment['name'],
            'description' => $fields['description'],
            'notes' => $fields['notes'],
            'status' => LedgerStatus::PENDING,
        ]);

        return redirect()
            ->route('bank.bank-transfers.show', $transfer->document_number)
            ->with('success', FlashMessage::created(FileResources::BANK_TRANSFERS));
    }

    /**
     * Change a transfer that has not been posted.
     *
     * @param  UpdateBankTransferRequest  $request  The submitted transfer.
     * @param  string  $documentNumber  The transfer's document number.
     *
     * @throws Throwable
     */
    public function update(UpdateBankTransferRequest $request, string $documentNumber): RedirectResponse
    {
        $transfer = $this->findInbound($documentNumber);

        abort_if(! $transfer->isEditable(), 409);

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $fields = $this->fields($validated);

        $bank = $this->resolveBank($fields['bankAccountId']);

        $uploaded = $this->storeAttachment($request);

        // Saving without choosing a file keeps the attachment already there. A
        // form that silently dropped the slip every time someone edited a note
        // would be worse than one that had no upload field at all.
        $replaced = $uploaded['path'] !== null;

        $supersededPath = $replaced ? $transfer->attachment_path : null;

        $transfer->update([
            'bank_account_id' => $bank->id,
            'document_date' => $fields['documentDate'],
            'amount' => $this->toAmount($fields['amount']),
            'reference_number' => $fields['referenceNumber'],
            'attachment_path' => $replaced ? $uploaded['path'] : $transfer->attachment_path,
            'attachment_name' => $replaced ? $uploaded['name'] : $transfer->attachment_name,
            'description' => $fields['description'],
            'notes' => $fields['notes'],
        ]);

        // The file the row used to point at is deleted only once the row no
        // longer names it, so a failed save leaves the old slip reachable.
        if ($supersededPath !== null) {
            $this->deleteDocument($supersededPath);
        }

        return redirect()
            ->route('bank.bank-transfers.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::BANK_TRANSFERS));
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
        $this->allows(FileResources::BANK_TRANSFERS, FileAction::APPROVE);

        DB::transaction(function () use ($documentNumber): void {
            $transfer = Transfer::query()
                ->where('document_number', $documentNumber)
                ->where('direction', Transfer::DIRECTION_IN)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($transfer->status !== LedgerStatus::PENDING, 409);

            $bank = $transfer->bankAccount;

            abort_if(! $bank instanceof Bank, 422);

            $rawAmount = $transfer->getAttribute('amount');
            abort_if(! is_numeric($rawAmount), 422);

            // Normalised with decimal arithmetic rather than cast to a float, so
            // what is posted is what was asked for and the two sides of the entry
            // add to the same figure to the cent.
            $amount = $this->toAmount((string) $rawAmount);

            $transitChart = $transfer->transitChart;

            abort_if($transitChart === null, 422);

            // Transit cannot hand over more than it holds. Read inside the lock
            // from the general ledger the poster writes to, so two transfers
            // approved together cannot both claim the same money.
            $available = $this->transitBalance($transitChart);

            if (bccomp($available, '0', 2) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => __('bank_transfers.validation.transit_empty'),
                ]);
            }

            if (bccomp($amount, $available, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => __('bank_transfers.validation.exceeds_transit', ['available' => $available]),
                ]);
            }

            $moneyChartId = $bank->chart_id;

            if ($moneyChartId === '') {
                abort(422);
            }

            // The bank is debited, transit credited: the money has reached the
            // bank and has left the account that was holding it meanwhile.
            $legs = [
                PostingLeg::debit($moneyChartId, $amount, $transfer->description, $transfer->reference_number),
                PostingLeg::credit($transitChart->id, $amount, $transfer->description, $transfer->reference_number),
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
            ->route('bank.bank-transfers.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::BANK_TRANSFERS));
    }

    /**
     * Cancel a pending transfer.
     *
     * @param  string  $documentNumber  The transfer's document number.
     *
     * @throws Throwable
     */
    public function cancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::BANK_TRANSFERS, FileAction::CANCEL);

        DB::transaction(function () use ($documentNumber): void {
            $transfer = Transfer::query()
                ->where('document_number', $documentNumber)
                ->where('direction', Transfer::DIRECTION_IN)
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
            ->route('bank.bank-transfers.index')
            ->with('success', FlashMessage::cancelled(FileResources::BANK_TRANSFERS));
    }

    /**
     * Remove a transfer that has not been posted.
     *
     * @param  string  $documentNumber  The transfer's document number.
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::BANK_TRANSFERS, FileAction::REMOVE);

        $transfer = $this->findInbound($documentNumber);

        abort_if($transfer->status !== LedgerStatus::PENDING, 409);

        $transfer->delete();

        return redirect()
            ->route('bank.bank-transfers.index')
            ->with('success', FlashMessage::deleted(FileResources::BANK_TRANSFERS));
    }

    /**
     * The bank accounts a transfer may be made into.
     *
     * @return Collection<int, Bank>
     */
    private function bankAccounts(): Collection
    {
        return Bank::query()
            ->with('chart')
            ->where('is_active', true)
            ->whereNotNull('chart_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Re-read a bank account rather than trust the form.
     *
     * A bank account may have been deactivated since the form was rendered, and
     * a transfer naming one would then credit an account nobody is watching.
     *
     * @throws ValidationException When the bank account cannot be used.
     */
    private function resolveBank(string $accountId): Bank
    {
        /** @var Bank|null $account */
        $account = Bank::query()
            ->whereKey($accountId)
            ->where('is_active', true)
            ->whereNotNull('chart_id')
            ->first();

        if (! $account instanceof Bank) {
            throw ValidationException::withMessages([
                'bank_account_id' => __('bank_transfers.validation.account_inactive'),
            ]);
        }

        return $account;
    }

    /**
     * Load a transfer, insisting it is one of this screen's.
     *
     * The direction is part of the lookup so that a cash transfer's number cannot
     * be opened, edited or approved through the bank transfer screen.
     */
    private function findInbound(string $documentNumber): Transfer
    {
        return Transfer::query()
            ->with(['bankAccount.chart', 'transitChart'])
            ->where('document_number', $documentNumber)
            ->where('direction', Transfer::DIRECTION_IN)
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
     * Store the deposit slip, if one was chosen.
     *
     * Both halves of the result come from the one upload: where it was written,
     * and the name the user recognises it by. The stored file is renamed to a
     * random string on the way in, so without keeping the original name a
     * download would be offered as something like "a8Fj2....pdf".
     *
     * @return array{path: string|null, name: string|null}
     */
    private function storeAttachment(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return ['path' => null, 'name' => null];
        }

        $file = $request->file('attachment');

        abort_if(! $file instanceof UploadedFile, 422);

        return [
            'path' => $this->storeUpload($file, 'bank_transfer_attachments'),
            'name' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Send the attached deposit slip to the browser.
     *
     * @param  string  $documentNumber  The transfer's document number.
     * @return StreamedResponse The file, or a 404 when none is attached or the
     *                          stored file has gone.
     */
    public function downloadAttachment(string $documentNumber): StreamedResponse
    {
        $this->allows(FileResources::BANK_TRANSFERS);

        $transfer = $this->findInbound($documentNumber);

        $path = $transfer->attachment_path;

        abort_if(! $this->documentExists($path), 404);

        return $this->downloadDocument($path, $transfer->attachment_name);
    }

    /**
     * The validated form, named and typed.
     *
     * Everything below works from this array rather than from the request, so
     * each field is checked once here and read as the type it is meant to be.
     *
     * @param  array<string, mixed>  $validated  The validated request input.
     * @return array{bankAccountId: string, documentDate: string, amount: string, referenceNumber: string|null, description: string|null, notes: string|null}
     */
    private function fields(array $validated): array
    {
        $bankAccountId = $validated['bank_account_id'] ?? null;
        $documentDate = $validated['document_date'] ?? null;
        $rawAmount = $validated['amount'] ?? null;
        $referenceNumber = $validated['reference_number'] ?? null;
        $description = $validated['description'] ?? null;
        $notes = $validated['notes'] ?? null;

        if (
            ! is_string($bankAccountId)
            || ! is_string($documentDate)
            || ! is_numeric($rawAmount)
        ) {
            abort(422);
        }

        return [
            'bankAccountId' => $bankAccountId,
            'documentDate' => $documentDate,
            'amount' => (string) $rawAmount,
            'referenceNumber' => is_string($referenceNumber) ? $referenceNumber : null,
            'description' => is_string($description) ? $description : null,
            'notes' => is_string($notes) ? $notes : null,
        ];
    }

    /**
     * The validation rules for a transfer in.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'uuid'],
            'document_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
