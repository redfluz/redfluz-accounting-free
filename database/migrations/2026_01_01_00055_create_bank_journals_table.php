<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the bank journals table.
     *
     * Bank journals are for first-time setup only (e.g. opening balances, initial
     * data migration). They are not intended for ongoing daily transactions. Regular
     * money movements come from posted documents: receipts, payment vouchers,
     * invoices, and other operational documents that write to the ledgers directly.
     *
     * A bank journal is a document in its own right: it has a number, one money
     * account, a direction, a date and a figure, and it can be posted, cancelled
     * and restored. It moves money in or out of a single bank, cash or e-wallet
     * account against one offsetting chart account, which is what an opening
     * balance or a money adjustment is.
     *
     * The amount sits here as a single figure rather than being summed from child
     * rows. A bank journal is one movement across two accounts, not a total of
     * independent lines the way an invoice's is, so there is nothing to aggregate.
     */
    public function up(): void
    {
        Schema::create('bank_journals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // A bank journal can move any of the three account types. Exactly
            // one of these is set per row; the other two are null. Three
            // nullable FKs rather than a polymorphic pair so each column carries
            // a real foreign key constraint.
            $table->foreignUuid('bank_account_id')->nullable()->constrained('banks')->restrictOnDelete()->index();
            $table->foreignUuid('cash_account_id')->nullable()->constrained('cash_accounts')->restrictOnDelete()->index();
            $table->foreignUuid('e_wallet_account_id')->nullable()->constrained('e_wallet_accounts')->restrictOnDelete()->index();
            $table->string('direction', 3);
            $table->string('document_number')->unique();
            $table->date('document_date');
            $table->foreignUuid('offset_chart_id')->constrained('charts')->restrictOnDelete()->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', array_column(LedgerStatus::cases(), 'value'))
                ->default(LedgerStatus::PENDING->value);
            // Who raised this document. The name is a snapshot taken at the
            // moment it was created, so the record of who prepared a document
            // survives that user being renamed or removed. created_at alone
            // cannot say who did it.
            $table->uuid('created_by')->nullable();
            $table->string('created_name')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->string('updated_name')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->string('deleted_name')->nullable();
            // Who decided the fate of this document: approved it, cancelled it,
            // or undid a cancellation. A uuid, the name at the time, and when it
            // happened, so the record of a decision survives the user who made it
            // being renamed or removed.
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
            $table->index('document_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_journals');
    }
};
