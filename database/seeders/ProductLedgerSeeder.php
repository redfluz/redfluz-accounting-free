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
use Database\Seeders\Concerns\SeedsDocumentSequences;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use stdClass;
use Throwable;

/**
 * Seed stock for every physical product plus posted and pending movement examples.
 */
class ProductLedgerSeeder extends Seeder
{
    use SeedsDocumentSequences;

    /** @var array<string, numeric-string> Products intentionally below reorder level for dashboard demos. */
    private const array LOW_STOCK_QUANTITIES = [
        'HP-PAV-15' => '1.0000',
        'LEN-E14-G5' => '1.0000',
        'LOG-MX-M3S' => '1.0000',
        'KIN-NV2-1TB' => '1.0000',
        'APL-IP15' => '1.0000',
    ];

    /**
     * Stock booked by GoodsReceiptSeeder, subtracted from the opening rows.
     *
     * GoodsReceiptSeeder writes real inbound movements so the Complete and Partial
     * receipt filters have data. Those rows add to on-hand stock, so this offset
     * removes the same quantity from the opening row for the same product. Total
     * stock therefore matches a seed without GoodsReceiptSeeder, which keeps
     * inventory valuation and the low stock dashboard figures stable.
     *
     * @var array<string, numeric-string>
     */
    private const array GOODS_RECEIPT_OFFSETS = [
        'PART-USB-C-PORT' => '30.0000',
    ];

    /**
     * Seed the product movement flow after ledger documents exist.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        $this->seedGoodsReceiptSequence();

        $product = DB::table('products')
            ->where('sku', 'HP-PAV-15')
            ->whereNull('deleted_at')
            ->first(['id', 'sku', 'name', 'base_price']);

        if ($product === null) {
            throw new LogicException('Product ledger seeding requires one physical product.');
        }
        $stockLocation = DB::table('stock_locations')->where('code', 'WH-001')->first(['id', 'name']);
        if ($stockLocation === null) {
            throw new LogicException('Product ledger seeding requires the WH-001 stock location.');
        }

        $postedInvoice = $this->invoiceLine('IV-DEMO-0001');
        $pendingInvoice = $this->invoiceLine('IV-DEMO-0002');

        if ($postedInvoice === null || $pendingInvoice === null) {
            throw new LogicException('Product ledger seeding requires IV-DEMO-0001 and IV-DEMO-0002.');
        }

        $chartSetting = DB::table('chart_settings')->orderBy('created_at')->first([
            'cogs_chart_uuid',
            'inventory_chart_uuid',
        ]);
        if ($chartSetting === null) {
            throw new LogicException('Chart settings are required for product ledger seeding.');
        }

        $cogsChart = $chartSetting->cogs_chart_uuid
            ? DB::table('charts')->where('id', $chartSetting->cogs_chart_uuid)->first(['id', 'code', 'name'])
            : null;
        $inventoryChart = $chartSetting->inventory_chart_uuid
            ? DB::table('charts')->where('id', $chartSetting->inventory_chart_uuid)->first(['id', 'code', 'name'])
            : null;

        if ($cogsChart === null || $inventoryChart === null) {
            throw new LogicException('COGS and inventory chart settings are required for product ledger seeding.');
        }

        DB::transaction(function () use (
            $cogsChart,
            $inventoryChart,
            $pendingInvoice,
            $postedInvoice,
            $product,
            $stockLocation
        ): void {
            $this->seedMovement([
                'document_number' => 'GRN-DEMO-0001',
                'reference_number' => 'PI-DEMO-0001',
                'source_entry_id' => null,
                'source_type' => ProductMovementSource::GOODS_RECEIPT,
                'direction' => ProductMovementDirection::IN,
                'date' => '2026-08-09',
                'quantity' => '25.0000',
                'unit_cost' => (string) $product->base_price,
                'status' => ProductMovementStatus::ACTIVE,
                'activated_at' => '2026-08-09 10:00:00',
                'notes' => 'Opening demo receipt linked to PI-DEMO-0001.',
            ], $product, $postedInvoice, false, $cogsChart, $inventoryChart, $stockLocation);

            $this->seedMovement([
                'document_number' => 'IV-DEMO-0001',
                'reference_number' => 'IV-DEMO-0001',
                'source_entry_id' => $postedInvoice->id,
                'source_type' => ProductMovementSource::SALES_INVOICE,
                'direction' => ProductMovementDirection::OUT,
                'date' => $postedInvoice->date,
                'quantity' => (string) $postedInvoice->quantity,
                'unit_cost' => (string) $product->base_price,
                'status' => ProductMovementStatus::ACTIVE,
                'activated_at' => '2026-08-01 10:00:00',
                'notes' => 'Posted physical-product sales invoice movement.',
            ], $product, $postedInvoice, true, $cogsChart, $inventoryChart, $stockLocation);

            $this->seedMovement([
                'document_number' => 'IV-DEMO-0002',
                'reference_number' => 'IV-DEMO-0002',
                'source_entry_id' => $pendingInvoice->id,
                'source_type' => ProductMovementSource::SALES_INVOICE,
                'direction' => ProductMovementDirection::OUT,
                'date' => $pendingInvoice->date,
                'quantity' => (string) $pendingInvoice->quantity,
                'unit_cost' => (string) $product->base_price,
                'status' => ProductMovementStatus::PENDING,
                'activated_at' => null,
                'notes' => 'Pending invoice movement excluded from active stock.',
            ], $product, $pendingInvoice, false, $cogsChart, $inventoryChart, $stockLocation);
        });

        $this->seedDemoProductStock();
    }

    /**
     * Give every active physical product deterministic demo stock at the default warehouse.
     *
     * One tagged opening row is maintained per product, so repeated seeding
     * refreshes the same row instead of increasing stock indefinitely.
     */
    private function seedDemoProductStock(): void
    {
        $location = DB::table('stock_locations')
            ->where('code', 'WH-001')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first(['id', 'code', 'name']);

        if ($location === null) {
            throw new LogicException('Demo product stock requires the WH-001 warehouse.');
        }

        $products = DB::table('products')
            ->where('is_product', true)
            ->where('is_service', false)
            ->whereNull('deleted_at')
            ->orderBy('sku')
            ->get(['id', 'sku', 'name', 'base_price', 'selling_price']);

        $existingRows = DB::table('product_ledgers')
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT->value)
            ->where('reference_number', 'DEMO-OPENING-STOCK')
            ->get(['id', 'product_id', 'stock_location_id', 'created_at'])
            ->keyBy(fn (object $movement): string => $movement->product_id.'|'.$movement->stock_location_id);

