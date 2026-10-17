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

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Purchase adjustments: debit notes and credit notes issued against a supplier.
 *
 * The sales side has its own table since the sales split, and the purchase side
 * had nothing — so purchase notes had nowhere to live except the shared ledger
 * table that every document shared. This gives them the same home sales
 * adjustments have, with the same shape, so both sides of the business are
 * handled the same way and neither keeps documents in a table that is not a
 * document store.
 *
 * The subtype is which way round the note goes: a purchase_debit_note raises
 * what is owed to the supplier, a purchase_credit_note reduces it. The posting
 * differs by direction, so it is recorded on the header where it can be read
 * once rather than inferred per line.
 *
 * Existing notes are copied across rather than recreated, so numbers, audit
 * columns and the postings already filed against them survive untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_adjustments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->index();
            $table->foreignUuid('contact_id')->nullable()->index();
            $table->string('document_number')->unique();
            $table->enum('subtype', [
                LedgerSubType::PURCHASE_DEBIT_NOTE->value,
                LedgerSubType::PURCHASE_CREDIT_NOTE->value,
            ]);
            // The purchase invoice this corrects. Without it a note is a figure
            // with no origin, which is the one thing a note must not be.
            $table->string('reference_number')->nullable()->index();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->date('date');
            $table->enum('status', array_column(LedgerStatus::cases(), 'value'))
                ->default(LedgerStatus::PENDING->value);
            $table->string('attachment_path')->nullable();
            $table->decimal('subtotal', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
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
            $table->index('date');
        });

        Schema::create('purchase_adjustment_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_adjustment_id')
                ->constrained('purchase_adjustments')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->index();
            $table->string('product_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15)->default(0);
            $table->decimal('subtotal_amount', 15)->default(0);
            $table->decimal('discount_rate', 15)->default(0);
            $table->decimal('discount_amount', 15)->default(0);
            $table->decimal('tax_rate', 15)->default(0);
            $table->decimal('tax_amount', 15)->default(0);
            $table->string('tax_mode')->nullable();
            $table->decimal('line_total_amount', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->text('description')->nullable();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_adjustment_lines');
        Schema::dropIfExists('purchase_adjustments');
    }
};
