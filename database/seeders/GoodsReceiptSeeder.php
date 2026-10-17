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

use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Enums\PurchaseOrderType;
use App\Models\Inventory\Product;
use App\Models\Purchase\PurchaseOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Seed purchase orders whose receiving progress demonstrates every goods receipt
 * completion bucket.
 *
 * The goods receipt list derives completion from the linked purchase order, not
 * from the receipt, so a filter over that column is only meaningful when some
 * orders are fully received and some are not. Without this seeder every seeded
 * order sits at draft or approved and the Complete and Partial filters return
 * nothing.
 *
 * Two orders are created:
 *
 *  - PO-DEMO-10001 receives all of its ordered quantity, so the order reaches
 *    is_received = true and its receipt shows as Complete.
 *  - PO-DEMO-10002 receives part of its ordered quantity, so the order stays
 *    is_partial_received = true and its receipt shows as Partial.
 *
 * Receipts are written straight into product_ledgers, which is the same table the
 * receiving flow writes to. Stock is deliberately unchanged: the quantities
 * received here are subtracted from the matching DEMO-OPENING-STOCK rows by
 * ProductLedgerSeeder, so on-hand totals stay identical to a seed without this
 * file.
 */
class GoodsReceiptSeeder extends Seeder
{
    /**
     * The scenario definitions, keyed by purchase order number.
     *
     * ordered is the purchase order line quantity. received is what the goods
     * receipt actually booked, which is what decides the order status.
     *
     * @var array<string, array{supplier: string, is_received: bool, is_partial_received: bool, ordered: int, received: int, reference: string, received_date: string}>
     */
    private const array SCENARIOS = [
        'PO-DEMO-10001' => [
            'supplier' => 'Ingram Micro Malaysia Sdn Bhd',
            'is_received' => true,
            'is_partial_received' => false,
            'ordered' => 20,
            'received' => 20,
            'reference' => 'SUP-PO10001',
            'received_date' => '2026-09-02',
        ],
        'PO-DEMO-10002' => [
            'supplier' => 'VSTECS Berhad',
            'is_received' => false,
            'is_partial_received' => true,
            'ordered' => 24,
            'received' => 10,
            'reference' => 'SUP-PO10002',
            'received_date' => '2026-09-09',
        ],
    ];

    /**
     * SKU used for both scenarios.
     *
     * PART-USB-C-PORT carries opening stock well above what is received here, so
     * the offset cannot drive on-hand below zero.
     */
    private const string SKU = 'PART-USB-C-PORT';

    /**
     * Goods receipt document number per purchase order.
     *
     * @var array<string, string>
     */
    private const array RECEIPT_NUMBERS = [
        'PO-DEMO-10001' => 'GRN-DEMO-10001',
        'PO-DEMO-10002' => 'GRN-DEMO-10002',
    ];

    public function run(): void
    {
        $product = Product::query()
            ->where('sku', self::SKU)
            ->whereNull('deleted_at')
            ->first();

        if ($product === null) {
            throw new LogicException('Goods receipt seeding requires the '.self::SKU.' product.');
        }

        $location = DB::table('stock_locations')
            ->where('code', 'WH-001')
            ->whereNull('deleted_at')
            ->first(['id', 'name']);

        if ($location === null) {
            throw new LogicException('Goods receipt seeding requires the WH-001 stock location.');
        }

        $inventoryChart = DB::table('chart_settings')
            ->orderBy('created_at')
            ->value('inventory_chart_uuid');

        if ($inventoryChart === null) {
            throw new LogicException('Goods receipt seeding requires an inventory chart setting.');
        }

        $suppliers = DB::table('companies')
            ->whereIn('name', array_column(self::SCENARIOS, 'supplier'))
            ->pluck('id', 'name');

        if ($suppliers->count() !== count(self::SCENARIOS)) {
            throw new LogicException('Goods receipt seeding requires both demo suppliers.');
        }

        DB::transaction(function () use ($inventoryChart, $location, $product, $suppliers): void {
            foreach (self::SCENARIOS as $number => $scenario) {
                $this->seedScenario($scenario, $inventoryChart, $location, $number, $product, $suppliers);
            }
        });
    }

