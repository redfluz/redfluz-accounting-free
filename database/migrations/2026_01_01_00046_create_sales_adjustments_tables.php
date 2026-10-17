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
use App\Enums\LedgerSubType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give sales adjustments their own tables.
     *
     * A sales adjustment is a credit note or a debit note raised against an
     * invoice that has already gone out. It was stored in the shared client
     * ledger, one row per side of the entry, which put it in the same table as the
     * invoices themselves and made "every line of every document" one table that
     * every query against any kind of document had to filter.
     *
     * The split is the same one every other document already follows: a header
     * that is the document, and lines that are what it is made of. An adjustment
     * that raised more than one line previously had several copies of its own
     * header, so its status and its link to the invoice being corrected could
     * disagree between them.
     *
     * The kind stays as a column here rather than becoming the table, because both
     * kinds of adjustment are the same document with the direction reversed and
     * belong to the same grid. What the split buys is that an adjustment is a
     * document in its own right rather than a row that happens to carry an
     * adjustment's subtype.
     */
    public function up(): void
    {
        Schema::create('sales_adjustments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->index();
            $table->foreignUuid('contact_id')->nullable()->index();
            $table->string('document_number')->unique();
            // Which way round the adjustment is. increase_adjustment raises the
            // invoice, decrease_adjustment reduces it, and the posting depends on
            // which, so it is a fact about the document rather than a detail of a
            // line.
            $table->enum('subtype', [
                LedgerSubType::INCREASE_ADJUSTMENT->value,
                LedgerSubType::DECREASE_ADJUSTMENT->value,
            ]);
            // The invoice this corrects. The whole reason the document exists:
            // without it an adjustment is a figure with no origin.
            $table->string('reference_number')->nullable()->index();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->date('date');
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

        Schema::create('sales_adjustment_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_adjustment_id')->constrained('sales_adjustments')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->index();
            $table->string('product_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15)->default(0);
            $table->decimal('subtotal_amount', 15)->default(0);
            $table->decimal('discount_rate', 15)->default(0);
            $table->decimal('discount_amount', 15)->default(0);
            $table->decimal('tax_rate', 15)->default(0);
            $table->decimal('tax_amount', 15)->default(0);
            $table->string('tax_mode')->nullable();
            $table->decimal('line_total_amount', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->text('description')->nullable();
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
        });

        Schema::create('sales_adjustment_charts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_adjustment_id')->constrained('sales_adjustments')->cascadeOnDelete();
            $table->foreignUuid('debit_id')->nullable();
            $table->foreignUuid('credit_id')->nullable();
            $table->string('debit_acc')->nullable();
            $table->string('credit_acc')->nullable();
            $table->string('debit_acc_desc')->nullable();
            $table->string('credit_acc_desc')->nullable();
            $table->string('document_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->string('description')->nullable();
            $table->decimal('debit_amount', 15)->default(0.00);
            $table->decimal('credit_amount', 15)->default(0.00);
            $table->decimal('total_amount', 15)->default(0.00);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_adjustment_charts');
        Schema::dropIfExists('sales_adjustment_lines');
        Schema::dropIfExists('sales_adjustments');
    }
};
