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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the cash accounts table.
     *
     * Physical cash held by the business: a till drawer, a petty cash tin, cash
     * in hand. Separate from banks because a drawer is not a bank account, and
     * separate from e-wallets because cash is counted, not reconciled against a
     * provider statement.
     */
    public function up(): void
    {
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chart_id')->unique()->constrained('charts')->restrictOnDelete();
            $table->string('name');
            $table->string('account_name');
            $table->string('account_type', 100)->nullable();
            // Where physical cash is kept. A drawer or petty cash tin is in a
            // specific shop or office, and that is what a cash count is
            // performed against.
            $table->foreignUuid('asset_location_id')
                ->nullable()
                ->constrained('stock_locations')
                ->nullOnDelete();
            // What the drawer is meant to hold. A float target: the drawer is
            // topped back up to this figure and held there.
            $table->decimal('float_amount', 15)->nullable();
            $table->char('country_code', 2)->default('MY');
            $table->string('email')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_accounts');
    }
};
