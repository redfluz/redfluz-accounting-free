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

namespace App\Http\Controllers\Purchase;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\Purchase\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderAttachment;
use App\Models\Purchase\PurchaseOrderLine;
use App\Models\Purchase\PurchaseOrderSetting;
use App\Models\System\Setting;
use App\Support\Data\Purchase\PurchaseOrderData;
use App\Traits\GeneratesDocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Maintain supplier purchase orders.
 *
 * PO approval locks the order. A separate supplier Sales Order step creates the
 * pending Purchase Invoice; accounting approval and GRN remain independent.
 */
class PurchaseOrderController extends Controller
{
    use GeneratesDocumentNumber;

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::PURCHASE_ORDERS);
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();
        $purchaseOrders = PurchaseOrder::query()
            ->withCount('lines')
            ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('document_number', 'like', "%$search%")
                    ->orWhere('supplier_reference_number', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%");
            }))
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->latest('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('purchase.purchase-orders.index', compact('purchaseOrders', 'search', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::PURCHASE_ORDERS, FileAction::ADD);

        return view('purchase.purchase-orders.create', $this->formData());
    }

    /**
     * Create a supplier without leaving the Purchase Order form.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return JsonResponse Returns the new supplier for the form selector.
     */
    public function storeCompany(Request $request): JsonResponse
    {
        $this->allows(FileResources::PURCHASE_ORDERS, FileAction::ADD);
        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $nameRaw = $validated['name'];
        $company = Company::query()->create([
            'name' => trim(is_string($nameRaw) ? $nameRaw : ''),
            'is_creditor' => true,
        ]);

        return response()->json(['id' => $company->id, 'name' => $company->name], 201);
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StorePurchaseOrderRequest  $request  The validated request instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $purchaseOrder = new PurchaseOrder;
        $data = PurchaseOrderData::from($request->validated());

        $company = Company::query()->whereKey($data->companyId)->where('is_creditor', true)->firstOrFail();
        $contact = filled($data->contactId)
            ? Contact::query()->whereKey($data->contactId)->firstOrFail()
            : null;
        if ($contact !== null && $contact->company_id !== $company->id) {
            throw ValidationException::withMessages([
                'contact_id' => __('purchase_orders.validation.contact_company'),
            ]);
        }

        /** @var Collection<int, array<string, mixed>> $lines */
        $lines = $request->collect('lines')->filter(
            fn (mixed $line): bool => is_array($line) && (filled($line['description'] ?? null)
                || filled($line['quantity'] ?? null)
                || filled($line['unit_price'] ?? null))
        );
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => __('purchase_orders.validation.lines_required'),
            ]);
        }
        foreach ($lines as $index => $line) {
            if (! filled($line['quantity'] ?? null)) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => __('purchase_orders.validation.quantity_required'),
                ]);
            }
            if (! array_key_exists('unit_price', $line) || $line['unit_price'] === null || $line['unit_price'] === '') {
                throw ValidationException::withMessages([
                    "lines.$index.unit_price" => __('purchase_orders.validation.unit_price_required'),
                ]);
            }
        }
        $preparedLines = $this->prepareLines($lines);
        $subtotal = $preparedLines->reduce(
            fn (string $total, array $line): string => bcadd($total, is_numeric($line['total_amount'] ?? 0) ? (string) ($line['total_amount'] ?? 0) : '0.00', 2),
            '0.00'
        );
        $taxAmount = '0.00';
        $totalAmount = $subtotal;

        DB::transaction(function () use (
            $company, $contact, $preparedLines, &$purchaseOrder,
            $subtotal, $taxAmount, $totalAmount, $data, $request
        ): void {
            $purchaseOrder->fill([
                'company_id' => $company->id,
                'contact_id' => $contact?->id,
                'document_number' => $purchaseOrder->document_number
                    ?? $this->generatePurchaseOrderNumber(),
                'purchase_type' => $data->purchaseType?->value,
                'supplier_reference_number' => $data->supplierReferenceNumber,
                'supplier_sales_order_number' => $data->supplierSalesOrderNumber,
                'company_name' => $company->name,
                'contact_name' => $contact?->name,
                'date' => $data->date?->format('Y-m-d'),
                'expected_date' => $data->expectedDate?->format('Y-m-d'),
                'credit_term_days' => $request->creditTermDays(),
                'due_date' => $data->date?->addDays($request->creditTermDays())->format('Y-m-d'),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $data->notes,
            ])->save();
            $now = now();
            $lineRows = $preparedLines->map(fn (array $line): array => [
                'id' => (string) Str::uuid7(),
                'purchase_order_id' => $purchaseOrder->id,
                ...$line,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            PurchaseOrderLine::query()->insert($lineRows);
        });
        $this->storeAttachments($request, $purchaseOrder);

        return redirect()->route('purchase.purchase-orders.show', $purchaseOrder)
            ->with('success', FlashMessage::created(FileResources::PURCHASE_ORDERS));
    }

    /**
     * Display the specified resource.
     *
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->allows(FileResources::PURCHASE_ORDERS);
        $purchaseOrder->load(['lines', 'attachments', 'invoice']);
        $user = auth()->user();
        abort_if($user === null, 403);

        return view('purchase.purchase-orders.show', compact('purchaseOrder'));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->allows(FileResources::PURCHASE_ORDERS, FileAction::UPDATE);
        $this->abortIfLocked($purchaseOrder);
        $purchaseOrder->load(['lines', 'attachments', 'invoice']);

        return view('purchase.purchase-orders.edit', $this->formData() + compact('purchaseOrder'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdatePurchaseOrderRequest  $request  The validated request instance.
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->abortIfLocked($purchaseOrder);
        $data = PurchaseOrderData::from($request->validated());

        $company = Company::query()->whereKey($data->companyId)->where('is_creditor', true)->firstOrFail();
        $contact = filled($data->contactId)
            ? Contact::query()->whereKey($data->contactId)->firstOrFail()
            : null;
        if ($contact !== null && $contact->company_id !== $company->id) {
            throw ValidationException::withMessages([
                'contact_id' => __('purchase_orders.validation.contact_company'),
            ]);
        }

        /** @var Collection<int, array<string, mixed>> $lines */
        $lines = $request->collect('lines')->filter(
            fn (mixed $line): bool => is_array($line) && (filled($line['description'] ?? null)
                || filled($line['quantity'] ?? null)
                || filled($line['unit_price'] ?? null))
        );
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => __('purchase_orders.validation.lines_required'),
            ]);
        }
        foreach ($lines as $index => $line) {
            if (! filled($line['quantity'] ?? null)) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => __('purchase_orders.validation.quantity_required'),
                ]);
            }
            if (! array_key_exists('unit_price', $line) || $line['unit_price'] === null || $line['unit_price'] === '') {
                throw ValidationException::withMessages([
                    "lines.$index.unit_price" => __('purchase_orders.validation.unit_price_required'),
                ]);
            }
        }
        $preparedLines = $this->prepareLines($lines);
        $subtotal = $preparedLines->reduce(
            fn (string $total, array $line): string => bcadd($total, is_numeric($line['total_amount'] ?? 0) ? (string) ($line['total_amount'] ?? 0) : '0.00', 2),
            '0.00'
        );
        $taxAmount = '0.00';
        $totalAmount = $subtotal;

        $deleteAttachmentsRaw = $request->validated('delete_attachments');
        /** @var array<int, mixed> $deleteAttachments */
        $deleteAttachments = is_array($deleteAttachmentsRaw) ? array_values($deleteAttachmentsRaw) : [];
        $this->validateAttachmentLimit($request, $purchaseOrder, $deleteAttachments);

        DB::transaction(function () use (
            $company, $contact, $preparedLines, &$purchaseOrder,
            $subtotal, $taxAmount, $totalAmount, $data, $request
        ): void {
            $purchaseOrder->fill([
                'company_id' => $company->id,
                'contact_id' => $contact?->id,
                'document_number' => $purchaseOrder->document_number
                    ?? $this->generatePurchaseOrderNumber(),
                'purchase_type' => $data->purchaseType?->value,
                'supplier_reference_number' => $data->supplierReferenceNumber,
                'supplier_sales_order_number' => $data->supplierSalesOrderNumber,
                'company_name' => $company->name,
                'contact_name' => $contact?->name,
                'date' => $data->date?->format('Y-m-d'),
                'expected_date' => $data->expectedDate?->format('Y-m-d'),
                'credit_term_days' => $request->creditTermDays(),
                'due_date' => $data->date?->addDays($request->creditTermDays())->format('Y-m-d'),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $data->notes,
            ])->save();

            PurchaseOrderLine::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->delete();
            $now = now();
            $lineRows = $preparedLines->map(fn (array $line): array => [
                'id' => (string) Str::uuid7(),
                'purchase_order_id' => $purchaseOrder->id,
                ...$line,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            PurchaseOrderLine::query()->insert($lineRows);
        });
        $this->deleteAttachments($purchaseOrder, $deleteAttachments);
        $this->storeAttachments($request, $purchaseOrder);

        return redirect()->route('purchase.purchase-orders.show', $purchaseOrder)
            ->with('success', FlashMessage::updated(FileResources::PURCHASE_ORDERS));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_ORDERS, FileAction::REMOVE);
        $this->abortIfLocked($purchaseOrder);

        DB::transaction(function () use ($purchaseOrder): void {
            PurchaseOrderLine::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->delete();
            PurchaseOrderAttachment::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->delete();
            $purchaseOrder->delete();
        });

        return redirect()->route('purchase.purchase-orders.index')
            ->with('success', FlashMessage::deleted(FileResources::PURCHASE_ORDERS));
    }

    /**
     * Approve and lock the PO within the user's configured role authority.
     *
     * Approval does not create a Purchase Invoice, post accounting, or change
     * product quantity. The supplier Sales Order is recorded in the next step.
     *
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_ORDERS, FileAction::APPROVE);

        DB::transaction(function () use ($purchaseOrder): void {
            $lockedOrder = PurchaseOrder::query()->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedOrder->approved_at !== null, 409);
            $lockedOrder->load('lines');
            abort_if($lockedOrder->lines->isEmpty(), 422, __('purchase_orders.validation.lines_required'));
            abort_if(PurchaseInvoice::query()->where('purchase_order_id', $lockedOrder->id)->exists(), 409);

            $user = auth()->user();
            abort_if($user === null, 403);
            $lockedOrder->update([
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approved_name' => $user->name,
            ]);
        });

        return redirect()->route('purchase.purchase-orders.show', $purchaseOrder)
            ->with('success', FlashMessage::approved(FileResources::PURCHASE_ORDERS));
    }

    /**
     * Record the supplier Sales Order and create the pending Purchase Invoice.
     *
     * The Purchase Invoice retains separate PO and supplier Sales Order numbers.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function createPurchaseInvoice(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::ADD);
        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'supplier_sales_order_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
        ]);

        /** @var string $invoiceDate */
        $invoiceDate = $validated['invoice_date'];
        /** @var string $supplierSalesOrderNumber */
        $supplierSalesOrderNumber = $validated['supplier_sales_order_number'];

        $documentNumber = DB::transaction(function () use ($purchaseOrder, $invoiceDate, $supplierSalesOrderNumber): string {
            $lockedOrder = PurchaseOrder::query()
                ->whereKey($purchaseOrder->id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if($lockedOrder->approved_at === null || $lockedOrder->is_partial_received || $lockedOrder->is_received, 409);
            $lockedOrder->load('lines');
            abort_if($lockedOrder->lines->isEmpty(), 422, __('purchase_orders.validation.lines_required'));

            // A purchase order raises at most one invoice.
            abort_if(PurchaseInvoice::query()->where('purchase_order_id', $lockedOrder->id)->exists(), 409);

            $documentNumber = $this->generateDocumentNumber(LedgerSubType::PURCHASE_INVOICE);
            $dueDate = Carbon::parse($invoiceDate)
                ->addDays($lockedOrder->credit_term_days)
                ->toDateString();

            $now = now();

            // Create the PurchaseInvoice header.
            $invoice = new PurchaseInvoice;
            $invoice->forceFill([
                'company_id' => $lockedOrder->company_id,
                'contact_id' => $lockedOrder->contact_id,
                'purchase_type' => $lockedOrder->purchase_type,
                'status' => LedgerStatus::PENDING->value,
                'document_number' => $documentNumber,
                'reference_number' => $documentNumber,
                'purchase_order_id' => $lockedOrder->id,
                'company_name' => $lockedOrder->company_name,
                'contact_name' => $lockedOrder->contact_name,
                'date' => $invoiceDate,
                'due_date' => $dueDate,
                'notes' => $lockedOrder->notes,
            ]);
            $invoice->save();

            // Create invoice lines from the PO lines.
            $invoiceSubtotal = '0.00';
            $invoiceTotal = '0.00';
            $lineRows = [];

            foreach ($lockedOrder->lines as $poLine) {
                /** @var PurchaseOrderLine $poLine */
                $lineQuantity = $poLine->quantity;
                $lineTotal = (string) $poLine->total_amount;
                $lineSubtotal = $lineTotal;

                $lineRows[] = [
                    'id' => (string) Str::uuid7(),
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $poLine->product_id,
                    'product_name' => $poLine->product_name,
                    'quantity' => $lineQuantity,
                    'unit_price' => (string) $poLine->unit_price,
                    'subtotal_amount' => $lineSubtotal,
                    'discount_value' => '0.00',
                    'discount_amount' => '0.00',
                    'tax_amount' => '0.00',
                    'line_total_amount' => $lineTotal,
                    'total_amount' => $lineTotal,
                    'description' => $poLine->description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $invoiceSubtotal = bcadd($invoiceSubtotal, $lineSubtotal, 2);
                $invoiceTotal = bcadd($invoiceTotal, $lineTotal, 2);
            }

            PurchaseInvoiceLine::query()->insert($lineRows);

            // Update invoice totals.
            $invoice->update([
                'subtotal' => $invoiceSubtotal,
                'total_amount' => $invoiceTotal,
            ]);

            $lockedOrder->update([
                'supplier_sales_order_number' => $supplierSalesOrderNumber,
                'due_date' => $dueDate,
            ]);

            return $documentNumber;
        });

        return redirect()->route('purchase.invoices.show', [
            'documentNumber' => $documentNumber,
        ])->with('success', __('purchase_orders.messages.invoice_created'));
    }

    /**
     * Generate the PDF representation of the resource.
     *
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @return Response Returns the HTTP response.
     */
    public function pdf(PurchaseOrder $purchaseOrder): Response
    {
        $this->allows(FileResources::PURCHASE_ORDERS);
        $purchaseOrder->load('lines');

        return Pdf::loadView('purchase.purchase-orders.pdf', [
            'purchaseOrder' => $purchaseOrder,
            'setting' => Setting::query()->first(),
        ])->setPaper('a4')->stream($purchaseOrder->document_number.'.pdf');
    }

    /**
     * Download one supporting document belonging to the Purchase Order.
     *
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @param  PurchaseOrderAttachment  $attachment  The attachment resource instance.
     * @return StreamedResponse Returns the requested file download.
     */
    public function downloadAttachment(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderAttachment $attachment
    ): StreamedResponse {
        $this->allows(FileResources::PURCHASE_ORDERS);
        abort_unless($attachment->purchase_order_id === $purchaseOrder->id, 404);
        abort_unless($this->documentExists($attachment->file_path), 404);

        return $this->downloadDocument($attachment->file_path, $attachment->original_name);
    }

    /**
     * Store uploaded Purchase Order files and insert their metadata in one batch.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @return void Returns nothing.
     *
     * @throws Throwable
     */
    private function storeAttachments(Request $request, PurchaseOrder $purchaseOrder): void
    {
        $uploadedFiles = $request->file('attachments', []);
        if (! is_array($uploadedFiles) || $uploadedFiles === []) {
            return;
        }

        $user = $request->user();
        abort_if($user === null, 403);
        $now = now();
        $attachmentRows = [];
        $storedPaths = [];
        try {
            foreach ($uploadedFiles as $uploadedFile) {
                try {
                    $filePath = $this->storeUpload($uploadedFile, "purchase-orders/$purchaseOrder->id");
                } catch (RuntimeException) {
                    throw ValidationException::withMessages([
                        'attachments' => __('purchase_orders.validation.attachment_upload_failed'),
                    ]);
                }

                $storedPaths[] = $filePath;
                $attachmentRows[] = [
                    'id' => (string) Str::uuid7(),
                    'purchase_order_id' => $purchaseOrder->id,
                    'file_path' => $filePath,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getMimeType(),
                    'file_size' => (int) $uploadedFile->getSize(),
                    'uploaded_by' => $user->id,
                    'uploaded_by_name' => $user->name,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }
            PurchaseOrderAttachment::query()->insert($attachmentRows);
        } catch (Throwable $throwable) {
            $this->deleteDocuments($storedPaths);

            throw $throwable;
        }
    }

    /**
     * Soft-delete selected Purchase Order attachment records.
     *
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @param  array<int, mixed>  $attachmentIds  The validated attachment identifiers.
     * @return void Returns nothing.
     */
    private function deleteAttachments(PurchaseOrder $purchaseOrder, array $attachmentIds): void
    {
        $ids = array_values(array_filter($attachmentIds, is_string(...)));
        if ($ids === []) {
            return;
        }

        PurchaseOrderAttachment::query()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->whereKey($ids)
            ->delete();
    }

    /**
     * Ensure one Purchase Order does not retain more than ten attachments.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @param  array<int, mixed>  $deleteAttachmentIds  The attachments selected for removal.
     * @return void Returns nothing.
     */
    private function validateAttachmentLimit(
        Request $request,
        PurchaseOrder $purchaseOrder,
        array $deleteAttachmentIds
    ): void {
        $ids = array_values(array_filter($deleteAttachmentIds, is_string(...)));
        $deleteCount = $ids === []
            ? 0
            : PurchaseOrderAttachment::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->whereKey($ids)
                ->count();
        $uploadedFiles = $request->file('attachments', []);
        $newCount = is_array($uploadedFiles) ? count($uploadedFiles) : 0;
        $retainedCount = PurchaseOrderAttachment::query()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->count() - $deleteCount;

        if ($retainedCount + $newCount > 10) {
            throw ValidationException::withMessages([
                'attachments' => __('purchase_orders.validation.attachment_limit'),
            ]);
        }
    }

    /**
     * Prepare purchase-order lines for persistence.
     *
     * @param  Collection<int, array<string, mixed>>  $lines  The validated line data.
     * @return Collection<int, covariant array<string, mixed>>
     *
     * @throws Throwable
     */
    private function prepareLines(Collection $lines): Collection
    {
        return $lines->values()->map(function (array $line, int $index): array {
            $descriptionRaw = $line['description'] ?? '';
            $description = trim(is_string($descriptionRaw) ? $descriptionRaw : '');
            if ($description === '') {
                throw ValidationException::withMessages([
                    "lines.$index.description" => __('purchase_orders.validation.description_required'),
                ]);
            }
            $quantityRaw = $line['quantity'] ?? 0;
            $quantity = is_numeric($quantityRaw) ? (int) $quantityRaw : 0;
            $unitPriceRaw = $line['unit_price'] ?? 0;
            $unitPrice = number_format(is_numeric($unitPriceRaw) ? (float) $unitPriceRaw : 0.0, 2, '.', '');

            return [
                'product_id' => null,
                'product_sku' => null,
                'product_name' => $description,
                'description' => $description,
                'is_service' => false,
                'acquisition_type' => null,
                'debit_chart_id' => null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => bcmul((string) $quantity, $unitPrice, 2),
            ];
        });
    }

    /**
     * Handle the form data operation.
     *
     * @return array<string, mixed> Returns the resulting data array.
     */
    private function formData(): array
    {
        $purchaseOrderSetting = PurchaseOrderSetting::query()->firstOrCreate([], [
            'default_credit_term_days' => 30,
        ]);

        return [
            'companies' => Company::query()->where('is_creditor', true)->orderBy('name')->get(),
            'contacts' => Contact::query()->whereHas('company', fn ($query) => $query->where('is_creditor', true))->orderBy('name')->get(),
            'defaultCreditTermDays' => $purchaseOrderSetting->default_credit_term_days,
        ];
    }

    /**
     * Handle the abort if locked operation.
     *
     * @param  PurchaseOrder  $purchaseOrder  The purchase order resource instance.
     * @return void Returns nothing.
     */
    private function abortIfLocked(PurchaseOrder $purchaseOrder): void
    {
        abort_if($purchaseOrder->approved_at !== null, 409);
    }
}
