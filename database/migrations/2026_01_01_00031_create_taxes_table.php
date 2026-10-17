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
use App\Enums\TaxMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->decimal('rate', 8, 4);
            $table->enum('default_mode', array_column(TaxMode::cases(), 'value'));
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('ledgers', function (Blueprint $table): void {
            $table->uuid('tax_id')->nullable()->after('discount_amount');
            $table->string('tax_name')->nullable()->after('tax_id');
            $table->index('tax_id');
        });
    }

    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table): void {
            $table->dropIndex(['tax_id']);
            $table->dropColumn(['tax_id', 'tax_name']);
        });

        Schema::dropIfExists('taxes');
    }
};
