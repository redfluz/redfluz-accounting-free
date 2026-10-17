<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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
use App\Enums\LedgerStatus;
use App\Enums\PurchaseOrderType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give purchase invoices their own tables.
     *
     * A purchase invoice is the document a supplier sends requesting payment for
     * goods or services received. Like the sales invoice, it was stored in the
     * shared client ledger, one row per side of every accounting entry, which put
     * it in the same table as every other document type.
     *
     * The split mirrors the sales invoice: a header that is the document, and
     * lines that are what it is made of. A purchase invoice that had more than
     * one line previously had several copies of its own header (two rows per
     * line: a debit and a credit, plus optional tax rows), so its status and its
     * link to the supplier could disagree between them.
     *
     * The purchase side differs from sales in three structural ways: the type
     * column is purchase_type (bill or purchase, not product or service), there
     * is no link to a sales order, and the discount and tax fields are supplier-
     * entered flat amounts rather than references to master data.
     */
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->index();
            $table->foreignUuid('contact_id')->nullable()->index();
            $table->string('document_number')->unique();
            // The kind of purchase: a bill or a direct purchase. The subtype that
            // distinguished it inside the shared ledger is no longer needed as a
            // column because the table itself carries that meaning.
            $table->enum('purchase_type', array_column(PurchaseOrderType::cases(), 'value'))
                ->default(PurchaseOrderType::PURCHASE->value);
            // The document that originated this invoice: a purchase order number,
            // a goods received note number, or the invoice number itself when
            // there is no preceding document.
            $table->string('reference_number')->nullable()->index();
            $table->foreignUuid('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete()->index();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->enum('status', array_column(LedgerStatus::cases(), 'value'))
                ->default(LedgerStatus::PENDING->value);
            $table->string('attachment_path')->nullable();
            $table->decimal('subtotal', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->text('notes')->nullable();

            $table->uuid('created_by')->nullable();
            $table->string('created_name')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->string('updated_name')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->string('deleted_name')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->string('approved_name')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->string('cancelled_name')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->uuid('undone_by')->nullable();
            $table->string('undone_name')->nullable();
            $table->timestamp('undone_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index('date');
        });

        Schema::create('purchase_invoice_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->index();
            $table->string('product_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15)->default(0);
            $table->decimal('subtotal_amount', 15)->default(0);
            $table->decimal('discount_value', 15)->default(0);
            $table->decimal('discount_amount', 15)->default(0);
            $table->decimal('tax_amount', 15)->default(0);
            $table->decimal('line_total_amount', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_lines');
        Schema::dropIfExists('purchase_invoices');
    }
};
