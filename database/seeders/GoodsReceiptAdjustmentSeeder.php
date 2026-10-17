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
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds quantity corrections against the seeded goods received notes.
 *
 * A goods receipt adjustment is a product_ledger row that carries a
 * source_entry_id, which is what distinguishes a correction from the receipt
 * line it corrects. Nothing else seeded one, so on a clean database all three
 * adjustment lists were empty: the pending grid, the history grid, and the
 * cancelled grid. This seeder gives each of them a row to render.
 *
 * The three statuses are the whole point, because the grids are the same query
 * filtered by status:
 *
 *   pending   awaiting approval, counts for nothing
 *   active    approved, counted in stock and in received quantity
 *   cancelled approved and then withdrawn, kept for the audit trail
 *
 * Scenarios are placed deliberately. Received quantity only counts active rows,
 * so an active correction reduces it. PO-DEMO-10001 received its order in full
 * (20 of 20), so an active correction against it would demote it to
 * partially_received and contradict the seeded state; it therefore carries
 * pending corrections only. PO-DEMO-10002 received 10 of 24, which has enough
 * headroom to absorb an approved correction and stay partially_received, so it
 * carries the approved and the cancelled scenario.
 *
 * Re-running is safe. Every row is written with a stable id derived from its
 * scenario key, so a repeat seed updates in place instead of piling up copies,
 * and nothing is ever hard deleted.
 */
class GoodsReceiptAdjustmentSeeder extends Seeder
{
    /**
     * The corrections to seed, in ascending document order.
     *
     * Each scenario names the goods receipt it corrects and the state the
     * correction should end up in. quantity is an outbound movement: it takes
     * stock back for goods that were received in error, arrived damaged, or
     * were over-supplied.
     *
     * @var list<array<string, mixed>>
     */
    private const SCENARIOS = [
        [
            'key' => 'grn-demo-10001-damage',
            'goods_receipt' => 'GRN-DEMO-10001',
            'order' => 'PO-DEMO-10001',
            'quantity' => 2,
            'date' => '2026-09-21',
            'status' => ProductMovementStatus::PENDING,
            'activated_at' => null,
            'notes' => 'Damaged on arrival, awaiting approval before the units are written back out of stock.',
        ],
        [
            'key' => 'grn-demo-10002-short',
            'goods_receipt' => 'GRN-DEMO-10002',
            'order' => 'PO-DEMO-10002',
            'quantity' => 1,
            'date' => '2026-09-22',
            'status' => ProductMovementStatus::PENDING,
            'activated_at' => null,
            'notes' => 'One case short-supplied, correction awaiting approval.',
        ],
        [
            'key' => 'grn-demo-10002-approved',
            'goods_receipt' => 'GRN-DEMO-10002',
            'order' => 'PO-DEMO-10002',
            'quantity' => 2,
            'date' => '2026-09-23',
            'status' => ProductMovementStatus::ACTIVE,
            'activated_at' => '2026-09-23 09:30:00',
            'notes' => 'Approved correction: two units returned to the supplier as over-supplied.',
        ],
        [
            'key' => 'grn-demo-10002-cancelled',
            'goods_receipt' => 'GRN-DEMO-10002',
            'order' => 'PO-DEMO-10002',
            'quantity' => 1,
            'date' => '2026-09-24',
            'status' => ProductMovementStatus::CANCELLED,
            'activated_at' => '2026-09-24 10:15:00',
            'notes' => 'Cancelled correction: the count was correct on recount, so the write-back was withdrawn.',
        ],
    ];

    /**
     * Seed the corrections.
     */
    public function run(): void
    {
        $touchedOrders = [];

        foreach (self::SCENARIOS as $scenario) {
            $order = PurchaseOrder::query()
                ->where('document_number', $scenario['order'])
                ->first();

            if ($order === null) {
                continue;
            }

            // Corrections attach to a real receipt line, so pick one of the
            // active inbound lines the seeded goods receipt already has.
            $receiptLine = ProductLedger::query()
                ->where('document_number', $scenario['goods_receipt'])
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT->value)
                ->whereNull('source_entry_id')
                ->where('direction', ProductMovementDirection::IN->value)
                ->where('status', ProductMovementStatus::ACTIVE->value)
                ->orderBy('created_at')
                ->first();

            if ($receiptLine === null) {
                continue;
            }

            $this->seedCorrection($scenario, $order, $receiptLine);
            $touchedOrders[$scenario['order']] = true;
        }

