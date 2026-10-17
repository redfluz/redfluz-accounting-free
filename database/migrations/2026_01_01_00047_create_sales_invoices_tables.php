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
use App\Enums\SalesType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give sales invoices their own tables.
     *
     * A sales invoice is the primary document sent to a customer requesting
     * payment for goods or services. It was stored in the shared client ledger,
     * one row per side of every accounting entry, which put it in the same table
     * as every other document type and made "every line of every document" one
     * table that every query had to filter.
     *
     * The split is the same one every other document already follows: a header
     * that is the document, and lines that are what it is made of. An invoice
     * that had more than one product previously had several copies of its own
     * header (two rows per product: a debit and a credit, plus optional tax
     * rows), so its status and its link to the customer could disagree between
     * them.
     *
     * What the split buys is that an invoice is a document in its own right
     * rather than a collection of rows that happen to share a document number.
     */
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->index();
            $table->foreignUuid('contact_id')->nullable()->index();
            $table->string('document_number')->unique();
            // The invoice is always a sales invoice; the subtype that
            // distinguished it inside the shared ledger is no longer needed as a
            // column because the table itself carries that meaning.
            $table->enum('sales_type', array_column(SalesType::cases(), 'value'))
                ->default(SalesType::PRODUCT->value);
            // The document that originated this invoice: a sales order number, a
            // delivery order number, or the invoice number itself when there is
            // no preceding document.
            $table->string('reference_number')->nullable()->index();
            $table->foreignUuid('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete()->index();
            $table->foreignUuid('quotation_id')->nullable()->constrained('quotations')->nullOnDelete()->index();
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
            // The reversing journal, once reversed. One reversal per document:
            // a second press finds this filled and is refused instead of
            // doubling the reversal back on top of the original.
            $table->foreignUuid('reversal_journal_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
            $table->index('date');
        });

        Schema::create('sales_invoice_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->index();
            $table->string('product_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15)->default(0);
            $table->decimal('subtotal_amount', 15)->default(0);
            $table->foreignUuid('discount_id')->nullable();
            $table->string('discount_name')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 15)->default(0);
            $table->decimal('discount_rate', 15)->default(0);
            $table->decimal('discount_amount', 15)->default(0);
            $table->foreignUuid('tax_id')->nullable();
            $table->string('tax_name')->nullable();
            $table->decimal('tax_rate', 15)->default(0);
            $table->decimal('tax_amount', 15)->default(0);
            $table->string('tax_mode')->nullable();
            $table->decimal('line_total_amount', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->integer('warranty_months')->default(0);
            $table->boolean('deduct_inventory')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_lines');
        Schema::dropIfExists('sales_invoices');
    }
};
