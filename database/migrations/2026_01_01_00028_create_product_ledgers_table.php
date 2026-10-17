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
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_ledgers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->foreignUuid('stock_location_id')
                ->constrained('stock_locations')->restrictOnDelete();
            $table->string('stock_location_name');
            $table->uuid('company_id')->nullable();
            $table->uuid('source_entry_id')->nullable();
            $table->uuid('cogs_ledger_id')->nullable();
            $table->uuid('inventory_ledger_id')->nullable();
            $table->uuid('variance_ledger_id')->nullable()->index();
            $table->string('product_sku');
            $table->string('product_name');
            $table->string('company_name')->nullable();
            $table->string('document_number');
            $table->string('reference_number')->nullable();
            $table->string('source_type');
            $table->enum(
                'direction',
                array_column(ProductMovementDirection::cases(), 'value')
            );
            $table->enum(
                'stock_bucket',
                array_column(ProductStockBucket::cases(), 'value')
            )->default(ProductStockBucket::SALEABLE->value);
            $table->date('date');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->decimal('total_cost', 15, 2)->default(0.00);
            $table->enum(
                'status',
                array_column(ProductMovementStatus::cases(), 'value')
            )->default(ProductMovementStatus::PENDING->value);
            $table->timestamp('activated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index(['stock_location_id', 'product_id', 'status']);
            $table->index('company_id');
            $table->index('source_entry_id');
            $table->index(['document_number', 'source_type']);
            $table->index(['product_id', 'status', 'date']);
            $table->index('stock_bucket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ledgers');
    }
};
