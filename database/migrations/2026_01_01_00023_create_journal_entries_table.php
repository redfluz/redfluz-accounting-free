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
use App\Enums\Finance\LedgerStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reversal_of_id')
                ->nullable()
                ->unique()
                ->constrained('journal_entries')
                ->nullOnDelete();
            $table->string('document_number')->unique();
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->decimal('total_debit', 15)->default(0.00);
            $table->decimal('total_credit', 15)->default(0.00);
            $table->enum(
                'status',
                array_column(LedgerStatus::cases(), 'value')
            )->default(LedgerStatus::PENDING->value);
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

        // Each chart line is one single-sided entry: one account, one amount,
        // debit or credit, never both. A journal's balance is the sum of its
        // lines, and the posting routine copies each line straight into the
        // ledger, so the line carries the same standard columns a ledger row
        // carries rather than a debit side and a credit side side by side.
        Schema::create('journal_entry_charts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignUuid('chart_id')->nullable()->constrained('charts');
            $table->foreignUuid('company_id')->nullable()->constrained('companies');
            $table->foreignUuid('currency_id')->nullable()->constrained('currencies');
            $table->enum('type', array_column(LedgerType::cases(), 'value'));
            $table->enum('subtype', array_column(LedgerSubType::cases(), 'value'));
            $table->string('document_number');
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->string('chart_code')->nullable();
            $table->string('chart_name')->nullable();
            $table->string('company_name')->nullable();
            $table->text('description')->nullable();
            $table->text('line_description')->nullable();
            $table->decimal('debit_amount', 15)->default(0.00);
            $table->decimal('credit_amount', 15)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index('journal_entry_id');
            $table->index('document_number');
            $table->index('reference_number');
            $table->index('date');
            $table->index('chart_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_charts');
        Schema::dropIfExists('journal_entries');
    }
};
