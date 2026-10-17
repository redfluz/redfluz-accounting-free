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
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Enums\PurchaseOrderType;
use App\Models\Crm\Company;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use App\Models\Inventory\StockLocation;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceLine;
use Database\Seeders\Concerns\SeedsDocumentSequences;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the purchase invoices and returns that nothing else populated.
 *
 * Three gaps existed on a clean database:
 *
 *   purchase invoices      SubLedgerSeeder wrote posted and cancelled
 *                          invoices (now to ledgers, ar_ledgers and
 *                          ap_ledgers), but never a pending one, so the
 *                          pending grid was empty
 *   invoice document rows  the cancelled and history grids read the purchase
 *                          invoice documents, which nothing wrote for the posted
 *                          and cancelled invoices the ledger stream posts —
 *                          the ledger knew the figures, the grids had nothing to
 *                          show
 *   purchase returns       nothing seeded a return at all, so the grid, the
 *                          detail page and the approval path had nothing to show
 *
 * The posted and cancelled invoices below mirror the ledger stream's
 * documents of the same numbers — same company, same date, same amounts, same
 * product — so the document row and its ledger rows tell one story. Their
 * ledger rows are written once, by the ledger seeder through the poster the
 * controllers approve with; this seeder writes only the document row and
 * touches no ledger for them. A pending or cancelled invoice has no ledger
 * effect, so any ledger rows left under those numbers are removed rather than
 * left to contradict the document's status.
 *
 * A return is a product ledger row, and approving one only flips its status
 * after checking the stock is there, so the approved scenario below reduces the
 * on hand quantity by a single unit against a product holding several dozen.
 *
 * Quantities are deliberately tiny against the seeded stock on hand. Every
 * return here is far below what is available, so the grid, the detail page and
 * the approval guard all have real data to work with without stock ever going
 * negative.
 *
 * Documents are seeded in ascending number order. Re-running is safe: each row is
 * found by its own document number before a new id is minted, and no sequence is
 * rewound. Nothing is ever hard deleted.
 */
class PurchaseDocumentSeeder extends Seeder
{
    use SeedsDocumentSequences;

    /**
     * The invoices to seed, in ascending document order.
     *
     * @var list<array<string, mixed>>
     */
    private const INVOICES = [
        [
            'number' => 'PI-CANCEL-0001',
            'reference_number' => 'PI-CANCEL-0001',
            'company' => 'Ingram Micro Malaysia Sdn Bhd',
            'product_sku' => 'HP-PAV-15',
            'quantity' => '1',
            'unit_price' => '2750.00',
            'amount' => '2750.00',
            'date' => '2026-07-20',
            'due_date' => '2026-08-19',
            'status' => LedgerStatus::CANCELLED,
            'notes' => 'Cancelled purchase invoice — wrong supplier selected.',
        ],
        [
            // The document row for the posted purchase invoice the client
            // ledger seeder already writes and posts through the poster. Same
            // company, date, amounts and product as that stream, so the
            // history grid shows the document the ledger rows belong to.
            'number' => 'PI-DEMO-0001',
            'reference_number' => 'PI-DEMO-0001',
            'company' => 'Ingram Micro Malaysia Sdn Bhd',
            'product_sku' => 'HP-PAV-15',
            'quantity' => '2',
            'unit_price' => '900.00',
            'amount' => '1800.00',
            'date' => '2026-08-08',
            'due_date' => '2026-09-07',
            'status' => LedgerStatus::POSTED,
            'notes' => 'Posted purchase invoice used to demonstrate supplier document flow.',
        ],
        [
            'number' => 'PI-DEMO-0101',
            'reference_number' => 'PO-DEMO-10001',
            'company' => 'Ingram Micro Malaysia Sdn Bhd',
            'product_sku' => 'PART-LCD-156-FHD',
            'quantity' => '4',
            'unit_price' => '305.00',
            'amount' => '1220.00',
            'date' => '2026-09-25',
            'due_date' => '2026-10-25',
            'status' => LedgerStatus::PENDING,
            'notes' => 'Supplier invoice received, awaiting posting to the ledger.',
        ],
        [
            'number' => 'PI-DEMO-0102',
            'reference_number' => null,
            'company' => 'VSTECS Berhad',
            'product_sku' => 'ASU-VIVO-15',
            'quantity' => '2',
            'unit_price' => '2525.00',
            'amount' => '5050.00',
            'date' => '2026-09-26',
            'due_date' => '2026-10-26',
            'status' => LedgerStatus::PENDING,
            'notes' => 'Second supplier invoice awaiting posting.',
        ],
    ];

    /**
     * The returns to seed, in ascending document order.
     *
     * @var list<array<string, mixed>>
     */
    private const RETURNS = [
        [
            'number' => 'PR-DEMO-0101',
            'goods_receipt' => 'GRN-DEMO-10002',
            'order' => 'PO-DEMO-10002',
            'product_sku' => 'PART-USB-C-PORT',
            'quantity' => '2',
            'unit_cost' => '28.00',
            'date' => '2026-09-27',
            'status' => ProductMovementStatus::PENDING,
            'activated_at' => null,
            'notes' => 'Two faulty charging ports returned to the supplier, awaiting approval.',
        ],
        [
            'key' => 'pr-demo-0102-approved',
            'number' => 'PR-DEMO-0102',
            'goods_receipt' => 'GRN-DEMO-10001',
            'order' => 'PO-DEMO-10001',
            'product_sku' => 'PART-USB-C-PORT',
            'quantity' => '1',
            'unit_cost' => '28.00',
            'date' => '2026-09-28',
            'status' => ProductMovementStatus::ACTIVE,
            'activated_at' => '2026-09-28 10:20:00',
            'notes' => 'Approved return: one faulty charging port sent back.',
        ],
    ];

