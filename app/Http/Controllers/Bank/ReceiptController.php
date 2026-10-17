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
use App\Enums\PaymentTransferType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\Receipt\BatchApproveReceiptRequest;
use App\Http\Requests\Bank\Receipt\BatchCancelReceiptRequest;
use App\Http\Requests\Bank\Receipt\StoreReceiptRequest;
use App\Http\Requests\Bank\Receipt\UpdateReceiptRequest;
use App\Models\Bank\Bank;
use App\Models\Bank\CashAccount;
use App\Models\Bank\EWalletAccount;
use App\Models\Bank\Receipt;
use App\Models\Bank\ReceiptChart;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\Company;
use App\Models\Sales\SalesInvoice;
use App\Services\DecimalService;
use App\Support\Data\Bank\ReceiptData;
use App\Support\Posting\PaymentLedgerPoster;
use App\Support\Posting\PostingLeg;
use App\Support\PostingException;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Receipts: money received from a customer.
 *
 * A receipt is a document with its own number, its own approval and its own
 * history. It is not a pair of rows that happen to share a document number, and
 * it is not a journal entry. That distinction is the whole reason this controller
 * exists separately from payment vouchers and refunds: each of those is a
 * different document with different rules about which accounts may face each
 * other, and sharing one controller for all three meant each rule was a branch
 * inside a method that had to remember which document it was acting on.
 *
 * What a receipt writes on approval is decided by {@see LedgerPoster} from the
 * accounts its lines name, not from anything here. A receipt paid into a drawer
 * and one paid into a bank account are the same document to this controller; the
 * ledger each produces follows from the account the money actually landed in.
 */
