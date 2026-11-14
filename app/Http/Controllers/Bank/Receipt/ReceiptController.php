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

namespace App\Http\Controllers\Bank\Receipt;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Enums\Finance\PaymentTransferType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\Receipt\BatchApproveReceiptRequest;
use App\Http\Requests\Bank\Receipt\BatchCancelReceiptRequest;
use App\Http\Requests\Bank\Receipt\StoreReceiptRequest;
use App\Http\Requests\Bank\Receipt\UpdateReceiptRequest;
use App\Models\Bank\Bank;
use App\Models\Bank\CashAccount;
use App\Models\Bank\EWalletAccount;
use App\Models\Bank\Receipt;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\Company;
use App\Models\Sales\SalesInvoice;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Bank\ReceiptData;
use App\Support\Posting\Posting;
use App\Traits\GeneratesDocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ReceiptController extends Controller
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

        $receipts = Receipt::query()
            ->where('status', DocumentStatus::PENDING)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('document_number', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%")
                    ->orWhere('reference_number', 'like', "%$search%");
            }))
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->latest('date')->latest('created_at')
            ->paginate(static::PER_PAGE_GRID_STANDARD)->withQueryString();

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
        return view('bank.receipt.create', [
            'companies' => Company::query()->where('is_debtor', true)->whereNull('deleted_at')->orderBy('name')->get(),
            'documentNumber' => $this->generateDocumentNumber(LedgerSubType::RECEIPT),
            ...$this->moneyAccounts(),
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
            ->with(['bankAccount.chart', 'cashAccount.chart', 'eWalletAccount.chart'])
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        return view('bank.receipt.show', compact('receipt', 'documentNumber'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return View Returns the rendered Blade view.
     */
    public function edit(string $documentNumber): View
    {
        $this->allows(FileResources::PAYMENTS, FileAction::UPDATE);
        $receipt = Receipt::query()->where('document_number', $documentNumber)->firstOrFail();
        abort_if($receipt->isPosted(), 409);
        $companies = Company::query()->where('is_active', true)->orderBy('name')->get();

        return view('bank.receipt.edit', [
            'receipt' => $receipt,
            'documentNumber' => $documentNumber,
            'companies' => $companies,
            ...$this->moneyAccounts(),
        ]);
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreReceiptRequest  $request  The validated receipt being created.
     * @return RedirectResponse Redirect response to the receipt detail page.
     *
     * @throws Throwable
     */
    public function store(StoreReceiptRequest $request): RedirectResponse
    {
        $attachmentPath = $request->hasFile('attachment') ? $this->storeUpload($request->file('attachment'), 'receipt_attachments') : null;
        $data = ReceiptData::from($request->validated());
        $documentNumber = $this->generateDocumentNumber(LedgerSubType::RECEIPT);
        $amount = bcadd(DecimalService::normalize($data->totalAmount ?? $data->amount()), '0', 2);
        $transferType = $data->paymentTransferType ?? PaymentTransferType::CHEQUE;

        DB::transaction(function () use ($data, $documentNumber, $amount, $attachmentPath, $transferType): void {
            Receipt::query()->create([
                'id' => (string) Str::uuid(),
                'document_number' => $documentNumber,
                'company_id' => $data->companyId,
                'company_name' => Company::query()->whereKey($data->companyId)->value('name') ?? '',
                'date' => $data->date?->format('Y-m-d'),
                'status' => $data->status,
                'payment_transfer_type' => $transferType,
                'reference_number' => $data->referenceNumber,
                'attachment_path' => $attachmentPath,
                'notes' => $data->notes,
                'total_amount' => $amount,
                ...$this->moneyAccountColumns($transferType, $data),
            ]);
        });

        return redirect()->route('bank.receipts.show', $documentNumber)->with('success', FlashMessage::created(FileResources::PAYMENTS));
    }

    /**
     * Handle the incoming request to update the specified resource.
     *
     * @param  UpdateReceiptRequest  $request  The validated receipt being updated.
     * @param  string  $documentNumber  The document number of the receipt.
     * @return RedirectResponse Redirect response to the receipt detail page.
     *
     * @throws Throwable
     */
    public function update(UpdateReceiptRequest $request, string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PAYMENTS, FileAction::UPDATE);
        $receipt = Receipt::query()->where('document_number', $documentNumber)->firstOrFail();
        abort_if($receipt->isPosted(), 409);
        $data = ReceiptData::from($request->validated());
        $amount = bcadd(DecimalService::normalize($data->totalAmount ?? $data->amount()), '0', 2);
        $transferType = $data->paymentTransferType ?? $receipt->payment_transfer_type ?? PaymentTransferType::CHEQUE;

        DB::transaction(function () use ($receipt, $data, $amount, $transferType): void {
            $receipt->update([
                'company_id' => $data->companyId,
                'company_name' => Company::query()->whereKey($data->companyId)->value('name') ?? (string) $receipt->company_name,
                'date' => $data->date?->format('Y-m-d'),
                'payment_transfer_type' => $transferType,
                'reference_number' => $data->referenceNumber,
                'notes' => $data->notes,
                'total_amount' => $amount,
                ...$this->moneyAccountColumns($transferType, $data),
            ]);
        });

        return redirect()->route('bank.receipts.show', $documentNumber)->with('success', FlashMessage::updated(FileResources::PAYMENTS));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $documentNumber  The receipt document number.
     * @return RedirectResponse Redirect response to the receipt index page.
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PAYMENTS, FileAction::REMOVE);
        $receipt = Receipt::query()->where('document_number', $documentNumber)->firstOrFail();
        abort_if($receipt->isPosted(), 409);
        $receipt->delete();

        return redirect()->route('bank.receipts.index')->with('success', FlashMessage::deleted(FileResources::PAYMENTS));
    }

    /**
     * Approve a receipt and post it to the ledgers.
     * Posting accounts are resolved at approval time from chart settings.
     * Receipt: debit money (by transfer type), credit client/AR — direct from receipt.
     *
     * @param  string  $documentNumber  The receipt document number.
     * @return RedirectResponse Redirect response to the receipt detail page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        DB::transaction(function () use ($documentNumber): void {
            $receipt = Receipt::query()->where('document_number', $documentNumber)->lockForUpdate()->firstOrFail();
            abort_if($receipt->status === DocumentStatus::POSTED, 409);
            [$debitChartId, $creditChartId, $amount] = $this->postingAccountsFor($receipt);
            $this->posting->postPayment(
                $receipt,
                $receipt->document_number,
                $receipt->date,
                LedgerType::PAYMENT,
                LedgerSubType::RECEIPT,
                $debitChartId,
                $creditChartId,
                $amount,
                companyId: $receipt->company_id,
                companyName: $receipt->company_name,
                referenceNumber: $receipt->reference_number,
                description: $receipt->notes,
            );
            $receipt->update(['status' => DocumentStatus::POSTED]);
            $this->attributeApproved($receipt);
        });

        return redirect()->route('bank.receipts.show', $documentNumber)->with('success', FlashMessage::approved(FileResources::PAYMENTS));
    }

    /**
     * Approve multiple receipts and post them to the ledgers.
     *
     * @param  BatchApproveReceiptRequest  $request  The request containing document numbers.
     * @return RedirectResponse Redirect response to the receipt index page.
     *
     * @throws Throwable
     */
    public function batchApprove(BatchApproveReceiptRequest $request): RedirectResponse
    {
        $this->allows(FileResources::PAYMENTS, FileAction::APPROVE);
        $documentNumbers = $request->documentNumbers();
        $approved = 0;
        $failed = [];
        DB::transaction(function () use ($documentNumbers, &$approved, &$failed): void {
            foreach ($documentNumbers as $documentNumber) {
                $receipt = Receipt::query()->where('document_number', $documentNumber)->lockForUpdate()->first();
                if (! $receipt || $receipt->status === DocumentStatus::POSTED) {
                    $failed[] = $documentNumber;

                    continue;
                }
                try {
                    [$debitChartId, $creditChartId, $amount] = $this->postingAccountsFor($receipt);
                    $this->posting->postPayment(
                        $receipt,
                        $receipt->document_number,
                        $receipt->date,
                        LedgerType::PAYMENT,
                        LedgerSubType::RECEIPT,
                        $debitChartId,
                        $creditChartId,
                        $amount,
                        companyId: $receipt->company_id,
                        companyName: $receipt->company_name,
                        referenceNumber: $receipt->reference_number,
                        description: $receipt->notes,
                    );
                    $receipt->update(['status' => DocumentStatus::POSTED]);
                    $this->attributeApproved($receipt);
                    $approved++;
                } catch (Throwable) {
                    $failed[] = $documentNumber;
                }
            }
        });
        $redirect = redirect()->route('bank.receipts.index');
        if ($approved > 0) {
            $redirect->with('success', __('common.messages.batch_approved', ['count' => $approved]));
        }
        if ($failed !== []) {
            $redirect->with('warning', __('common.messages.batch_approve_failed', ['numbers' => implode(', ', $failed)]));
        }

        return $redirect;
    }

    /**
     * Resolve posting accounts for a receipt at approval time.
     * Receipt: debit money (selected account chart, fallback chart settings), credit client/AR.
     *
     * @return array{0: string, 1: string, 2: numeric-string}
     */
    private function postingAccountsFor(Receipt $receipt): array
    {
        $setting = ChartSetting::query()->first();
        $transferType = $receipt->payment_transfer_type ?? PaymentTransferType::CHEQUE;
        $moneyChartId = match (true) {
            filled($receipt->bank_account_id) => $receipt->bankAccount->chart_id ?? Bank::query()->whereKey($receipt->bank_account_id)->value('chart_id'),
            filled($receipt->cash_account_id) => $receipt->cashAccount->chart_id ?? CashAccount::query()->whereKey($receipt->cash_account_id)->value('chart_id'),
            filled($receipt->e_wallet_account_id) => $receipt->eWalletAccount->chart_id ?? EWalletAccount::query()->whereKey($receipt->e_wallet_account_id)->value('chart_id'),
            default => null,
        };
        $transferChartId = match ($transferType) {
            PaymentTransferType::CASH => $setting?->cash_chart_uuid,
            PaymentTransferType::E_WALLET => $setting?->e_wallet_chart_uuid,
            default => $setting?->bank_chart_uuid,
        };
        $debitChartId = $moneyChartId ?? $transferChartId;
        $creditChartId = $setting?->client_chart_uuid;
        if (! is_string($debitChartId) || $debitChartId === '' || ! is_string($creditChartId) || $creditChartId === '') {
            throw ValidationException::withMessages(['document_number' => __('ledgers.validation.debit_account_or_default_required')]);
        }
        $amount = bcadd(DecimalService::normalize((string) $receipt->total_amount), '0', 2);

        /** @var numeric-string $amount */
        return [$debitChartId, $creditChartId, $amount];
    }

    /**
     * Money accounts a receipt may be deposited into, grouped by kind.
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
     * Keep only the money-account column matching the transfer type.
     *
     * @return array{bank_account_id: string|null, cash_account_id: string|null, e_wallet_account_id: string|null}
     */
    private function moneyAccountColumns(?PaymentTransferType $transferType, ReceiptData $data): array
    {
        $columns = [
            'bank_account_id' => null,
            'cash_account_id' => null,
            'e_wallet_account_id' => null,
        ];

        if ($transferType === null) {
            return $columns;
        }

        return match ($transferType->accountColumn()) {
            'cash_account_id' => ['bank_account_id' => null, 'cash_account_id' => $data->cashAccountId, 'e_wallet_account_id' => null],
            'e_wallet_account_id' => ['bank_account_id' => null, 'cash_account_id' => null, 'e_wallet_account_id' => $data->eWalletAccountId],
            default => ['bank_account_id' => $data->bankAccountId, 'cash_account_id' => null, 'e_wallet_account_id' => null],
        };
    }

    public function batchCancel(BatchCancelReceiptRequest $request): RedirectResponse
    {

        // Fetched and updated one at a time rather than in a single bulk update,
        // because a bulk update writes the same columns for every row it touches
        // and so cannot record which user cancelled each document. Each receipt
        // is a document in its own right and needs its own attribution.
        $receipts = Receipt::query()
            ->whereIn('document_number', $request->documentNumbers())
            ->where('status', DocumentStatus::PENDING)
            ->get();

        foreach ($receipts as $receipt) {
            $receipt->update(['status' => DocumentStatus::CANCELLED]);
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
        /** @var Collection<int, SalesInvoice> $invoices */
        $invoices = SalesInvoice::query()
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

        $results = $invoices->map(function (
            SalesInvoice $invoice
        ): array {

            $amount = number_format((float) $invoice->total_amount, 2, '.', '');
            $notes = filled($invoice->notes) ? " - $invoice->notes" : '';

            return [
                'value' => $invoice->document_number,
                'text' => "{$invoice->document_number} - {$invoice->date} - (RM {$amount}){$notes}",
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Print one receipt.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::PAYMENTS);

        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        return Pdf::loadView('bank.receipt.pdf', [
            'receipt' => $receipt,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->stream("receipt_$documentNumber.pdf");
    }
}
