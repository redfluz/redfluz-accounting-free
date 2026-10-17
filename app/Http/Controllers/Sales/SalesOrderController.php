<?php

namespace App\Http\Controllers\Sales;

use App\Constants\FlashMessage;
use App\Enums\DiscountType;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\SalesOrderStatus;
use App\Enums\SalesType;
use App\Enums\TaxMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SalesOrder\StoreSalesOrderRequest;
use App\Http\Requests\Sales\SalesOrder\UpdateSalesOrderRequest;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Finance\Discount;
use App\Models\Finance\Tax;
use App\Models\Inventory\Product;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderLine;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Sales\SalesOrderData;
use App\Support\Traits\AttributesDocuments;
use App\Traits\GeneratesDocumentNumber;
use App\Traits\HandlesInvoiceProductMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Maintain customer Sales Orders before delivery fulfilment.
 */
class SalesOrderController extends Controller
{
    use AttributesDocuments, GeneratesDocumentNumber, HandlesInvoiceProductMovement;

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::SALES_ORDERS);
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $salesOrders = SalesOrder::query()
            ->withCount('lines')
            ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('document_number', 'like', "%$search%")
                    ->orWhereHas('quotation', fn ($query) => $query->where('document_number', 'like', "%$search%"))
                    ->orWhere('customer_reference_number', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%");
            }))
            ->when($startDate !== '', fn ($query) => $query->whereDate('order_date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('order_date', '<=', $endDate))
            ->latest('order_date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('sales.sales-orders.index', compact('salesOrders', 'search', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::SALES_ORDERS, FileAction::ADD);

        return view('sales.sales-orders.create', $this->formData());
    }

    /**
     * Create a customer without leaving the Sales Order form.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return JsonResponse Returns the new customer for the form selector.
     */
    public function storeCompany(Request $request): JsonResponse
    {
        $this->allows(FileResources::SALES_ORDERS, FileAction::ADD);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $companyName = is_array($validated) && is_string($validated['name'] ?? null) ? $validated['name'] : '';
        $company = Company::query()->create([
            'name' => trim($companyName),
            'is_debtor' => true,
        ]);

        return response()->json([
            'id' => $company->id,
            'name' => $company->name,
        ], 201);
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreSalesOrderRequest  $request  The validated sales order being created.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function store(StoreSalesOrderRequest $request): RedirectResponse
    {
        $data = SalesOrderData::from($request->validated());
        /** @var Collection<int, array<string, mixed>> $linesCollection */
        $linesCollection = $request->collect('lines');
        $lines = $linesCollection->filter(
            fn (array $line): bool => filled($line['product_id'] ?? null)
        )->values();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('sales_orders.validation.lines_required')]);
        }

        /** @var EloquentCollection<string, Product> $products */
        $products = Product::query()->whereIn('id', $lines->pluck('product_id')->unique())
            ->get()->keyBy('id');
        $preparedLines = $lines->map(function (array $line, int $index) use ($data, $products): array {
            /** @var Product|null $product */
            $product = $products->get(is_string($line['product_id']) ? $line['product_id'] : null);
            if ($product === null || $product->is_service) {
                throw ValidationException::withMessages([
                    "lines.$index.product_id" => __('sales_orders.validation.physical_product_required'),
                ]);
            }
            if (! filled($line['quantity'] ?? null)) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => __('sales_orders.validation.quantity_required'),
                ]);
            }
            $quantity = DecimalService::normalize(is_string($line['quantity']) || is_int($line['quantity']) || is_float($line['quantity']) ? (string) $line['quantity'] : '0');
            $unitPriceRaw = $line['unit_price'] ?? $product->selling_price;
            $unitPrice = DecimalService::normalize(is_string($unitPriceRaw) || is_int($unitPriceRaw) || is_float($unitPriceRaw) ? (string) $unitPriceRaw : $product->selling_price);

            $rawWarranty = $line['warranty_months'] ?? $product->warranty_months ?? -1;
            $warrantyMonths = is_numeric($rawWarranty) ? (int) (string) $rawWarranty : -1;

            return [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'description' => $line['description'] ?? $product->description,
                'is_service' => false,
                'ordered_quantity' => $quantity,
                'delivered_quantity' => '0.0000',
                'unit_price' => $unitPrice,
                'warranty_months' => $warrantyMonths === -1 ? null : $warrantyMonths,
                ...$this->resolveSalesOrderLineTerms($line, $product, $quantity, $unitPrice, (string) $data->orderDate?->format('Y-m-d'), $index),
            ];
        });
        $total = $preparedLines->reduce(
            /** @param numeric-string $sum */
            fn (string $sum, array $line): string => bcadd(
                $sum,
                DecimalService::normalize(is_string($line['total_amount']) ? $line['total_amount'] : '0.00'),
                2
            ),
            '0.00'
        );
        $company = Company::query()->whereKey($data->companyId)->where('is_debtor', true)->firstOrFail();
        $contact = filled($data->contactId)
            ? Contact::query()->whereKey($data->contactId)->firstOrFail()
            : null;
        if ($contact !== null && $contact->company_id !== $company->id) {
            throw ValidationException::withMessages(['contact_id' => __('sales_orders.validation.contact_company')]);
        }

        $salesOrder = new SalesOrder;
        $attachmentPath = $request->hasFile('attachment') ? $this->storeUpload($request->file('attachment'), 'sales_orders') : null;
        DB::transaction(function () use ($attachmentPath, $company, $contact, $data, $preparedLines, $salesOrder, $total): void {
            $salesOrder->fill([
                'company_id' => $company->id,
                'contact_id' => $contact?->id,
                'document_number' => $this->generateSalesOrderNumber(),
                'customer_reference_number' => $data->customerReferenceNumber,
                'company_name' => $company->name,
                'contact_name' => $contact?->name,
                'order_date' => $data->orderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'status' => SalesOrderStatus::DRAFT,
                'subtotal' => $total,
                'total_amount' => $total,
                'notes' => $data->notes,
                'attachment_path' => $attachmentPath,
            ])->save();
            $now = now();
            $rows = $preparedLines->map(fn (array $line): array => [
                'id' => (string) Str::uuid7(),
                'sales_order_id' => $salesOrder->id,
                ...$line,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ])->all();
            SalesOrderLine::query()->insert($rows);
        });

        return redirect()->route('sales.sales-orders.show', $salesOrder)
            ->with('success', FlashMessage::created(FileResources::SALES_ORDERS));
    }

    /**
     * Display the specified resource.
     *
     * @param  SalesOrder  $salesOrder  The Sales Order resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(SalesOrder $salesOrder): View
    {
        $this->allows(FileResources::SALES_ORDERS);
        $salesOrder->load(['lines', 'deliveryOrders', 'quotation', 'invoice']);

        return view('sales.sales-orders.show', compact('salesOrder'));
    }

    /**
     * Stream a printable Sales Order document.
     *
     * @param  SalesOrder  $salesOrder  The sales-order resource instance.
     * @return Response Returns the printable Sales Order response.
     */
    public function pdf(SalesOrder $salesOrder): Response
    {
        $this->allows(FileResources::SALES_ORDERS);
        $salesOrder->load(['lines', 'company', 'contact', 'quotation']);

        return Pdf::loadView('sales.sales-orders.pdf', [
            'salesOrder' => $salesOrder,
            'setting' => Setting::query()->firstOrNew(),
        ])->setPaper('a4')->stream($salesOrder->document_number.'.pdf');
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  SalesOrder  $salesOrder  The Sales Order resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(SalesOrder $salesOrder): View
    {
        $this->allows(FileResources::SALES_ORDERS, FileAction::UPDATE);
        abort_if($salesOrder->status !== SalesOrderStatus::DRAFT, 409);
        $salesOrder->load('lines');

        return view('sales.sales-orders.edit', $this->formData() + compact('salesOrder'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateSalesOrderRequest  $request  The validated sales order being updated.
     * @param  SalesOrder  $salesOrder  The Sales Order resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        abort_if($salesOrder->status !== SalesOrderStatus::DRAFT, 409);
        $data = SalesOrderData::from($request->validated());
        /** @var Collection<int, array<string, mixed>> $linesCollection */
        $linesCollection = $request->collect('lines');
        $lines = $linesCollection->filter(
            fn (array $line): bool => filled($line['product_id'] ?? null)
        )->values();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('sales_orders.validation.lines_required')]);
        }

        /** @var EloquentCollection<string, Product> $products */
        $products = Product::query()->whereIn('id', $lines->pluck('product_id')->unique())
            ->get()->keyBy('id');
        $preparedLines = $lines->map(function (array $line, int $index) use ($data, $products): array {
            /** @var Product|null $product */
            $product = $products->get(is_string($line['product_id']) ? $line['product_id'] : null);
            if ($product === null || $product->is_service) {
                throw ValidationException::withMessages([
                    "lines.$index.product_id" => __('sales_orders.validation.physical_product_required'),
                ]);
            }
            if (! filled($line['quantity'] ?? null)) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => __('sales_orders.validation.quantity_required'),
                ]);
            }
            $quantity = DecimalService::normalize(is_string($line['quantity']) || is_int($line['quantity']) || is_float($line['quantity']) ? (string) $line['quantity'] : '0');
            $unitPriceRaw = $line['unit_price'] ?? $product->selling_price;
            $unitPrice = DecimalService::normalize(is_string($unitPriceRaw) || is_int($unitPriceRaw) || is_float($unitPriceRaw) ? (string) $unitPriceRaw : $product->selling_price);

            $rawWarranty = $line['warranty_months'] ?? $product->warranty_months ?? -1;
            $warrantyMonths = is_numeric($rawWarranty) ? (int) (string) $rawWarranty : -1;

            return [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'description' => $line['description'] ?? $product->description,
                'is_service' => false,
                'ordered_quantity' => $quantity,
                'delivered_quantity' => '0.0000',
                'unit_price' => $unitPrice,
                'warranty_months' => $warrantyMonths === -1 ? null : $warrantyMonths,
                ...$this->resolveSalesOrderLineTerms($line, $product, $quantity, $unitPrice, (string) $data->orderDate?->format('Y-m-d'), $index),
            ];
        });
        $total = $preparedLines->reduce(
            /** @param numeric-string $sum */
            fn (string $sum, array $line): string => bcadd(
                $sum,
                DecimalService::normalize(is_string($line['total_amount']) ? $line['total_amount'] : '0.00'),
                2
            ),
            '0.00'
        );
        $company = Company::query()->whereKey($data->companyId)->where('is_debtor', true)->firstOrFail();
        $contact = filled($data->contactId)
            ? Contact::query()->whereKey($data->contactId)->firstOrFail()
            : null;
        if ($contact !== null && $contact->company_id !== $company->id) {
            throw ValidationException::withMessages(['contact_id' => __('sales_orders.validation.contact_company')]);
        }

        $attachmentPath = $request->hasFile('attachment') ? $this->storeUpload($request->file('attachment'), 'sales_orders') : $salesOrder->attachment_path;
        DB::transaction(function () use ($attachmentPath, $company, $contact, $data, $preparedLines, $salesOrder, $total): void {
            $salesOrder->fill([
                'company_id' => $company->id,
                'contact_id' => $contact?->id,
                'customer_reference_number' => $data->customerReferenceNumber,
                'company_name' => $company->name,
                'contact_name' => $contact?->name,
                'order_date' => $data->orderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'subtotal' => $total,
                'total_amount' => $total,
                'notes' => $data->notes,
                'attachment_path' => $attachmentPath,
            ])->save();
            SalesOrderLine::query()->where('sales_order_id', $salesOrder->id)->delete();
            $now = now();
            $rows = $preparedLines->map(fn (array $line): array => [
                'id' => (string) Str::uuid7(),
                'sales_order_id' => $salesOrder->id,
                ...$line,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ])->all();
            SalesOrderLine::query()->insert($rows);
        });

        return redirect()->route('sales.sales-orders.show', $salesOrder)
            ->with('success', FlashMessage::updated(FileResources::SALES_ORDERS));
    }

    /**
     * Remove a resource by its object.
     * Remove the specified resource from storage.
     *
     * @param  SalesOrder  $salesOrder  The Sales Order resource instance.
     * @return RedirectResponse Redirects to the resource list.
     */
    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        $this->allows(FileResources::SALES_ORDERS, FileAction::REMOVE);
        abort_if($salesOrder->status !== SalesOrderStatus::DRAFT, 409);
        $salesOrder->delete();

        return redirect()->route('sales.sales-orders.index')
            ->with('success', FlashMessage::deleted(FileResources::SALES_ORDERS));
    }

    /**
     * Approve a Sales Order without changing stock.
     *
     * @param  SalesOrder  $salesOrder  The Sales Order resource instance.
     * @return RedirectResponse Redirects to the Sales Order.
     *
     * @throws Throwable
     */
    public function approve(SalesOrder $salesOrder): RedirectResponse
    {
        $this->allows(FileResources::SALES_ORDERS, FileAction::APPROVE);

        DB::transaction(function () use ($salesOrder): void {
            $lockedOrder = SalesOrder::query()->with('lines')
                ->whereKey($salesOrder->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedOrder->status !== SalesOrderStatus::DRAFT, 409);
            abort_if($lockedOrder->lines->isEmpty(), 422, __('sales_orders.validation.lines_required'));
            $lockedOrder->update([
                'status' => SalesOrderStatus::APPROVED,
            ]);
            $this->attributeApproved($lockedOrder);
        });

        return redirect()->route('sales.sales-orders.show', $salesOrder)
            ->with('success', FlashMessage::approved(FileResources::SALES_ORDERS));
    }

    /**
     * Create a pending Sales Invoice directly from an approved Sales Order.
     *
     * This path is for sales that do not require a Delivery Order. It is only
     * available before delivery fulfilment starts, so the same quantities
     * cannot be invoiced through both workflows.
     *
     * @param  SalesOrder  $salesOrder  The approved Sales Order resource instance.
     * @return RedirectResponse Redirects to the newly created Sales Invoice.
     *
     * @throws Throwable
     */
    public function createSalesInvoice(SalesOrder $salesOrder): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::ADD);

        $documentNumber = DB::transaction(function () use ($salesOrder): string {
            $lockedOrder = SalesOrder::query()
                ->with(['lines', 'deliveryOrders'])
                ->whereKey($salesOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($lockedOrder->status !== SalesOrderStatus::APPROVED, 409);
            abort_if(SalesInvoice::query()->where('sales_order_id', $lockedOrder->id)->exists(), 409);
            abort_if($lockedOrder->deliveryOrders->isNotEmpty(), 409);
            abort_if($lockedOrder->lines->isEmpty(), 422, __('sales_orders.validation.lines_required'));

            $documentNumber = $this->generateDocumentNumber(LedgerSubType::SALES_INVOICE);
            $invoiceDate = today()->toDateString();
            $now = now();
            $stockLines = $lockedOrder->lines->map(fn (SalesOrderLine $line): array => [
                'product_id' => $line->product_id,
                'quantity' => $line->ordered_quantity,
            ]);
            $this->ensureSalesStockCanBeReserved($stockLines, $documentNumber);

            // A pending invoice is a document, not ledger rows. The invoice posts
            // to the ledgers on approval through the ledger services, which
            // resolve accounts from the chart configuration.
            $invoice = SalesInvoice::query()->create([
                'id' => (string) Str::uuid7(),
                'company_id' => $lockedOrder->company_id,
                'contact_id' => $lockedOrder->contact_id,
                'document_number' => $documentNumber,
                'sales_type' => SalesType::PRODUCT,
                'reference_number' => $lockedOrder->document_number,
                'sales_order_id' => $lockedOrder->id,
                'company_name' => $lockedOrder->company_name,
                'contact_name' => $lockedOrder->contact?->name,
                'date' => $invoiceDate,
                'due_date' => null,
                'status' => LedgerStatus::PENDING,
                'attachment_path' => null,
                'notes' => $lockedOrder->notes,
                'subtotal' => $lockedOrder->subtotal,
                'total_amount' => $lockedOrder->total_amount,
            ]);

            $lineRows = $lockedOrder->lines->map(function (SalesOrderLine $line) use ($invoice, $now): array {
                return [
                    'id' => (string) Str::uuid7(),
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product_name,
                    'quantity' => $line->ordered_quantity,
                    'unit_price' => $line->unit_price,
                    'subtotal_amount' => $line->subtotal_amount,
                    'discount_id' => $line->discount_id,
                    'discount_name' => $line->discount_name,
                    'discount_type' => $line->discount_type?->value,
                    'discount_value' => $line->discount_value,
                    'discount_rate' => $line->discount_rate,
                    'discount_amount' => $line->discount_amount,
                    'tax_id' => $line->tax_id,
                    'tax_name' => $line->tax_name,
                    'tax_rate' => $line->tax_rate,
                    'tax_amount' => $line->tax_amount,
                    'tax_mode' => $line->tax_mode?->value,
                    'line_total_amount' => $line->line_total_amount,
                    'total_amount' => $line->line_total_amount,
                    'warranty_months' => $line->warranty_months,
                    'deduct_inventory' => false,
                    'description' => $line->description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            SalesInvoiceLine::query()->insert($lineRows);
            $this->syncPendingSalesProductMovements($documentNumber);

            return $documentNumber;
        });

        return redirect()->route('sales.invoices.show', $documentNumber)
            ->with('success', FlashMessage::created(FileResources::SALES_INVOICES));
    }

    /**
     * Resolve and snapshot the product defaults or manual values for one Sales Order line.
     *
     * @param  array<string, mixed>  $line  The submitted line values.
     * @param  Product  $product  The selected product resource instance.
     * @param  numeric-string  $quantity  The ordered quantity.
     * @param  numeric-string  $unitPrice  The unit selling price.
     * @param  string  $orderDate  The Sales Order date.
     * @param  int  $index  The submitted line index.
     * @return array<string, string|null>
     */
    private function resolveSalesOrderLineTerms(
        array $line,
        Product $product,
        string $quantity,
        string $unitPrice,
        string $orderDate,
        int $index
    ): array {
        $subtotal = round((float) $quantity * (float) $unitPrice, 2);
        $discountOptionRaw = $line['discount_option'] ?? $product->default_discount_id ?? 'none';
        $discountOption = is_string($discountOptionRaw) ? $discountOptionRaw : 'none';
        $discount = in_array($discountOption, ['none', 'manual'], true) ? null : Discount::query()->whereKey($discountOption)->where('is_active', true)->first();
        if (! in_array($discountOption, ['none', 'manual'], true) && $discount === null) {
            throw ValidationException::withMessages(["lines.$index.discount_option" => __('discounts.messages.inactive')]);
        }
        if ($discount instanceof Discount && (($discount->starts_at !== null && $orderDate < $discount->starts_at->format('Y-m-d')) || ($discount->ends_at !== null && $orderDate > $discount->ends_at->format('Y-m-d')))) {
            throw ValidationException::withMessages(["lines.$index.discount_option" => __('discounts.messages.outside_date_range')]);
        }
        $discountTypeRaw = $line['discount_type'] ?? DiscountType::PERCENT->value;
        $discountType = $discount instanceof Discount ? $discount->type : DiscountType::from(is_string($discountTypeRaw) ? $discountTypeRaw : DiscountType::PERCENT->value);
        $discountValueRaw = $discount instanceof Discount ? $discount->value : ($line['discount_value'] ?? 0);
        $discountValue = $discountOption === 'none' ? 0.0 : (is_numeric($discountValueRaw) ? (float) $discountValueRaw : 0.0);
        if ($discountType === DiscountType::PERCENT && $discountValue > 100) {
            throw ValidationException::withMessages(["lines.$index.discount_value" => __('validation.max.numeric', ['attribute' => __('discounts.form.value'), 'max' => 100])]);
        }
        $discountAmount = $discountType === DiscountType::PERCENT ? round($subtotal * $discountValue / 100, 2) : round($discountValue, 2);
        if ($discountAmount > $subtotal) {
            throw ValidationException::withMessages(["lines.$index.discount_value" => __('discounts.messages.amount_exceeds_subtotal')]);
        }
        $net = round($subtotal - $discountAmount, 2);

        $taxOptionRaw = $line['tax_option'] ?? $product->default_tax_id ?? 'none';
        $taxOption = is_string($taxOptionRaw) ? $taxOptionRaw : 'none';
        $tax = in_array($taxOption, ['none', 'manual'], true) ? null : Tax::query()->whereKey($taxOption)->where('is_active', true)->first();
        if (! in_array($taxOption, ['none', 'manual'], true) && $tax === null) {
            throw ValidationException::withMessages(["lines.$index.tax_option" => __('taxes.messages.inactive')]);
        }
        $taxRateRaw = $tax instanceof Tax ? $tax->rate : ($line['tax_rate'] ?? 0);
        $taxRate = $taxOption === 'none' ? 0.0 : (is_numeric($taxRateRaw) ? (float) $taxRateRaw : 0.0);
        $taxModeRaw = $line['tax_mode'] ?? TaxMode::EXCLUSIVE->value;
        $taxMode = $tax instanceof Tax ? $tax->default_mode : TaxMode::from(is_string($taxModeRaw) ? $taxModeRaw : TaxMode::EXCLUSIVE->value);
        $taxAmount = $taxMode === TaxMode::INCLUSIVE && $taxRate > 0 ? round($net * $taxRate / (100 + $taxRate), 2) : round($net * $taxRate / 100, 2);
        $total = $taxMode === TaxMode::INCLUSIVE ? $net : round($net + $taxAmount, 2);

        return [
            'subtotal_amount' => number_format($subtotal, 2, '.', ''),
            'discount_id' => $discount?->id,
            'discount_name' => $discount instanceof Discount ? $discount->name : ($discountOption === 'manual' ? (string) __('discounts.form.manual') : null),
            'discount_type' => $discountOption === 'none' ? null : $discountType->value,
            'discount_value' => number_format($discountValue, 4, '.', ''),
            'discount_rate' => $discountType === DiscountType::PERCENT ? number_format($discountValue, 4, '.', '') : '0.0000',
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'tax_id' => $tax?->id,
            'tax_name' => $tax instanceof Tax ? $tax->name : ($taxOption === 'manual' ? (string) __('taxes.form.manual') : null),
            'tax_rate' => number_format($taxRate, 4, '.', ''),
            'tax_amount' => number_format($taxAmount, 2, '.', ''),
            'tax_mode' => $taxMode->value,
            'line_total_amount' => number_format($total, 2, '.', ''),
            'total_amount' => number_format($total, 2, '.', ''),
        ];
    }

    /**
     * Build the data required by the sales-order form.
     *
     * @return array<string, mixed> The sales-order form data.
     */
    private function formData(): array
    {
        /** @var EloquentCollection<int, Company> $companies */
        $companies = Company::query()->where('is_debtor', true)->orderBy('name')->get();
        /** @var EloquentCollection<int, Contact> $contacts */
        $contacts = Contact::query()->whereHas('company', fn ($query) => $query->where('is_debtor', true))->orderBy('name')->get();
        /** @var EloquentCollection<int, Product> $products */
        $products = Product::query()->where('is_service', false)->orderBy('name')->get();
        /** @var EloquentCollection<int, Discount> $discounts */
        $discounts = Discount::query()->where('is_active', true)->orderBy('code')->get();
        /** @var EloquentCollection<int, Tax> $taxes */
        $taxes = Tax::query()->where('is_active', true)->orderBy('code')->get();

        return compact('companies', 'contacts', 'products', 'discounts', 'taxes');
    }
}
