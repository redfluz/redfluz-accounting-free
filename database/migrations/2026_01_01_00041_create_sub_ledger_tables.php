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
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the receivable and payable sub ledgers.
     *
     * The two tables share one shape, deliberately. A sub ledger row is the same
     * thing whoever it is about — a figure a customer or a supplier is owed
     * against one chart account, in one currency, raised by one document — so
     * the schema says so once, and a report written against one table reads the
     * other without caring. Two shapes written twice drift apart; one shape
     * written twice cannot.
     *
     * What a row holds:
     *
     * - Who. The company the figure belongs to: the customer on the receivable
     *   side, the supplier on the payable side — as a reference to its row and
     *   as the name it read when the row was written.
     * - Against which account. The chart account, as the reference its join
     *   needs and as the code, name and description it had when the row was
     *   written. A uuid is something a database reads; a report printed years
     *   later reads the snapshot, and still names the account the figure was
     *   booked against after the chart has been renamed.
     * - In which currency. Everything on these rows is money.
     * - The kind of posting. The document type and sub-type the row was raised
     *   by — the same enums the general ledger carries — so a ledger that a
     *   receipt and an invoice both write into can still tell them apart, and
     *   a report can say what raised a figure without joining back to the
     *   document.
     * - The document. Its number, and what that document settles or corrects.
     *   The number is the drill-back: every document kind that raises a sub
     *   ledger row carries a unique number, so the row reaches its document
     *   through a column it already has rather than a source pair kept in step.
     * - The movement. One row, one movement: a debit or a credit against one
     *   account, with the other side zero.
     *
     * A sub ledger row is not a document and carries no line detail — no tax, no
     * discount, no products, no contact. That belongs to the document, which is
     * where the rates and the names live; keeping a copy here would give the
     * two ways to disagree, and the ledger is the one that gets reported from.
     */
    public function up(): void
    {
        Schema::create('ar_ledgers', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignUuid('chart_id')->constrained('charts')->restrictOnDelete()->index();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete()->index();
            $table->foreignUuid('currency_id')->constrained('currencies')->restrictOnDelete()->index();

            // The kind of posting the row was raised by, as the general ledger
            // records it: a receipt and an invoice both move a customer's
            // balance, and a ledger that cannot tell them apart cannot explain
            // where a figure came from. The same enums the ledgers table
            // carries, so a sub ledger row and the general ledger rows of one
            // document name that document the same way.
            $table->enum('type', array_column(LedgerType::cases(), 'value'));
            $table->enum('subtype', array_column(LedgerSubType::cases(), 'value'));

            // What this document settles or corrects: the invoice a payment is
            // against, the invoice a note was raised on. This is the second half
            // of the drill-down and the one most easily lost in a refactor,
            // because nothing on the row needs it in order to balance and every
            // other column does. Without it a receipt that paid off two invoices
            // becomes two rows with nothing saying which was which.
            $table->string('document_number')->index();
            $table->date('date')->nullable()->index();
            $table->string('reference_number')->nullable()->index();

            // The account as it read at posting time. Nullable only because the
            // chart's own columns are: a chart whose code was never given has
            // none to snapshot, and inventing one would be worse than none.
            $table->string('chart_code')->nullable();
            $table->string('chart_name')->nullable();
            $table->string('chart_description')->nullable();

            // The company the figure belongs to, as its name read at posting
            // time. The reference above is what a join resolves; this is what
            // a report prints, and the row keeps the name it was written under
            // after the company has been renamed.
            $table->string('company_name')->nullable();

            // What the movement was for, in the words of the line that raised
            // it. The snapshot above is the account as it was booked against;
            // this is the sentence a printed ledger shows under the figure.
            // The general ledger and the money ledgers carry the same column,
            // and the ledgers keep one shape.
            $table->text('description')->nullable();

            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ap_ledgers', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignUuid('chart_id')->constrained('charts')->restrictOnDelete()->index();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete()->index();
            $table->foreignUuid('currency_id')->constrained('currencies')->restrictOnDelete()->index();

            $table->enum('type', array_column(LedgerType::cases(), 'value'));
            $table->enum('subtype', array_column(LedgerSubType::cases(), 'value'));

            $table->string('document_number')->index();
            $table->date('date')->nullable()->index();
            $table->string('reference_number')->nullable()->index();

            $table->string('chart_code')->nullable();
            $table->string('chart_name')->nullable();
            $table->string('chart_description')->nullable();
            $table->string('company_name')->nullable();

            // What the movement was for, in the words of the line that raised
            // it. The snapshot above is the account as it was booked against;
            // this is the sentence a printed ledger shows under the figure.
            // The general ledger and the money ledgers carry the same column,
            // and the ledgers keep one shape.
            $table->text('description')->nullable();

            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_ledgers');
        Schema::dropIfExists('ap_ledgers');
    }
};
