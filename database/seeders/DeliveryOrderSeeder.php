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

/**
 * @noinspection ALL
 *
 * @phpstan-ignore-file
 */

namespace Database\Seeders;

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Enums\SalesOrderStatus;
use App\Enums\ShippingStatus;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Finance\Ledger;
use App\Models\Inventory\ProductLedger;
use App\Models\Inventory\StockLocation;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\DeliveryOrderLine;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderLine;
use Database\Seeders\Concerns\SeedsDocumentSequences;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds delivery orders across the statuses the delivery order grid can show.
 *
 * The delivery_orders table had no seeder, so the grid was empty on a clean
 * database. Unlike quotations and sales orders, a posted delivery order is not
 * inert: it draws stock down and posts the matching cost of sale, so this seeder
 * has to reproduce both or the trial balance would disagree with the inventory
 * on hand.
 *
 * It therefore mirrors DeliveryOrderController::post() closely:
 *
 *   product_ledgers  one outbound movement per line, marked active
 *   ledgers          a debit to cost of sales and a credit to inventory, so the
 *                    stock that left is accounted for
 *   order lines      delivered_quantity is advanced by the delivered amount
 *   sales order      becomes partially delivered, or delivered once fully covered
 *
 * Quantities are kept small against the seeded stock on hand so nothing can go
 * negative, and no delivery is seeded against a cancelled sales order. Only the
 * posted delivery moves stock; the draft and the cancelled one leave inventory
 * untouched, which is what the application does for them.
 *
 * Documents are seeded in ascending number order and re-running is safe. Nothing
 * is ever hard deleted: rows are located by their own document number and
 * updated in place.
 */
class DeliveryOrderSeeder extends Seeder
{
    use SeedsDocumentSequences;

    /**
     * The delivery orders to seed, in ascending document order.
     *
     * @var list<array<string, mixed>>
     */
    private const DELIVERIES = [
        [
            'number' => 'do10001',
            'sales_order' => 'so10001',
            'product_sku' => 'ASU-VIVO-15',
            'quantity' => '3',
            'delivery_date' => '2026-09-22',
            'shipping_date' => '2026-09-22',
            'status' => 'posted',
            'shipping_status' => ShippingStatus::SHIPPED,
            'courier_company' => 'DHL Express',
            'tracking_number' => 'DHL-MY-88213470',
            'recipient_name' => 'Tradenet Financial Terminals',
            'recipient_phone' => '03-7788 1200',
            'delivery_address' => 'Level 12, Menara Cyberjaya, Cyberjaya, Selangor',
            'posted_at' => '2026-09-22 14:30:00',
            'notes' => 'Partial despatch against so10001; the balance follows next week.',
        ],
        [
            'number' => 'do10002',
            'sales_order' => 'so10002',
            'product_sku' => 'PART-LCD-156-FHD',
            'quantity' => '25',
            'delivery_date' => '2026-09-30',
            'shipping_date' => null,
            'status' => 'draft',
            'shipping_status' => ShippingStatus::PENDING,
            'courier_company' => 'Pos Lalam',
            'tracking_number' => null,
            'recipient_name' => 'Horizon Wealth Management',
            'recipient_phone' => '03-2287 4400',
            'delivery_address' => '18, Jalan Bukit Bintang, Kuala Lumpur',
            'posted_at' => null,
            'notes' => 'Draft delivery, quantities still being confirmed with the customer.',
        ],
        [
            'number' => 'do10003',
            'sales_order' => 'so10003',
            'product_sku' => 'ACE-ASP-5',
            'quantity' => '3',
            'delivery_date' => '2026-10-01',
            'shipping_date' => '2026-09-28',
            'status' => 'cancelled',
            'shipping_status' => ShippingStatus::PENDING,
            'courier_company' => 'Pos Lalam',
            'tracking_number' => null,
            'recipient_name' => 'Khoo & Sons Private Investment',
            'recipient_phone' => '03-3165 2200',
            'delivery_address' => 'Unit 5, Jalan Kiara, Kuala Lumpur',
            'posted_at' => null,
            'notes' => 'Cancelled with the sales order: nothing was despatched, so no stock moved.',
        ],
    ];

    /**
     * Seed the delivery orders.
     */
    public function run(): void
    {
        $this->seedSequence();

        $chartSetting = ChartSetting::query()->first();
        $cogsChart = Chart::query()->whereKey($chartSetting?->cogs_chart_uuid)->first();
        $inventoryChart = Chart::query()->whereKey($chartSetting?->inventory_chart_uuid)->first();
        $stockLocation = StockLocation::query()->first();

        if ($cogsChart === null || $inventoryChart === null || $stockLocation === null) {
            return;
        }

        foreach (self::DELIVERIES as $delivery) {
            $salesOrder = SalesOrder::query()
                ->where('document_number', $delivery['sales_order'])
                ->first();

            if ($salesOrder === null) {
                continue;
            }

            $orderLine = SalesOrderLine::query()
                ->where('sales_order_id', $salesOrder->id)
                ->where('product_sku', $delivery['product_sku'])
                ->first();

            if ($orderLine === null) {
                continue;
            }

            $this->seedDelivery($delivery, $salesOrder, $orderLine, $stockLocation);

            if ($delivery['status'] === 'posted') {
                $this->postDelivery($delivery, $salesOrder, $orderLine, $stockLocation, $cogsChart, $inventoryChart);
            }
        }

        // Assert the sequence once the documents exist.
        $this->seedSequence();
    }

