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
     * Create the receipt tables.
     *
     * A receipt is a document in its own right: it has a number, a customer, a
     * date and a figure, and it can be printed, posted, cancelled and restored.
     * Keeping those facts on one row means the document can be read without
     * deriving anything from its own double entry, which is what happens when
     * the document and the accounting entry are the same row.
     *
     * The amount sits here as total_amount rather than being summed from a child
     * table. A receipt's figure is a single agreed amount, not a total of
     * independent lines the way an invoice's is, so storing it keeps the listing
     * from having to aggregate on every page load and keeps a receipt whose
     * lines are edited to match it.
     *
     * The lines are the double entry of a receipt: one line debits the cash or
     * bank account the money landed in, the other credits the customer's
     * account. Two lines for a straight receipt, more when the money is split
     * across several accounts, which a single row per receipt could not express
     * at all.
     *
     * A line carries either a debit or a credit, never both. That is what makes
     * the two sides add to the same figure without each line having to hold an
     * amount in both directions, and it is why the posting routine can sum each
     * column independently and still balance.
     */
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table): void {
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
            // The invoice this receipt settles. A receipt settling several
            // invoices says so in its lines rather than by repeating the header.
            $table->string('reference_number')->nullable()->index();
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15)->default(0);
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
            $table->index('date');
        });

        Schema::create('receipt_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->decimal('amount', 15)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('receipt_charts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('receipt_id')->constrained('receipts')->cascadeOnDelete();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_charts');
        Schema::dropIfExists('receipt_lines');
        Schema::dropIfExists('receipts');
    }
};
