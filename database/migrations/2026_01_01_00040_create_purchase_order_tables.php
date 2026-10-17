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
use App\Enums\PurchaseOrderType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->index();
            $table->foreignUuid('contact_id')->nullable()->index();
            $table->string('document_number')->unique();
            $table->enum('purchase_type', array_column(PurchaseOrderType::cases(), 'value'))
                ->default(PurchaseOrderType::BILL->value);
            $table->string('supplier_reference_number')->nullable();
            $table->string('supplier_sales_order_number')->nullable();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->date('date');
            $table->date('expected_date')->nullable();
            $table->unsignedSmallInteger('credit_term_days')->default(30);
            $table->date('due_date')->nullable();
            $table->boolean('is_partial_received')->default(false);
            $table->boolean('is_received')->default(false);
            $table->decimal('subtotal', 15)->default(0);
            $table->decimal('tax_amount', 15)->default(0);
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
        });

        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->index();
            $table->string('product_sku')->nullable();
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->boolean('is_service')->default(false);
            $table->string('acquisition_type')->nullable()->default(null);
            $table->foreignUuid('debit_chart_id')->nullable()->index();
            $table->integer('quantity');
            $table->decimal('unit_price', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_order_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('default_credit_term_days')->default(30);
            $table->unsignedSmallInteger('default_required_days')->default(5);
            $table->unsignedSmallInteger('default_rfq_response_days')->default(30);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_order_attachments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->uuid('uploaded_by')->nullable()->index();
            $table->string('uploaded_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('goods_receipt_attachments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('document_number')->index();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->uuid('uploaded_by')->nullable()->index();
            $table->string('uploaded_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_attachments');
        Schema::dropIfExists('purchase_order_attachments');
        Schema::dropIfExists('purchase_order_settings');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