    /**
     * Write the delivery order and its line.
     *
     * @param  array<string, mixed>  $delivery  The delivery definition.
     * @param  SalesOrder  $salesOrder  The order being delivered.
     * @param  SalesOrderLine  $orderLine  The order line being delivered.
     * @param  StockLocation  $stockLocation  The stock location to despatch from.
     */
    private function seedDelivery(
        array $delivery,
        SalesOrder $salesOrder,
        SalesOrderLine $orderLine,
        StockLocation $stockLocation
    ): void {
        $quantity = (string) $delivery['quantity'];
        $unitPrice = (string) $orderLine->unit_price;
        $total = bcmul($quantity, $unitPrice, 2);
        $now = now();

        $existing = DeliveryOrder::query()
            ->where('document_number', $delivery['number'])
            ->first(['id', 'created_at']);

        $deliveryOrderId = $existing->id ?? (string) Str::uuid7();

        DeliveryOrder::query()->updateOrInsert(
            ['id' => $deliveryOrderId],
            [
                'sales_order_id' => $salesOrder->id,
                'stock_location_id' => $stockLocation->id,
                'stock_location_name' => $stockLocation->name,
                'document_number' => $delivery['number'],
                'delivery_date' => $delivery['delivery_date'],
                'recipient_name' => $delivery['recipient_name'],
                'recipient_phone' => $delivery['recipient_phone'],
                'delivery_address' => $delivery['delivery_address'],
                'courier_company_id' => null,
                'courier' => $delivery['courier_company'],
                'tracking_number' => $delivery['tracking_number'],
                'shipping_date' => $delivery['shipping_date'],
                'shipping_status' => $delivery['shipping_status']->value,
                'status' => $delivery['status'],
                'attachment_path' => null,
                'notes' => $delivery['notes'],
                'posted_by' => null,
                'posted_name' => null,
                'posted_at' => $delivery['posted_at'],
                'company_address_id' => null,
                'deleted_at' => null,
                'created_at' => $existing->created_at ?? $now,
                'updated_at' => $now,
            ]
        );

        $existingLine = DeliveryOrderLine::query()
            ->where('delivery_order_id', $deliveryOrderId)
            ->where('sales_order_line_id', $orderLine->id)
            ->first(['id', 'created_at']);

        DeliveryOrderLine::query()->updateOrInsert(
            ['id' => $existingLine->id ?? (string) Str::uuid7()],
            [
                'delivery_order_id' => $deliveryOrderId,
                'sales_order_line_id' => $orderLine->id,
                'product_id' => $orderLine->product_id,
                'product_sku' => $orderLine->product_sku,
                'product_name' => $orderLine->product_name,
                'description' => $orderLine->description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => '0.00',
                'total_amount' => $total,
                'deleted_at' => null,
                'created_at' => $existingLine->created_at ?? $now,
                'updated_at' => $now,
            ]
        );
    }

