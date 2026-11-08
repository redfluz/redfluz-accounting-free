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
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\LedgerStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Enums\Finance\PaymentTransferType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\PaymentVoucher\BatchApprovePaymentVoucherRequest;
use App\Http\Requests\Bank\PaymentVoucher\BatchCancelPaymentVoucherRequest;
use App\Http\Requests\Bank\PaymentVoucher\StorePaymentVoucherRequest;
use App\Http\Requests\Bank\PaymentVoucher\UpdatePaymentVoucherRequest;
use App\Models\Bank\PaymentVoucher;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\Company;
use App\Services\DecimalService;
use App\Support\Data\Bank\PaymentVoucherData;
use App\Support\Posting\PaymentLedgerPoster;
use App\Support\Posting\PostingLeg;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PaymentVoucherController extends Controller
{
    use GeneratesDocumentNumber;

    public function __construct(private readonly PaymentLedgerPoster $poster) {}

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $startDate = (string) ($request->input('start_date') ?? '');
        $endDate = (string) ($request->input('end_date') ?? '');
        $vouchers = PaymentVoucher::query()
            ->where('status', LedgerStatus::PENDING)
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
     * @throws Throwable
     */
    public function create(): View
    {
        return view('bank.voucher.create', [
            'companies' => Company::query()->where('is_creditor', true)->whereNull('deleted_at')->orderBy('name')->get(),
            'documentNumber' => $this->generateDocumentNumber(LedgerSubType::PAYMENT_VOUCHER),
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
        $voucher = PaymentVoucher::query()->where('document_number', $documentNumber)->firstOrFail();
        $voucher->loadMissing('apLedgers.chart');
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
        return view('bank.voucher.edit', compact('voucher', 'documentNumber', 'companies'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StorePaymentVoucherRequest  $request  The validated voucher.
     * @return RedirectResponse Redirect response.
     * @throws Throwable
     */
    public function store(StorePaymentVoucherRequest $request): RedirectResponse
    {
        $attachmentPath = $request->hasFile('attachment') ? $this->storeUpload($request->file('attachment'), 'voucher_attachments') : null;
        $data = PaymentVoucherData::from($request->validated());
        $documentNumber = $this->generateDocumentNumber(LedgerSubType::PAYMENT_VOUCHER);
        $amount = bcadd(DecimalService::normalize((string) ($data->totalAmount ?? $data->amount())), '0', 2);
        DB::transaction(function () use ($data, $documentNumber, $amount, $attachmentPath): void {
            $voucher = PaymentVoucher::query()->create([
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
        return redirect()->route('bank.payment-vouchers.show', $documentNumber)->with('success', FlashMessage::created(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Handle the incoming request to update the specified resource.
     *
     * @param  UpdatePaymentVoucherRequest  $request  The validated voucher.
     * @param  string  $documentNumber  The document number.
     * @return RedirectResponse Redirect response.
     * @throws Throwable
     */
    public function update(UpdatePaymentVoucherRequest $request, string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS, FileAction::UPDATE);
        $voucher = PaymentVoucher::query()->where('document_number', $documentNumber)->firstOrFail();
        abort_if($voucher->isPosted(), 409);
        $data = PaymentVoucherData::from($request->validated());
        $amount = bcadd(DecimalService::normalize((string) ($data->totalAmount ?? $data->amount())), '0', 2);
        DB::transaction(function () use ($voucher, $data, $amount): void {
            $voucher->update([
                'company_id' => $data->companyId,
                'company_name' => Company::query()->whereKey($data->companyId)->value('name') ?? (string) $voucher->company_name,
                'date' => $data->date?->format('Y-m-d'),
                'payment_transfer_type' => $data->paymentTransferType ?? $voucher->payment_transfer_type,
                'reference_number' => $data->referenceNumber, 'notes' => $data->notes, 'total_amount' => $amount,
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
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        DB::transaction(function () use ($documentNumber): void {
            $voucher = PaymentVoucher::query()->where('document_number', $documentNumber)->lockForUpdate()->firstOrFail();
            abort_if($voucher->status === LedgerStatus::POSTED, 409);
            $legs = $this->legsFor($voucher);
            $this->poster->post($voucher, $voucher->document_number, $voucher->date, LedgerType::PAYMENT, LedgerSubType::PAYMENT_VOUCHER, $legs, companyId: $voucher->company_id, contactId: $voucher->contact_id, referenceNumber: $voucher->reference_number);
            $voucher->update(['status' => LedgerStatus::POSTED]);
            $this->attributeApproved($voucher);
        });
        return redirect()->route('bank.payment-vouchers.show', $documentNumber)->with('success', FlashMessage::approved(FileResources::PAYMENT_VOUCHERS));
    }

    /**
     * Approve multiple payment vouchers and post them to the ledgers.
     *
     * @param  BatchApprovePaymentVoucherRequest  $request  The request.
     * @return RedirectResponse Redirect response.
     * @throws Throwable
     */
    public function batchApprove(BatchApprovePaymentVoucherRequest $request): RedirectResponse
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS, FileAction::APPROVE);
        $documentNumbers = $request->validatedDocumentNumbers();
        $approved = 0; $failed = [];
        DB::transaction(function () use ($documentNumbers, &$approved, &$failed): void {
            foreach ($documentNumbers as $documentNumber) {
                $voucher = PaymentVoucher::query()->where('document_number', $documentNumber)->lockForUpdate()->first();
                if (! $voucher || $voucher->status === LedgerStatus::POSTED) { $failed[] = $documentNumber; continue; }
                try {
                    $legs = $this->legsFor($voucher);
                    $this->poster->post($voucher, $voucher->document_number, $voucher->date, LedgerType::PAYMENT, LedgerSubType::PAYMENT_VOUCHER, $legs, companyId: $voucher->company_id, contactId: $voucher->contact_id, referenceNumber: $voucher->reference_number);
                    $voucher->update(['status' => LedgerStatus::POSTED]);
                    $this->attributeApproved($voucher); $approved++;
                } catch (Throwable) { $failed[] = $documentNumber; }
            }
        });
        $redirect = redirect()->route('bank.payment-vouchers.index');
        if ($approved > 0) $redirect->with('success', __('common.messages.batch_approved', ['count' => $approved]));
        if ($failed !== []) $redirect->with('warning', __('common.messages.batch_approve_failed', ['numbers' => implode(', ', $failed)]));
        return $redirect;
    }

    /**
     * Build posting legs at approval time from chart settings.
     * Payment voucher: debit vendor/AP, credit money (by transfer type).
     *
     * @param  PaymentVoucher  $voucher  The voucher being posted.
     * @return list<PostingLeg>
     */
    private function legsFor(PaymentVoucher $voucher): array
    {
        $setting = ChartSetting::query()->first();
        $transferType = $voucher->payment_transfer_type ?? PaymentTransferType::CHEQUE;
        $creditId = $transferType === PaymentTransferType::CASH ? ($setting?->cash_chart_uuid ?? $setting?->bank_chart_uuid) : ($transferType === PaymentTransferType::E_WALLET ? ($setting?->e_wallet_chart_uuid ?? $setting?->bank_chart_uuid) : $setting?->bank_chart_uuid);
        $debitId = $setting?->vendor_chart_uuid;
        if (blank($debitId) || blank($creditId)) throw ValidationException::withMessages(['document_number' => __('ledgers.validation.debit_account_or_default_required')]);
        $amount = bcadd(DecimalService::normalize((string) $voucher->total_amount), '0', 2);
        return [PostingLeg::debit((string) $debitId, $amount, $voucher->notes, $voucher->reference_number), PostingLeg::credit((string) $creditId, $amount, $voucher->notes, $voucher->reference_number)];
    }


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
}
