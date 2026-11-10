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
use App\Enums\FileResources;
use App\Enums\Finance\LedgerStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Enums\Finance\PaymentTransferType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\SupplierRefund\BatchApproveSupplierRefundRequest;
use App\Http\Requests\Bank\SupplierRefund\BatchCancelSupplierRefundRequest;
use App\Http\Requests\Bank\SupplierRefund\StoreSupplierRefundRequest;
use App\Http\Requests\Bank\SupplierRefund\UpdateSupplierRefundRequest;
use App\Models\Bank\Bank;
use App\Models\Bank\EWalletAccount;
use App\Models\Bank\Refund\SupplierRefund;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\ApLedger;
use App\Models\Crm\Company;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Bank\SupplierRefundData;
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

/**
 * Supplier refunds: money taken back from a creditor.
 *
 * A supplier refund is a document with its own number, its own approval and its
 * own history, not a pair of rows that happen to share a document number.
 *
 * It is the mirror of a customer refund, and the two differ in exactly one way
 * that matters: which way round the money sits. Taking money back from a supplier
 * debits the money account, because the money leaves again; the payable is
 * credited, because what the supplier is owed falls. So the money is always on the
 * debit side here and the creditor is always on the credit side, which is why this
 * controller may never accept a bank account as the credit.
 *
 * That is also why it posts to the payables ledger rather than the receivables
 * one. A supplier refund that reached the receivable ledger would leave the
 * customer and supplier balances disagreeing with the bank.
 *
 * What a refund writes on approval is decided by {@see LedgerPoster} from the
 * accounts its lines name, not from anything here. A refund paid out of a drawer
 * and one paid from a bank account are the same document to this controller; the
 * ledger each produces follows from where the money actually went.
 */
class SupplierRefundController extends Controller
{
    use GeneratesDocumentNumber;

    public function __construct(private readonly Posting $posting) {}

    /**
     * Display a listing of the refunds awaiting approval.
     *
     * Only pending refunds are listed: approving one writes it to the ledgers,
     * and a document that has been written is read on the history page instead,
     * so the grid answers "what is left to do" rather than "what exists".
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $startDate = (string) ($request->input('start_date') ?? '');
        $endDate = (string) ($request->input('end_date') ?? '');

        $refunds = SupplierRefund::query()
            ->where('status', LedgerStatus::PENDING)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('document_number', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%")
                    ->orWhere('reference_number', 'like', "%$search%");
            }))
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->latest('date')->latest('created_at')
            ->paginate(static::PER_PAGE_GRID_STANDARD)->withQueryString();

        return view('bank.supplier-refund.index', compact('refunds', 'search', 'startDate', 'endDate'));
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
        return view('bank.supplier-refund.create', [
            'companies' => Company::query()->where('is_creditor', true)->whereNull('deleted_at')->orderBy('name')->get(),
            'documentNumber' => $this->generateDocumentNumber(LedgerSubType::SUPPLIER_REFUND),
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
        $refund = SupplierRefund::query()->where('document_number', $documentNumber)->firstOrFail();

        return view('bank.supplier-refund.show', compact('refund', 'documentNumber'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreSupplierRefundRequest  $request  The validated refund.
     * @return RedirectResponse Redirect response.
     *
     * @throws Throwable
     */
    public function store(StoreSupplierRefundRequest $request): RedirectResponse
    {
        $attachmentPath = $request->hasFile('attachment') ? $this->storeUpload($request->file('attachment'), 'refund_attachments') : null;
        $data = SupplierRefundData::from($request->validated());
        $documentNumber = $this->generateDocumentNumber(LedgerSubType::SUPPLIER_REFUND);
        $amount = bcadd(DecimalService::normalize((string) ($data->totalAmount ?? $data->amount())), '0', 2);
        $transferType = $data->paymentTransferType ?? PaymentTransferType::CHEQUE;

        DB::transaction(function () use ($data, $documentNumber, $amount, $attachmentPath, $transferType): void {
            $refund = SupplierRefund::query()->create([
                'id' => (string) Str::uuid(), 'document_number' => $documentNumber,
                'company_id' => $data->companyId,
                'company_name' => Company::query()->whereKey($data->companyId)->value('name') ?? '',
                'date' => $data->date?->format('Y-m-d'),
                'status' => $data->status ?? LedgerStatus::PENDING,
                'payment_transfer_type' => $transferType,
                ...$this->moneyAccountColumns($transferType, $data),
                'reference_number' => $data->referenceNumber, 'attachment_path' => $attachmentPath,
                'notes' => $data->notes, 'total_amount' => $amount,
            ]);
        });

        return redirect()->route('bank.supplier-refunds.show', $documentNumber)->with('success', FlashMessage::created(FileResources::SUPPLIER_REFUNDS));
    }

