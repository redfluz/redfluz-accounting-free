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
use App\Enums\DiscountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('type', array_column(DiscountType::cases(), 'value'));
            $table->decimal('value', 15, 4);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('ledgers', function (Blueprint $table): void {
            $table->uuid('discount_id')->nullable()->after('subtotal_amount');
            $table->string('discount_name')->nullable()->after('discount_id');
            $table->string('discount_type', 20)->nullable()->after('discount_name');
            $table->decimal('discount_value', 15, 4)->default(0.0000)->after('discount_type');
            $table->index('discount_id');
        });
    }

    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table): void {
            $table->dropIndex(['discount_id']);
            $table->dropColumn(['discount_id', 'discount_name', 'discount_type', 'discount_value']);
        });

        Schema::dropIfExists('discounts');
    }
};
