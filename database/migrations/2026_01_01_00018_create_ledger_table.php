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

use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The accounts and parties.
            $table->foreignUuid('chart_id')->nullable()->constrained('charts');
            $table->foreignUuid('company_id')->nullable()->constrained('companies');
            $table->foreignUuid('currency_id')->nullable()->constrained('currencies');

            // The kind of posting.
            $table->enum('type', array_column(LedgerType::cases(), 'value'));
            $table->enum('subtype', array_column(LedgerSubType::cases(), 'value'));

            // The document.
            $table->string('document_number');
            $table->string('reference_number')->nullable();

            // When.
            $table->date('date');

            // The account and company as they were named at posting time.
            $table->string('chart_code')->nullable();
            $table->string('chart_name')->nullable();
            $table->string('company_name')->nullable();

            // What the posting was for, at document level and at line level.
            $table->text('description')->nullable();
            $table->text('line_description')->nullable();

            // The figures.
            $table->decimal('debit_amount', 15)->default(0.00);
            $table->decimal('credit_amount', 15)->default(0.00);

            $table->timestamps();
            $table->softDeletes();

            $table->index('document_number');
            $table->index('reference_number');
            $table->index('date');
            $table->index('chart_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