    /**
     * Seed the documents.
     */
    public function run(): void
    {
        $now = now();

        foreach (self::INVOICES as $invoice) {
            $this->seedInvoice($invoice, $now);
        }

        foreach (self::RETURNS as $return) {
            $this->seedReturn($return, $now);
        }

        // Only advance past numbers already issued, never rewind.
        $this->seedDocumentSequence('purchase_invoice', 'PI', 10007, 'ap_ledgers', 'document_number');
    }

    /**
     * Write one invoice to the purchase_invoices table.
     *
     * A pending invoice is not posted, so no ledger rows are written and any left
     * from an earlier seed are cleared, exactly as the ledger seeder does.
     *
     * @param  array<string, mixed>  $invoice  The invoice definition.
     * @param  Carbon  $now  The current time.
     */
    private function seedInvoice(array $invoice, $now): void
    {
        $company = Company::query()->where('name', $invoice['company'])->first();
        $product = Product::query()->where('sku', $invoice['product_sku'])->first();

        if ($company === null || $product === null) {
            return;
        }

        $existing = PurchaseInvoice::query()
            ->where('document_number', $invoice['number'])
            ->first(['id', 'created_at']);

        $headerId = $existing->id ?? (string) Str::uuid7();

        PurchaseInvoice::query()->updateOrInsert(
            ['id' => $headerId],
            [
                'document_number' => $invoice['number'],
                'purchase_type' => PurchaseOrderType::PURCHASE->value,
                'reference_number' => $invoice['reference_number'],
                'company_id' => $company->id,
                'contact_id' => null,
                'company_name' => $company->name,
                'contact_name' => null,
                'date' => $invoice['date'],
                'due_date' => $invoice['due_date'],
                'status' => $invoice['status']->value,
                'attachment_path' => null,
                'subtotal' => $invoice['amount'],
                'total_amount' => $invoice['amount'],
                'notes' => $invoice['notes'],
                'created_at' => $existing->created_at ?? $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        $existingLine = PurchaseInvoiceLine::query()
            ->where('purchase_invoice_id', $headerId)
            ->first(['id', 'created_at']);

        PurchaseInvoiceLine::query()->updateOrInsert(
            ['id' => $existingLine->id ?? (string) Str::uuid7()],
            [
                'purchase_invoice_id' => $headerId,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $invoice['quantity'],
                'unit_price' => $invoice['unit_price'],
                'subtotal_amount' => $invoice['amount'],
                'discount_value' => '0.00',
                'discount_amount' => '0.00',
                'tax_amount' => '0.00',
                'line_total_amount' => $invoice['amount'],
                'total_amount' => $invoice['amount'],
                'description' => $product->name,
                'created_at' => $existingLine->created_at ?? $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        // A pending or cancelled invoice has no ledger effect, so any rows
        // from an earlier seed are removed rather than left to contradict its
        // status. A posted invoice's rows belong to the ledger stream and are
        // left exactly as that seeder wrote them.
        if ($invoice['status'] !== LedgerStatus::POSTED) {
            DB::table('ledgers')->where('document_number', $invoice['number'])->delete();
        }
    }

    /**
     * Write one purchase return.
     *
     * A return is one product ledger row carrying a source_entry_id that points
     * at the goods receipt line being corrected, which is what makes it a return
     * rather than a receipt.
     *
     * @param  array<string, mixed>  $return  The return definition.
     * @param  Carbon  $now  The current time.
     */
    private function seedReturn(array $return, $now): void
    {
        $receipt = ProductLedger::query()
            ->where('document_number', $return['goods_receipt'])
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT->value)
            ->whereNull('source_entry_id')
            ->where('direction', ProductMovementDirection::IN->value)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->where('product_id', Product::query()->where('sku', $return['product_sku'])->value('id'))
            ->first();

        if ($receipt === null) {
            return;
        }

        $stockLocation = StockLocation::query()->first();

        if ($stockLocation === null) {
            return;
        }

        $existing = ProductLedger::query()
            ->where('document_number', $return['number'])
            ->where('source_type', ProductMovementSource::PURCHASE_RETURN->value)
            ->first(['id', 'created_at']);

        ProductLedger::query()->updateOrInsert(
            ['id' => $existing->id ?? (string) Str::uuid7()],
            [
                'product_id' => $receipt->product_id,
                'stock_location_id' => $stockLocation->id,
                'stock_location_name' => $stockLocation->name,
                'company_id' => $receipt->company_id,
                'source_entry_id' => $receipt->id,
                'cogs_ledger_id' => null,
                'inventory_ledger_id' => null,
                'variance_ledger_id' => null,
                'product_sku' => $receipt->product_sku,
                'product_name' => $receipt->product_name,
                'company_name' => $receipt->company_name,
                'document_number' => $return['number'],
                'reference_number' => $return['order'],
                'source_type' => ProductMovementSource::PURCHASE_RETURN->value,
                'direction' => ProductMovementDirection::OUT->value,
                'stock_bucket' => ProductStockBucket::SALEABLE->value,
                'date' => $return['date'],
                'quantity' => $return['quantity'],
                'unit_cost' => $return['unit_cost'],
                'total_cost' => bcmul((string) $return['quantity'], (string) $return['unit_cost'], 2),
                'status' => $return['status']->value,
                'activated_at' => $return['activated_at'],
                'notes' => $return['notes'],
                'deleted_at' => null,
                'created_at' => $existing->created_at ?? $now,
                'updated_at' => $now,
            ]
        );
    }
}