    /**
     * Reproduce the effects of posting a delivery order.
     *
     * Mirrors DeliveryOrderController::post(): the outbound stock movement, the
     * cost of sale entry, the advanced delivered quantity, and the resulting
     * sales order status.
     *
     * @param  array<string, mixed>  $delivery  The delivery definition.
     * @param  SalesOrder  $salesOrder  The order being delivered.
     * @param  SalesOrderLine  $orderLine  The order line being delivered.
     * @param  StockLocation  $stockLocation  The stock location despatched from.
     * @param  Chart  $cogsChart  The cost of sales chart.
     * @param  Chart  $inventoryChart  The inventory chart.
     */
    private function postDelivery(
        array $delivery,
        SalesOrder $salesOrder,
        SalesOrderLine $orderLine,
        StockLocation $stockLocation,
        Chart $cogsChart,
        Chart $inventoryChart
    ): void {
        $quantity = (string) $delivery['quantity'];
        $unitCost = (string) $this->unitCostFor($orderLine->product_id, $stockLocation->id);
        $totalCost = bcmul($quantity, $unitCost, 2);
        $now = now();

        $existingMovement = ProductLedger::query()
            ->where('document_number', $delivery['number'])
            ->where('source_type', ProductMovementSource::DELIVERY_ORDER->value)
            ->first(['id', 'cogs_ledger_id', 'inventory_ledger_id', 'created_at']);

        $movementId = $existingMovement->id ?? (string) Str::uuid7();
        $cogsLedgerId = $existingMovement->cogs_ledger_id ?? (string) Str::uuid7();
        $inventoryLedgerId = $existingMovement->inventory_ledger_id ?? (string) Str::uuid7();

        ProductLedger::query()->updateOrInsert(
            ['id' => $movementId],
            [
                'product_id' => $orderLine->product_id,
                'stock_location_id' => $stockLocation->id,
                'stock_location_name' => $stockLocation->name,
                'company_id' => $salesOrder->company_id,
                'source_entry_id' => $orderLine->id,
                'cogs_ledger_id' => $cogsLedgerId,
                'inventory_ledger_id' => $inventoryLedgerId,
                'variance_ledger_id' => null,
                'product_sku' => $orderLine->product_sku,
                'product_name' => $orderLine->product_name,
                'company_name' => $salesOrder->company_name,
                'document_number' => $delivery['number'],
                'reference_number' => $salesOrder->document_number,
                'source_type' => ProductMovementSource::DELIVERY_ORDER->value,
                'direction' => ProductMovementDirection::OUT->value,
                'stock_bucket' => ProductStockBucket::SALEABLE->value,
                'date' => $delivery['delivery_date'],
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'status' => ProductMovementStatus::ACTIVE->value,
                'activated_at' => $delivery['posted_at'],
                'notes' => $orderLine->description,
                'deleted_at' => null,
                'created_at' => $existingMovement->created_at ?? $now,
                'updated_at' => $now,
            ]
        );

        $common = [
            'source_id' => $movementId,
            'source_type' => null,
            'company_id' => $salesOrder->company_id,
            'contact_id' => null,
            'company_name' => $salesOrder->company_name,
            'document_number' => $delivery['number'],
            'reference_number' => $salesOrder->document_number,
            'type' => LedgerType::JOURNAL->value,
            'subtype' => LedgerSubType::JOURNAL->value,
            'date' => $delivery['delivery_date'],
            'document_date' => $delivery['delivery_date'],
            'total_amount' => $totalCost,
            'status' => LedgerStatus::POSTED->value,
            'description' => $orderLine->description,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        foreach ([
            [$cogsLedgerId, $cogsChart, true],
            [$inventoryLedgerId, $inventoryChart, false],
        ] as [$ledgerId, $chart, $isDebit]) {
            $existingLedger = Ledger::query()->whereKey($ledgerId)->first(['id', 'created_at']);

            Ledger::query()->updateOrInsert(
                ['id' => $existingLedger->id ?? $ledgerId],
                $common + [
                    'debit_id' => $isDebit ? $chart->id : null,
                    'credit_id' => $isDebit ? null : $chart->id,
                    'debit_acc' => $isDebit ? $chart->code : null,
                    'credit_acc' => $isDebit ? null : $chart->code,
                    'debit_acc_desc' => $isDebit ? $chart->name : null,
                    'credit_acc_desc' => $isDebit ? null : $chart->name,
                    'debit_amount' => $isDebit ? $totalCost : '0.00',
                    'credit_amount' => $isDebit ? '0.00' : $totalCost,
                    'created_at' => $existingLedger->created_at ?? $now,
                ]
            );
        }

        // Advance the delivered quantity, then the order status that follows it.
        $delivered = bcadd((string) $orderLine->delivered_quantity, $quantity, 4);
        $ordered = (string) $orderLine->ordered_quantity;
        $fullyCovered = bccomp($delivered, $ordered, 4) >= 0;

        $orderLine->update(['delivered_quantity' => $delivered, 'updated_at' => $now]);

        $salesOrder->update([
            'status' => $fullyCovered
                ? SalesOrderStatus::DELIVERED->value
                : SalesOrderStatus::PARTIALLY_DELIVERED->value,
            'updated_at' => $now,
        ]);
    }

    /**
     * The average cost of the stock on hand for a product.
     *
     * The controller takes the cost from the product ledger; seeding uses the
     * average of the active inbound movements, which is the same figure when
     * there is a single receipt and a reasonable one when there are several.
     *
     * @param  string  $productId  The product.
     * @param  string  $stockLocationId  The stock location.
     * @return string The average unit cost.
     */
    private function unitCostFor(string $productId, string $stockLocationId): string
    {
        $cost = ProductLedger::query()
            ->where('product_id', $productId)
            ->where('stock_location_id', $stockLocationId)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT->value)
            ->where('direction', ProductMovementDirection::IN->value)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->avg('unit_cost');

        return $cost === null ? '0.00' : bcadd((string) $cost, '0', 2);
    }

    /**
     * Seed the delivery order number sequence without rewinding it.
     */
    private function seedSequence(): void
    {
        $this->seedDocumentSequence('delivery_order', 'DO', 10001, 'delivery_orders', 'document_number');
    }
}
