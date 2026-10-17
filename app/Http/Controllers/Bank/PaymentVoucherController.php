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
use App\Http\Requests\Bank\PaymentVoucher\BatchApprovePaymentVoucherRequest;
use App\Http\Requests\Bank\PaymentVoucher\BatchCancelPaymentVoucherRequest;
use App\Http\Requests\Bank\PaymentVoucher\StorePaymentVoucherRequest;
use App\Http\Requests\Bank\PaymentVoucher\UpdatePaymentVoucherRequest;
use App\Models\Bank\Bank;
use App\Models\Bank\CashAccount;
use App\Models\Bank\EWalletAccount;
use App\Models\Bank\PaymentVoucher;
use App\Models\Bank\PaymentVoucherChart;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\Company;
use App\Models\Purchase\PurchaseInvoice;
use App\Services\DecimalService;
use App\Support\Data\Bank\PaymentVoucherData;
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
 * Payment vouchers: money paid to a supplier.
 *
 * A voucher is a document with its own number, its own approval and its own
 * history, not a pair of rows that happen to share a document number.
 *
 * A voucher is the mirror of a receipt. A receipt debits money received and
 * credits what a customer owes; a voucher debits what is owed to a supplier and
 * credits the money account it left from. That symmetry is why the two write to
 * opposite ledgers: a receipt feeds the receivables ledger and a voucher feeds
 * the payables one, and a payment that reached the wrong one would leave the
 * customer and supplier balances disagreeing with the bank.
 *
 * What a voucher writes on approval is decided by {@see LedgerPoster} from the
 * accounts its lines name, not from anything here. A voucher paid out of a
 * drawer and one drawn from a bank account are the same document to this
 * controller; the ledger each produces follows from where the money actually went.
 */
