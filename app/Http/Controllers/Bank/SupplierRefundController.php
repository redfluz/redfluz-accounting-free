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
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Enums\PaymentTransferType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\SupplierRefund\BatchApproveSupplierRefundRequest;
use App\Http\Requests\Bank\SupplierRefund\BatchCancelSupplierRefundRequest;
use App\Http\Requests\Bank\SupplierRefund\StoreSupplierRefundRequest;
use App\Http\Requests\Bank\SupplierRefund\UpdateSupplierRefundRequest;
use App\Models\Bank\Refund\SupplierRefund;
use App\Models\Bank\Refund\SupplierRefundChart;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\ApLedger;
use App\Models\Crm\Company;
use App\Services\DecimalService;
use App\Support\Data\Bank\SupplierRefundData;
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
     * Display every supplier refund that is still pending.
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

        $refunds = SupplierRefund::query()
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

        return view('bank.supplier-refund.index', compact('refunds', 'search', 'startDate', 'endDate'));
    }

    /**
     * Load purchase invoices for the linked document dropdown.
     *
     * Returns a JSON list of posted purchase invoices for the selected company,
     * each carrying the payment balance still available to refund. The balance is
     * sent with the option because the user has to see it to choose correctly, and
     * it is recomputed again on submit rather than trusted from here.
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

        // The invoices offered are the ones the payable sub-ledger actually holds.
        // A row only reaches ap_ledgers once the document is posted, so this is the
        // same set of invoices the money was actually billed against and no
        // separate status test is needed to reach the same answer.
        $invoices = ApLedger::query()
            ->selectRaw(
                'document_number, MAX(company_name) as company_name, '
                .'SUM(credit_amount) as total_amount, MAX(created_at) as date'
            )
            ->where('subtype', LedgerSubType::PURCHASE_INVOICE)
            ->where('company_id', $validated['company_id'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%");
                });
            })
            ->groupBy('document_number')
            ->orderByDesc('date')
            ->limit(50)
            ->get();

        $results = $invoices
            ->map(function (ApLedger $invoice) use ($validated): array {
                $invoiceNumber = (string) $invoice->document_number;
                $rawTotal = $invoice->getAttribute('total_amount');
                $total = is_numeric($rawTotal) ? DecimalService::normalize((string) $rawTotal) : '0.00';
                $amount = number_format((float) $total, 2, '.', '');
                $paidAmount = $this->paidAmount((string) $validated['company_id'], $invoiceNumber);
                $availableAmount = $this->refundableAmount(
                    (string) $validated['company_id'],
                    $invoiceNumber
                );

                return [
                    'value' => $invoiceNumber,
                    'text' => "$invoiceNumber - (Invoice RM $amount; Paid RM $paidAmount; "
                        ."Available RM $availableAmount)",
                    'available_amount' => $availableAmount,
                ];
            })
            ->values();

        return response()->json(['results' => $results]);
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
        // Only creditor companies can be refunded, and only a money account can be
        // paid out of, so the form is not offered choices that would be refused on
        // submit.
        return view('bank.supplier-refund.create', [
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
            'refundNumber' => $this->generateDocumentNumber(LedgerSubType::SUPPLIER_REFUND),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $documentNumber  The document number of the refund.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $refund = SupplierRefund::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        $refund->load([
            'charts.debitChart',
            'charts.creditChart',
        ]);

        return view('bank.supplier-refund.show', compact('refund', 'documentNumber'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     @param  StoreSupplierRefundRequest  $request  The validated refund being created.
     * @return RedirectResponse Redirect response to the refund detail page.
     *
     * @throws Throwable
     */
    public function store(StoreSupplierRefundRequest $request): RedirectResponse
    {
        // The file is written to disk before the form is parsed, because an
        // uploaded file is not form data: it has no stored name and no path yet,
        // and only the write gives it one. This is the last the request is read.
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'refund_attachments')
            : null;

        // The form, parsed into a document. Everything below works from this
        // object, and the request is not asked anything again.
        $data = SupplierRefundData::from($request->validated());

        $chartSetting = ChartSetting::query()->first();

        // A refund reverses the direction of the payment it is undoing. The money
        // leaves again, so the money account is debited and the payable is
        // credited, because what the supplier is owed falls.
        $debitId = $data->debitAccountId ?? $chartSetting?->bank_chart_uuid;
        $creditId = $data->creditAccountId ?? $chartSetting?->vendor_chart_uuid;

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

        // The money account is the debit and the creditor is the credit. A bank
        // account on the credit would be money arriving rather than leaving, which
        // is a receipt and not a refund.
        if (! $debitChart->isBank) {
            throw ValidationException::withMessages([
                'debit_id' => __('payments.validation.debit_account_invalid'),
            ]);
        }

        $creditorIsValid = $creditChart->chartType?->isLiability === true && $creditChart->isCreditor;

        if (! $creditorIsValid) {
            throw ValidationException::withMessages([
                'credit_id' => __('payments.validation.credit_account_invalid'),
            ]);
        }

        $documentNumber = $this->generateDocumentNumber(LedgerSubType::SUPPLIER_REFUND);

        // The amount is normalised with decimal arithmetic rather than cast to a
        // float, so what is written is what the user typed and a ledger has to
        // balance to the cent.
        $amount = bcadd(DecimalService::normalize($data->amount()), '0', 2);

        $this->assertLinkedInvoiceIsValid($data->companyId, $data->referenceNumber);
        $this->assertWithinPaidBalance($amount, $data->companyId, $data->referenceNumber);

        DB::transaction(function () use ($data, $documentNumber, $amount, $attachmentPath, $debitChart, $creditChart): void {
            $refund = SupplierRefund::query()->create([
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
            // accounts live on the refund's chart rows, so a refund printed
            // later still shows the accounts that were correct when the money
            // came back in.
            $refund->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);

            $refund->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);

            $refund->charts()->create([
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

            $refund->charts()->create([
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
            ->route('bank.supplier-refunds.show', $documentNumber)
            ->with('success', FlashMessage::created(FileResources::SUPPLIER_REFUNDS));
    }

    /**
     * Update an existing resource by its object.
     * Update one unposted supplier refund.
     *
     * @param  UpdateSupplierRefundRequest  $request  The validated refund being saved.
     * @param  string  $documentNumber  The refund document number.
     * @return RedirectResponse Redirect response to the refund detail page.
     *
     * @throws Throwable
     */
    public function update(UpdateSupplierRefundRequest $request, string $documentNumber): RedirectResponse
    {
        $refund = SupplierRefund::query()
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        abort_if($refund->isPosted(), 409);

        // The form, parsed into a document. Everything below works from this
        // object, and the request is not asked anything again.
        $data = SupplierRefundData::from($request->validated());

        /** @var Chart|null $debitChart */
        $debitChart = Chart::query()->with('chartType')->find($data->debitAccountId);
        /** @var Chart|null $creditChart */
        $creditChart = Chart::query()->with('chartType')->find($data->creditAccountId);

        abort_if($debitChart === null || $creditChart === null, 422);
        abort_unless($this->accountsFaceTheRightWay($debitChart, $creditChart), 422);

        $amount = bcadd(DecimalService::normalize($data->amount()), '0', 2);

        // The refund being edited is excluded from its own balance, or saving an
        // unchanged form would count the amount against itself and refuse it.
        $this->assertLinkedInvoiceIsValid($data->companyId, $data->referenceNumber);
        $this->assertWithinPaidBalance(
            $amount,
            $data->companyId,
            $data->referenceNumber,
            $refund->document_number
        );

        DB::transaction(function () use ($refund, $data, $amount, $debitChart, $creditChart): void {
            $refund->update([
                'company_id' => $data->companyId,
                'company_name' => filled($data->companyId)
                    ? (Company::query()->whereKey($data->companyId)->value('name') ?? $refund->company_name)
                    : $refund->company_name,
                'date' => $data->date?->format('Y-m-d'),
                'payment_transfer_type' => $data->paymentTransferType ?? $refund->payment_transfer_type,
                'reference_number' => $data->referenceNumber,
                'notes' => $data->notes,
                'total_amount' => $amount,
            ]);

            // The lines and chart rows are replaced rather than amended. A
            // refund carries one amount across two accounts, so editing one
            // side without the other is the one change that would leave it
            // unbalanceable.
            $refund->lines()->delete();
            $refund->charts()->delete();
            $refund->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);
            $refund->lines()->create([
                'amount' => $amount,
                'description' => $data->notes,
            ]);
            $refund->charts()->create([
                'debit_id' => $debitChart->id,
                'debit_acc' => $debitChart->code,
                'debit_acc_desc' => $debitChart->name,
                'document_number' => $refund->document_number,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => $amount,
                'credit_amount' => '0.00',
                'total_amount' => $amount,
            ]);
            $refund->charts()->create([
                'credit_id' => $creditChart->id,
                'credit_acc' => $creditChart->code,
                'credit_acc_desc' => $creditChart->name,
                'document_number' => $refund->document_number,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date?->format('Y-m-d'),
                'description' => $data->notes,
                'debit_amount' => '0.00',
                'credit_amount' => $amount,
                'total_amount' => $amount,
            ]);
        });

        return redirect()
            ->route('bank.supplier-refunds.show', $documentNumber)
            ->with('success', FlashMessage::updated(FileResources::SUPPLIER_REFUNDS));
    }

    /**
     * Approve a supplier refund and post it to the ledgers.
     *
     * @param  string  $documentNumber  The refund document number.
     * @return RedirectResponse Redirect response to the refund detail page.
     *
     * @throws Throwable
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        DB::transaction(function () use ($documentNumber): void {
            $refund = SupplierRefund::query()
                ->where('document_number', $documentNumber)
                ->lockForUpdate()
                ->firstOrFail();

            $refund->load('charts');

            abort_if($refund->status === LedgerStatus::POSTED, 409);
            abort_if($refund->charts->isEmpty(), 422, __('refunds.validation.lines_required'));

            $legs = $this->legs($refund->charts, $refund->reference_number);

            // A refund that does not balance cannot be posted, because posting it
            // would write an entry the general ledger cannot accept.
            if (bccomp(PostingLeg::sumDebit($legs), PostingLeg::sumCredit($legs), 2) !== 0) {
                throw ValidationException::withMessages([
                    'document_number' => __('journal_entries.unbalanced_entry_error'),
                ]);
            }

            $this->poster->post(
                $refund,
                $refund->document_number,
                $refund->date,
                LedgerType::PAYMENT,
                LedgerSubType::SUPPLIER_REFUND,
                $legs,
                companyId: $refund->company_id,
                contactId: $refund->contact_id,
                referenceNumber: $refund->reference_number,
            );

            $refund->update(['status' => LedgerStatus::POSTED]);

            // Recorded after the posting so an approver is never recorded against a
            // refund that did not post. The attribution saves the refund itself, so
            // this is the last write.
            $this->attributeApproved($refund);
        });

        return redirect()
            ->route('bank.supplier-refunds.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::SUPPLIER_REFUNDS));
    }

    /**
     * Approve multiple supplier refunds and post them to the ledgers.
     *
     * @param  BatchApproveSupplierRefundRequest  $request  The refunds selected for approval.
     * @return RedirectResponse Redirect response to the refund detail page.
     *
     * @throws Throwable
     */
    public function batchApprove(BatchApproveSupplierRefundRequest $request): RedirectResponse
    {
        $documentNumbers = $request->documentNumbers();

        // Every check runs inside the transaction and rejects rather than skips. A batch
        // is approved or it is not: if one document fails a check the whole
        // selection is rolled back and the user is told which document stopped it,
        // because a count that quietly dropped some is worse than no count.
        $count = DB::transaction(function () use ($documentNumbers): int {
            $count = 0;

            foreach ($documentNumbers as $documentNumber) {
                $refund = SupplierRefund::query()
                    ->where('document_number', $documentNumber)
                    ->where('status', LedgerStatus::PENDING)
                    ->lockForUpdate()
                    ->first();

                if ($refund === null) {
                    throw ValidationException::withMessages([
                        'document_number' => __('common.batch_rejected.not_pending', ['number' => $documentNumber]),
                    ]);
                }

                $refund->load('charts');

                if ($refund->charts->isEmpty()) {
                    throw ValidationException::withMessages([
                        'document_number' => __('common.batch_rejected.no_lines', ['number' => $documentNumber]),
                    ]);
                }

                $legs = $this->legs($refund->charts, $refund->reference_number);

                if (bccomp(PostingLeg::sumDebit($legs), PostingLeg::sumCredit($legs), 2) !== 0) {
                    throw ValidationException::withMessages([
                        'document_number' => __('common.batch_rejected.unbalanced', ['number' => $documentNumber]),
                    ]);
                }

                $this->poster->post(
                    $refund,
                    $refund->document_number,
                    $refund->date,
                    LedgerType::PAYMENT,
                    LedgerSubType::SUPPLIER_REFUND,
                    $legs,
                    companyId: $refund->company_id,
                    contactId: $refund->contact_id,
                    referenceNumber: $refund->reference_number,
                );

                $refund->update(['status' => LedgerStatus::POSTED]);
                $this->attributeApproved($refund);

                $count++;
            }

            return $count;
        });

        return redirect()
            ->route('bank.supplier-refunds.history.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending supplier refunds.
     *
     @param  BatchCancelSupplierRefundRequest  $request  The refunds selected for cancellation.
     * @return RedirectResponse Redirect response to the refund detail page.
     *
     * @throws Throwable
     */
    public function batchCancel(BatchCancelSupplierRefundRequest $request): RedirectResponse
    {

        // Fetched and updated one at a time rather than in a single bulk update,
        // because a bulk update writes the same columns for every row it touches
        // and so cannot record which user cancelled each document. Each refund is
        // a document in its own right and needs its own attribution.
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
     * Whether these two accounts may face each other on a supplier refund.
     *
     * The same pair of accounts is legal or not depending on which way the money
     * is going, so this cannot be a property of the accounts alone. For a supplier
     * refund the money account is debited and the creditor is credited.
     *
     * @param  Chart  $debitChart  The account being debited.
     * @param  Chart  $creditChart  The account being credited.
     */
    private function accountsFaceTheRightWay(Chart $debitChart, Chart $creditChart): bool
    {
        return $debitChart->isBank
            && $creditChart->chartType?->isLiability === true
            && $creditChart->isCreditor;
    }

    /**
     * How much has been paid against one purchase invoice.
     *
     * Read from the payable sub-ledger rather than the payment voucher documents,
     * because what a supplier refund may undo is the money that reached the
     * ledger. A voucher the supplier was never paid by has no ledger row at all,
     * so the two agree by construction and only one of them has to be trusted.
     *
     * @param  string  $companyId  Company UUID belonging to the invoice.
     * @param  string  $invoiceNumber  The purchase invoice number.
     * @return numeric-string The paid total.
     */
    private function paidAmount(string $companyId, string $invoiceNumber): string
    {
        $paid = ApLedger::query()
            ->where('subtype', LedgerSubType::PAYMENT_VOUCHER)
            ->where('company_id', $companyId)
            ->where('reference_number', $invoiceNumber)
            ->sum('debit_amount');

        return bcadd(DecimalService::normalize((string) $paid), '0.00', 2);
    }

    /**
     * Calculate the remaining paid balance that may be refunded for one invoice.
     *
     * Refunds already posted to the payable ledger come off the total, and draft
     * refunds reserve theirs from the document table so two people cannot both
     * recover the same money while the first is still being approved. A cancelled
     * draft released its reservation because it never took the money back.
     *
     * @param  string  $companyId  Company UUID belonging to the invoice.
     * @param  string  $invoiceNumber  Original purchase invoice number.
     * @param  string|null  $excludeRefundNumber  Current refund when validating an update.
     * @return numeric-string Returns the available refund amount.
     */
    private function refundableAmount(
        string $companyId,
        string $invoiceNumber,
        ?string $excludeRefundNumber = null
    ): string {
        $paidAmount = $this->paidAmount($companyId, $invoiceNumber);

        // Posted refunds have a payable ledger row of their own.
        $posted = ApLedger::query()
            ->where('subtype', LedgerSubType::SUPPLIER_REFUND)
            ->where('company_id', $companyId)
            ->where('reference_number', $invoiceNumber)
            ->sum('credit_amount');

        // Draft refunds have not been posted, so they are only in the documents.
        $reserved = SupplierRefund::query()
            ->where('status', LedgerStatus::PENDING)
            ->where('company_id', $companyId)
            ->where('reference_number', $invoiceNumber)
            ->when(
                $excludeRefundNumber !== null,
                fn ($query) => $query->where('document_number', '!=', $excludeRefundNumber)
            )
            ->sum('total_amount');

        $refundedAmount = bcadd(
            bcadd(DecimalService::normalize((string) $posted), '0.00', 2),
            bcadd(DecimalService::normalize((string) $reserved), '0.00', 2),
            2
        );

        $availableAmount = bcsub($paidAmount, $refundedAmount, 2);

        return bccomp($availableAmount, '0.00', 2) === 1
            ? $availableAmount
            : '0.00';
    }

    /**
     * Refuse a refund that is not attached to one of this company's posted invoices.
     *
     * Without this a refund is money leaving with no record of which document it
     * undoes, which is the one thing the linked document exists to prevent. The
     * invoice is looked for in the payable ledger, so an invoice that was never
     * posted cannot be refunded against.
     *
     * @param  string|null  $companyId  The refund's company.
     * @param  string|null  $invoiceNumber  The chosen purchase invoice.
     *
     * @throws ValidationException
     */
    private function assertLinkedInvoiceIsValid(?string $companyId, ?string $invoiceNumber): void
    {
        if (blank($companyId) || blank($invoiceNumber)) {
            return;
        }

        $exists = ApLedger::query()
            ->where('subtype', LedgerSubType::PURCHASE_INVOICE)
            ->where('company_id', $companyId)
            ->where('document_number', $invoiceNumber)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'reference_number' => __('common.errors.linked_invoice_invalid'),
            ]);
        }
    }

    /**
     * Refuse a refund larger than the balance still available on its invoice.
     *
     * @param  string  $amount  The refund's normalised total.
     * @param  string|null  $companyId  The refund's company.
     * @param  string|null  $invoiceNumber  The chosen purchase invoice.
     * @param  string|null  $excludeRefundNumber  Current refund when validating an update.
     *
     * @throws ValidationException
     */
    private function assertWithinPaidBalance(
        string $amount,
        ?string $companyId,
        ?string $invoiceNumber,
        ?string $excludeRefundNumber = null
    ): void {
        if (blank($companyId) || blank($invoiceNumber)) {
            return;
        }

        $availableAmount = $this->refundableAmount($companyId, $invoiceNumber, $excludeRefundNumber);
        $safeAmount = is_numeric($amount) ? $amount : '0';

        if (bccomp($safeAmount, $availableAmount, 2) === 1) {
            throw ValidationException::withMessages([
                'amount' => __('refunds.validation.amount_exceeds_paid', [
                    'amount' => number_format((float) $availableAmount, 2),
                ]),
            ]);
        }
    }

    /**
     * Turn a refund's chart rows into the double entry the poster expects.
     *
     * Each chart row is a single movement — a refund chart debits one account
     * or credits one, never both — so every row becomes exactly one leg. A row
     * that names no account cannot become a movement and is refused rather
     * than quietly dropped, because a refund that lost a row on the way to
     * the ledger would still balance while carrying less than it says.
     *
     * @param  Collection<int, SupplierRefundChart>  $charts  The refund's chart rows.
     * @param  string|null  $referenceNumber  The refund's reference number, carried
     *                                        onto each leg so the general ledger rows
     *                                        record what the refund settles.
     * @return list<PostingLeg> The double entry, one leg per chart row.
     */
    private function legs(Collection $charts, ?string $referenceNumber = null): array
    {
        $legs = [];

        foreach ($charts as $chart) {
            /** @var SupplierRefundChart $chart */
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
