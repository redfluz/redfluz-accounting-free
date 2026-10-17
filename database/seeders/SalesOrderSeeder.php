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

use App\Enums\SalesOrderStatus;
use App\Models\Crm\Company;
use App\Models\Inventory\Product;
use App\Models\Sales\Quotation;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderLine;
use Database\Seeders\Concerns\SeedsDocumentSequences;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds sales orders across the statuses the sales order grid can show.
 *
 * The sales_orders table had no seeder, so the grid was empty on a clean
 * database. Approving a sales order writes no stock movement and posts nothing
 * to the general ledger: stock only leaves when a delivery order is posted, and
 * the invoice posts the ledger entries. Seeding orders is therefore safe to do
 * alongside any inventory state.
 *
 * Two orders stay in the states that need nothing downstream: a draft being
 * edited, and a cancelled order kept for the audit trail. One is linked back to
 * a converted quotation so the quotation-to-order relationship has a real row
 * behind it.
 *
 * Delivery is deliberately left to GoodsReceiptSeeder's counterpart,
 * DeliveryOrderSeeder, which is where stock actually moves. This seeder leaves
 * delivered_quantity at zero on every order so that seeding a delivery order
 * later has a clean starting point.
 *
 * Documents are seeded in ascending number order and re-running is safe: each
 * order is found by its own number before a new id is minted. Nothing is ever
 * hard deleted.
 */
class SalesOrderSeeder extends Seeder
{
    use SeedsDocumentSequences;

    /**
     * The sales orders to seed, in ascending document order.
     *
     * @var list<array<string, mixed>>
     */
    private const ORDERS = [
        [
            'number' => 'so10001',
            'company' => 'Tradenet Financial Terminals',
            'product_sku' => 'ASU-VIVO-15',
            'quantity' => '8',
            'unit_price' => '2575.00',
            'order_date' => '2026-09-14',
            'expected_delivery_date' => '2026-09-28',
            'status' => SalesOrderStatus::APPROVED,
            'quotation_number' => 'qt10005',
            'customer_reference_number' => 'TN-PO-4471',
            'approved_at' => '2026-09-15 09:20:00',
            'notes' => 'Converted from quotation qt10005 and approved for despatch.',
        ],
        [
            'number' => 'so10002',
            'company' => 'Horizon Wealth Management',
            'product_sku' => 'PART-LCD-156-FHD',
            'quantity' => '25',
            'unit_price' => '310.00',
            'order_date' => '2026-09-16',
            'expected_delivery_date' => '2026-09-30',
            'status' => SalesOrderStatus::DRAFT,
            'quotation_number' => null,
            'customer_reference_number' => null,
            'approved_at' => null,
            'notes' => 'Draft order, quantities still being confirmed with the customer.',
        ],
        [
            'number' => 'so10003',
            'company' => 'Khoo & Sons Private Investment',
            'product_sku' => 'ACE-ASP-5',
            'quantity' => '3',
            'unit_price' => '2699.00',
            'order_date' => '2026-09-17',
            'expected_delivery_date' => '2026-10-01',
            'status' => SalesOrderStatus::CANCELLED,
            'quotation_number' => null,
            'customer_reference_number' => null,
            'approved_at' => null,
            'notes' => 'Cancelled: the customer sourced the laptops elsewhere.',
        ],
        [
            'number' => 'so10004',
            'company' => 'Apex Capital Advisory Ltd',
            'product_sku' => 'ASU-VIVO-15',
            'quantity' => '5',
            'unit_price' => '2599.00',
            'order_date' => '2026-09-18',
            'expected_delivery_date' => '2026-10-02',
            'status' => SalesOrderStatus::APPROVED,
            'quotation_number' => 'qt10003',
            'customer_reference_number' => 'APA-REF-88',
            'approved_at' => '2026-09-19 11:00:00',
            'notes' => 'Raised from the accepted quotation qt10003, awaiting delivery.',
        ],
    ];

    /**
     * Seed the sales orders.
     */
    public function run(): void
    {
        $this->seedSequence();

        foreach (self::ORDERS as $order) {
            $company = Company::query()
                ->where('name', $order['company'])
                ->where('is_debtor', true)
                ->first();

            $product = Product::query()->where('sku', $order['product_sku'])->first();

            if ($company === null || $product === null) {
                continue;
            }

            $this->seedOrder($order, $company, $product);
        }

        // Assert the sequence once the documents exist, so the first run already
        // leaves the counter above every number issued.
        $this->seedSequence();
    }

    /**
     * Seed one sales order with its single line.
     *
     * @param  array<string, mixed>  $order  The order definition.
     * @param  Company  $company  The debtor company.
     * @param  Product  $product  The ordered product.
     */
    private function seedOrder(array $order, Company $company, Product $product): void
    {
        $subtotal = bcmul((string) $order['quantity'], (string) $order['unit_price'], 2);
        $now = now();

        $existing = SalesOrder::query()
            ->where('document_number', $order['number'])
            ->first(['id', 'created_at']);

        $salesOrderId = $existing->id ?? (string) Str::uuid7();
        $quotationId = filled($order['quotation_number'] ?? null)
            ? Quotation::query()->where('document_number', $order['quotation_number'])->value('id')
            : null;

        SalesOrder::query()->updateOrInsert(
            ['id' => $salesOrderId],
            [
                'company_id' => $company->id,
                'contact_id' => null,
                'quotation_id' => $quotationId,
                'document_number' => $order['number'],
                'customer_reference_number' => $order['customer_reference_number'],
                'company_name' => $company->name,
                'contact_name' => null,
                'order_date' => $order['order_date'],
                'expected_delivery_date' => $order['expected_delivery_date'],
                'status' => $order['status']->value,
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'attachment_path' => null,
                'notes' => $order['notes'],
                'approved_by' => null,
                'approved_at' => $order['approved_at'],
                'deleted_at' => null,
                'created_at' => $existing->created_at ?? $now,
                'updated_at' => $now,
            ]
        );

        $existingLine = SalesOrderLine::query()
            ->where('sales_order_id', $salesOrderId)
            ->where('product_id', $product->id)
            ->first(['id', 'created_at']);

        SalesOrderLine::query()->updateOrInsert(
            ['id' => $existingLine->id ?? (string) Str::uuid7()],
            [
                'sales_order_id' => $salesOrderId,
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'description' => $product->name,
                'is_service' => false,
                'ordered_quantity' => (string) $order['quantity'],
                // Left at zero: stock moves when a delivery order is posted, so
                // the delivery seeder owns this column.
                'delivered_quantity' => '0',
                'unit_price' => (string) $order['unit_price'],
                'warranty_months' => 12,
                'subtotal_amount' => $subtotal,
                'discount_amount' => '0.00',
                'tax_amount' => '0.00',
                'line_total_amount' => $subtotal,
                'total_amount' => $subtotal,
                'deleted_at' => null,
                'created_at' => $existingLine->created_at ?? $now,
                'updated_at' => $now,
            ]
        );
    }

    /**
     * Seed the sales order number sequence without rewinding it.
     */
    private function seedSequence(): void
    {
        $this->seedDocumentSequence('sales_order', 'SO', 10001, 'sales_orders', 'document_number');
    }
}