    /**
     * Seed one purchase order and the goods receipt that booked against it.
     *
     * @param  array{supplier: string, is_received: bool, is_partial_received: bool, ordered: int, received: int, reference: string, received_date: string}  $scenario
     * @param  string  $inventoryChart  The inventory chart id for the order line.
     * @param  object  $location  The receiving warehouse.
     * @param  string  $number  The purchase order number.
     * @param  Product  $product  The received product.
     * @param  Collection<string, string>  $suppliers  Supplier name to id.
     */
    private function seedScenario(
        array $scenario,
        string $inventoryChart,
        object $location,
        string $number,
        Product $product,
        Collection $suppliers
    ): void {
        $now = now();
        $supplierId = $suppliers->get($scenario['supplier']);
        $contactId = DB::table('contacts')->where('company_id', $supplierId)->value('id');
        $unitPrice = number_format((float) $product->base_price, 2, '.', '');
        $ordered = $scenario['ordered'];
        $lineTotal = bcmul((string) $ordered, $unitPrice, 2);

        $order = PurchaseOrder::query()->updateOrCreate(
            ['document_number' => $number],
            [
                'company_id' => $supplierId,
                'contact_id' => $contactId,

                'purchase_type' => PurchaseOrderType::PURCHASE->value,
                'supplier_reference_number' => $scenario['reference'],
                'company_name' => $scenario['supplier'],
                'contact_name' => null,
                'date' => now()->subDays(20)->toDateString(),
                'expected_date' => now()->subDays(6)->toDateString(),
                'credit_term_days' => 30,
                'due_date' => now()->subDays(20)->addDays(30)->toDateString(),
                'is_received' => $scenario['is_received'],
                'is_partial_received' => $scenario['is_partial_received'],
                'subtotal' => $lineTotal,
                'tax_amount' => '0.00',
                'total_amount' => $lineTotal,
                'notes' => 'Seeded to demonstrate goods receipt completion states.',
                'approved_at' => now()->subDays(19),
                'deleted_at' => null,
            ]
        );

        $order->lines()->forceDelete();
        $order->lines()->create([
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'description' => $product->description,
            'is_service' => false,
            'acquisition_type' => $product->default_acquisition_type->value,
            'debit_chart_id' => $inventoryChart,
            'quantity' => $ordered,
            'unit_price' => $unitPrice,
            'total_amount' => $lineTotal,
        ]);

        $receipt = self::RECEIPT_NUMBERS[$number];
        $received = $scenario['received'];

        DB::table('product_ledgers')->updateOrInsert(
            ['document_number' => $receipt, 'product_id' => $product->id],
            [
                'id' => (string) Str::uuid7(),
                'stock_location_id' => $location->id,
                'stock_location_name' => $location->name,
                'company_id' => null,
                'source_entry_id' => null,
                'cogs_ledger_id' => null,
                'inventory_ledger_id' => null,
                'variance_ledger_id' => null,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'company_name' => $scenario['supplier'],
                'reference_number' => $number,
                'source_type' => ProductMovementSource::GOODS_RECEIPT->value,
                'direction' => ProductMovementDirection::IN->value,
                'stock_bucket' => ProductStockBucket::SALEABLE->value,
                'date' => $scenario['received_date'],
                'quantity' => number_format((float) $received, 4, '.', ''),
                'unit_cost' => $unitPrice,
                'total_cost' => bcmul((string) $received, $unitPrice, 2),
                'status' => ProductMovementStatus::ACTIVE->value,
                'activated_at' => $scenario['received_date'].' 10:00:00',
                'notes' => $scenario['is_received']
                    ? 'Seeded receipt that fully received the order.'
                    : 'Seeded receipt that partially received the order.',
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
