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
use App\Enums\AcquisitionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_category_id')->constrained('product_categories')->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_category_id', 'name']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_product_id')->nullable()->index();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode', 100)->nullable();
            $table->string('mpn', 100)->nullable();
            $table->string('variant_name')->nullable();
            $table->text('variant_details')->nullable();
            $table->boolean('inherits_selling_price')->default(false);
            $table->string('image_path')->nullable();
            $table->decimal('base_price', 15)->default(0.00);
            $table->decimal('selling_price', 15)->default(0.00);
            $table->unsignedSmallInteger('warranty_months')->nullable()->default(12);
            $table->foreignUuid('supplier_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignUuid('product_type_id')->nullable()->constrained('product_types')->nullOnDelete();
            $table->uuid('default_tax_id')->nullable()->index();
            $table->uuid('default_discount_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_service')->default(false);
            $table->boolean('is_product')->default(true);
            $table->enum('default_acquisition_type', array_column(AcquisitionType::cases(), 'value'))
                ->default(AcquisitionType::STOCK->value);
            $table->decimal('reorder_level', 15, 4)->default(0);
            $table->decimal('reorder_quantity', 15, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });

        Schema::create('product_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->uuid('uploaded_by')->nullable()->index();
            $table->string('uploaded_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('product_attachments');
        Schema::dropIfExists('products');
    }
};
