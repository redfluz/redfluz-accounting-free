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
 * All code, concepts and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace Database\Seeders;

use App\Enums\AcquisitionType;
use App\Enums\LedgerStatus;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Enums\PurchaseOrderType;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use App\Models\Inventory\StockLocation;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderLine;
use Illuminate\Database\Seeder;

/**
 * Seeds purchase accounting scenarios using Apple iPhone 15 to exercise every
 * workflow path: GRN, invoice, returns, adjustments, before and after posting.
 *
 * Scenarios
 * ---------
 * A. Full happy path — receive all, invoice all, post.
 * B. Partial receipt then invoice — only 60 of 100 received, invoice capped.
 * C. Full return after GRN approved, before invoice — return all 100.
 * D. Partial return after GRN approved, before invoice — return 20 of 100.
 * E. Full return after invoice posted — return all, then credit note.
 * F. Partial return after invoice posted — return 30 of 100, then credit note.
 * G. GRN adjustment before invoice — correct quantity from 100 to 95.
 * H. GRN adjustment after invoice posted — correction does not affect invoice.
 */
class PurchaseScenarioSeeder extends Seeder
{
    /**
     * Seed all iPhone 15 purchase scenarios.
     */
    public function run(): void
    {
        $supplier = $this->supplier();
        $company = $this->company();
        $product = $this->product();
        $stockLocation = $this->stockLocation();
        $contact = $this->contact($supplier);

        // Scenario A: Full happy path — PO10001
        $this->scenarioAFullHappyPath($supplier, $company, $product, $stockLocation, $contact);

        // Scenario B: Partial receipt, invoice capped — PO10002
        $this->scenarioBPartialReceipt($supplier, $company, $product, $stockLocation, $contact);

        // Scenario C: Full return before invoice — PO10003
        $this->scenarioCFullReturnBeforeInvoice($supplier, $company, $product, $stockLocation, $contact);

        // Scenario D: Partial return before invoice — PO10004
        $this->scenarioDPartialReturnBeforeInvoice($supplier, $company, $product, $stockLocation, $contact);

        // Scenario E: Full return after invoice posted — PO10005
        $this->scenarioEFullReturnAfterInvoice($supplier, $company, $product, $stockLocation, $contact);

        // Scenario F: Partial return after invoice posted — PO10006
        $this->scenarioFPartialReturnAfterInvoice($supplier, $company, $product, $stockLocation, $contact);

        // Scenario G: GRN adjustment before invoice — PO10007
        $this->scenarioGAdjustmentBeforeInvoice($supplier, $company, $product, $stockLocation, $contact);

        // Scenario H: GRN adjustment after invoice posted — PO10008
        $this->scenarioHAdjustmentAfterInvoice($supplier, $company, $product, $stockLocation, $contact);
    }

    // -----------------------------------------------------------------------
    //  Lookup helpers
    // -----------------------------------------------------------------------

    private function supplier(): Company
    {
        return Company::query()->firstOrCreate(
            ['code' => 'SUP-IPHONE'],
            [
                'name' => 'Apple Asia Distribution',
                'is_creditor' => true,
                'is_debtor' => false,
            ]
        );
    }

    private function company(): Company
    {
        return Company::query()->firstOrCreate(
            ['code' => 'COM-MAIN'],
            [
                'name' => 'Redfluz Sdn Bhd',
                'is_creditor' => false,
                'is_debtor' => true,
            ]
        );
    }

    private function product(): Product
    {
        return Product::query()->firstOrCreate(
            ['sku' => 'IPHONE-15-BLK-128'],
            [
                'name' => 'Apple iPhone 15 128GB Black',
                'is_service' => false,
                'base_price' => '4000.00',
                'selling_price' => '5200.00',
            ]
        );
    }

    private function stockLocation(): StockLocation
    {
        return StockLocation::query()->firstOrCreate(
            ['name' => 'Main Warehouse'],
            [
                'is_active' => true,
            ]
        );
    }

    /**
     * @param  Company  $supplier  The supplier company.
     */
    private function contact(Company $supplier): Contact
    {
        return Contact::query()->firstOrCreate(
            ['company_id' => $supplier->id, 'name' => 'Alan Tan'],
            [
                'email' => 'alan.tan@apple-asia.example',
                'phone' => '+60123456789',
            ]
        );
    }

    // -----------------------------------------------------------------------
    //  PO factory
    // -----------------------------------------------------------------------

