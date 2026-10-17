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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow the bank_journal subtype through the ledgers CHECK constraints.
     *
     * The ledgers table bakes its source_type/subtype value lists into CHECK
     * constraints at creation time, so the BANK_JOURNAL enum case added for
     * bank journals is rejected on insert. SQLite cannot alter a CHECK in
     * place, hence the table rebuild below; MySQL ENUMs are altered directly.
     * Both paths only ever widen the lists, never narrow them.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ledgers MODIFY source_type ENUM('sales_invoice', 'sales_adjustment', 'receipt', 'receipt_refund', 'purchase_invoice', 'purchase_adjustment', 'payment_voucher', 'supplier_refund', 'bank_journal') NULL");
            DB::statement("ALTER TABLE ledgers MODIFY subtype ENUM('sales_invoice', 'manual_sales_invoice', 'purchase_invoice', 'receipt', 'payment_voucher', 'journal', 'stock_variance', 'increase_adjustment', 'decrease_adjustment', 'purchase_debit_note', 'purchase_credit_note', 'receipt_refund', 'supplier_refund', 'refund', 'transfer', 'withdrawal', 'bank_journal') NOT NULL");

            return;
        }

        $this->rebuildWith(['bank_journal']);
    }

    /**
     * Reverse the migration.
     *
     * Fails naturally if bank_journal rows exist, which is the safe outcome:
     * silently dropping the constraint would orphan their meaning.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ledgers MODIFY source_type ENUM('sales_invoice', 'sales_adjustment', 'receipt', 'receipt_refund', 'purchase_invoice', 'purchase_adjustment', 'payment_voucher', 'supplier_refund') NULL");
            DB::statement("ALTER TABLE ledgers MODIFY subtype ENUM('sales_invoice', 'manual_sales_invoice', 'purchase_invoice', 'receipt', 'payment_voucher', 'journal', 'stock_variance', 'increase_adjustment', 'decrease_adjustment', 'purchase_debit_note', 'purchase_credit_note', 'receipt_refund', 'supplier_refund', 'refund', 'transfer', 'withdrawal') NOT NULL");

            return;
        }

        $this->rebuildWith([]);
    }

    /**
     * Rebuild the ledgers table with the given extra CHECK values.
     *
     * The replacement table is derived from the live definition, so later
     * columns and indexes survive the swap. Only the two value lists change.
     *
     * @param  list<string>  $extra  Extra values admitted by both CHECKs.
     */
    private function rebuildWith(array $extra): void
    {
        $definition = DB::selectOne(
            "select sql from sqlite_master where type = 'table' and name = 'ledgers'"
        );

        $sql = is_object($definition) && isset($definition->sql) && is_string($definition->sql)
            ? $definition->sql
            : '';

        abort_if($sql === '', 500, 'The ledgers table definition could not be read.');

        $patched = $sql;
        foreach (['source_type', 'subtype'] as $column) {
            $patched = (string) preg_replace_callback(
                '/("'.preg_quote($column, '/').'" varchar check \("'.preg_quote($column, '/').'" in \()([^)]*)(\))/',
                static function (array $matches) use ($extra): string {
                    $values = $matches[2];

                    foreach ($extra as $value) {
                        if (! str_contains($values, "'{$value}'")) {
                            $values .= ", '{$value}'";
                        }
                    }

                    return $matches[1].$values.$matches[3];
                },
                $patched,
                1
            );
        }

        $indexes = DB::select("select sql from sqlite_master where type = 'index' and tbl_name = 'ledgers' and sql is not null");

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::statement(str_replace('CREATE TABLE "ledgers"', 'CREATE TABLE "ledgers_new"', $patched));
            DB::statement('INSERT INTO "ledgers_new" SELECT * FROM "ledgers"');
            Schema::dropIfExists('ledgers');
            DB::statement('ALTER TABLE "ledgers_new" RENAME TO "ledgers"');

            foreach ($indexes as $index) {
                if (is_object($index) && isset($index->sql) && is_string($index->sql)) {
                    DB::statement($index->sql);
                }
            }
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        if (! Schema::hasTable('ledgers')) {
            throw new RuntimeException('The ledgers table rebuild did not complete.');
        }
    }
};