    /**
     * Handle the incoming request to update the specified resource.
     *
     * @param  UpdateSupplierRefundRequest  $request  The validated refund.
     * @param  string  $documentNumber  The document number.
     * @return RedirectResponse Redirect response.
     *
     * @throws Throwable
     */
    public function update(UpdateSupplierRefundRequest $request, string $documentNumber): RedirectResponse
    {
        $refund = SupplierRefund::query()->where('document_number', $documentNumber)->firstOrFail();
        abort_if($refund->isPosted(), 409);
        $data = SupplierRefundData::from($request->validated());
        $amount = bcadd(DecimalService::normalize((string) ($data->totalAmount ?? $data->amount())), '0', 2);

        $transferType = $data->paymentTransferType ?? $refund->payment_transfer_type;

        DB::transaction(function () use ($refund, $data, $amount, $transferType): void {
            $refund->update([
                'company_id' => $data->companyId,
                'company_name' => filled($data->companyId) ? (Company::query()->whereKey($data->companyId)->value('name') ?? $refund->company_name) : $refund->company_name,
                'date' => $data->date?->format('Y-m-d'),
                'payment_transfer_type' => $transferType,
                ...$this->moneyAccountColumns($transferType, $data),
                'reference_number' => $data->referenceNumber, 'notes' => $data->notes, 'total_amount' => $amount,
            ]);
        });

        return redirect()->route('bank.supplier-refunds.show', $documentNumber)->with('success', FlashMessage::updated(FileResources::SUPPLIER_REFUNDS));
    }

