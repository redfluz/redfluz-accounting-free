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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the money ledger tables: cash, bank and e-wallet.
     *
     * The cash ledger holds physical cash held by the business: a till drawer, a
     * petty cash tin, a float carried by whoever is out selling. It is company
     * money that has not been banked, so it is an asset and it posts to the
     * general ledger like any other asset. What makes it different from a bank
     * balance is where it sits, and that it is expected to be topped back up to a
     * figure.
     *
     * This is a separate table rather than another kind inside one ledger table
     * because cash behaves differently in ways a column cannot express. A drawer
     * is emptied and refilled on a petty cash cycle rather than drained on a
     * statement, it carries a float target, and it has to be counted. Reconciling
     * it against a bank statement is not the same job as reconciling a bank
     * account, and a report that treats them as one kind of row produces a
     * figure nobody can act on.
     *
     * The bank ledger holds the balance at a financial institution. This is the
     * one money ledger that reconciles against something the bank itself
     * produces: a statement. That is what separates it from the cash and
     * e-wallet ledgers, and why it is its own table rather than a kind column on
     * one shared ledger. The balance after each movement is stored so a statement
     * line can be matched against the ledger row it settles, which is the whole
     * purpose of keeping the two side by side.
     *
     * The e-wallet ledger holds money in a third party wallet. In Malaysia these
     * are Touch 'n Go, Boost, GrabPay and their peers, and they are ordinary
     * places to hold company money rather than somewhere exotic. This is a
     * separate table because a wallet is not a small bank account. It reconciles
     * against a statement the provider issues, but that statement behaves
     * differently in two ways that matter here. It has no concept of a cleared
     * cheque: funds settle immediately, so there is nothing to match against
     * later, and a receipt is final the moment it lands. And money moves between
     * wallets far more often than between banks, so a transfer from one wallet
     * to another is an ordinary occurrence here rather than the exception it is
     * for a bank account.
     *
     * Folding wallets into the bank ledger would make both of those look like
     * anomalies, and would leave a transfer between two wallets with no ledger
     * that describes it.
     *
     * In every one of these ledgers, rows record movements, not balances. The
     * balance at any point is the sum of the rows up to it, so the ledger cannot
     * silently disagree with itself, and a count that disagrees with the ledger
     * is a visible discrepancy rather than an overwrite.
     */
    public function up(): void
    {
        Schema::create('cash_ledgers', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            // The cash account this row belongs to.
            //
            // Not bank_id, and this table is not a bank ledger. Cash is not held
            // at a bank and does not belong to a bank record: naming the column
            // bank_id would make a till drawer look like a current account and
            // invite code that assumes the two can be handled the same way. It
            // points at the same money account record as the other ledgers, but
            // the column says what it is.
            $table->foreignUuid('cash_account_id')->constrained('cash_accounts')->restrictOnDelete();

            // Where the cash was, for a row that moved a specific amount from a
            // specific place. Nullable and denormalised on purpose: a movement is
            // a fact about when it happened, and re-pointing it later when a
            // branch is renamed or a drawer moves would rewrite history. The
            // name is stored alongside for the same reason the other ledgers
            // keep the account name rather than resolving it on every read.
            $table->string('location_name')->nullable();
            $table->foreignUuid('asset_location_id')
                ->nullable()
                ->constrained('stock_locations')
                ->nullOnDelete();

            // Everything here is money, so it carries the currency it is in.
            // Recorded as a reference rather than a code so a currency is named
            // in one place and cannot be spelled two ways in two tables.
            $table->foreignUuid('currency_id')->constrained('currencies')->restrictOnDelete();

            // The document that moved the money. Named rather than joined,
            // because the document is one of several kinds and the ledger should
            // not have to know which table holds it.
            $table->uuid('source_id')->nullable();
            $table->string('source_type', 50)->nullable();

            $table->string('document_number')->index();
            $table->date('document_date');
            // What this movement settles or corrects, where that applies.
            $table->string('reference_number')->nullable()->index();

            // The drawer balance after this movement. Stored rather than summed
            // because counting cash is the point: the counted figure and this one
            // are compared, and a running balance that had to be recomputed from
            // every prior row would be a balance nobody could check by hand.
            $table->decimal('debit_amount', 15)->default(0);
            $table->decimal('credit_amount', 15)->default(0);
            $table->decimal('balance', 15)->default(0);

            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cash_account_id', 'document_date'], 'cash_ledgers_account_date_index');
            $table->index(['source_type', 'source_id'], 'cash_ledgers_source_index');
        });

        Schema::create('bank_ledgers', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            // The bank account this row belongs to. Named for what it is rather
            // than as a generic account_id, because a cash ledger is a different
            // table with a different column and a reader should not have to open
            // the schema to know which one they are looking at.
            $table->foreignUuid('bank_account_id')->constrained('banks')->restrictOnDelete();
            $table->foreignUuid('currency_id')->constrained('currencies')->restrictOnDelete();

            // The document that moved the money: a receipt, a payment voucher, a
            // refund, or a transfer between two of the company own accounts.
            $table->uuid('source_id')->nullable();
            $table->string('source_type', 50)->nullable();

            $table->string('document_number')->index();
            $table->date('document_date');
            $table->string('reference_number')->nullable()->index();

            $table->decimal('debit_amount', 15)->default(0);
            $table->decimal('credit_amount', 15)->default(0);
            $table->decimal('balance', 15)->default(0);

            // The bank own reference for this movement, taken from a statement.
            // Kept separate from document_number because the two are unrelated:
            // the document is ours, this is theirs, and a statement is matched on
            // this one rather than on our own numbering.
            $table->string('bank_reference', 100)->nullable()->index();

            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['bank_account_id', 'document_date'], 'bank_ledgers_account_date_index');
            $table->index(['source_type', 'source_id'], 'bank_ledgers_source_index');
        });

        Schema::create('e_wallet_ledgers', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            // The e-wallet account this row belongs to. The provider itself is a
            // record reached through the account, not copied onto the row: a
            // movement is against an account at a point in time, and the provider
            // that account sits with is a property of the account rather than of
            // each movement made through it.
            $table->foreignUuid('e_wallet_account_id')->constrained('e_wallet_accounts')->restrictOnDelete();
            $table->foreignUuid('currency_id')->constrained('currencies')->restrictOnDelete();

            $table->uuid('source_id')->nullable();
            $table->string('source_type', 50)->nullable();

            $table->string('document_number')->index();
            $table->date('document_date');
            $table->string('reference_number')->nullable()->index();

            $table->decimal('debit_amount', 15)->default(0);
            $table->decimal('credit_amount', 15)->default(0);
            $table->decimal('balance', 15)->default(0);

            // The provider own reference for this movement, taken from a statement.
            $table->string('wallet_reference', 100)->nullable()->index();

            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['e_wallet_account_id', 'document_date'], 'e_wallet_ledgers_account_date_index');
            $table->index(['source_type', 'source_id'], 'e_wallet_ledgers_source_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_wallet_ledgers');
        Schema::dropIfExists('bank_ledgers');
        Schema::dropIfExists('cash_ledgers');
    }
};
