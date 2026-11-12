<?php

/**
 * @noinspection ALL
 *
 * @phpstan-ignore-file
 */

use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\Finance\JournalEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the id-based reversal links with the document_original number.
     *
     * A reversing document names the document it undoes by its document number,
     * so the drill-down is a direct link and no join is needed. The id columns
     * (journal_entries.reversal_of_id/reversal_id, sales_invoices.reversal_id,
     * purchase_invoices.reversal_id, manual adjustments' reversal_journal_id)
     * are dropped, and the ledger value lists are widened to the current enums
     * so the debit/credit note subtypes and inventory deductions can post.
     *
     * SQLite bakes the enum lists into CHECK constraints at creation time and
     * cannot drop an indexed or foreign-key column in place, so each affected
     * table is rebuilt from its live definition. MySQL alters directly.
     */
    public function up(): void
    {
        $originalNumbers = $this->journalReversalPairs();

        if (DB::getDriverName() === 'mysql') {
            $this->upMysql();
        } else {
            $this->upSqlite();
        }

        foreach ($originalNumbers as $reversalId => $originalNumber) {
            JournalEntry::query()
                ->whereKey($reversalId)
                ->update(['document_original' => $originalNumber]);
        }
    }

    /**
     * Restore the previous column shape.
     *
     * The id columns come back as plain nullable columns and the value lists
     * narrow again; the number links cannot be turned back into id links, so
     * their data is not recoverable on rollback.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->downMysql();

            return;
        }

        $this->downSqlite();
    }

    /**
     * Read the existing journal reversal pairs as reversal id => original number.
     *
     * @return array<string, string> The original document numbers keyed by reversing entry id.
     */
    private function journalReversalPairs(): array
    {
        if (! Schema::hasColumn('journal_entries', 'reversal_of_id')) {
            return [];
        }

        $reversals = JournalEntry::query()
            ->whereNotNull('reversal_of_id')
            ->get(['id', 'reversal_of_id']);

        if ($reversals->isEmpty()) {
            return [];
        }

        $originals = JournalEntry::query()
            ->whereIn('id', $reversals->pluck('reversal_of_id'))
            ->pluck('document_number', 'id');

        $pairs = [];

        foreach ($reversals as $reversal) {
            /** @var JournalEntry $reversal */
            $number = $originals[$reversal->reversal_of_id] ?? null;

            if (is_string($number)) {
                $pairs[$reversal->id] = $number;
            }
        }

        return $pairs;
    }

    /**
     * Apply the change on SQLite by rebuilding each affected table.
     */
    private function upSqlite(): void
    {
        $types = array_column(LedgerType::cases(), 'value');
        $subtypes = array_column(LedgerSubType::cases(), 'value');

        foreach ([
            'ledgers',
            'ar_ledgers',
            'ap_ledgers',
            'journal_entry_charts',
            'manual_sales_adjustment_charts',
            'manual_purchase_adjustment_charts',
        ] as $table) {
            $sql = $this->definition($table);
            $sql = $this->replaceCheck($sql, 'type', $types);
            $sql = $this->replaceCheck($sql, 'subtype', $subtypes);
            $this->swap($table, $sql);
        }

        foreach (['sales', 'purchase'] as $side) {
            $table = 'manual_'.$side.'_adjustments';
            $sql = $this->definition($table);
            $sql = $this->replaceCheck($sql, 'subtype', $this->adjustmentSubtypes($side));
            $sql = $this->removeColumn($sql, 'reversal_journal_id');
            $sql = $this->addDocumentOriginal($sql);
            $this->swap($table, $sql, ['reversal_journal_id']);
        }

        $sql = $this->definition('journal_entries');
        $sql = $this->removeColumn($sql, 'reversal_of_id');
        $sql = $this->removeColumn($sql, 'reversal_id');
        $sql = $this->addDocumentOriginal($sql);
        $this->swap('journal_entries', $sql, ['reversal_of_id', 'reversal_id']);

        foreach (['sales_invoices', 'purchase_invoices'] as $table) {
            $sql = $this->definition($table);
            $sql = $this->removeColumn($sql, 'reversal_id');
            $this->swap($table, $sql, ['reversal_id']);
        }
    }

    /**
     * Apply the change on MySQL with direct alters.
     */
    private function upMysql(): void
    {
        $types = "'".implode("', '", array_column(LedgerType::cases(), 'value'))."'";
        $subtypes = "'".implode("', '", array_column(LedgerSubType::cases(), 'value'))."'";

        foreach ([
            'ledgers',
            'ar_ledgers',
            'ap_ledgers',
            'journal_entry_charts',
            'manual_sales_adjustment_charts',
            'manual_purchase_adjustment_charts',
        ] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY type ENUM({$types}) NOT NULL");
            DB::statement("ALTER TABLE {$table} MODIFY subtype ENUM({$subtypes}) NOT NULL");
        }

        foreach (['sales', 'purchase'] as $side) {
            $table = 'manual_'.$side.'_adjustments';
            $list = "'".implode("', '", $this->adjustmentSubtypes($side))."'";
            DB::statement("ALTER TABLE {$table} MODIFY subtype ENUM({$list}) NOT NULL");

            if (! Schema::hasColumn($table, 'document_original')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->string('document_original')->nullable()->after('reference_number');
                });
            }

            if (Schema::hasColumn($table, 'reversal_journal_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropForeign(['reversal_journal_id']);
                    $blueprint->dropColumn('reversal_journal_id');
                });
            }
        }

        if (! Schema::hasColumn('journal_entries', 'document_original')) {
            Schema::table('journal_entries', function (Blueprint $blueprint): void {
                $blueprint->string('document_original')->nullable()->after('reference_number');
            });
        }

        if (Schema::hasColumn('journal_entries', 'reversal_of_id')) {
            Schema::table('journal_entries', function (Blueprint $blueprint): void {
                $blueprint->dropForeign(['reversal_of_id']);
                $blueprint->dropUnique(['reversal_of_id']);
                $blueprint->dropColumn('reversal_of_id');
            });
        }

        if (Schema::hasColumn('journal_entries', 'reversal_id')) {
            Schema::table('journal_entries', function (Blueprint $blueprint): void {
                $blueprint->dropForeign(['reversal_id']);
                $blueprint->dropUnique(['reversal_id']);
                $blueprint->dropColumn('reversal_id');
            });
        }

        foreach (['sales_invoices', 'purchase_invoices'] as $table) {
            if (Schema::hasColumn($table, 'reversal_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropForeign(['reversal_id']);
                    $blueprint->dropColumn('reversal_id');
                });
            }
        }
    }

    /**
     * Reverse the change on SQLite.
     */
    private function downSqlite(): void
    {
        $types = array_values(array_filter(
            array_column(LedgerType::cases(), 'value'),
            static fn (string $value): bool => $value !== LedgerType::INVENTORY->value
        ));
        $subtypes = array_values(array_filter(
            array_column(LedgerSubType::cases(), 'value'),
            static fn (string $value): bool => ! in_array($value, [
                LedgerSubType::INVENTORY_DEDUCTION->value,
                LedgerSubType::PURCHASE_DEBIT_NOTE->value,
                LedgerSubType::PURCHASE_CREDIT_NOTE->value,
                LedgerSubType::SALES_DEBIT_NOTE->value,
                LedgerSubType::SALES_CREDIT_NOTE->value,
            ], true)
        ));

        foreach ([
            'ledgers',
            'ar_ledgers',
            'ap_ledgers',
            'journal_entry_charts',
            'manual_sales_adjustment_charts',
            'manual_purchase_adjustment_charts',
        ] as $table) {
            $sql = $this->definition($table);
            $sql = $this->replaceCheck($sql, 'type', $types);
            $sql = $this->replaceCheck($sql, 'subtype', $subtypes);
            $this->swap($table, $sql);
        }

        foreach (['sales', 'purchase'] as $side) {
            $table = 'manual_'.$side.'_adjustments';
            $sql = $this->definition($table);
            $sql = $this->replaceCheck($sql, 'subtype', array_slice($this->adjustmentSubtypes($side), 0, 3));
            $sql = $this->removeColumn($sql, 'document_original');
            $this->swap($table, $sql, ['document_original']);

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->uuid('reversal_journal_id')->nullable();
            });
        }

        $sql = $this->definition('journal_entries');
        $sql = $this->removeColumn($sql, 'document_original');
        $this->swap('journal_entries', $sql, ['document_original']);

        Schema::table('journal_entries', function (Blueprint $blueprint): void {
            $blueprint->uuid('reversal_of_id')->nullable();
            $blueprint->uuid('reversal_id')->nullable();
        });

        foreach (['sales_invoices', 'purchase_invoices'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->uuid('reversal_id')->nullable();
            });
        }
    }

    /**
     * Reverse the change on MySQL.
     */
    private function downMysql(): void
    {
        $types = "'".implode("', '", array_filter(
            array_column(LedgerType::cases(), 'value'),
            static fn (string $value): bool => $value !== LedgerType::INVENTORY->value
        ))."'";
        $subtypes = "'".implode("', '", array_filter(
            array_column(LedgerSubType::cases(), 'value'),
            static fn (string $value): bool => ! in_array($value, [
                LedgerSubType::INVENTORY_DEDUCTION->value,
                LedgerSubType::PURCHASE_DEBIT_NOTE->value,
                LedgerSubType::PURCHASE_CREDIT_NOTE->value,
                LedgerSubType::SALES_DEBIT_NOTE->value,
                LedgerSubType::SALES_CREDIT_NOTE->value,
            ], true)
        ))."'";

        foreach ([
            'ledgers',
            'ar_ledgers',
            'ap_ledgers',
            'journal_entry_charts',
            'manual_sales_adjustment_charts',
            'manual_purchase_adjustment_charts',
        ] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY type ENUM({$types}) NOT NULL");
            DB::statement("ALTER TABLE {$table} MODIFY subtype ENUM({$subtypes}) NOT NULL");
        }

        foreach (['sales', 'purchase'] as $side) {
            $table = 'manual_'.$side.'_adjustments';
            $list = "'".implode("', '", array_slice($this->adjustmentSubtypes($side), 0, 3))."'";
            DB::statement("ALTER TABLE {$table} MODIFY subtype ENUM({$list}) NOT NULL");

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('document_original');
                $blueprint->uuid('reversal_journal_id')->nullable();
            });
        }

        Schema::table('journal_entries', function (Blueprint $blueprint): void {
            $blueprint->dropColumn('document_original');
            $blueprint->uuid('reversal_of_id')->nullable();
            $blueprint->uuid('reversal_id')->nullable();
        });

        foreach (['sales_invoices', 'purchase_invoices'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->uuid('reversal_id')->nullable();
            });
        }
    }

    /**
     * The header subtypes a manual adjustment family admits, legacy values first.
     *
     * @param  string  $side  Either "sales" or "purchase".
     * @return list<string> The admitted subtype values.
     */
    private function adjustmentSubtypes(string $side): array
    {
        if ($side === 'sales') {
            return [
                LedgerSubType::INCREASE_ADJUSTMENT->value,
                LedgerSubType::DECREASE_ADJUSTMENT->value,
                LedgerSubType::MANUAL_SALES_ADJUSTMENT->value,
                LedgerSubType::SALES_DEBIT_NOTE->value,
                LedgerSubType::SALES_CREDIT_NOTE->value,
            ];
        }

        return [
            LedgerSubType::INCREASE_ADJUSTMENT->value,
            LedgerSubType::DECREASE_ADJUSTMENT->value,
            LedgerSubType::MANUAL_PURCHASE_ADJUSTMENT->value,
            LedgerSubType::PURCHASE_DEBIT_NOTE->value,
            LedgerSubType::PURCHASE_CREDIT_NOTE->value,
        ];
    }

    /**
     * Read a table's live CREATE TABLE statement.
     *
     * @param  string  $table  The table name.
     * @return string The live definition.
     */
    private function definition(string $table): string
    {
        $definition = DB::selectOne(
            "select sql from sqlite_master where type = 'table' and name = ?",
            [$table]
        );

        $sql = is_object($definition) && isset($definition->sql) && is_string($definition->sql)
            ? $definition->sql
            : '';

        abort_if($sql === '', 500, "The {$table} table definition could not be read.");

        return $sql;
    }

    /**
     * Replace one column's CHECK value list with the given values.
     *
     * @param  string  $sql  The CREATE TABLE statement to patch.
     * @param  string  $column  The checked column name.
     * @param  list<string>  $values  The admitted values.
     * @return string The patched statement.
     */
    private function replaceCheck(string $sql, string $column, array $values): string
    {
        $list = "'".implode("', '", $values)."'";
        $quoted = preg_quote($column, '/');

        return (string) preg_replace_callback(
            '/("'.$quoted.'" varchar check \("'.$quoted.'" in \()([^)]*)(\))/',
            static fn (array $matches): string => $matches[1].$list.$matches[3],
            $sql,
            1
        );
    }

    /**
     * Remove a column definition and its foreign key clause from a statement.
     *
     * @param  string  $sql  The CREATE TABLE statement to patch.
     * @param  string  $column  The column to remove.
     * @return string The patched statement.
     */
    private function removeColumn(string $sql, string $column): string
    {
        $quoted = preg_quote($column, '/');

        $sql = (string) preg_replace('/"'.$quoted.'" varchar, /', '', $sql, 1);

        return (string) preg_replace(
            '/, foreign key\("'.$quoted.'"\) references "[^"]+"\("[^"]+"\)( on delete [a-z ]+)?( on update [a-z ]+)?/',
            '',
            $sql,
            1
        );
    }

    /**
     * Add the document_original column after reference_number.
     *
     * @param  string  $sql  The CREATE TABLE statement to patch.
     * @return string The patched statement.
     */
    private function addDocumentOriginal(string $sql): string
    {
        if (str_contains($sql, '"document_original"')) {
            return $sql;
        }

        return (string) preg_replace(
            '/"reference_number" varchar, /',
            '"reference_number" varchar, "document_original" varchar, ',
            $sql,
            1
        );
    }

    /**
     * Swap a table for its patched definition, preserving rows and indexes.
     *
     * The replacement table is derived from the live definition, so later
     * columns survive the swap. Rows are copied over the columns both tables
     * share, and indexes that name a dropped column are left behind.
     *
     * @param  string  $table  The table to rebuild.
     * @param  string  $patched  The patched CREATE TABLE statement.
     * @param  list<string>  $dropped  Columns removed by the patch.
     */
    private function swap(string $table, string $patched, array $dropped = []): void
    {
        $indexes = DB::select(
            "select sql from sqlite_master where type = 'index' and tbl_name = ? and sql is not null",
            [$table]
        );

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::statement(str_replace(
                'CREATE TABLE "'.$table.'"',
                'CREATE TABLE "'.$table.'_new"',
                $patched
            ));

            $oldColumns = array_column(DB::select('PRAGMA table_info("'.$table.'")'), 'name');
            $newColumns = array_column(DB::select('PRAGMA table_info("'.$table.'_new")'), 'name');
            $columns = '"'.implode('", "', array_intersect($newColumns, $oldColumns)).'"';

            DB::statement('INSERT INTO "'.$table.'_new" ('.$columns.') SELECT '.$columns.' FROM "'.$table.'"');

            Schema::dropIfExists($table);
            DB::statement('ALTER TABLE "'.$table.'_new" RENAME TO "'.$table.'"');

            foreach ($indexes as $index) {
                if (! is_object($index) || ! isset($index->sql) || ! is_string($index->sql)) {
                    continue;
                }

                $skip = false;

                foreach ($dropped as $column) {
                    if (str_contains($index->sql, '"'.$column.'"')) {
                        $skip = true;

                        break;
                    }
                }

                if (! $skip) {
                    DB::statement($index->sql);
                }
            }
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        if (! Schema::hasTable($table)) {
            throw new RuntimeException("The {$table} table rebuild did not complete.");
        }
    }
};
