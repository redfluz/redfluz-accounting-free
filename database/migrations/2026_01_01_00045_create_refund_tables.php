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

/**
 * @noinspection ALL
 *
 * @phpstan-ignore-file
 */
use App\Enums\LedgerStatus;
use App\Enums\PaymentTransferType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split the refunds table into one table per kind.
     *
     * A customer refund and a supplier refund are mirror images: the same document
     * with the direction of the money reversed. Sharing a table meant every query
     * against one kind had to carry a refund_type filter, and a query that forgot
     * it returned the other kind's documents without complaint. Nothing in the
     * database stopped that, because the column was nullable in practice and no
     * constraint tied a row's kind to the ledger it posted into.
     *
     * So the kind moves from a column into the table itself. Two tables means a
     * supplier refund physically cannot appear in a customer refund query, so the
     * filter can no longer be forgotten, and refund_type has nothing left to say.
     * The lines split with their parents rather than staying polymorphic, for the
     * same reason: a line that could name either parent could name the wrong one.
     */
    public function up(): void
    {
        Schema::create('customer_refunds', function (Blueprint $table): void {
            $this->documentColumns($table);
        });

        Schema::create('supplier_refunds', function (Blueprint $table): void {
            $this->documentColumns($table);
        });

        Schema::create('customer_refund_lines', function (Blueprint $table): void {
            $this->lineColumns($table, 'customer_refunds');
        });

        Schema::create('supplier_refund_lines', function (Blueprint $table): void {
            $this->lineColumns($table, 'supplier_refunds');
        });

        Schema::create('customer_refund_charts', function (Blueprint $table): void {
            $this->chartColumns($table, 'customer_refunds');
        });

        Schema::create('supplier_refund_charts', function (Blueprint $table): void {
            $this->chartColumns($table, 'supplier_refunds');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_refund_charts');
        Schema::dropIfExists('customer_refund_charts');
        Schema::dropIfExists('supplier_refund_lines');
        Schema::dropIfExists('customer_refund_lines');
        Schema::dropIfExists('supplier_refunds');
        Schema::dropIfExists('customer_refunds');
    }

    /**
     * The columns a refund document carries, on whichever table holds it.
     */
    private function documentColumns(Blueprint $table): void
    {
        $table->uuid('id')->primary();
        $table->foreignUuid('company_id')->index();
        $table->foreignUuid('contact_id')->nullable()->index();
        $table->string('document_number')->unique();
        $table->string('company_name');
        $table->string('contact_name')->nullable();
        $table->date('date');
        $table->enum('status', array_column(LedgerStatus::cases(), 'value'))
            ->default(LedgerStatus::PENDING->value);
        $table->enum('payment_transfer_type', array_column(PaymentTransferType::cases(), 'value'))
            ->nullable();
        // The receipt or payment being refunded. This is the refund's reason for
        // existing: without it a refund is just money leaving with no record of
        // which earlier document it undoes.
        $table->string('reference_number')->nullable()->index();
        $table->string('attachment_path')->nullable();
        $table->text('notes')->nullable();
        $table->decimal('total_amount', 15)->default(0);
        // Who raised this document. The name is a snapshot taken at the moment it
        // was created, so the record of who prepared a document survives that user
        // being renamed or removed. created_at alone cannot say who did it.
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
    }

    /**
     * The columns one leg of a refund's double entry carries.
     */
    private function lineColumns(Blueprint $table, string $parent): void
    {
        $table->uuid('id')->primary();
        $table->foreignUuid('refund_id')->constrained($parent)->cascadeOnDelete();
        $table->decimal('amount', 15)->default(0);
        $table->text('description')->nullable();
        $table->timestamps();
        $table->softDeletes();
    }

    /**
     * The chart of accounts a refund posts to, on whichever table holds it.
     */
    private function chartColumns(Blueprint $table, string $parent): void
    {
        $table->uuid('id')->primary();
        $table->foreignUuid('refund_id')->constrained($parent)->cascadeOnDelete();
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
    }
};