    /**
     * Approve a refund and post it to the ledgers.
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
            $refund = SupplierRefund::query()->where('document_number', $documentNumber)->lockForUpdate()->firstOrFail();
            abort_if($refund->status === LedgerStatus::POSTED, 409);
            [$debitChartId, $creditChartId, $amount] = $this->postingAccountsFor($refund);
            $this->posting->postPayment(
                $refund, $refund->document_number, $refund->date, LedgerType::PAYMENT, LedgerSubType::SUPPLIER_REFUND,
                $debitChartId, $creditChartId, $amount,
                companyId: $refund->company_id, companyName: $refund->company_name, referenceNumber: $refund->reference_number, description: $refund->notes,
            );
            $refund->update(['status' => LedgerStatus::POSTED]);
            $this->attributeApproved($refund);
        });

        return redirect()->route('bank.supplier-refunds.show', $documentNumber)->with('success', FlashMessage::approved(FileResources::SUPPLIER_REFUNDS));
    }

    /**
     * Approve multiple refunds and post them to the ledgers.
     *
     * @param  BatchApproveSupplierRefundRequest  $request  The request.
     * @return RedirectResponse Redirect response.
     *
     * @throws Throwable
     */
    public function batchApprove(BatchApproveSupplierRefundRequest $request): RedirectResponse
    {
        $documentNumbers = $request->documentNumbers();
        $count = 0;
        DB::transaction(function () use ($documentNumbers, &$count): void {
            foreach ($documentNumbers as $documentNumber) {
                $refund = SupplierRefund::query()->where('document_number', $documentNumber)->lockForUpdate()->firstOrFail();
                abort_if($refund->status === LedgerStatus::POSTED, 409);
                [$debitChartId, $creditChartId, $amount] = $this->postingAccountsFor($refund);
                $this->posting->postPayment(
                    $refund, $refund->document_number, $refund->date, LedgerType::PAYMENT, LedgerSubType::SUPPLIER_REFUND,
                    $debitChartId, $creditChartId, $amount,
                    companyId: $refund->company_id, companyName: $refund->company_name, referenceNumber: $refund->reference_number, description: $refund->notes,
                );
                $refund->update(['status' => LedgerStatus::POSTED]);
                $this->attributeApproved($refund);
                $count++;
            }
        });

        return redirect()->route('bank.supplier-refunds.index')->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending refunds without touching the ledgers.
     *
     * Fetched and updated one at a time rather than in a single bulk update,
     * because a bulk update writes the same columns for every row it touches and
     * so cannot record which user cancelled each document.
     *
     * @param  BatchCancelSupplierRefundRequest  $request  The request.
     * @return RedirectResponse Redirect response.
     */
    public function batchCancel(BatchCancelSupplierRefundRequest $request): RedirectResponse
    {
        $refunds = SupplierRefund::query()
            ->whereIn('document_number', $request->documentNumbers())
            ->where('status', LedgerStatus::PENDING)
            ->get();

        foreach ($refunds as $refund) {
            $refund->update(['status' => LedgerStatus::CANCELLED]);
            $this->attributeCancelled($refund);
        }

        $count = $refunds->count();

        return redirect()
            ->route('bank.supplier-refunds.cancelled.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $count]));
    }

    /**
     * Load purchase invoices for the reference number dropdown.
     *
     * A refund is only ever claimed back against money the supplier has actually
     * been paid, so every option carries the balance that is still refundable and
     * the form refuses an invoice with nothing on it. The figure is read from the
     * payables ledger, which is where a payment is recorded: a payment voucher
     * debits the payable and a refund credits it again, so what is left debit is
     * what has been paid and not yet recovered.
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

        /** @var Collection<int, PurchaseInvoice> $invoices */
        $invoices = PurchaseInvoice::query()
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
            ->get();

        /** @var Collection<int, ApLedger> $entries */
        $entries = ApLedger::query()
            ->where('type', LedgerType::PAYMENT)
            ->whereIn('reference_number', $invoices->pluck('document_number')->all())
            ->get(['reference_number', 'debit_amount', 'credit_amount']);

        $currency = Setting::getCurrencySymbol();

        $results = $invoices->map(function (PurchaseInvoice $invoice) use ($entries, $currency): array {
            /** @var Collection<int, ApLedger> $rows */
            $rows = $entries->where('reference_number', $invoice->document_number);
            $available = bcsub(
                bcadd((string) $rows->sum('debit_amount'), '0', 2),
                bcadd((string) $rows->sum('credit_amount'), '0', 2),
                2,
            );
            $notes = filled($invoice->notes) ? " - $invoice->notes" : '';

            return [
                'value' => $invoice->document_number,
                'text' => "$invoice->document_number - {$invoice->date->format('Y-m-d')} - ($currency $available)$notes",
                'available_amount' => (float) $available,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Print one refund.
     *
     * @param  string  $documentNumber  The document number of the refund.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::SUPPLIER_REFUNDS);

        $refund = SupplierRefund::query()
            ->where('document_number', $documentNumber)
            ->with('apLedgers')
            ->firstOrFail();

        return Pdf::loadView('bank.supplier-refund.pdf', [
            'refund' => $refund,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->download("refund_$documentNumber.pdf");
    }

    /**
     * The money accounts a refund may come back into, one list per kind.
     *
     * Split by kind rather than merged into one collection, because the form shows
     * the list the transfer type allows: a cheque or a bank transfer is drawn on a
     * bank and a wallet payment lands in a wallet. There is no register list
     * because a refund cannot be taken back in cash. Each account has to be live
     * and have a chart behind it, since the chart is what the money posts against.
     *
     * @return array{banks: Collection<int, Bank>, eWalletAccounts: Collection<int, EWalletAccount>}
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
            'eWalletAccounts' => EWalletAccount::query()
                ->with('chart')
                ->where('is_active', true)
                ->whereNotNull('chart_id')
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * The two account columns of a refund, with only one of them filled.
     *
     * Which one is filled follows from the transfer type, and the other is written
     * as null rather than left alone: an edit that changes a refund from a bank
     * transfer to a wallet payment has to let go of the bank it named, or the
     * refund would carry two accounts and the money ledger would be a matter of
     * which one the reader looked at first.
     *
     * @param  PaymentTransferType|null  $transferType  The type the refund is saved with, or null when it never said.
     * @param  SupplierRefundData  $data  The refund as the form supplied it.
     * @return array{bank_account_id: string|null, e_wallet_account_id: string|null} The two account columns.
     */
    private function moneyAccountColumns(?PaymentTransferType $transferType, SupplierRefundData $data): array
    {
        $columns = [
            'bank_account_id' => null,
            'e_wallet_account_id' => null,
        ];

        if ($transferType === null) {
            return $columns;
        }

        $columns[$transferType->accountColumn()] = match ($transferType->accountColumn()) {
            'e_wallet_account_id' => $data->eWalletAccountId,
            default => $data->bankAccountId,
        };

        return $columns;
    }

    /**
     * Resolve the posting accounts: debit the money, credit the payable.
     *
     * The money side is the chart of the account the refund names, because every
     * account owns exactly one chart: posting against that chart is what lands the
     * movement in that account's own money ledger instead of whichever one the
     * settings happen to name. A refund that never named an account, which is every
     * refund raised before the account became a field, falls back to the settings
     * chart for its transfer type so it still posts.
     *
     * @param  SupplierRefund  $refund  The pending refund being approved.
     * @return array{0: string, 1: string, 2: numeric-string} Debit chart, credit chart, amount.
     */
    private function postingAccountsFor(SupplierRefund $refund): array
    {
        $setting = ChartSetting::query()->first();
        $transferType = $refund->payment_transfer_type ?? PaymentTransferType::CHEQUE;
        $moneyChartId = $refund->moneyAccount()?->chart_id;
        $debitChartId = $moneyChartId ?? ($transferType === PaymentTransferType::E_WALLET
            ? ($setting?->e_wallet_chart_uuid ?? $setting?->bank_chart_uuid)
            : $setting?->bank_chart_uuid);
        $creditChartId = $setting?->vendor_chart_uuid;
        if (blank($debitChartId) || blank($creditChartId)) {
            throw ValidationException::withMessages(['document_number' => __('ledgers.validation.debit_account_or_default_required')]);
        }
        $amount = bcadd(DecimalService::normalize((string) $refund->total_amount), '0', 2);

        return [(string) $debitChartId, (string) $creditChartId, $amount];
    }
}
