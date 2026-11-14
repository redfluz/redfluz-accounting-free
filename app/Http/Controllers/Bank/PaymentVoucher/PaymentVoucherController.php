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

namespace App\Http\Controllers\Bank\PaymentVoucher;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Enums\Finance\PaymentTransferType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\PaymentVoucher\BatchApprovePaymentVoucherRequest;
use App\Http\Requests\Bank\PaymentVoucher\BatchCancelPaymentVoucherRequest;
use App\Http\Requests\Bank\PaymentVoucher\StorePaymentVoucherRequest;
use App\Http\Requests\Bank\PaymentVoucher\UpdatePaymentVoucherRequest;
use App\Models\Bank\Bank;
use App\Models\Bank\CashAccount;
use App\Models\Bank\EWalletAccount;
use App\Models\Bank\PaymentVoucher;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\Company;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Bank\PaymentVoucherData;
use App\Support\Posting\Posting;
use App\Traits\GeneratesDocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PaymentVoucherController extends Controller
{
    use GeneratesDocumentNumber;

    public function __construct(private readonly Posting $posting) {}

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $vouchers = PaymentVoucher::query()
            ->where('status', DocumentStatus::PENDING)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('document_number', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%")
                    ->orWhere('reference_number', 'like', "%$search%");
            }))
            ->when($startDate !== '', fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($q) => $q->whereDate('date', '<=', $endDate))
            ->latest('date')->latest('created_at')
            ->paginate(static::PER_PAGE_GRID_STANDARD)->withQueryString();

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
        return view('bank.voucher.create', [
            'companies' => Company::query()->where('is_creditor', true)->whereNull('deleted_at')->orderBy('name')->get(),
            'documentNumber' => $this->generateDocumentNumber(LedgerSubType::PAYMENT_VOUCHER),
            ...$this->moneyAccounts(),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $documentNumber  The document number.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $voucher = PaymentVoucher::query()
            ->with(['bankAccount', 'cashAccount', 'eWalletAccount'])
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        return view('bank.voucher.show', compact('voucher', 'documentNumber'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $documentNumber  The document number.
     * @return View Returns the rendered Blade view.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS, FileAction::UPDATE);
        $voucher = PaymentVoucher::query()->where('document_number', $documentNumber)->firstOrFail();
        abort_if($voucher->isPosted(), 409);
        $companies = Company::query()->where('is_active', true)->orderBy('name')->get();

        return view('bank.voucher.edit', [
            'voucher' => $voucher,
            'documentNumber' => $documentNumber,
            'companies' => $companies,
            ...$this->moneyAccounts(),
        ]);
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StorePaymentVoucherRequest  $request  The validated voucher.
     * @return RedirectResponse Redirect response.
     *
     * @throws Throwable
     */
    public function store(StorePaymentVoucherRequest $request): RedirectResponse
    {
        $attachmentPath = $request->hasFile('attachment') ? $this->storeUpload($request->file('attachment'), 'voucher_attachments') : null;
        $data = PaymentVoucherData::from($request->validated());
        $documentNumber = $this->generateDocumentNumber(LedgerSubType::PAYMENT_VOUCHER);
        $amount = bcadd(DecimalService::normalize((string) ($data->totalAmount ?? $data->amount())), '0', 2);
        $transferType = $data->paymentTransferType ?? PaymentTransferType::CHEQUE;
        DB::transaction(function () use ($data, $documentNumber, $amount, $attachmentPath, $transferType): void {
            PaymentVoucher::query()->create([
                'id' => (string) Str::uuid(),
                'document_number' => $documentNumber,
                'company_id' => $data->companyId,
                'company_name' => Company::query()->whereKey($data->companyId)->value('name') ?? '',
                'date' => $data->date?->format('Y-m-d'),
                'status' => $data->status,
                'payment_transfer_type' => $transferType,
                ...$this->moneyAccountColumns($transferType, $data),
                'reference_number' => $data->referenceNumber,
                'attachment_path' => $attachmentPath,
                'notes' => $data->notes,
                'total_amount' => $amount,
            ]);
        });

        return redirect()->route('bank.payment-vouchers.show', $documentNumber)->with('success', FlashMessage::created(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Handle the incoming request to update the specified resource.
     *
     * @param  UpdatePaymentVoucherRequest  $request  The validated voucher.
     * @param  string  $documentNumber  The document number.
     * @return RedirectResponse Redirect response.
     *
     * @throws Throwable
     */
    public function update(UpdatePaymentVoucherRequest $request, string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS, FileAction::UPDATE);
        $voucher = PaymentVoucher::query()->where('document_number', $documentNumber)->firstOrFail();
        abort_if($voucher->isPosted(), 409);
        $data = PaymentVoucherData::from($request->validated());
        $amount = bcadd(DecimalService::normalize((string) ($data->totalAmount ?? $data->amount())), '0', 2);
        $transferType = $data->paymentTransferType ?? $voucher->payment_transfer_type;
        DB::transaction(function () use ($voucher, $data, $amount, $transferType): void {
            $voucher->update([
                'company_id' => $data->companyId,
                'company_name' => Company::query()->whereKey($data->companyId)->value('name') ?? (string) $voucher->company_name,
                'date' => $data->date?->format('Y-m-d'),
                'payment_transfer_type' => $transferType,
                ...$this->moneyAccountColumns($transferType, $data),
                'reference_number' => $data->referenceNumber,
                'notes' => $data->notes,
                'total_amount' => $amount,
            ]);
        });

        return redirect()->route('bank.payment-vouchers.show', $documentNumber)->with('success', FlashMessage::updated(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $documentNumber  The document number.
     * @return RedirectResponse Redirect response.
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS, FileAction::REMOVE);
        $voucher = PaymentVoucher::query()->where('document_number', $documentNumber)->firstOrFail();
        abort_if($voucher->isPosted(), 409);
        $voucher->delete();

        return redirect()->route('bank.payment-vouchers.index')->with('success', FlashMessage::deleted(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Approve a payment voucher and post it to the ledgers.
     * Posting accounts are resolved at approval time from chart settings.
     *
     * @param  string  $documentNumber  The document number.
     * @return RedirectResponse Redirect response.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        DB::transaction(function () use ($documentNumber): void {
            $voucher = PaymentVoucher::query()->where('document_number', $documentNumber)->lockForUpdate()->firstOrFail();
            abort_if($voucher->status === DocumentStatus::POSTED, 409);
            [$debitChartId, $creditChartId, $amount] = $this->postingAccountsFor($voucher);
            $this->posting->postPayment(
                $voucher,
                $voucher->document_number,
                $voucher->date,
                LedgerType::PAYMENT,
                LedgerSubType::PAYMENT_VOUCHER,
                $debitChartId,
                $creditChartId,
                $amount,
                companyId: $voucher->company_id,
                companyName: $voucher->company_name,
                referenceNumber: $voucher->reference_number,
                description: $voucher->notes,
            );
            $voucher->update(['status' => DocumentStatus::POSTED]);
            $this->attributeApproved($voucher);
        });

        return redirect()->route('bank.payment-vouchers.show', $documentNumber)->with('success', FlashMessage::approved(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Approve multiple payment vouchers and post them to the ledgers.
     *
     * @param  BatchApprovePaymentVoucherRequest  $request  The request.
     * @return RedirectResponse Redirect response.
     *
     * @throws Throwable
     */
    public function batchApprove(BatchApprovePaymentVoucherRequest $request): RedirectResponse
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS, FileAction::APPROVE);
        $documentNumbers = $request->documentNumbers();
        $approved = 0;
        $failed = [];
        DB::transaction(function () use ($documentNumbers, &$approved, &$failed): void {
            foreach ($documentNumbers as $documentNumber) {
                $voucher = PaymentVoucher::query()->where('document_number', $documentNumber)->lockForUpdate()->first();
                if (! $voucher || $voucher->status === DocumentStatus::POSTED) {
                    $failed[] = $documentNumber;

                    continue;
                }
                try {
                    [$debitChartId, $creditChartId, $amount] = $this->postingAccountsFor($voucher);
                    $this->posting->postPayment(
                        $voucher,
                        $voucher->document_number,
                        $voucher->date,
                        LedgerType::PAYMENT,
                        LedgerSubType::PAYMENT_VOUCHER,
                        $debitChartId,
                        $creditChartId,
                        $amount,
                        companyId: $voucher->company_id,
                        companyName: $voucher->company_name,
                        referenceNumber: $voucher->reference_number,
                        description: $voucher->notes,
                    );
                    $voucher->update(['status' => DocumentStatus::POSTED]);
                    $this->attributeApproved($voucher);
                    $approved++;
                } catch (Throwable) {
                    $failed[] = $documentNumber;
                }
            }
        });
        $redirect = redirect()->route('bank.payment-vouchers.index');
        if ($approved > 0) {
            $redirect->with('success', __('common.messages.batch_approved', ['count' => $approved]));
        }
        if ($failed !== []) {
            $redirect->with('warning', __('common.messages.batch_approve_failed', ['numbers' => implode(', ', $failed)]));
        }

        return $redirect;
    }

    /**
     * The money accounts a voucher may be paid from, one list per kind.
     *
     * Split by kind rather than merged into one collection, because the form shows
     * the list the transfer type allows: a cheque is drawn on a bank, a cash
     * payment leaves a register and a wallet payment leaves a wallet. Each account
     * has to be live and have a chart behind it, since the chart is what the money
     * posts against.
     *
     * @return array{banks: Collection<int, Bank>, cashAccounts: Collection<int, CashAccount>, eWalletAccounts: Collection<int, EWalletAccount>}
     */
    private function moneyAccounts(): array
    {
        return [
            'banks' => Bank::query()
                ->with('chart')
                ->where('is_active', true)
                ->whereNotNull('chart_id')
                ->orderBy('name')
                ->get(),
            'cashAccounts' => CashAccount::query()
                ->with('chart')
                ->where('is_active', true)
                ->whereNotNull('chart_id')
                ->orderBy('name')
                ->get(),
            'eWalletAccounts' => EWalletAccount::query()
                ->with('chart')
                ->where('is_active', true)
                ->whereNotNull('chart_id')
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * The three account columns of a voucher, with only one of them filled.
     *
     * Which one is filled follows from the transfer type, and the other two are
     * written as null rather than left alone: an edit that changes a payment from
     * cash to a bank transfer has to let go of the drawer it named, or the voucher
     * would carry two accounts and the money ledger would be a matter of which one
     * the reader looked at first.
     *
     * @param  PaymentTransferType|null  $transferType  The type the voucher is saved with, or null when it never said.
     * @param  PaymentVoucherData  $data  The voucher as the form supplied it.
     * @return array{bank_account_id: string|null, cash_account_id: string|null, e_wallet_account_id: string|null} The three account columns.
     */
    private function moneyAccountColumns(?PaymentTransferType $transferType, PaymentVoucherData $data): array
    {
        $columns = [
            'bank_account_id' => null,
            'cash_account_id' => null,
            'e_wallet_account_id' => null,
        ];

        if ($transferType === null) {
            return $columns;
        }

        $column = $transferType->accountColumn();

        return match ($column) {
            'cash_account_id' => ['bank_account_id' => null, 'cash_account_id' => $data->cashAccountId, 'e_wallet_account_id' => null],
            'e_wallet_account_id' => ['bank_account_id' => null, 'cash_account_id' => null, 'e_wallet_account_id' => $data->eWalletAccountId],
            default => ['bank_account_id' => $data->bankAccountId, 'cash_account_id' => null, 'e_wallet_account_id' => null],
        };
    }

    /**
     * Resolve posting accounts (direct from chart settings). Debit vendor/AP, credit money.
     *
     * The money side is the chart of the account the voucher names, because every
     * account owns exactly one chart: posting against that chart is what lands the
     * movement in that account's own money ledger instead of whichever one the
     * settings happen to name. A voucher that never named an account, which is every
     * voucher raised before the account became a field, falls back to the settings
     * chart for its transfer type so it still posts.
     *
     * @return array{0: string, 1: string, 2: numeric-string}
     */
    private function postingAccountsFor(PaymentVoucher $voucher): array
    {
        $setting = ChartSetting::query()->first();
        $transferType = $voucher->payment_transfer_type ?? PaymentTransferType::CHEQUE;
        $moneyChartId = $voucher->moneyAccount()?->chart_id;
        $transferChartId = match ($transferType) {
            PaymentTransferType::CASH => $setting?->cash_chart_uuid,
            PaymentTransferType::E_WALLET => $setting?->e_wallet_chart_uuid,
            default => null,
        };
        $creditChartId = $moneyChartId ?? $transferChartId ?? $setting?->bank_chart_uuid;
        $debitChartId = $setting?->vendor_chart_uuid;
        if (blank($debitChartId) || blank($creditChartId)) {
            throw ValidationException::withMessages(['document_number' => __('ledgers.validation.debit_account_or_default_required')]);
        }
        $amount = bcadd(DecimalService::normalize((string) $voucher->total_amount), '0', 2);

        return [(string) $debitChartId, (string) $creditChartId, $amount];
    }

    public function batchCancel(BatchCancelPaymentVoucherRequest $request): RedirectResponse
    {

        // Fetched and updated one at a time rather than in a single bulk update,
        // because a bulk update writes the same columns for every row it touches
        // and so cannot record which user cancelled each document. Each voucher
        // is a document in its own right and needs its own attribution.
        $vouchers = PaymentVoucher::query()
            ->whereIn('document_number', $request->documentNumbers())
            ->where('status', DocumentStatus::PENDING)
            ->get();

        foreach ($vouchers as $voucher) {
            $voucher->update(['status' => DocumentStatus::CANCELLED]);
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

        $invoices = PurchaseInvoice::query()
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
            ->get();

        $results = $invoices
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
     * Print one payment voucher.
     *
     * @param  string  $documentNumber  The document number of the payment voucher.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS);

        $voucher = PaymentVoucher::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        return Pdf::loadView('bank.voucher.pdf', [
            'voucher' => $voucher,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->stream("voucher_$documentNumber.pdf");
    }
}
