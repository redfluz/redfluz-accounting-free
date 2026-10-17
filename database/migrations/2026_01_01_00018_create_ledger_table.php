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
use App\Enums\LedgerSourceType;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Provenance: what document raised this row.
            $table->uuid('source_id')->nullable();
            $table->enum('source_type', array_column(LedgerSourceType::cases(), 'value'))->nullable();

            // The kind of posting.
            $table->enum('type', array_column(LedgerType::cases(), 'value'));
            $table->enum('subtype', array_column(LedgerSubType::cases(), 'value'));

            // Who this posting is for.
            $table->foreignUuid('company_id')->nullable();
            $table->string('company_name')->nullable();
            $table->foreignUuid('contact_id')->nullable();

            // The document this posting belongs to.
            $table->string('document_number')->nullable();
            $table->string('reference_number')->nullable();

            // When.
            $table->date('date');
            $table->date('document_date')->nullable();

            // The accounts.
            $table->foreignUuid('debit_id')->nullable();
            $table->foreignUuid('credit_id')->nullable();
            $table->string('debit_acc')->nullable();
            $table->string('credit_acc')->nullable();
            $table->string('debit_acc_desc')->nullable();
            $table->string('credit_acc_desc')->nullable();

            // The figures.
            $table->decimal('debit_amount', 15)->default(0.00);
            $table->decimal('credit_amount', 15)->default(0.00);
            $table->decimal('total_amount', 15)->default(0.00);

            // Status, notes and description.
            $table->enum('status', array_column(LedgerStatus::cases(), 'value'))
                ->default(LedgerStatus::PENDING->value);
            $table->text('notes')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_type', 'source_id'], 'ledgers_source_index');
            $table->index('reference_number');
            $table->index('document_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