class ReceiptController extends Controller
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
     * Display a listing of the resource.
     * Display every receipt that is still pending.
     *
     * @param  Request  $request  The request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $receipts = Receipt::query()
            ->where('status', LedgerStatus::PENDING)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('document_number', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%")
                    ->orWhere('reference_number', 'like', "%$search%");
            }))
            ->when(filled($startDate), fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when(filled($endDate), fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->latest('date')
            ->paginate(static::PER_PAGE_GRID)
            ->withQueryString();

        return view('bank.receipt.index', compact('receipts', 'search', 'startDate', 'endDate'));
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
        // Only debtor companies can be paid by a receipt, and only a money
        // account can receive one, so the form is not offered choices that
        // would be refused on submit.
        return view('bank.receipt.create', [
            'companies' => Company::query()
                ->where('is_debtor', true)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),
            'charts' => Chart::query()
                ->with('chartType')
                ->whereNull('deleted_at')
                ->orderBy('code')
                ->get(),
            'documentNumber' => $this->generateDocumentNumber(LedgerSubType::RECEIPT),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        $receipt->load([
            'charts.debitChart',
            'charts.creditChart',
        ]);

        return view('bank.receipt.show', compact('receipt', 'documentNumber'));
    }

    /**
     * Show the form for editing an open resource.
     * Show the edit form for one pending receipt.
     *
     * @param  string  $documentNumber  The receipt document number.
     * @return View Returns the rendered Blade view.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::PAYMENTS, FileAction::UPDATE);

        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if($receipt->isPosted(), 409);

        $companies = Company::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $charts = Chart::query()
            ->with('chartType')
            ->orderBy('code')
            ->get();

        return view('bank.receipt.edit', compact('receipt', 'documentNumber', 'companies', 'charts'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     @param  StoreReceiptRequest  $request  The validated receipt being created.
     * @return RedirectResponse Redirect response to the receipt detail page.
     *
     * @throws Throwable
     */
    public function store(StoreReceiptRequest $request): RedirectResponse
    {
        // The file is written to disk before the form is parsed, because an
        // uploaded file is not form data: it has no stored name and no path yet,
        // and only the write gives it one. This is the last the request is read.
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'receipt_attachments')
            : null;

        // The form, parsed into a document. Everything below works from this
        // object, and the request is not asked anything again.
        $data = ReceiptData::from($request->validated());

        $chartSetting = ChartSetting::query()->first();

        // A receipt debits money received and credits what the customer owes.
        // The accounts default to the chart settings so a receipt can be raised
        // without choosing them, which is the common case. The money default
        // follows the transfer type: cash lands in the cash drawer, e-wallet in
        // the wallet account, anything else in the bank account.
        $transferType = $data->paymentTransferType ?? PaymentTransferType::CHEQUE;
        $debitId = $data->debitAccountId ?? (
            $transferType === PaymentTransferType::CASH
                ? ($chartSetting->cash_chart_uuid ?? $chartSetting?->bank_chart_uuid)
                : ($transferType === PaymentTransferType::E_WALLET
                    ? ($chartSetting->e_wallet_chart_uuid ?? $chartSetting?->bank_chart_uuid)
                    : $chartSetting?->bank_chart_uuid)
        );
        $creditId = $data->creditAccountId ?? $chartSetting?->client_chart_uuid;

        if (blank($debitId)) {
            throw ValidationException::withMessages([
                'debit_id' => __('ledgers.validation.debit_account_or_default_required'),
            ]);
        }

        if (blank($creditId)) {
            throw ValidationException::withMessages([
                'credit_id' => __('ledgers.validation.credit_account_or_default_required'),
            ]);
        }

        /** @var Chart|null $debitChart */
        $debitChart = Chart::query()->with('chartType')->find($debitId);
        /** @var Chart|null $creditChart */
        $creditChart = Chart::query()->with('chartType')->find($creditId);

        if ($debitChart === null || $creditChart === null) {
            throw ValidationException::withMessages([
                'debit_id' => __('ledgers.validation.debit_account_or_default_required'),
            ]);
        }

        // Money received is always a money account; what the customer pays
        // against is always a debtor. Anything else is a different document and
        // must be raised as one.
        if (! $debitChart->isBank) {
            throw ValidationException::withMessages([
                'debit_id' => __('payments.validation.debit_account_invalid'),
            ]);
        }

        $this->ensureMoneyAccountMatchesTransferType(
            $debitChart,
            $data->paymentTransferType ?? PaymentTransferType::CHEQUE
        );

        if ($creditChart->chartType?->isAsset !== true || ! $creditChart->isDebtor) {
            throw ValidationException::withMessages([
                'credit_id' => __('payments.validation.credit_account_invalid'),
            ]);
        }

        $documentNumber = $this->generateDocumentNumber(LedgerSubType::RECEIPT);

        // The amount is normalised with decimal arithmetic rather than cast to a
        // float, so what is written is what the user typed and a ledger has to
        // balance to the cent.
        $amount = bcadd(DecimalService::normalize($data->amount()), '0', 2);

        DB::transaction(function () use ($data, $documentNumber, $amount, $attachmentPath, $debitChart, $creditChart): void {
            $receipt = Receipt::query()->create([
                'id' => (string) Str::uuid(),
                'document_number' => $documentNumber,
                'company_id' => $data->companyId,
                // Looked up rather than taken from the form, so the two can never
                // disagree and a company renamed afterwards is not rewritten on
                // documents raised while it had the old name.
                'company_name' => Company::query()->whereKey($data->companyId)->value('name') ?? '',
                'date' => $data->date?->format('Y-m-d'),
                'status' => $data->status,
                // An unstated transfer type is a cheque, which is what it has
                // always meant here.
                'payment_transfer_type' => $data->paymentTransferType ?? PaymentTransferType::CHEQUE,
                'reference_number' => $data->referenceNumber,
                'attachment_path' => $attachmentPath,
                'notes' => $data->notes,
                'total_amount' => $amount,
            ]);

            // One line per side. The lines carry what the money was for; the
            // accounts live on the receipt's chart rows, resolved on read so a
            // receipt printed later still shows the accounts that were correct
            // when the money arrived.
            $receipt->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);

            $receipt->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);

            $receipt->charts()->create([
                'debit_id' => $debitChart->id,
                'debit_acc' => $debitChart->code,
                'debit_acc_desc' => $debitChart->name,
                'document_number' => $documentNumber,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => $amount,
                'credit_amount' => '0.00',
                'total_amount' => $amount,
            ]);

            $receipt->charts()->create([
                'credit_id' => $creditChart->id,
                'credit_acc' => $creditChart->code,
                'credit_acc_desc' => $creditChart->name,
                'document_number' => $documentNumber,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => '0.00',
                'credit_amount' => $amount,
                'total_amount' => $amount,
            ]);
        });

        return redirect()
            ->route('bank.receipts.show', $documentNumber)
            ->with('success', FlashMessage::created(FileResources::PAYMENTS));
    }

    /**
     * Update an existing resource by its object.
     * Update one unposted receipt.
     *
     @param  UpdateReceiptRequest  $request  The validated receipt being saved.
     * @param  string  $documentNumber  The receipt document number.
     * @return RedirectResponse Redirect response to the receipt detail page.
     *
     * @throws Throwable
     */
    public function update(UpdateReceiptRequest $request, string $documentNumber): RedirectResponse
    {
        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if($receipt->isPosted(), 409);

        // The form, parsed into a document. Everything below works from this
        // object, and the request is not asked anything again.
        $data = ReceiptData::from($request->validated());

        /** @var Chart|null $debitChart */
        $debitChart = Chart::query()->with('chartType')->find($data->debitAccountId);
        /** @var Chart|null $creditChart */
        $creditChart = Chart::query()->with('chartType')->find($data->creditAccountId);

        abort_if($debitChart === null || $creditChart === null, 422);
        abort_if(! $debitChart->isBank, 422);
        abort_if($creditChart->chartType?->isAsset !== true || ! $creditChart->isDebtor, 422);

        $this->ensureMoneyAccountMatchesTransferType(
            $debitChart,
            $data->paymentTransferType ?? $receipt->payment_transfer_type ?? PaymentTransferType::CHEQUE
        );

        $amount = bcadd(DecimalService::normalize($data->amount()), '0', 2);

        DB::transaction(function () use ($receipt, $data, $amount, $debitChart, $creditChart): void {
            $receipt->update([
                'company_id' => $data->companyId,
                'company_name' => filled($data->companyId)
                    ? (Company::query()->whereKey($data->companyId)->value('name') ?? $receipt->company_name)
                    : $receipt->company_name,
                'date' => $data->date?->format('Y-m-d'),
                'payment_transfer_type' => $data->paymentTransferType ?? $receipt->payment_transfer_type,
                'reference_number' => $data->referenceNumber,
                'notes' => $data->notes,
                'total_amount' => $amount,
            ]);

            // The lines and chart rows are replaced rather than amended. A
            // receipt carries one amount across two accounts, so editing one
            // side without the other is the one change that would leave it
            // unbalanceable.
            $receipt->lines()->delete();
            $receipt->charts()->delete();
            $receipt->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);
            $receipt->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);
            $receipt->charts()->create([
                'debit_id' => $debitChart->id,
                'debit_acc' => $debitChart->code,
                'debit_acc_desc' => $debitChart->name,
                'document_number' => $receipt->document_number,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => $amount,
                'credit_amount' => '0.00',
                'total_amount' => $amount,
            ]);
            $receipt->charts()->create([
                'credit_id' => $creditChart->id,
                'credit_acc' => $creditChart->code,
                'credit_acc_desc' => $creditChart->name,
                'document_number' => $receipt->document_number,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => '0.00',
                'credit_amount' => $amount,
                'total_amount' => $amount,
            ]);
        });

        return redirect()
            ->route('bank.receipts.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::PAYMENTS));
    }

    /**
     * Remove the specified resource from storage.
     * Delete one pending receipt.
     *
     * @param  string  $documentNumber  The receipt document number.
     * @return RedirectResponse Redirect response to the receipt index page.
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PAYMENTS, FileAction::REMOVE);

        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if($receipt->isPosted(), 409);

        $receipt->lines()->delete();
        $receipt->charts()->delete();
        $receipt->delete();

        return redirect()
            ->route('bank.receipts.index')
            ->with('success', FlashMessage::deleted(FileResources::PAYMENTS));
    }

    /**
     * Approve a receipt and post it to the ledgers.
     *
     * @param  string  $documentNumber  The receipt document number.
     * @return RedirectResponse Redirect response to the receipt detail page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {

        DB::transaction(function () use ($documentNumber): void {
            $receipt = Receipt::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->firstOrFail();

            $receipt->load('charts');

            abort_if($receipt->status === LedgerStatus::POSTED, 409);
            abort_if($receipt->charts->isEmpty(), 422, __('receipts.validation.lines_required'));

            $legs = $this->legs($receipt->charts, $receipt->reference_number);

            // A receipt that does not balance cannot be posted, because posting
            // it would write an entry the general ledger cannot accept.
            if (bccomp(PostingLeg::sumDebit($legs), PostingLeg::sumCredit($legs), 2) !== 0) {
                throw ValidationException::withMessages([
                    'document_number' => __('journal_entries.unbalanced_entry_error'),
                ]);
            }

            $this->poster->post(
                $receipt,
                $receipt->document_number,
                $receipt->date,
                LedgerType::PAYMENT,
                LedgerSubType::RECEIPT,
                $legs,
                companyId: $receipt->company_id,
                contactId: $receipt->contact_id,
                referenceNumber: $receipt->reference_number,
            );

            $receipt->update(['status' => LedgerStatus::POSTED]);

            // Recorded after the posting so an approver is never recorded against
            // a receipt that did not post. The attribution saves the receipt
            // itself, so this is the last write.
            $this->attributeApproved($receipt);
        });

        return redirect()
            ->route('bank.receipts.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::PAYMENTS));
    }

    /**
     * Approve multiple receipts and post them to the ledgers.
     *
     @param  BatchApproveReceiptRequest  $request  The receipts selected for approval.
     * @return RedirectResponse Redirect response to the receipt detail page.
     *
     * @throws Throwable
     */
    public function batchApprove(BatchApproveReceiptRequest $request): RedirectResponse
    {
        $documentNumbers = $request->documentNumbers();

        // Every check runs inside the transaction and rejects rather than skips. A
        // batch is approved or it is not: if one receipt fails a check the whole
        // selection is rolled back and the user is told which receipt stopped it,
        // because a count that quietly dropped some is worse than no count.
        $count = DB::transaction(function () use ($documentNumbers): int {
            $count = 0;

            foreach ($documentNumbers as $documentNumber) {
                $receipt = Receipt::query()
                    ->where('document_number', $documentNumber)
                    ->where('status', LedgerStatus::PENDING)
                    ->lockForUpdate()
                    ->first();

                if ($receipt === null) {
                    throw ValidationException::withMessages([
                        'document_number' => __('common.batch_rejected.not_pending', ['number' => $documentNumber]),
                    ]);
                }

                $receipt->load('charts');

                if ($receipt->charts->isEmpty()) {
                    throw ValidationException::withMessages([
                        'document_number' => __('common.batch_rejected.no_lines', ['number' => $documentNumber]),
                    ]);
                }

                $legs = $this->legs($receipt->charts, $receipt->reference_number);

                if (bccomp(PostingLeg::sumDebit($legs), PostingLeg::sumCredit($legs), 2) !== 0) {
                    throw ValidationException::withMessages([
                        'document_number' => __('common.batch_rejected.unbalanced', ['number' => $documentNumber]),
                    ]);
                }

                $this->poster->post(
                    $receipt,
                    $receipt->document_number,
                    $receipt->date,
                    LedgerType::PAYMENT,
                    LedgerSubType::RECEIPT,
                    $legs,
                    companyId: $receipt->company_id,
                    contactId: $receipt->contact_id,
                    referenceNumber: $receipt->reference_number,
                );

                $receipt->update(['status' => LedgerStatus::POSTED]);
                $this->attributeApproved($receipt);

                $count++;
            }

            return $count;
        });

        return redirect()
            ->route('bank.receipts.history.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending receipts.
     *
     @param  BatchCancelReceiptRequest  $request  The receipts selected for cancellation.
     * @return RedirectResponse Redirect response to the receipt detail page.
     *
     * @throws Throwable
     */
    public function batchCancel(BatchCancelReceiptRequest $request): RedirectResponse
    {

        // Fetched and updated one at a time rather than in a single bulk update,
        // because a bulk update writes the same columns for every row it touches
        // and so cannot record which user cancelled each document. Each receipt
        // is a document in its own right and needs its own attribution.
        $receipts = Receipt::query()
            ->whereIn('document_number', $request->documentNumbers())
            ->where('status', LedgerStatus::PENDING)
            ->get();

        foreach ($receipts as $receipt) {
            $receipt->update(['status' => LedgerStatus::CANCELLED]);
            $this->attributeCancelled($receipt);
        }

        $count = $receipts->count();

        return redirect()
            ->route('bank.receipts.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $count]));
    }

    /**
     * Load sales invoices for the reference number dropdown.
     *
     * Returns a JSON list of posted sales invoices for the selected company,
     * formatted for autocomplete. The user can search by invoice number, company
     * name, or notes.
     *
     * @param  Request  $request  The incoming request with company_id and search.
     * @return JsonResponse The JSON response with invoice options.
     */
    public function invoiceOptions(Request $request): JsonResponse
    {
        /** @var array{company_id: string, search?: string|null} $validated */
        $validated = $request->validate([
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim($validated['search'] ?? '');

        $results = SalesInvoice::query()
            ->where('status', LedgerStatus::POSTED)
            ->where('company_id', $validated['company_id'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
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
                $notes = filled($invoice->notes) ? " - $invoice->notes" : '';

                return [
                    'value' => $invoice->document_number,
                    'text' => "$invoice->document_number - $invoice->date - (RM $amount)$notes",
                ];
            })
            ->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Ensure the debit account's money kind matches the transfer type.
     *
     * Posting resolves the money ledger purely from the debit chart's bank
     * account, so a cash receipt debiting a bank chart would land in the bank
     * ledger while labelled cash. The mismatch is refused here, at write
     * time, using the same chart-to-account resolution as the poster.
     *
     * @param  Chart  $debitChart  The resolved debit chart account.
     * @param  PaymentTransferType  $transferType  The declared transfer type.
     * @return void No return value; throws when the pair disagrees.
     *
     * @throws ValidationException When the account kind cannot carry the type.
     */
    private function ensureMoneyAccountMatchesTransferType(
        Chart $debitChart,
        PaymentTransferType $transferType
    ): void {
        $chartKey = $debitChart->getKey();

        if (! is_string($chartKey)) {
            throw ValidationException::withMessages([
                'debit_id' => __('payments.validation.debit_account_invalid'),
            ]);
        }

        // Accounts now live in three separate tables; search each in turn and
        // derive the kind from the table the account was found in.
        $kind = $this->resolveMoneyAccountKind($chartKey);

        if ($kind === null) {
            throw ValidationException::withMessages([
                'debit_id' => __('payments.validation.debit_account_invalid'),
            ]);
        }

        $matches = match ($transferType) {
            PaymentTransferType::CASH => $kind === BankAccountKind::CASH,
            PaymentTransferType::CHEQUE,
            PaymentTransferType::BANK_TRANSFER => $kind === BankAccountKind::BANK,
            PaymentTransferType::E_WALLET => $kind === BankAccountKind::E_WALLET,
        };

        if (! $matches) {
            throw ValidationException::withMessages([
                'debit_id' => __('payments.validation.debit_account_invalid'),
            ]);
        }
    }

    /**
     * Look up which money-account table holds the given chart and return its kind.
     *
     * The three money-account tables (banks, cash_accounts, e_wallet_accounts)
     * each represent a different kind of account. This helper checks each table
     * for an active row whose chart_id matches, returning the kind implied by
     * the table it was found in, or null when no match exists.
     *
     * @param  string  $chartKey  The chart primary key to look up.
     * @return BankAccountKind|null The account kind, or null if not found.
     */
    private function resolveMoneyAccountKind(string $chartKey): ?BankAccountKind
    {
        $bank = Bank::query()
            ->whereNotNull('chart_id')
            ->where('chart_id', $chartKey)
            ->where('is_active', true)
            ->first();

        if ($bank instanceof Bank) {
            return BankAccountKind::BANK;
        }

        $cashAccount = CashAccount::query()
            ->whereNotNull('chart_id')
            ->where('chart_id', $chartKey)
            ->where('is_active', true)
            ->first();

        if ($cashAccount instanceof CashAccount) {
            return BankAccountKind::CASH;
        }

        $eWalletAccount = EWalletAccount::query()
            ->whereNotNull('chart_id')
            ->where('chart_id', $chartKey)
            ->where('is_active', true)
            ->first();

        if ($eWalletAccount instanceof EWalletAccount) {
            return BankAccountKind::E_WALLET;
        }

        return null;
    }

    /**
     * Turn a receipt's chart rows into the double entry the poster expects.
     *
     * Each chart row is a single movement — a receipt chart debits one account
     * or credits one, never both — so every row becomes exactly one leg. A row
     * that names no account cannot become a movement and is refused rather
     * than quietly dropped, because a receipt that lost a row on the way to
     * the ledger would still balance while carrying less than it says.
     *
     * @param  Collection<int, ReceiptChart>  $charts  The receipt's chart rows.
     * @param  string|null  $referenceNumber  The receipt's reference number, carried
     *                                        onto each leg so the general ledger rows
     *                                        record what the receipt settles.
     * @return list<PostingLeg> The double entry, one leg per chart row.
     */
    private function legs(Collection $charts, ?string $referenceNumber = null): array
    {
        $legs = [];

        foreach ($charts as $chart) {
            /** @var ReceiptChart $chart */
            $chartId = $chart->debit_id ?? $chart->credit_id;

            if ($chartId === null) {
                throw PostingException::oneSidedEntryRequired($chart->id);
            }

            $legs[] = $chart->debit_id !== null
                ? PostingLeg::debit($chartId, DecimalService::normalize($chart->debit_amount), $chart->description, $referenceNumber)
                : PostingLeg::credit($chartId, DecimalService::normalize($chart->credit_amount), $chart->description, $referenceNumber);
        }

        return $legs;
    }
}