        $now = now();
        $rows = $products->map(function (object $product) use ($existingRows, $location, $now): array {
            $randomSeed = hexdec(substr(hash('sha256', $product->sku.'|'.$location->code), 0, 8));
            $quantity = self::LOW_STOCK_QUANTITIES[$product->sku]
                ?? number_format(10 + ($randomSeed % 31), 4, '.', '');
            // Keep on-hand stock identical to a seed without GoodsReceiptSeeder by
            // removing the quantity that seeder books as an inbound receipt.
            $offset = self::GOODS_RECEIPT_OFFSETS[$product->sku] ?? '0.0000';
            $quantity = number_format(
                max((float) $quantity - (float) $offset, 0),
                4,
                '.',
                ''
            );
            $unitCost = (float) $product->base_price > 0
                ? number_format((float) $product->base_price, 2, '.', '')
                : number_format((float) $product->selling_price * 0.80, 2, '.', '');
            $key = $product->id.'|'.$location->id;
            $existing = $existingRows->get($key);

            return [
                'id' => $existing->id ?? (string) Str::uuid7(),
                'product_id' => $product->id,
                'stock_location_id' => $location->id,
                'stock_location_name' => $location->name,
                'company_id' => null,
                'source_entry_id' => null,
                'cogs_ledger_id' => null,
                'inventory_ledger_id' => null,
                'variance_ledger_id' => null,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'company_name' => null,
                'document_number' => 'DEMO-GRN-'.$location->code.'-'.strtoupper(substr(hash('sha256', $product->sku), 0, 12)),
                'reference_number' => 'DEMO-OPENING-STOCK',
                'source_type' => ProductMovementSource::GOODS_RECEIPT->value,
                'direction' => ProductMovementDirection::IN->value,
                'stock_bucket' => ProductStockBucket::SALEABLE->value,
                'date' => $now->copy()->subDays(7 + ($randomSeed % 60))->toDateString(),
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => bcmul($quantity, $unitCost, 2),
                'status' => ProductMovementStatus::ACTIVE->value,
                'activated_at' => $now,
                'notes' => 'Seeded opening stock for delivery and inventory testing.',
                'deleted_at' => null,
                'created_at' => $existing->created_at ?? $now,
                'updated_at' => $now,
            ];
        });

