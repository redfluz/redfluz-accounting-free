<?php

namespace App\Http\Controllers\Sales;

use App\Constants\FlashMessage;
use App\Enums\DeliveryOrderStatus;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Enums\SalesOrderStatus;
use App\Enums\SalesType;
use App\Enums\ShippingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\DeliveryOrder\StoreDeliveryOrderRequest;
use App\Http\Requests\Sales\DeliveryOrder\UpdateDeliveryOrderRequest;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\Company;
use App\Models\Crm\CompanyAddress;
use App\Models\Finance\Ledger;
use App\Models\Inventory\ProductLedger;
use App\Models\Inventory\StockLocation;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\DeliveryOrderLine;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderLine;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Sales\DeliveryOrderData;
use App\Traits\GeneratesDocumentNumber;
use App\Traits\HandlesInvoiceProductMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Maintain Delivery Orders, physical stock issues, and delivery-based invoices.
 */
class DeliveryOrderController extends Controller
{
    use GeneratesDocumentNumber, HandlesInvoiceProductMovement;

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::SALES_DELIVERY_ORDERS);
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $deliveryOrders = DeliveryOrder::query()->with('salesOrder')
            ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('document_number', 'like', "%$search%")
                    ->orWhere('tracking_number', 'like', "%$search%")
                    ->orWhereHas('salesOrder', fn ($orderQuery) => $orderQuery
                        ->where('document_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%"));
            }))
            ->when($startDate !== '', fn ($query) => $query->whereDate('delivery_date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('delivery_date', '<=', $endDate))
            ->latest('delivery_date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('sales.delivery-orders.index', compact('deliveryOrders', 'search', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function create(Request $request): View
    {
        $this->allows(FileResources::SALES_DELIVERY_ORDERS, FileAction::ADD);
        $salesOrderId = $request->string('sales_order')->toString();
        abort_if($salesOrderId === '', 404);
        $selectedSalesOrder = SalesOrder::query()->with(['lines', 'company.addresses'])
            ->whereKey($salesOrderId)
            ->whereIn('status', [SalesOrderStatus::APPROVED->value, SalesOrderStatus::PARTIALLY_DELIVERED->value])
            ->firstOrFail();

        return view('sales.delivery-orders.create', compact('selectedSalesOrder') + $this->formData());
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreDeliveryOrderRequest  $request  The HTTP request instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function store(StoreDeliveryOrderRequest $request): RedirectResponse
    {
        $data = DeliveryOrderData::from($request->validated());

        $salesOrder = SalesOrder::query()->with('lines.product')
            ->whereKey($data->salesOrderId)->firstOrFail();
        abort_unless(in_array($salesOrder->status, [SalesOrderStatus::APPROVED, SalesOrderStatus::PARTIALLY_DELIVERED], true), 409);
        $courierCompany = $this->courierCompany($data->courierCompanyId);
        $stockLocation = StockLocation::query()
            ->whereKey($data->stockLocationId)
            ->where('is_active', true)
            ->first();
        if ($stockLocation === null) {
            throw ValidationException::withMessages([
                'stock_location_id' => __('delivery_orders.validation.stock_location_required'),
            ]);
        }
        $orderLines = $salesOrder->lines->where('is_service', false)->keyBy('id');
        /** @var Collection<int, array<string, mixed>> $linesCollection */
        $linesCollection = $request->collect('lines');
        $lines = $linesCollection->filter(
            fn (array $line): bool => is_numeric($line['quantity'] ?? null) && bccomp(DecimalService::normalize((string) $line['quantity']), '0.0000', 4) === 1
        )->values();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('delivery_orders.validation.lines_required')]);
        }
        $prepared = $lines->map(function (array $line, int $index) use ($orderLines): array {
            /** @var SalesOrderLine|null $orderLine */
            $orderLine = $orderLines->get(is_string($line['sales_order_line_id']) || is_int($line['sales_order_line_id']) ? $line['sales_order_line_id'] : null);
            if ($orderLine === null) {
                throw ValidationException::withMessages([
                    "lines.$index.sales_order_line_id" => __('delivery_orders.validation.invalid_order_line'),
                ]);
            }
            $quantity = DecimalService::normalize(is_string($line['quantity']) || is_int($line['quantity']) ? (string) $line['quantity'] : '0');
            $remaining = bcsub($orderLine->ordered_quantity, $orderLine->delivered_quantity, 4);
            if (bccomp($quantity, $remaining, 4) === 1) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => __('delivery_orders.validation.quantity_exceeds_remaining'),
                ]);
            }
            $product = $orderLine->product;
            abort_if($product === null, 422);

            return [
                'sales_order_line_id' => $orderLine->id,
                'product_id' => $product->id,
                'product_sku' => $orderLine->product_sku ?? $product->sku,
                'product_name' => $orderLine->product_name,
                'description' => $orderLine->description,
                'quantity' => $quantity,
                'unit_price' => $orderLine->unit_price,
                'unit_cost' => $product->base_price,
                'total_amount' => bcmul($quantity, $orderLine->unit_price, 2),
            ];
        });
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'delivery_orders')
            : null;
        $deliveryOrder = new DeliveryOrder;
        DB::transaction(function () use ($attachmentPath, $courierCompany, $deliveryOrder, $prepared, $salesOrder, $stockLocation, $data): void {
            /** @var CompanyAddress $companyAddress */
            $companyAddress = $this->companyAddress(
                $data->companyAddressId,
                $salesOrder,
                $data->deliveryAddress ?? '',
                $data->recipientPhone
            );
            $deliveryOrder->fill([
                'sales_order_id' => $salesOrder->id,
                'company_address_id' => $companyAddress->id,
                'stock_location_id' => $stockLocation->id,
                'stock_location_name' => $stockLocation->name,
                'document_number' => $this->generateDeliveryOrderNumber(),
                'delivery_date' => $data->deliveryDate,
                'recipient_name' => $data->recipientName,
                'recipient_phone' => $data->recipientPhone,
                'delivery_address' => $data->deliveryAddress,
                'courier_company_id' => $courierCompany?->id,
                'courier' => $courierCompany?->name,
                'tracking_number' => $data->trackingNumber,
                'shipping_date' => $data->shippingDate,
                'shipping_status' => $data->shippingStatus?->value,
                'status' => DeliveryOrderStatus::DRAFT,
                'attachment_path' => $attachmentPath,
                'notes' => $data->notes,
            ])->save();
            $now = now();
            $rows = $prepared->map(fn (array $line): array => [
                'id' => (string) Str::uuid7(),
                'delivery_order_id' => $deliveryOrder->id,
                ...$line,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ])->all();
            DeliveryOrderLine::query()->insert($rows);
        });

        return redirect()->route('sales.delivery-orders.show', $deliveryOrder)
            ->with('success', FlashMessage::created(FileResources::SALES_DELIVERY_ORDERS));
    }

    /**
     * Display the specified resource.
     *
     * @param  DeliveryOrder  $deliveryOrder  The Delivery Order resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(DeliveryOrder $deliveryOrder): View
    {
        $this->allows(FileResources::SALES_DELIVERY_ORDERS);
        $deliveryOrder->load(['salesOrder', 'lines']);
        $invoice = SalesInvoice::query()
            ->where('reference_number', $deliveryOrder->document_number)
            ->first(['id', 'document_number']);

        return view('sales.delivery-orders.show', compact('deliveryOrder', 'invoice'));
    }

    /**
     * Stream a printable Delivery Order for dispatch and customer acknowledgement.
     *
     * @param  DeliveryOrder  $deliveryOrder  The delivery-order resource instance.
     * @return Response Returns the printable Delivery Order response.
     */
    public function pdf(DeliveryOrder $deliveryOrder): Response
    {
        $this->allows(FileResources::SALES_DELIVERY_ORDERS);
        $deliveryOrder->load(['salesOrder', 'lines']);

        return Pdf::loadView('sales.delivery-orders.pdf', [
            'deliveryOrder' => $deliveryOrder,
            'setting' => Setting::query()->firstOrNew(),
        ])->setPaper('a4')->stream($deliveryOrder->document_number.'.pdf');
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  DeliveryOrder  $deliveryOrder  The Delivery Order resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(DeliveryOrder $deliveryOrder): View
    {
        $this->allows(FileResources::SALES_DELIVERY_ORDERS, FileAction::UPDATE);
        abort_if($deliveryOrder->status === DeliveryOrderStatus::CANCELLED, 409);
        $deliveryOrder->load(['salesOrder.lines', 'salesOrder.company.addresses', 'lines']);
        $selectedSalesOrder = $deliveryOrder->salesOrder;

        return view('sales.delivery-orders.edit', compact('deliveryOrder', 'selectedSalesOrder') + $this->formData());
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateDeliveryOrderRequest  $request  The HTTP request instance.
     * @param  DeliveryOrder  $deliveryOrder  The Delivery Order resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(UpdateDeliveryOrderRequest $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $data = DeliveryOrderData::from($request->validated());

        if ($deliveryOrder->status === DeliveryOrderStatus::POSTED) {
            $attachmentPath = $request->hasFile('attachment')
                ? $this->storeUpload($request->file('attachment'), 'delivery_orders')
                : $deliveryOrder->attachment_path;
            $salesOrder = $deliveryOrder->salesOrder()->firstOrFail();
            $courierCompany = $this->courierCompany($data->courierCompanyId);
            DB::transaction(function () use ($attachmentPath, $courierCompany, $deliveryOrder, $salesOrder, $data): void {
                /** @var CompanyAddress $companyAddress */
                $companyAddress = $this->companyAddress(
                    $data->companyAddressId,
                    $salesOrder,
                    $data->deliveryAddress ?? '',
                    $data->recipientPhone
                );
                $deliveryOrder->fill([
                    'company_address_id' => $companyAddress->id,
                    'recipient_name' => $data->recipientName,
                    'recipient_phone' => $data->recipientPhone,
                    'delivery_address' => $data->deliveryAddress,
                    'courier_company_id' => $courierCompany?->id,
                    'courier' => $courierCompany?->name,
                    'tracking_number' => $data->trackingNumber,
                    'shipping_date' => $data->shippingDate,
                    'shipping_status' => $data->shippingStatus?->value,
                    'attachment_path' => $attachmentPath,
                    'notes' => $data->notes,
                ])->save();
            });

            return redirect()->route('sales.delivery-orders.show', $deliveryOrder)
                ->with('success', FlashMessage::updated(FileResources::SALES_DELIVERY_ORDERS));
        }
        abort_if($deliveryOrder->status !== DeliveryOrderStatus::DRAFT, 409);
        $salesOrder = SalesOrder::query()->with('lines.product')
            ->whereKey($data->salesOrderId)->firstOrFail();
        abort_unless(in_array($salesOrder->status, [SalesOrderStatus::APPROVED, SalesOrderStatus::PARTIALLY_DELIVERED], true), 409);
        $courierCompany = $this->courierCompany($data->courierCompanyId);
        $stockLocation = StockLocation::query()
            ->whereKey($data->stockLocationId)
            ->where('is_active', true)
            ->first();
        if ($stockLocation === null) {
            throw ValidationException::withMessages([
                'stock_location_id' => __('delivery_orders.validation.stock_location_required'),
            ]);
        }
        $orderLines = $salesOrder->lines->where('is_service', false)->keyBy('id');
        /** @var Collection<int, array<string, mixed>> $linesCollection */
        $linesCollection = $request->collect('lines');
        $lines = $linesCollection->filter(
            fn (array $line): bool => is_numeric($line['quantity'] ?? null) && bccomp(DecimalService::normalize((string) $line['quantity']), '0.0000', 4) === 1
        )->values();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('delivery_orders.validation.lines_required')]);
        }
        $prepared = $lines->map(function (array $line, int $index) use ($orderLines): array {
            /** @var SalesOrderLine|null $orderLine */
            $orderLine = $orderLines->get(is_string($line['sales_order_line_id']) || is_int($line['sales_order_line_id']) ? $line['sales_order_line_id'] : null);
            if ($orderLine === null) {
                throw ValidationException::withMessages([
                    "lines.$index.sales_order_line_id" => __('delivery_orders.validation.invalid_order_line'),
                ]);
            }
            $quantity = DecimalService::normalize(is_string($line['quantity']) || is_int($line['quantity']) ? (string) $line['quantity'] : '0');
            $remaining = bcsub($orderLine->ordered_quantity, $orderLine->delivered_quantity, 4);
            if (bccomp($quantity, $remaining, 4) === 1) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => __('delivery_orders.validation.quantity_exceeds_remaining'),
                ]);
            }
            $product = $orderLine->product;
            abort_if($product === null, 422);

            return [
                'sales_order_line_id' => $orderLine->id,
                'product_id' => $product->id,
                'product_sku' => $orderLine->product_sku ?? $product->sku,
                'product_name' => $orderLine->product_name,
                'description' => $orderLine->description,
                'quantity' => $quantity,
                'unit_price' => $orderLine->unit_price,
                'unit_cost' => $product->base_price,
                'total_amount' => bcmul($quantity, $orderLine->unit_price, 2),
            ];
        });
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'delivery_orders')
            : $deliveryOrder->attachment_path;
        DB::transaction(function () use ($attachmentPath, $courierCompany, $deliveryOrder, $prepared, $salesOrder, $stockLocation, $data): void {
            /** @var CompanyAddress $companyAddress */
            $companyAddress = $this->companyAddress(
                $data->companyAddressId,
                $salesOrder,
                $data->deliveryAddress ?? '',
                $data->recipientPhone
            );
            $deliveryOrder->fill([
                'sales_order_id' => $salesOrder->id,
                'company_address_id' => $companyAddress->id,
                'stock_location_id' => $stockLocation->id,
                'stock_location_name' => $stockLocation->name,
                'delivery_date' => $data->deliveryDate,
                'recipient_name' => $data->recipientName,
                'recipient_phone' => $data->recipientPhone,
                'delivery_address' => $data->deliveryAddress,
                'courier_company_id' => $courierCompany?->id,
                'courier' => $courierCompany?->name,
                'tracking_number' => $data->trackingNumber,
                'shipping_date' => $data->shippingDate,
                'shipping_status' => $data->shippingStatus?->value,
                'attachment_path' => $attachmentPath,
                'notes' => $data->notes,
            ])->save();
            DeliveryOrderLine::query()->where('delivery_order_id', $deliveryOrder->id)->delete();
            $now = now();
            $rows = $prepared->map(fn (array $line): array => [
                'id' => (string) Str::uuid7(),
                'delivery_order_id' => $deliveryOrder->id,
                ...$line,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ])->all();
            DeliveryOrderLine::query()->insert($rows);
        });

        return redirect()->route('sales.delivery-orders.show', $deliveryOrder)
            ->with('success', FlashMessage::updated(FileResources::SALES_DELIVERY_ORDERS));
    }

    /**
     * Remove a resource by its object.
     * Remove an unposted Delivery Order.
     *
     * @param  DeliveryOrder  $deliveryOrder  The Delivery Order resource instance.
     * @return RedirectResponse Redirects to the resource list.
     */
    public function destroy(DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->allows(FileResources::SALES_DELIVERY_ORDERS, FileAction::REMOVE);
        abort_if($deliveryOrder->status !== DeliveryOrderStatus::DRAFT, 409);
        $deliveryOrder->delete();

        return redirect()->route('sales.delivery-orders.index')
            ->with('success', FlashMessage::deleted(FileResources::SALES_DELIVERY_ORDERS));
    }

    /**
     * Post a Delivery Order, issue physical stock, and recognise COGS.
     *
     * @param  DeliveryOrder  $deliveryOrder  The Delivery Order resource instance.
     * @return RedirectResponse Redirects to the Delivery Order.
     *
     * @throws Throwable
     */
    public function post(DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->allows(FileResources::SALES_DELIVERY_ORDERS, FileAction::APPROVE);

        DB::transaction(function () use ($deliveryOrder): void {
            $lockedDelivery = DeliveryOrder::query()->with(['salesOrder.lines.product', 'lines'])
                ->whereKey($deliveryOrder->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedDelivery->status !== DeliveryOrderStatus::DRAFT, 409);
            $salesOrder = SalesOrder::query()->whereKey($lockedDelivery->sales_order_id)
                ->lockForUpdate()->firstOrFail();
            $loadedSalesOrder = $lockedDelivery->salesOrder;
            abort_if($loadedSalesOrder === null, 422);
            $orderLines = $loadedSalesOrder->lines->keyBy('id');
            foreach ($lockedDelivery->lines as $index => $line) {
                /** @var DeliveryOrderLine $line */
                $orderLine = $orderLines->get($line->sales_order_line_id);
                $remaining = $orderLine === null
                    ? '0.0000'
                    : bcsub($orderLine->ordered_quantity, $orderLine->delivered_quantity, 4);
                if ($orderLine === null || bccomp($line->quantity, $remaining, 4) === 1) {
                    throw ValidationException::withMessages([
                        "lines.$index.quantity" => __('delivery_orders.validation.quantity_exceeds_remaining'),
                    ]);
                }
            }
            $this->ensureSalesStockCanBeReserved(
                $lockedDelivery->lines->map(fn (DeliveryOrderLine $line): array => [
                    'product_id' => $line->product_id,
                    'quantity' => $line->quantity,
                ]),
                $lockedDelivery->document_number,
                $lockedDelivery->stock_location_id
            );

            $chartSetting = ChartSetting::query()->first();
            $cogsChart = Chart::query()->whereKey($chartSetting?->cogs_chart_uuid)->first();
            $inventoryChart = Chart::query()->whereKey($chartSetting?->inventory_chart_uuid)->first();
            if ($cogsChart === null) {
                throw ValidationException::withMessages(['document_number' => __('product_ledgers.validation.cogs_account_required')]);
            }
            if ($inventoryChart === null) {
                throw ValidationException::withMessages(['document_number' => __('product_ledgers.validation.inventory_account_required')]);
            }

            $now = now();

            // The sales invoice may already own the stock reduction for this order.
            // When it does, this delivery only records the shipment and must not
            // deduct a second time.
            $invoiceOwnsDeduction = $this->invoiceOwnsStockDeduction($salesOrder);

            $movementRows = $lockedDelivery->lines->map(fn (DeliveryOrderLine $line): array => [
                'id' => (string) Str::uuid7(),
                'product_id' => $line->product_id,
                'stock_location_id' => $lockedDelivery->stock_location_id,
                'stock_location_name' => $lockedDelivery->stock_location_name,
                'company_id' => $salesOrder->company_id,
                'source_entry_id' => $line->id,
                'cogs_ledger_id' => (string) Str::uuid7(),
                'inventory_ledger_id' => (string) Str::uuid7(),
                'product_sku' => $line->product_sku,
                'product_name' => $line->product_name,
                'company_name' => $salesOrder->company_name,
                'document_number' => $lockedDelivery->document_number,
                'reference_number' => $salesOrder->document_number,
                'source_type' => ProductMovementSource::DELIVERY_ORDER->value,
                'direction' => ProductMovementDirection::OUT->value,
                'stock_bucket' => ProductStockBucket::SALEABLE->value,
                'date' => $lockedDelivery->getRawOriginal('delivery_date'),
                'quantity' => $line->quantity,
                'unit_cost' => $line->unit_cost,
                'total_cost' => bcmul((string) $line->quantity, (string) $line->unit_cost, 2),
                'status' => ProductMovementStatus::ACTIVE->value,
                'activated_at' => $now,
                'notes' => $line->description,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
            if ($invoiceOwnsDeduction) {
                return;
            }

            ProductLedger::query()->insert($movementRows->all());

            // Mark any linked sales invoice as already deducted so its
            // deduct-inventory action is withdrawn instead of double posting.
            $linkedInvoiceId = SalesInvoice::query()
                ->where('sales_order_id', $salesOrder->id)
                ->value('id');
            if ($linkedInvoiceId !== null) {
                SalesInvoiceLine::query()
                    ->where('sales_invoice_id', $linkedInvoiceId)
                    ->update(['deduct_inventory' => true, 'updated_at' => now()]);
            }

            $ledgerRows = $movementRows->flatMap(function (array $movement) use ($cogsChart, $inventoryChart): array {
                $common = [
                    'source_id' => $movement['id'],
                    'source_type' => null,
                    'company_id' => $movement['company_id'],
                    'contact_id' => null,
                    'company_name' => $movement['company_name'],
                    'document_number' => $movement['document_number'],
                    'reference_number' => $movement['reference_number'],
                    'type' => LedgerType::JOURNAL->value,
                    'subtype' => LedgerSubType::JOURNAL->value,
                    'date' => $movement['date'],
                    'document_date' => $movement['date'],
                    'total_amount' => $movement['total_cost'],
                    'status' => LedgerStatus::POSTED->value,
                    'notes' => __('product_ledgers.entries.cogs_posting', ['document' => $movement['document_number']]),
                    'description' => $movement['notes'],
                    'created_at' => $movement['created_at'],
                    'updated_at' => $movement['updated_at'],
                    'deleted_at' => null,
                ];

                return [
                    $common + [
                        'id' => $movement['cogs_ledger_id'],
                        'debit_id' => $cogsChart->id,
                        'credit_id' => null,
                        'debit_acc' => $cogsChart->code,
                        'credit_acc' => null,
                        'debit_acc_desc' => $cogsChart->name,
                        'credit_acc_desc' => null,
                        'debit_amount' => $movement['total_cost'],
                        'credit_amount' => '0.00',
                    ],
                    $common + [
                        'id' => $movement['inventory_ledger_id'],
                        'debit_id' => null,
                        'credit_id' => $inventoryChart->id,
                        'debit_acc' => null,
                        'credit_acc' => $inventoryChart->code,
                        'debit_acc_desc' => null,
                        'credit_acc_desc' => $inventoryChart->name,
                        'debit_amount' => '0.00',
                        'credit_amount' => $movement['total_cost'],
                    ],
                ];
            })->all();
            Ledger::query()->insert($ledgerRows);

            $deliveredByLine = $lockedDelivery->lines->keyBy('sales_order_line_id');
            $lineUpdates = $orderLines->map(function (SalesOrderLine $line) use ($deliveredByLine, $now): array {
                $deliveryLine = $deliveredByLine->get($line->id);
                $delivered = $deliveryLine === null
                    ? (string) $line->delivered_quantity
                    : bcadd((string) $line->delivered_quantity, $deliveryLine->quantity, 4);

                return [
                    'id' => $line->id,
                    'sales_order_id' => $line->sales_order_id,
                    'product_id' => $line->product_id,
                    'product_sku' => $line->product_sku,
                    'product_name' => $line->product_name,
                    'description' => $line->description,
                    'is_service' => $line->is_service,
                    'ordered_quantity' => $line->ordered_quantity,
                    'delivered_quantity' => $delivered,
                    'unit_price' => $line->unit_price,
                    'total_amount' => $line->total_amount,
                    'created_at' => $line->created_at,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            });
            DB::table('sales_order_lines')->upsert($lineUpdates->all(), ['id'], ['delivered_quantity', 'updated_at']);

            $hasRemaining = $lineUpdates->contains(fn (array $line): bool => ! $line['is_service']
                && bccomp((string) $line['ordered_quantity'], (string) $line['delivered_quantity'], 4) === 1);
            $salesOrder->update(['status' => $hasRemaining
                ? SalesOrderStatus::PARTIALLY_DELIVERED
                : SalesOrderStatus::DELIVERED]);
            $lockedDelivery->update([
                'status' => DeliveryOrderStatus::POSTED,
                'posted_by' => auth()->id(),
                'posted_at' => $now,
                'shipping_status' => $lockedDelivery->shipping_status === ShippingStatus::PENDING
                    ? ShippingStatus::SHIPPED
                    : $lockedDelivery->shipping_status,
            ]);
        });

        return redirect()->route('sales.delivery-orders.show', $deliveryOrder)
            ->with('success', __('delivery_orders.messages.posted'));
    }

    /**
     * Create a pending Sales Invoice from the quantities on a posted Delivery Order.
     *
     * @param  DeliveryOrder  $deliveryOrder  The Delivery Order resource instance.
     * @return RedirectResponse Redirects to the new Sales Invoice.
     *
     * @throws Throwable
     */
    public function createSalesInvoice(DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::ADD);

        $documentNumber = DB::transaction(function () use ($deliveryOrder): string {
            $lockedDelivery = DeliveryOrder::query()->with(['salesOrder', 'lines.salesOrderLine'])
                ->whereKey($deliveryOrder->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedDelivery->status !== DeliveryOrderStatus::POSTED, 409);
            abort_if(SalesInvoice::query()->where('reference_number', $lockedDelivery->document_number)->exists(), 409);
            $salesOrder = $lockedDelivery->salesOrder;
            abort_if($salesOrder === null, 422);
            $documentNumber = $this->generateDocumentNumber(LedgerSubType::SALES_INVOICE);
            $now = now();
            // A pending invoice is a document, not ledger rows. The invoice posts
            // to the ledgers on approval through the ledger services, which
            // resolve accounts from the chart configuration.
            $invoice = SalesInvoice::query()->create([
                'id' => (string) Str::uuid7(),
                'company_id' => $salesOrder->company_id,
                'contact_id' => $salesOrder->contact_id,
                'document_number' => $documentNumber,
                'sales_type' => SalesType::PRODUCT,
                'reference_number' => $lockedDelivery->document_number,
                'sales_order_id' => $salesOrder->id,
                'company_name' => $salesOrder->company_name,
                'contact_name' => $salesOrder->contact?->name,
                'date' => $lockedDelivery->getRawOriginal('delivery_date'),
                'due_date' => null,
                'status' => LedgerStatus::PENDING,
                'attachment_path' => null,
                'notes' => $lockedDelivery->notes,
                'subtotal' => $lockedDelivery->lines->reduce(
                    fn (string $total, DeliveryOrderLine $line): string => bcadd($total, bcmul($line->quantity, $line->unit_price, 2), 2),
                    '0.00'
                ),
                'total_amount' => $lockedDelivery->lines->reduce(
                    fn (string $total, DeliveryOrderLine $line): string => bcadd($total, bcmul($line->quantity, $line->unit_price, 2), 2),
                    '0.00'
                ),
            ]);

            $lineRows = $lockedDelivery->lines->map(function (DeliveryOrderLine $line) use ($invoice, $now): array {
                $amount = bcmul($line->quantity, $line->unit_price, 2);

                return [
                    'id' => (string) Str::uuid7(),
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product_name,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'subtotal_amount' => $amount,
                    'discount_value' => '0.0000',
                    'discount_rate' => '0.0000',
                    'discount_amount' => '0.00',
                    'tax_rate' => '0.0000',
                    'tax_amount' => '0.00',
                    'line_total_amount' => $amount,
                    'total_amount' => $amount,
                    'warranty_months' => $line->salesOrderLine?->warranty_months,
                    // This delivery order already deducted the stock, so the
                    // invoice it generates must not deduct a second time.
                    'deduct_inventory' => false,
                    'description' => $line->description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            SalesInvoiceLine::query()->insert($lineRows);

            return $documentNumber;
        });

        return redirect()->route('sales.invoices.show', $documentNumber)
            ->with('success', FlashMessage::created(FileResources::SALES_INVOICES));
    }

    /**
     * Determine whether a posted sales invoice already owns the stock reduction.
     *
     * A direct product invoice carries the deduct_inventory flag and reduces
     * stock when it posts. This delivery order then records the shipment only,
     * so the same units are never deducted twice.
     *
     * @param  SalesOrder  $salesOrder  The sales order the delivery belongs to.
     * @return bool True when the linked sales invoice deducts inventory itself.
     */
    private function invoiceOwnsStockDeduction(SalesOrder $salesOrder): bool
    {
        $linkedInvoiceId = SalesInvoice::query()
            ->where('sales_order_id', $salesOrder->id)
            ->value('id');

        if ($linkedInvoiceId === null) {
            return false;
        }

        return SalesInvoiceLine::query()
            ->where('sales_invoice_id', $linkedInvoiceId)
            ->where('deduct_inventory', true)
            ->exists();
    }

    /**
     * Build the data required by the delivery-order form.
     *
     * @return array{stockLocations: EloquentCollection<int, StockLocation>, courierCompanies: EloquentCollection<int, Company>} The form data.
     */
    private function formData(): array
    {
        /** @var EloquentCollection<int, StockLocation> $stockLocations */
        $stockLocations = StockLocation::query()->where('is_active', true)
            ->orderBy('name')->get();
        /** @var EloquentCollection<int, Company> $courierCompanies */
        $courierCompanies = Company::query()
            ->where('is_courier', true)
            ->orderBy('name')
            ->get();

        return compact('courierCompanies', 'stockLocations');
    }

    /**
     * Resolve an existing company address or create the submitted delivery address.
     *
     * @param  string|null  $addressId  The selected company address identifier.
     * @param  SalesOrder  $salesOrder  The related sales order resource instance.
     * @param  string  $deliveryAddress  The submitted delivery address.
     * @param  string|null  $phone  The submitted delivery phone number.
     * @return CompanyAddress|Model
     */
    private function companyAddress(
        ?string $addressId,
        SalesOrder $salesOrder,
        string $deliveryAddress,
        ?string $phone
    ) {
        if (! filled($salesOrder->company_id)) {
            throw ValidationException::withMessages([
                'company_address_id' => __('delivery_orders.validation.company_required'),
            ]);
        }

        $submittedAddress = trim($deliveryAddress);
        $address = filled($addressId)
            ? CompanyAddress::query()->whereKey($addressId)->where('company_id', $salesOrder->company_id)->first()
            : null;

        if (filled($addressId) && $address === null) {
            throw ValidationException::withMessages([
                'company_address_id' => __('delivery_orders.validation.company_address_invalid'),
            ]);
        }

        if ($address !== null && $this->normalizedAddress($address->address) === $this->normalizedAddress($submittedAddress)) {
            return $address;
        }

        $existingAddress = CompanyAddress::query()
            ->where('company_id', $salesOrder->company_id)
            ->get()
            ->first(fn (CompanyAddress $candidate): bool => $this->normalizedAddress($candidate->address) === $this->normalizedAddress($submittedAddress));

        if ($existingAddress !== null) {
            return $existingAddress;
        }

        $companyHasAddresses = CompanyAddress::query()->where('company_id', $salesOrder->company_id)->exists();
        $firstLine = trim(Str::before(str_replace(["\r\n", "\r"], "\n", $submittedAddress), "\n"));

        return CompanyAddress::query()->create([
            'company_id' => $salesOrder->company_id,
            'name' => Str::limit($firstLine, 255, ''),
            'phone' => $phone,
            'address' => $submittedAddress,
            'is_default' => ! $companyHasAddresses,
        ]);
    }

    /**
     * Resolve and validate the selected courier company.
     *
     * @param  string|null  $companyId  The selected company identifier.
     * @return Company|null Returns the courier company, or null when none is selected.
     */
    private function courierCompany(?string $companyId): ?Company
    {
        if (! filled($companyId)) {
            return null;
        }

        $courier = Company::query()->whereKey($companyId)->where('is_courier', true)->first();
        if ($courier === null) {
            throw ValidationException::withMessages([
                'courier_company_id' => __('delivery_orders.validation.courier_invalid'),
            ]);
        }

        return $courier;
    }

    /**
     * Normalize an address for consistent comparison.
     *
     * @param  string  $address  The address value.
     * @return string Returns the normalized address.
     */
    private function normalizedAddress(string $address): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($address)) ?? trim($address));
    }
}
