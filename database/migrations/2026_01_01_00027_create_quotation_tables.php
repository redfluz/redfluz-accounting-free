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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the quotation and its lines.
     *
     * Every other document in the system is a header and its lines: a purchase
     * order with purchase_order_lines, a receipt with receipt_lines, a refund
     * with refund_lines. So is a quotation. The header states a fact about the
     * offer once — who it was made to, when it expires, what state it has
     * reached — and each line states a fact about one thing being offered.
     *
     * The header is one row, and a line is simply a product, a quantity and a
     * price. The accounts it will post to are determined at conversion time
     * from the charts configuration, not stored on the line.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->index();
            $table->foreignUuid('contact_id')->nullable()->index();
            $table->string('document_number')->unique();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('reference_number')->nullable()->index();
            $table->date('date');
            $table->date('expiry_date')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status');
            // What this quotation became. One place, rather than copied onto every
            // line, so a quotation cannot look converted on one line and not on
            // another.
            $table->boolean('is_converted')->default(false);
            $table->timestamp('converted_at')->nullable();
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

        Schema::create('quotation_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->index();
            $table->string('product_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->text('notes')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('quotations');
    }
};
