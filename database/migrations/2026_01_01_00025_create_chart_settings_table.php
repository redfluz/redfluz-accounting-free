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
    public function up(): void
    {
        Schema::create('chart_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cogs_chart_uuid')->nullable();
            $table->uuid('income_chart_uuid')->nullable();
            $table->uuid('bank_chart_uuid')->nullable();
            $table->uuid('client_chart_uuid')->nullable();
            $table->uuid('vendor_chart_uuid')->nullable();
            $table->uuid('inventory_chart_uuid')->nullable();
            $table->uuid('stock_shortage_chart_uuid')->nullable();
            $table->uuid('stock_surplus_chart_uuid')->nullable();
            $table->uuid('deposit_chart_uuid')->nullable();
            $table->uuid('input_tax_chart_uuid')->nullable();
            $table->uuid('output_tax_chart_uuid')->nullable();
            $table->uuid('retained_earnings_chart_uuid')->nullable();
            $table->uuid('disposal_gain_chart_uuid')->nullable();
            $table->uuid('disposal_loss_chart_uuid')->nullable();
            $table->uuid('employee_advance_chart_uuid')->nullable();
            $table->text('retained_earnings_formula')->nullable();
            $table->date('closing_date')->nullable();
            $table->integer('start_finance_period_month')->default(12);
            $table->boolean('strict_journal_posting')->default(0);
            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_settings');
    }
};