        foreach (array_keys($touchedOrders) as $purchaseOrderNumber) {
            $this->recomputePurchaseOrderStatus($purchaseOrderNumber);
        }
    }

    /**
     * Write one correction.
     *
     * @param  array<string, mixed>  $scenario  The scenario definition.
     * @param  PurchaseOrder  $order  The purchase order the receipt belongs to.
     * @param  ProductLedger  $receiptLine  The receipt line being corrected.
     */
    private function seedCorrection(array $scenario, PurchaseOrder $order, ProductLedger $receiptLine): void
    {
        $product = $this->productForLine($receiptLine, $scenario);
        $quantity = (string) $scenario['quantity'];
        $unitCost = (string) $receiptLine->unit_cost;

        $row = [
            'product_id' => $receiptLine->product_id,
            'stock_location_id' => $receiptLine->stock_location_id,
            'stock_location_name' => $receiptLine->stock_location_name,
            'company_id' => $order->company_id,
            'source_entry_id' => $receiptLine->id,
            'cogs_ledger_id' => null,
            'inventory_ledger_id' => null,
            'variance_ledger_id' => null,
            'product_sku' => $product?->sku ?? $receiptLine->product_sku,
            'product_name' => $product?->name ?? $receiptLine->product_name,
            'company_name' => $order->company_name,
            'document_number' => $scenario['goods_receipt'],
            'reference_number' => $scenario['order'],
            'source_type' => ProductMovementSource::GOODS_RECEIPT->value,
            'direction' => ProductMovementDirection::OUT->value,
            'stock_bucket' => $receiptLine->stock_bucket,
            'date' => $scenario['date'],
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => bcmul($quantity, $unitCost, 2),
            'status' => $scenario['status']->value,
            'activated_at' => $scenario['activated_at'],
            'notes' => $scenario['notes'],
            'deleted_at' => null,
            'updated_at' => now(),
        ];

        // The scenario's notes are its identity here: locating the row by them
        // is what keeps a repeat seed idempotent, so the id is only minted on
        // the first run and is then reused. Generation follows the project-wide
        // uuid7 convention rather than a derived value.
        $existing = DB::table('product_ledgers')
            ->where('notes', $scenario['notes'])
            ->first(['id', 'created_at']);

        DB::table('product_ledgers')->updateOrInsert(
            ['id' => $existing->id ?? (string) Str::uuid7()],
            $row + ['created_at' => $existing->created_at ?? now()]
        );
    }

    /**
     * The product a correction line refers to.
     *
     * @param  ProductLedger  $receiptLine  The receipt line being corrected.
     * @param  array<string, mixed>  $scenario  The scenario definition.
     * @return Product|null The product, or null when the receipt line has none.
     */
    private function productForLine(ProductLedger $receiptLine, array $scenario): ?Product
    {
        if ($receiptLine->product_id === null) {
            return null;
        }

        return Product::query()->find($receiptLine->product_id);
    }

    /**
     * Recompute a purchase order's status the way the controller does.
     *
     * Mirrors GoodsReceiptAdjustmentController::recomputePurchaseOrderStatus so
     * the seeded state and the state the app would calculate agree.
     *
     * @param  string  $purchaseOrderNumber  The purchase order number.
     */
    private function recomputePurchaseOrderStatus(string $purchaseOrderNumber): void
    {
        $purchaseOrder = PurchaseOrder::query()
            ->where('document_number', $purchaseOrderNumber)
            ->first();

        if ($purchaseOrder === null) {
            return;
        }

        $orderedQuantity = $purchaseOrder->lines->reduce(
            fn (string $total, PurchaseOrderLine $line): string => bcadd($total, (string) $line->quantity, 4),
            '0.0000'
        );

        $receivedQuantity = $this->receivedQuantityForOrder($purchaseOrderNumber);

        $isFullyReceived = bccomp($receivedQuantity, $orderedQuantity, 4) >= 0;
        $isPartiallyReceived = ! $isFullyReceived && bccomp($receivedQuantity, '0', 4) > 0;

        $purchaseOrder->update([
            'is_received' => $isFullyReceived,
            'is_partial_received' => $isPartiallyReceived,
        ]);
    }

    /**
     * The received quantity for an order, counting active movements only.
     *
     * @param  string  $purchaseOrderNumber  The purchase order number.
     * @return string The normalised received quantity.
     */
    private function receivedQuantityForOrder(string $purchaseOrderNumber): string
    {
        $in = (float) ProductLedger::query()
            ->where('reference_number', $purchaseOrderNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT->value)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->where('direction', ProductMovementDirection::IN->value)
            ->sum('quantity');

        $out = (float) ProductLedger::query()
            ->where('reference_number', $purchaseOrderNumber)
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT->value)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->where('direction', ProductMovementDirection::OUT->value)
            ->sum('quantity');

        $difference = $in - $out;

        // Normalise the float away so the comparison below is exact.
        return bcadd(number_format($difference, 4, '.', ''), '0', 4);
    }
}
