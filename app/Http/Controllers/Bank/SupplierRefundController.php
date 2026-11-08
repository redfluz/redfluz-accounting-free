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
use App\Http\Requests\Bank\SupplierRefund\StoreSupplierRefundRequest;
use App\Http\Requests\Bank\SupplierRefund\UpdateSupplierRefundRequest;
use App\Models\Bank\Refund\SupplierRefund;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\Company;
use App\Services\DecimalService;
use App\Support\Data\Bank\SupplierRefundData;
use App\Support\Posting\PostingLeg;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     * @throws Throwable
     */
    public function create(): View
    {
        return view('bank.supplier-refund.create', [
            'companies' => Company::query()->where('is_creditor', true)->whereNull('deleted_at')->orderBy('name')->get(),
            'documentNumber' => $this->generateDocumentNumber(LedgerSubType::SUPPLIER_REFUND),
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
        $refund->loadMissing('apLedgers.chart');
        return view('bank.supplier-refund.show', compact('refund', 'documentNumber'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreSupplierRefundRequest  $request  The validated refund.
     * @return RedirectResponse Redirect response.
     * @throws Throwable
     */
    public function store(StoreSupplierRefundRequest $request): RedirectResponse
    {
        $attachmentPath = $request->hasFile('attachment') ? $this->storeUpload($request->file('attachment'), 'refund_attachments') : null;
        $data = SupplierRefundData::from($request->validated());
        $documentNumber = $this->generateDocumentNumber(LedgerSubType::SUPPLIER_REFUND);
        $amount = bcadd(DecimalService::normalize((string) ($data->totalAmount ?? $data->amount())), '0', 2);
        $this->assertLinkedInvoiceIsValid($data->companyId, $data->referenceNumber);
        $this->assertWithinPaidBalance($amount, $data->companyId, $data->referenceNumber);

        DB::transaction(function () use ($data, $documentNumber, $amount, $attachmentPath): void {
            $refund = SupplierRefund::query()->create([
                'id' => (string) Str::uuid(), 'document_number' => $documentNumber,
                'company_id' => $data->companyId,
                'company_name' => Company::query()->whereKey($data->companyId)->value('name') ?? '',
                'date' => $data->date?->format('Y-m-d'),
                'status' => $data->status ?? LedgerStatus::PENDING,
                'payment_transfer_type' => $data->paymentTransferType ?? PaymentTransferType::CHEQUE,
                'reference_number' => $data->referenceNumber, 'attachment_path' => $attachmentPath,
                'notes' => $data->notes, 'total_amount' => $amount,
            ]);
        });
        return redirect()->route('bank.supplier-refunds.show', $documentNumber)->with('success', FlashMessage::created(FileResources::REFUNDS));
    }

    /**
     * Handle the incoming request to update the specified resource.
     *
     * @param  UpdateSupplierRefundRequest  $request  The validated refund.
     * @param  string  $documentNumber  The document number.
     * @return RedirectResponse Redirect response.
     * @throws Throwable
     */
    public function update(UpdateSupplierRefundRequest $request, string $documentNumber): RedirectResponse
    {
        $refund = SupplierRefund::query()->where('document_number', $documentNumber)->firstOrFail();
        abort_if($refund->isPosted(), 409);
        $data = SupplierRefundData::from($request->validated());
        $amount = bcadd(DecimalService::normalize((string) ($data->totalAmount ?? $data->amount())), '0', 2);
        $this->assertLinkedInvoiceIsValid($data->companyId, $data->referenceNumber);
        $this->assertWithinPaidBalance($amount, $data->companyId, $data->referenceNumber, $refund->document_number);

        DB::transaction(function () use ($refund, $data, $amount): void {
            $refund->update([
                'company_id' => $data->companyId,
                'company_name' => filled($data->companyId) ? (Company::query()->whereKey($data->companyId)->value('name') ?? $refund->company_name) : $refund->company_name,
                'date' => $data->date?->format('Y-m-d'),
                'payment_transfer_type' => $data->paymentTransferType ?? $refund->payment_transfer_type,
                'reference_number' => $data->referenceNumber, 'notes' => $data->notes, 'total_amount' => $amount,
            ]);
        });
        return redirect()->route('bank.supplier-refunds.show', $documentNumber)->with('success', FlashMessage::updated(FileResources::REFUNDS));
    }

    /**
     * Approve a refund and post it to the ledgers.
     * Posting accounts are resolved at approval time from chart settings.
     *
     * @param  string  $documentNumber  The document number.
     * @return RedirectResponse Redirect response.
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        DB::transaction(function () use ($documentNumber): void {
            $refund = SupplierRefund::query()->where('document_number', $documentNumber)->lockForUpdate()->firstOrFail();
            abort_if($refund->status === LedgerStatus::POSTED, 409);
            $legs = $this->legsFor($refund);
            $this->poster->post($refund, $refund->document_number, $refund->date, LedgerType::PAYMENT, LedgerSubType::SUPPLIER_REFUND, $legs, companyId: $refund->company_id, contactId: $refund->contact_id, referenceNumber: $refund->reference_number);
            $refund->update(['status' => LedgerStatus::POSTED]);
            $this->attributeApproved($refund);
        });
        return redirect()->route('bank.supplier-refunds.show', $documentNumber)->with('success', FlashMessage::approved(FileResources::REFUNDS));
    }

    /**
     * Approve multiple refunds and post them to the ledgers.
     *
     * @param  BatchApproveSupplierRefundRequest  $request  The request.
     * @return RedirectResponse Redirect response.
     * @throws Throwable
     */
    public function batchApprove(BatchApproveSupplierRefundRequest $request): RedirectResponse
    {
        $documentNumbers = $request->validatedDocumentNumbers();
        $count = 0;
        DB::transaction(function () use ($documentNumbers, &$count): void {
            foreach ($documentNumbers as $documentNumber) {
                $refund = SupplierRefund::query()->where('document_number', $documentNumber)->lockForUpdate()->firstOrFail();
                abort_if($refund->status === LedgerStatus::POSTED, 409);
                $legs = $this->legsFor($refund);
                $this->poster->post($refund, $refund->document_number, $refund->date, LedgerType::PAYMENT, LedgerSubType::SUPPLIER_REFUND, $legs, companyId: $refund->company_id, contactId: $refund->contact_id, referenceNumber: $refund->reference_number);
                $refund->update(['status' => LedgerStatus::POSTED]);
                $this->attributeApproved($refund);
                $count++;
            }
        });
        return redirect()->route('bank.supplier-refunds.index')->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Build posting legs at approval time from chart settings.
     *
     * @param  SupplierRefund  $refund  The refund being posted.
     * @return list<PostingLeg>
     */
    private function legsFor(SupplierRefund $refund): array
    {
        $setting = ChartSetting::query()->first();
        $transferType = $refund->payment_transfer_type ?? PaymentTransferType::CHEQUE;
        $debitId = $transferType === PaymentTransferType::CASH ? ($setting?->cash_chart_uuid ?? $setting?->bank_chart_uuid) : ($transferType === PaymentTransferType::E_WALLET ? ($setting?->e_wallet_chart_uuid ?? $setting?->bank_chart_uuid) : $setting?->bank_chart_uuid);
        $creditId = $setting?->vendor_chart_uuid;
        if (blank($debitId) || blank($creditId)) throw ValidationException::withMessages(['document_number' => __('ledgers.validation.debit_account_or_default_required')]);
        $amount = bcadd(DecimalService::normalize((string) $refund->total_amount), '0', 2);
        return [PostingLeg::debit((string) $debitId, $amount, $refund->notes, $refund->reference_number), PostingLeg::credit((string) $creditId, $amount, $refund->notes, $refund->reference_number)];
    }
}
