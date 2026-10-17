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
        Schema::create('sales_invoice_charts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->index();

            // Revenue side accounts
            $table->foreignUuid('receivable_id')->nullable()->comment('Accounts Receivable (debit)');
            $table->foreignUuid('revenue_id')->nullable()->comment('Sales Revenue (credit)');
            $table->foreignUuid('tax_id')->nullable()->comment('Tax Payable / Output Tax (credit)');
            $table->foreignUuid('discount_id')->nullable()->comment('Sales Discounts (debit, contra revenue)');

            // Cost side accounts
            $table->foreignUuid('cogs_id')->nullable()->comment('Cost of Goods Sold (debit)');
            $table->foreignUuid('inventory_id')->nullable()->comment('Inventory (credit)');

            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_charts');
    }
};
