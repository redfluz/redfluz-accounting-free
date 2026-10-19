<?php

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
     * Allow the transfer source type through the ledgers CHECK constraints.
     *
     * Same shape as the bank_journal widening that came before it: the ledgers
     * table bakes its source_type/subtype value lists into CHECK constraints at
     * creation time, so the TRANSFER case added for the cash transfer screens is
     * rejected on insert until the constraint is rebuilt. SQLite cannot alter a
     * CHECK in place, hence the table rebuild; MySQL ENUMs are altered directly.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ledgers MODIFY source_type ENUM('sales_invoice', 'sales_adjustment', 'receipt', 'receipt_refund', 'purchase_invoice', 'purchase_adjustment', 'payment_voucher', 'supplier_refund', 'bank_journal', 'transfer') NULL");

            return;
        }

        $this->rebuildWith(['transfer']);
    }

    /**
     * Reverse the migration.
     *
     * Fails naturally if transfer rows exist, which is the safe outcome:
     * silently dropping the constraint would orphan their meaning.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ledgers MODIFY source_type ENUM('sales_invoice', 'sales_adjustment', 'receipt', 'receipt_refund', 'purchase_invoice', 'purchase_adjustment', 'payment_voucher', 'supplier_refund', 'bank_journal') NULL");

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
