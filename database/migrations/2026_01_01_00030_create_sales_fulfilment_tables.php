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
use App\Enums\DeliveryOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\ShippingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_ledgers', function (Blueprint $table): void {
            $table->string('source_type')->change();
        });

        if (! Schema::hasTable('sales_orders')) {
            Schema::create('sales_orders', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('company_id')->nullable()->index();
                $table->foreignUuid('contact_id')->nullable()->index();
                $table->foreignUuid('quotation_id')->nullable()->constrained('quotations')->nullOnDelete()->index();
                $table->string('document_number')->unique();
                $table->string('customer_reference_number')->nullable();
                $table->string('company_name');
                $table->string('contact_name')->nullable();
                $table->date('order_date');
                $table->date('expected_delivery_date')->nullable();
                $table->enum('status', array_column(SalesOrderStatus::cases(), 'value'))
                    ->default(SalesOrderStatus::DRAFT->value);
                $table->decimal('subtotal', 15)->default(0);
                $table->decimal('total_amount', 15)->default(0);
                $table->string('attachment_path')->nullable();
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
        }

        if (! Schema::hasTable('sales_order_lines')) {
            Schema::create('sales_order_lines', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
                $table->foreignUuid('product_id')->nullable()->index();
                $table->string('product_sku')->nullable();
                $table->string('product_name');
                $table->text('description')->nullable();
                $table->boolean('is_service')->default(false);
                $table->decimal('ordered_quantity', 15, 4);
                $table->decimal('delivered_quantity', 15, 4)->default(0);
                $table->decimal('unit_price', 15)->default(0);
                $table->unsignedSmallInteger('warranty_months')->nullable()->default(12);
                $table->decimal('subtotal_amount', 15)->default(0);
                $table->uuid('discount_id')->nullable()->index();
                $table->string('discount_name')->nullable();
                $table->string('discount_type')->nullable();
                $table->decimal('discount_value', 15, 4)->default(0);
                $table->decimal('discount_rate', 8, 4)->default(0);
                $table->decimal('discount_amount', 15)->default(0);
                $table->uuid('tax_id')->nullable()->index();
                $table->string('tax_name')->nullable();
                $table->decimal('tax_rate', 8, 4)->default(0);
                $table->decimal('tax_amount', 15)->default(0);
                $table->string('tax_mode')->nullable();
                $table->decimal('line_total_amount', 15)->default(0);
                $table->decimal('total_amount', 15)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('delivery_orders')) {
            Schema::create('delivery_orders', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
                $table->foreignUuid('company_address_id')->nullable()->constrained('company_addresses')->nullOnDelete();
                $table->foreignUuid('stock_location_id')->constrained('stock_locations')->restrictOnDelete();
                $table->foreignUuid('courier_company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('document_number')->unique();
                $table->string('stock_location_name');
                $table->date('delivery_date');
                $table->string('recipient_name');
                $table->string('recipient_phone')->nullable();
                $table->text('delivery_address');
                $table->string('courier')->nullable();
                $table->string('tracking_number')->nullable();
                $table->date('shipping_date')->nullable();
                $table->enum('shipping_status', array_column(ShippingStatus::cases(), 'value'))
                    ->default(ShippingStatus::PENDING->value);
                $table->enum('status', array_column(DeliveryOrderStatus::cases(), 'value'))
                    ->default(DeliveryOrderStatus::DRAFT->value);
                $table->string('attachment_path')->nullable();
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
                $table->uuid('posted_by')->nullable();
                $table->string('posted_name')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::create('delivery_order_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignUuid('sales_order_line_id')->constrained('sales_order_lines')->restrictOnDelete();
            $table->foreignUuid('product_id')->index();
            $table->string('product_sku');
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15)->default(0);
            $table->decimal('unit_cost', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_lines');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');

    }
};