    /**
     * @param  Company  $company  Our company.
     * @param  Company  $supplier  The supplier.
     * @param  Contact  $contact  Supplier contact.
     * @param  string  $docNumber  PO document number.
     * @param  int  $quantity  Ordered quantity.
     * @param  string  $unitPrice  Price per unit.
     * @param  bool  $isApproved  Whether the PO is approved.
     * @param  bool  $isPartialReceived  Whether the PO is partially received.
     * @param  bool  $isReceived  Whether the PO is fully received.
     */
    private function createPo(
        Company $company,
        Company $supplier,
        Contact $contact,
        string $docNumber,
        int $quantity,
        string $unitPrice,
        bool $isApproved = false,
        bool $isPartialReceived = false,
        bool $isReceived = false,
    ): PurchaseOrder {
        $total = bcmul($unitPrice, (string) $quantity, 2);

        $po = PurchaseOrder::query()->create([
            'company_id' => $supplier->id,
            'contact_id' => $contact->id,
            'purchase_type' => PurchaseOrderType::PURCHASE,
            'document_number' => $docNumber,
            'company_name' => $supplier->name,
            'contact_name' => $contact->name,
            'date' => now(),
            'credit_term_days' => 30,
            'due_date' => now()->addDays(30),
            'subtotal' => $total,
            'tax_amount' => '0.00',
            'total_amount' => $total,
            'approved_by' => $isApproved ? '1' : null,
            'approved_name' => $isApproved ? 'Admin' : null,
            'approved_at' => $isApproved ? now() : null,
            'is_partial_received' => $isPartialReceived,
            'is_received' => $isReceived,
        ]);

        PurchaseOrderLine::query()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product()->id,
            'product_sku' => $this->product()->sku,
            'product_name' => $this->product()->name,
            'description' => 'Apple iPhone 15 128GB Black',
            'is_service' => false,
            'acquisition_type' => AcquisitionType::STOCK,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $total,
        ]);

        return $po;
    }

    // -----------------------------------------------------------------------
    //  GRN factory
    // -----------------------------------------------------------------------

    /**
     * @param  string  $poDocNumber  PO document number (reference_number on ledger).
     * @param  string  $grnDocNumber  GRN document number.
     * @param  int  $quantity  Received quantity.
     * @param  ProductMovementStatus  $status  PENDING or ACTIVE.
     */
    private function createGrn(
        string $poDocNumber,
        string $grnDocNumber,
        int $quantity,
        ProductMovementStatus $status,
    ): ProductLedger {
        $product = $this->product();
        $stockLocation = $this->stockLocation();
        $unitCost = (string) $product->base_price;
        $totalCost = bcmul($unitCost, (string) $quantity, 2);

        return ProductLedger::query()->create([
            'product_id' => $product->id,
            'stock_location_id' => $stockLocation->id,
            'stock_location_name' => $stockLocation->name,
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'document_number' => $grnDocNumber,
            'reference_number' => $poDocNumber,
            'source_type' => ProductMovementSource::GOODS_RECEIPT,
            'direction' => ProductMovementDirection::IN,
            'stock_bucket' => ProductStockBucket::SALEABLE,
            'date' => now(),
            'quantity' => (string) $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'status' => $status,
            'activated_at' => $status === ProductMovementStatus::ACTIVE ? now() : null,
        ]);
    }

    // -----------------------------------------------------------------------
    //  Purchase return factory
    // -----------------------------------------------------------------------

    /**
     * @param  string  $poDocNumber  PO document number (reference_number).
     * @param  string  $returnDocNumber  Return document number.
     * @param  int  $quantity  Returned quantity.
     * @param  ProductMovementStatus  $status  PENDING or ACTIVE.
     * @param  string|null  $sourceEntryId  Original GRN line ID (optional).
     */
    private function createPurchaseReturn(
        string $poDocNumber,
        string $returnDocNumber,
        int $quantity,
        ProductMovementStatus $status,
        ?string $sourceEntryId = null,
    ): ProductLedger {
        $product = $this->product();
        $stockLocation = $this->stockLocation();
        $unitCost = (string) $product->base_price;
        $totalCost = bcmul($unitCost, (string) $quantity, 2);

        return ProductLedger::query()->create([
            'product_id' => $product->id,
            'stock_location_id' => $stockLocation->id,
            'stock_location_name' => $stockLocation->name,
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'document_number' => $returnDocNumber,
            'reference_number' => $poDocNumber,
            'source_entry_id' => $sourceEntryId,
            'source_type' => ProductMovementSource::PURCHASE_RETURN,
            'direction' => ProductMovementDirection::OUT,
            'stock_bucket' => ProductStockBucket::SALEABLE,
            'date' => now(),
            'quantity' => (string) $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'status' => $status,
            'activated_at' => $status === ProductMovementStatus::ACTIVE ? now() : null,
        ]);
    }

    // -----------------------------------------------------------------------
    //  GRN adjustment factory
    // -----------------------------------------------------------------------

    /**
     * @param  string  $poDocNumber  PO document number (reference_number).
     * @param  string  $grnDocNumber  Parent GRN document number.
     * @param  string  $adjustDocNumber  Adjustment document number (same as GRN).
     * @param  int  $quantity  Correction magnitude.
     * @param  ProductMovementDirection  $direction  IN (increase) or OUT (decrease).
     * @param  ProductMovementStatus  $status  PENDING or ACTIVE.
     * @param  string  $sourceEntryId  Original GRN line ID.
     */
    private function createGrnAdjustment(
        string $poDocNumber,
        string $grnDocNumber,
        string $adjustDocNumber,
        int $quantity,
        ProductMovementDirection $direction,
        ProductMovementStatus $status,
        string $sourceEntryId,
    ): ProductLedger {
        $product = $this->product();
        $stockLocation = $this->stockLocation();
        $unitCost = (string) $product->base_price;
        $totalCost = bcmul($unitCost, (string) $quantity, 2);

        return ProductLedger::query()->create([
            'product_id' => $product->id,
            'stock_location_id' => $stockLocation->id,
            'stock_location_name' => $stockLocation->name,
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'document_number' => $adjustDocNumber,
            'reference_number' => $poDocNumber,
            'source_entry_id' => $sourceEntryId,
            'source_type' => ProductMovementSource::GOODS_RECEIPT,
            'direction' => $direction,
            'stock_bucket' => ProductStockBucket::SALEABLE,
            'date' => now(),
            'quantity' => (string) $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'status' => $status,
            'activated_at' => $status === ProductMovementStatus::ACTIVE ? now() : null,
        ]);
    }

    // -----------------------------------------------------------------------
    //  Invoice factory
    // -----------------------------------------------------------------------

    /**
     * @param  PurchaseOrder  $po  The source PO.
     * @param  string  $invoiceDocNumber  Invoice document number.
     * @param  int  $quantity  Invoiced quantity.
     * @param  LedgerStatus  $status  PENDING or POSTED.
     */
    private function createInvoice(
        PurchaseOrder $po,
        string $invoiceDocNumber,
        int $quantity,
        LedgerStatus $status,
    ): PurchaseInvoice {
        $unitPrice = '4000.00';
        $total = bcmul($unitPrice, (string) $quantity, 2);

        $invoice = PurchaseInvoice::query()->create([
            'company_id' => $po->company_id,
            'contact_id' => $po->contact_id,
            'purchase_type' => $po->purchase_type,
            'status' => $status,
            'document_number' => $invoiceDocNumber,
            'reference_number' => $invoiceDocNumber,
            'purchase_order_id' => $po->id,
            'company_name' => $po->company_name,
            'contact_name' => $po->contact_name,
            'date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $total,
            'total_amount' => $total,
            'approved_by' => $status === LedgerStatus::POSTED ? '1' : null,
            'approved_name' => $status === LedgerStatus::POSTED ? 'Admin' : null,
            'approved_at' => $status === LedgerStatus::POSTED ? now() : null,
        ]);

        PurchaseInvoiceLine::query()->create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->product()->id,
            'product_name' => $this->product()->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal_amount' => $total,
            'discount_value' => '0.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'line_total_amount' => $total,
            'total_amount' => $total,
            'description' => 'Apple iPhone 15 128GB Black',
        ]);

        return $invoice;
    }

    // =======================================================================
    //  SCENARIOS
    // =======================================================================

    /**
     * Scenario A: Full happy path.
     *
     * PO10001: Order 100 iPhone 15 @ RM 4,000 = RM 400,000
     * GRN10001: Receive all 100 (ACTIVE)
     * PI10001: Invoice 100 units (POSTED)
     *
     * State: PO = RECEIVED, GRN = ACTIVE, Invoice = POSTED
     * Stock: +100 iPhone 15
     * AP: RM 400,000 owed to supplier
     */
    private function scenarioAFullHappyPath(
        Company $supplier,
        Company $company,
        Product $product,
        StockLocation $stockLocation,
        Contact $contact,
    ): void {
        $po = $this->createPo($company, $supplier, $contact, 'PO10001', 100, '4000.00', true, false, true);
        $grn = $this->createGrn('PO10001', 'GRN10001', 100, ProductMovementStatus::ACTIVE);
        $this->createInvoice($po, 'PI10001', 100, LedgerStatus::POSTED);
    }

    /**
     * Scenario B: Partial receipt, invoice capped at received.
     *
     * PO10002: Order 100 iPhone 15 @ RM 4,000 = RM 400,000
     * GRN10002: Receive 60 of 100 (ACTIVE) → PO status = PARTIALLY_RECEIVED
     * PI10002: Invoice capped at 60 units (PENDING)
     *
     * State: PO = PARTIALLY_RECEIVED, GRN = ACTIVE, Invoice = PENDING
     * Stock: +60 iPhone 15
     * Invoice qty gate: 60 (not 100 — cannot invoice what hasn't arrived)
     */
    private function scenarioBPartialReceipt(
        Company $supplier,
        Company $company,
        Product $product,
        StockLocation $stockLocation,
        Contact $contact,
    ): void {
        $po = $this->createPo($company, $supplier, $contact, 'PO10002', 100, '4000.00', true, true, false);
        $this->createGrn('PO10002', 'GRN10002', 60, ProductMovementStatus::ACTIVE);
        $this->createInvoice($po, 'PI10002', 60, LedgerStatus::PENDING);
    }

    /**
     * Scenario C: Full return before invoice.
     *
     * PO10003: Order 100 iPhone 15 @ RM 4,000 = RM 400,000
     * GRN10003: Receive all 100 (ACTIVE)
     * PRTN10003: Return all 100 (ACTIVE) → net received = 0
     *
     * State: PO = RECEIVED (GRN was approved), stock = 0 (all returned)
     * Invoice gate: 0 units — cannot create invoice (no_received_quantity error)
     * User must cancel PO or wait for replacement goods.
     */
    private function scenarioCFullReturnBeforeInvoice(
        Company $supplier,
        Company $company,
        Product $product,
        StockLocation $stockLocation,
        Contact $contact,
    ): void {
        $po = $this->createPo($company, $supplier, $contact, 'PO10003', 100, '4000.00', true, false, true);
        $grn = $this->createGrn('PO10003', 'GRN10003', 100, ProductMovementStatus::ACTIVE);
        $this->createPurchaseReturn('PO10003', 'PRTN10003', 100, ProductMovementStatus::ACTIVE, $grn->id);
    }

    /**
     * Scenario D: Partial return before invoice.
     *
     * PO10004: Order 100 iPhone 15 @ RM 4,000 = RM 400,000
     * GRN10004: Receive all 100 (ACTIVE)
     * PRTN10004: Return 20 (ACTIVE) → net received = 80
     * PI10004: Invoice capped at 80 units (PENDING)
     *
     * State: Stock = +80, Invoice = 80 units pending
     * Invoice qty gate: 100 received − 20 returned = 80
     */
    private function scenarioDPartialReturnBeforeInvoice(
        Company $supplier,
        Company $company,
        Product $product,
        StockLocation $stockLocation,
        Contact $contact,
    ): void {
        $po = $this->createPo($company, $supplier, $contact, 'PO10004', 100, '4000.00', true, false, true);
        $grn = $this->createGrn('PO10004', 'GRN10004', 100, ProductMovementStatus::ACTIVE);
        $this->createPurchaseReturn('PO10004', 'PRTN10004', 20, ProductMovementStatus::ACTIVE, $grn->id);
        $this->createInvoice($po, 'PI10004', 80, LedgerStatus::PENDING);
    }

    /**
     * Scenario E: Full return after invoice posted.
     *
     * PO10005: Order 100 iPhone 15 @ RM 4,000 = RM 400,000
     * GRN10005: Receive all 100 (ACTIVE)
     * PI10005: Invoice 100 units (POSTED) → AP = RM 400,000
     * PRTN10005: Return all 100 (ACTIVE) → stock = 0
     *
     * State: GRN locked (invoice posted), stock = 0
     * Next step: User creates Purchase Adjustment (Credit Note) for RM 400,000
     * to reduce AP balance. The return handles stock; the credit note handles money.
     */
    private function scenarioEFullReturnAfterInvoice(
        Company $supplier,
        Company $company,
        Product $product,
        StockLocation $stockLocation,
        Contact $contact,
    ): void {
        $po = $this->createPo($company, $supplier, $contact, 'PO10005', 100, '4000.00', true, false, true);
        $grn = $this->createGrn('PO10005', 'GRN10005', 100, ProductMovementStatus::ACTIVE);
        $this->createInvoice($po, 'PI10005', 100, LedgerStatus::POSTED);
        $this->createPurchaseReturn('PO10005', 'PRTN10005', 100, ProductMovementStatus::ACTIVE, $grn->id);
    }

    /**
     * Scenario F: Partial return after invoice posted.
     *
     * PO10006: Order 100 iPhone 15 @ RM 4,000 = RM 400,000
     * GRN10006: Receive all 100 (ACTIVE)
     * PI10006: Invoice 100 units (POSTED) → AP = RM 400,000
     * PRTN10006: Return 30 (ACTIVE) → stock = 70
     *
     * State: GRN locked, stock = 70
     * Next step: User creates Purchase Adjustment (Credit Note) for RM 120,000
     * (30 × RM 4,000) to reduce AP. Remaining AP = RM 280,000.
     */
    private function scenarioFPartialReturnAfterInvoice(
        Company $supplier,
        Company $company,
        Product $product,
        StockLocation $stockLocation,
        Contact $contact,
    ): void {
        $po = $this->createPo($company, $supplier, $contact, 'PO10006', 100, '4000.00', true, false, true);
        $grn = $this->createGrn('PO10006', 'GRN10006', 100, ProductMovementStatus::ACTIVE);
        $this->createInvoice($po, 'PI10006', 100, LedgerStatus::POSTED);
        $this->createPurchaseReturn('PO10006', 'PRTN10006', 30, ProductMovementStatus::ACTIVE, $grn->id);
    }

    /**
     * Scenario G: GRN adjustment before invoice.
     *
     * PO10007: Order 100 iPhone 15 @ RM 4,000 = RM 400,000
     * GRN10007: Receive 100 (ACTIVE)
     * Adjustment: Found 5 damaged → adjust OUT 5 (ACTIVE) → net = 95
     * PI10007: Invoice capped at 95 units (PENDING)
     *
     * State: Stock = 95, Invoice = 95 units
     * Invoice qty gate: 100 received − 5 adjustment = 95
     */
    private function scenarioGAdjustmentBeforeInvoice(
        Company $supplier,
        Company $company,
        Product $product,
        StockLocation $stockLocation,
        Contact $contact,
    ): void {
        $po = $this->createPo($company, $supplier, $contact, 'PO10007', 100, '4000.00', true, false, true);
        $grn = $this->createGrn('PO10007', 'GRN10007', 100, ProductMovementStatus::ACTIVE);
        $this->createGrnAdjustment('PO10007', 'GRN10007', 'GRN10007-ADJ', 5, ProductMovementDirection::OUT, ProductMovementStatus::ACTIVE, $grn->id);
        $this->createInvoice($po, 'PI10007', 95, LedgerStatus::PENDING);
    }

    /**
     * Scenario H: GRN adjustment after invoice posted.
     *
     * PO10008: Order 100 iPhone 15 @ RM 4,000 = RM 400,000
     * GRN10008: Receive 100 (ACTIVE)
     * PI10008: Invoice 100 units (POSTED) → AP = RM 400,000
     * Adjustment: Found 3 damaged later → adjust OUT 3 (ACTIVE) → stock = 97
     *
     * State: GRN locked (invoice posted), stock = 97
     * Invoice is NOT affected — still 100 units, AP = RM 400,000
     * Next step: User creates Purchase Adjustment (Credit Note) for RM 12,000
     * (3 × RM 4,000) if the supplier agrees to a price reduction.
     * The GRN Adjustment fixed stock; the Credit Note fixes money.
     */
    private function scenarioHAdjustmentAfterInvoice(
        Company $supplier,
        Company $company,
        Product $product,
        StockLocation $stockLocation,
        Contact $contact,
    ): void {
        $po = $this->createPo($company, $supplier, $contact, 'PO10008', 100, '4000.00', true, false, true);
        $grn = $this->createGrn('PO10008', 'GRN10008', 100, ProductMovementStatus::ACTIVE);
        $this->createInvoice($po, 'PI10008', 100, LedgerStatus::POSTED);
        $this->createGrnAdjustment('PO10008', 'GRN10008', 'GRN10008-ADJ', 3, ProductMovementDirection::OUT, ProductMovementStatus::ACTIVE, $grn->id);
    }
}
