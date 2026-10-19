<?php

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
    /**
     * Create the transfers table.
     *
     * One row is one movement of money between a cash in transit account and one
     * money account. There are two directions and they are the two halves of a
     * banker's deposit, which happen at different times and answer to different
     * people:
     *
     *   out  a drawer hands its surplus to cash in transit. The money is counted
     *        out of the drawer and is nobody's yet.
     *   in   cash in transit hands to a bank account, once the bank says it has
     *        arrived. Only now is it the business's money at the bank.
     *
     * A row names exactly one money account, never several. Combining three
     * drawers into one transfer would produce a single entry that no single
     * drawer agrees with, so a count of any one drawer could not be reconciled
     * against it.
     *
     * The transit chart is stored per row rather than read from chart settings
     * at posting time. Settings hold the default a new transfer starts from, but
     * a transfer posted last month was posted against the account that was set
     * then, and re-reading the setting later would silently restate it.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_number')->unique();

            // 'out' is a drawer to transit, 'in' is transit to a bank account.
            $table->string('direction', 3);

            // Exactly one of these is set, and which one is set follows from the
            // direction. Nullable rather than a separate table per direction so
            // that both halves of a deposit are one document shape and one set of
            // rules, with the direction saying which half this is.
            $table->foreignUuid('cash_account_id')
                ->nullable()
                ->constrained('cash_accounts')
                ->restrictOnDelete();
            $table->foreignUuid('bank_account_id')
                ->nullable()
                ->constrained('banks')
                ->restrictOnDelete();

            // The account holding cash between the drawer and the bank.
            $table->foreignUuid('transit_chart_id')->constrained('charts')->restrictOnDelete();

            $table->date('document_date');
            $table->decimal('amount', 15, 2);
            $table->string('reference_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'cancelled', 'posted'])->default('pending');

            $table->string('created_by')->nullable();
            $table->string('created_name')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('updated_name')->nullable();
            $table->string('deleted_by')->nullable();
            $table->string('deleted_name')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('approved_name')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->string('cancelled_name')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('undone_by')->nullable();
            $table->string('undone_name')->nullable();
            $table->timestamp('undone_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['direction', 'status'], 'transfers_direction_status_index');
            $table->index('document_date', 'transfers_document_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