class PaymentVoucherController extends Controller
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
     * Display every payment voucher that is still pending.
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

        $vouchers = PaymentVoucher::query()
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

        return view('bank.voucher.index', compact('vouchers', 'search', 'startDate', 'endDate'));
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
        // Only creditor companies can be paid by a voucher, and only a money
        // account can be paid from, so the form is not offered choices that
        // would be refused on submit.
        return view('bank.voucher.create', [
            'companies' => Company::query()
                ->where('is_creditor', true)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),
            'charts' => Chart::query()
                ->with('chartType')
                ->whereNull('deleted_at')
                ->orderBy('code')
                ->get(),
            'documentNumber' => $this->generateDocumentNumber(LedgerSubType::PAYMENT_VOUCHER),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $documentNumber  The document number of the payment voucher.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $voucher = PaymentVoucher::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        $voucher->load([
            'charts.debitChart',
            'charts.creditChart',
        ]);

        return view('bank.voucher.show', compact('voucher', 'documentNumber'));
    }

    /**
     * Show the form for editing an open resource.
     * Show the edit form for one pending payment voucher.
     *
     * @param  string  $documentNumber  The payment voucher document number.
     * @return View Returns the rendered Blade view.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::PAYMENTS, FileAction::UPDATE);

        $voucher = PaymentVoucher::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if($voucher->isPosted(), 409);

        $companies = Company::query()
            ->where('is_creditor', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $charts = Chart::query()
            ->with('chartType')
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get();

        return view('bank.voucher.edit', compact('voucher', 'documentNumber', 'companies', 'charts'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     @param  StorePaymentVoucherRequest  $request  The validated voucher being created.
     * @return RedirectResponse Redirect response to the payment voucher detail page.
     *
     * @throws Throwable
     */
    public function store(StorePaymentVoucherRequest $request): RedirectResponse
    {
        // The file is written to disk before the form is parsed, because an
        // uploaded file is not form data: it has no stored name and no path yet,
        // and only the write gives it one. This is the last the request is read.
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'voucher_attachments')
            : null;

        // The form, parsed into a document. Everything below works from this
        // object, and the request is not asked anything again.
        $data = PaymentVoucherData::from($request->validated());

        $chartSetting = ChartSetting::query()->first();

        // A payment voucher debits what is owed to the supplier and credits the
        // money account it left. The accounts default to the chart settings so a
        // voucher can be raised without choosing them, which is the common case.
        // The money default follows the transfer type: e-wallet payments leave
        // the wallet account, everything else leaves the bank account.
        $debitId = $data->debitAccountId ?? $chartSetting?->vendor_chart_uuid;
        $transferType = $data->paymentTransferType ?? PaymentTransferType::CHEQUE;
        $creditId = $data->creditAccountId ?? (
            $transferType === PaymentTransferType::E_WALLET
                ? ($chartSetting->e_wallet_chart_uuid ?? $chartSetting?->bank_chart_uuid)
                : $chartSetting?->bank_chart_uuid
        );

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

        // What is being paid off is always a creditor, and the money always
        // leaves a money account. Anything else is a different document: paying
        // an expense is a journal entry, and receiving money is a receipt.
        if ($debitChart->chartType?->isLiability !== true || ! $debitChart->isCreditor) {
            throw ValidationException::withMessages([
                'debit_id' => __('payments.validation.debit_account_invalid'),
            ]);
        }

        if (! $creditChart->isBank) {
            throw ValidationException::withMessages([
                'credit_id' => __('payments.validation.credit_account_invalid'),
            ]);
        }

        $this->ensureMoneyAccountAllowsApTransfer(
            $creditChart,
            $data->paymentTransferType ?? PaymentTransferType::CHEQUE
        );

        $documentNumber = $this->generateDocumentNumber(LedgerSubType::PAYMENT_VOUCHER);

        // The amount is normalised with decimal arithmetic rather than cast to a
        // float, so what is written is what the user typed and a ledger has to
        // balance to the cent.
        $amount = bcadd(DecimalService::normalize($data->amount()), '0', 2);

        DB::transaction(function () use ($data, $documentNumber, $amount, $attachmentPath, $debitChart, $creditChart): void {
            $voucher = PaymentVoucher::query()->create([
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
            // accounts live on the voucher's chart rows, so a voucher printed
            // later still shows the accounts that were correct when the money
            // left.
            $voucher->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);

            $voucher->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);

            $voucher->charts()->create([
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

            $voucher->charts()->create([
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
            ->route('bank.payment-vouchers.show', $documentNumber)
            ->with('success', FlashMessage::created(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Update an existing resource by its object.
     * Update one unposted payment voucher.
     *
     @param  UpdatePaymentVoucherRequest  $request  The validated voucher being saved.
     * @param  string  $documentNumber  The payment voucher document number.
     * @return RedirectResponse Redirect response to the payment voucher detail page.
     *
     * @throws Throwable
     */
    public function update(UpdatePaymentVoucherRequest $request, string $documentNumber): RedirectResponse
    {
        $voucher = PaymentVoucher::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if($voucher->isPosted(), 409);

        // The form, parsed into a document. Everything below works from this
        // object, and the request is not asked anything again.
        $data = PaymentVoucherData::from($request->validated());

        /** @var Chart|null $debitChart */
        $debitChart = Chart::query()->with('chartType')->find($data->debitAccountId);
        /** @var Chart|null $creditChart */
        $creditChart = Chart::query()->with('chartType')->find($data->creditAccountId);

        abort_if($debitChart === null || $creditChart === null, 422);
        abort_if($debitChart->chartType?->isLiability !== true || ! $debitChart->isCreditor, 422);
        abort_if(! $creditChart->isBank, 422);

        $this->ensureMoneyAccountAllowsApTransfer(
            $creditChart,
            $data->paymentTransferType ?? $voucher->payment_transfer_type ?? PaymentTransferType::CHEQUE
        );

        $amount = bcadd(DecimalService::normalize($data->amount()), '0', 2);

        DB::transaction(function () use ($voucher, $data, $amount, $debitChart, $creditChart): void {
            $voucher->update([
                'company_id' => $data->companyId,
                'company_name' => filled($data->companyId)
                    ? (Company::query()->whereKey($data->companyId)->value('name') ?? $voucher->company_name)
                    : $voucher->company_name,
                'date' => $data->date?->format('Y-m-d'),
                'payment_transfer_type' => $data->paymentTransferType ?? $voucher->payment_transfer_type,
                'reference_number' => $data->referenceNumber,
                'notes' => $data->notes,
                'total_amount' => $amount,
            ]);

            // The lines and chart rows are replaced rather than amended. A
            // voucher carries one amount across two accounts, so editing one
            // side without the other is the one change that would leave it
            // unbalanceable.
            $voucher->lines()->delete();
            $voucher->charts()->delete();
            $voucher->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);
            $voucher->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);
            $voucher->charts()->create([
                'debit_id' => $debitChart->id,
                'debit_acc' => $debitChart->code,
                'debit_acc_desc' => $debitChart->name,
                'document_number' => $voucher->document_number,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => $amount,
                'credit_amount' => '0.00',
                'total_amount' => $amount,
            ]);
            $voucher->charts()->create([
                'credit_id' => $creditChart->id,
                'credit_acc' => $creditChart->code,
                'credit_acc_desc' => $creditChart->name,
                'document_number' => $voucher->document_number,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => '0.00',
                'credit_amount' => $amount,
                'total_amount' => $amount,
            ]);
        });

        return redirect()
            ->route('bank.payment-vouchers.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Remove the specified resource from storage.
     * Delete one pending payment voucher.
     *
     * @param  string  $documentNumber  The payment voucher document number.
     * @return RedirectResponse Redirect response to the payment voucher index page.
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PAYMENTS, FileAction::REMOVE);

        $voucher = PaymentVoucher::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if($voucher->isPosted(), 409);

        $voucher->lines()->delete();
        $voucher->charts()->delete();
        $voucher->delete();

        return redirect()
            ->route('bank.payment-vouchers.index')
            ->with('success', FlashMessage::deleted(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Approve a payment voucher and post it to the ledgers.
     *
     * @param  string  $documentNumber  The payment voucher document number.
     * @return RedirectResponse Redirect response to the payment voucher detail page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {

        DB::transaction(function () use ($documentNumber): void {
            $voucher = PaymentVoucher::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->firstOrFail();

            $voucher->load('charts');

            abort_if($voucher->status === LedgerStatus::POSTED, 409);
            abort_if($voucher->charts->isEmpty(), 422, __('payment_vouchers.validation.lines_required'));

            $legs = $this->legs($voucher->charts, $voucher->reference_number);

            // A voucher that does not balance cannot be posted, because posting
            // it would write an entry the general ledger cannot accept.
            if (bccomp(PostingLeg::sumDebit($legs), PostingLeg::sumCredit($legs), 2) !== 0) {
                throw ValidationException::withMessages([
                    'document_number' => __('journal_entries.unbalanced_entry_error'),
                ]);
            }

            $this->poster->post(
                $voucher,
                $voucher->document_number,
                $voucher->date,
                LedgerType::PAYMENT,
                LedgerSubType::PAYMENT_VOUCHER,
                $legs,
                companyId: $voucher->company_id,
                contactId: $voucher->contact_id,
                referenceNumber: $voucher->reference_number,
            );

            $voucher->update(['status' => LedgerStatus::POSTED]);

            // Recorded after the posting so an approver is never recorded against
            // a voucher that did not post. The attribution saves the voucher
            // itself, so this is the last write.
            $this->attributeApproved($voucher);
        });

        return redirect()
            ->route('bank.payment-vouchers.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Approve multiple payment vouchers and post them to the ledgers.
     *
     @param  BatchApprovePaymentVoucherRequest  $request  The vouchers selected for approval.
     * @return RedirectResponse Redirect response to the payment voucher detail page.
     *
     * @throws Throwable
     */
    public function batchApprove(BatchApprovePaymentVoucherRequest $request): RedirectResponse
    {
        $documentNumbers = $request->documentNumbers();

        // Every check runs inside the transaction and rejects rather than skips. A batch
        // is approved or it is not: if one voucher fails a check the whole
        // selection is rolled back and the user is told which voucher stopped it,
        // because a count that quietly dropped some is worse than no count.
        $count = DB::transaction(function () use ($documentNumbers): int {
            $count = 0;

            foreach ($documentNumbers as $documentNumber) {
                $voucher = PaymentVoucher::query()
                    ->where('document_number', $documentNumber)
                    ->where('status', LedgerStatus::PENDING)
                    ->lockForUpdate()
                    ->first();

                if ($voucher === null) {
                    throw ValidationException::withMessages([
                        'document_number' => __('common.batch_rejected.not_pending', ['number' => $documentNumber]),
                    ]);
                }

                $voucher->load('charts');

                if ($voucher->charts->isEmpty()) {
                    throw ValidationException::withMessages([
                        'document_number' => __('common.batch_rejected.no_lines', ['number' => $documentNumber]),
                    ]);
                }

                $legs = $this->legs($voucher->charts, $voucher->reference_number);

                if (bccomp(PostingLeg::sumDebit($legs), PostingLeg::sumCredit($legs), 2) !== 0) {
                    throw ValidationException::withMessages([
                        'document_number' => __('common.batch_rejected.unbalanced', ['number' => $documentNumber]),
                    ]);
                }

                $this->poster->post(
                    $voucher,
                    $voucher->document_number,
                    $voucher->date,
                    LedgerType::PAYMENT,
                    LedgerSubType::PAYMENT_VOUCHER,
                    $legs,
                    companyId: $voucher->company_id,
                    contactId: $voucher->contact_id,
                    referenceNumber: $voucher->reference_number,
                );

                $voucher->update(['status' => LedgerStatus::POSTED]);
                $this->attributeApproved($voucher);

                $count++;
            }

            return $count;
        });

        return redirect()
            ->route('bank.payment-vouchers.history.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending payment vouchers.
     *
     @param  BatchCancelPaymentVoucherRequest  $request  The vouchers selected for cancellation.
     * @return RedirectResponse Redirect response to the payment voucher detail page.
     *
     * @throws Throwable
     */
    public function batchCancel(BatchCancelPaymentVoucherRequest $request): RedirectResponse
    {

        // Fetched and updated one at a time rather than in a single bulk update,
        // because a bulk update writes the same columns for every row it touches
        // and so cannot record which user cancelled each document. Each voucher
        // is a document in its own right and needs its own attribution.
        $vouchers = PaymentVoucher::query()
            ->whereIn('document_number', $request->documentNumbers())
            ->where('status', LedgerStatus::PENDING)
            ->get();

        foreach ($vouchers as $voucher) {
            $voucher->update(['status' => LedgerStatus::CANCELLED]);
            $this->attributeCancelled($voucher);
        }

        $count = $vouchers->count();

        return redirect()
            ->route('bank.payment-vouchers.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $count]));
    }

    /**
     * Load purchase invoices for the reference number dropdown.
     *
     * Returns a JSON list of posted purchase invoices for the selected company,
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

        $results = PurchaseInvoice::query()
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
            ->map(function (PurchaseInvoice $invoice): array {
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
     * Ensure the money account can pay on the AP side.
     *
     * Supplier payments move on bank rails or e-wallets; cash never pays a
     * voucher. Accounts live in three separate tables (banks, cash_accounts,
     * e_wallet_accounts), so each is searched in turn to resolve the account
     * kind. Refused here, at write time, using the same chart-to-account
     * resolution as the poster.
     *
     * @param  Chart  $creditChart  The resolved money (credit) chart account.
     * @param  PaymentTransferType  $transferType  The declared transfer type.
     * @return void No return value; throws when the pair disagrees.
     *
     * @throws ValidationException When the account kind cannot pay this way.
     */
    private function ensureMoneyAccountAllowsApTransfer(
        Chart $creditChart,
        PaymentTransferType $transferType
    ): void {
        $chartKey = $creditChart->getKey();

        if (! is_string($chartKey)) {
            throw ValidationException::withMessages([
                'credit_id' => __('payments.validation.credit_account_invalid'),
            ]);
        }

        // Accounts now live in three separate tables. Search each in turn to
        // find which kind of money account the chart is backed by.
        $bank = Bank::query()
            ->whereNotNull('chart_id')
            ->where('is_active', true)
            ->get()
            ->keyBy('chart_id')
            ->get($chartKey);

        if ($bank instanceof Bank) {
            $kind = BankAccountKind::BANK;
        } else {
            $cashAccount = CashAccount::query()
                ->whereNotNull('chart_id')
                ->where('is_active', true)
                ->get()
                ->keyBy('chart_id')
                ->get($chartKey);

            if ($cashAccount instanceof CashAccount) {
                $kind = BankAccountKind::CASH;
            } else {
                $eWalletAccount = EWalletAccount::query()
                    ->whereNotNull('chart_id')
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('chart_id')
                    ->get($chartKey);

                if ($eWalletAccount instanceof EWalletAccount) {
                    $kind = BankAccountKind::E_WALLET;
                } else {
                    throw ValidationException::withMessages([
                        'credit_id' => __('payments.validation.credit_account_invalid'),
                    ]);
                }
            }
        }

        // Cash never moves on the AP side, whatever the label claims.
        // Each transfer type must match the kind of account it moves through.
        $matches = match ($transferType) {
            PaymentTransferType::CASH => false,
            PaymentTransferType::CHEQUE,
            PaymentTransferType::BANK_TRANSFER => $kind === BankAccountKind::BANK,
            PaymentTransferType::E_WALLET => $kind === BankAccountKind::E_WALLET,
        };

        if (! $matches) {
            throw ValidationException::withMessages([
                'credit_id' => __('payments.validation.credit_account_invalid'),
            ]);
        }
    }

    /**
     * Turn a voucher's chart rows into the double entry the poster expects.
     *
     * Each chart row is a single movement — a voucher chart debits one account
     * or credits one, never both — so every row becomes exactly one leg. A row
     * that names no account cannot become a movement and is refused rather
     * than quietly dropped, because a voucher that lost a row on the way to
     * the ledger would still balance while carrying less than it says.
     *
     * @param  Collection<int, PaymentVoucherChart>  $charts  The voucher's chart rows.
     * @param  string|null  $referenceNumber  The voucher's reference number, carried
     *                                        onto each leg so the general ledger rows
     *                                        record what the payment settles.
     * @return list<PostingLeg> The double entry, one leg per chart row.
     */
    private function legs(Collection $charts, ?string $referenceNumber = null): array
    {
        $legs = [];

        foreach ($charts as $chart) {
            /** @var PaymentVoucherChart $chart */
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
