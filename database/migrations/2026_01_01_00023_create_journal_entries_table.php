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
use App\Enums\LedgerStatus;
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

        Schema::create('journal_entry_charts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignUuid('debit_id')->nullable();
            $table->foreignUuid('credit_id')->nullable();
            $table->string('debit_acc')->nullable();
            $table->string('credit_acc')->nullable();
            $table->string('debit_acc_desc')->nullable();
            $table->string('credit_acc_desc')->nullable();
            $table->string('document_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->string('description')->nullable();
            $table->decimal('debit_amount', 15)->default(0.00);
            $table->decimal('credit_amount', 15)->default(0.00);
            $table->decimal('total_amount', 15)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index('debit_id');
            $table->index('credit_id');
            $table->index(['document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_charts');
        Schema::dropIfExists('journal_entries');
    }
};