        $rows->chunk(500)->each(fn ($chunk) => DB::table('product_ledgers')->upsert(
            $chunk->all(),
            ['id'],
            [
                'product_id', 'stock_location_id', 'stock_location_name', 'product_sku',
                'product_name', 'document_number', 'reference_number', 'source_type', 'direction',
                'stock_bucket', 'date', 'quantity', 'unit_cost', 'total_cost', 'status', 'activated_at',
                'notes', 'deleted_at', 'updated_at',
            ]
        ));
    }

    /**
     * Seed one product movement and optionally its posted COGS ledger pair.
     *
     * @param  array<string, mixed>  $movement
     * @param  stdClass  $product  The product record for the movement.
     * @param  stdClass  $invoice  The invoice record linked to the movement.
     * @param  bool  $postCogs  Whether to post the COGS ledger pair.
     * @param  stdClass  $cogsChart  The COGS chart record.
     * @param  stdClass  $inventoryChart  The inventory chart record.
     * @param  stdClass  $stockLocation  The stock location record.
     */
    private function seedMovement(
        array $movement,
        stdClass $product,
        stdClass $invoice,
        bool $postCogs,
        stdClass $cogsChart,
        stdClass $inventoryChart,
        stdClass $stockLocation
    ): void {
        $existing = DB::table('product_ledgers')
            ->where('document_number', $movement['document_number'])
            ->where('source_type', $movement['source_type']->value)
            ->where('product_id', $product->id)
            ->first(['id', 'cogs_ledger_id', 'inventory_ledger_id', 'created_at']);
        $movementId = $existing->id ?? (string) Str::uuid7();
        $cogsLedgerId = $existing->cogs_ledger_id ?? (string) Str::uuid7();
        $inventoryLedgerId = $existing->inventory_ledger_id ?? (string) Str::uuid7();
        $totalCost = bcmul($movement['quantity'], $movement['unit_cost'], 2);
        $now = now();

        DB::table('product_ledgers')->updateOrInsert(
            ['id' => $movementId],
            [
                'product_id' => $product->id,
                'stock_location_id' => $stockLocation->id,
                'stock_location_name' => $stockLocation->name,
                'company_id' => $invoice->company_id,
                'source_entry_id' => $movement['source_entry_id'],
                'cogs_ledger_id' => $movement['source_type'] === ProductMovementSource::SALES_INVOICE
                    ? $cogsLedgerId
                    : null,
                'inventory_ledger_id' => $movement['source_type'] === ProductMovementSource::SALES_INVOICE
                    ? $inventoryLedgerId
                    : null,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'company_name' => $invoice->company_name,
                'document_number' => $movement['document_number'],
                'reference_number' => $movement['reference_number'],
                'source_type' => $movement['source_type']->value,
                'direction' => $movement['direction']->value,
                'date' => $movement['date'],
                'quantity' => $movement['quantity'],
                'unit_cost' => $movement['unit_cost'],
                'total_cost' => $totalCost,
                'status' => $movement['status']->value,
                'activated_at' => $movement['activated_at'],
                'notes' => $movement['notes'],
                'deleted_at' => null,
                'created_at' => $existing->created_at ?? $now,
                'updated_at' => $now,
            ]
        );

        if (! $postCogs) {
            DB::table('ledgers')->whereIn('id', [$cogsLedgerId, $inventoryLedgerId])->delete();

            return;
        }

        $common = [
            'source_id' => $movementId,
            'source_type' => null,
            'company_id' => $invoice->company_id,
            'contact_id' => $invoice->contact_id,
            'company_name' => $invoice->company_name,
            'document_number' => $movement['document_number'],
            'reference_number' => $movement['reference_number'],
            'type' => LedgerType::JOURNAL->value,
            'subtype' => LedgerSubType::JOURNAL->value,
            'date' => $movement['date'],
            'document_date' => $movement['date'],
            'total_amount' => $totalCost,
            'status' => LedgerStatus::POSTED->value,
            'description' => $movement['notes'],
            'deleted_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('ledgers')->updateOrInsert(
            ['id' => $cogsLedgerId],
            $common + [
                'debit_id' => $cogsChart->id,
                'credit_id' => null,
                'debit_acc' => $cogsChart->code,
                'credit_acc' => null,
                'debit_acc_desc' => $cogsChart->name,
                'credit_acc_desc' => null,
                'debit_amount' => $totalCost,
                'credit_amount' => '0.00',
            ]
        );
        DB::table('ledgers')->updateOrInsert(
            ['id' => $inventoryLedgerId],
            $common + [
                'debit_id' => null,
                'credit_id' => $inventoryChart->id,
                'debit_acc' => null,
                'credit_acc' => $inventoryChart->code,
                'debit_acc_desc' => null,
                'credit_acc_desc' => $inventoryChart->name,
                'debit_amount' => '0.00',
                'credit_amount' => $totalCost,
            ]
        );
    }

    /**
     * Return the debit-side ledger row for one sales invoice.
     *
     * Pending documents are intentionally kept out of ar_ledgers, so a
     * pending demo invoice has no row to read back. Fall back to the
     * document the SubLedgerSeeder defines, so the movement still seeds.
     */
    private function invoiceLine(string $documentNumber): ?stdClass
    {
        $row = DB::table('ar_ledgers')
            ->where('document_number', $documentNumber)
            ->first();

        if ($row !== null) {
            $row->quantity = 1;
            $row->contact_id = null;

            return $row;
        }

        $fallbackDates = [
            'IV-DEMO-0001' => '2026-08-01',
            'IV-DEMO-0002' => '2026-08-25',
        ];

        if (! isset($fallbackDates[$documentNumber])) {
            return null;
        }

        $company = DB::table('companies')
            ->where('is_debtor', true)
            ->orderBy('created_at')
            ->first(['id', 'name']);

        if ($company === null) {
            return null;
        }

        $fallback = new stdClass;
        $fallback->id = (string) Str::uuid7();
        $fallback->company_id = $company->id;
        $fallback->company_name = $company->name;
        $fallback->contact_id = null;
        $fallback->document_number = $documentNumber;
        $fallback->date = $fallbackDates[$documentNumber];
        $fallback->quantity = 1;

        return $fallback;
    }

    /**
     * Seed the independent GRN document sequence.
     */
    private function seedGoodsReceiptSequence(): void
    {
        $this->seedDocumentSequence('goods_receipt', 'GRN', 10002, 'product_ledgers', 'document_number');
    }
}
