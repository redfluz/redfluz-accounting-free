<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trim stray time components off the date only columns.
 *
 * Documents created through a form that submits a full timestamp store
 * "2026-10-16 00:00:00" while goods receipts store "2026-10-16". These columns
 * are read as text, so on SQLite the two spellings do not compare as the same
 * day: the shorter string sorts first. That mis-orders same day movements and
 * makes the running balance on the product screen wrong.
 */
return new class extends Migration
{
    /**
     * The columns that hold a calendar date and nothing more.
     *
     * @var list<string>
     */
    private array $columns = [
        'sales_invoices.date',
        'quotations.date',
        'product_ledgers.date',
        'ledgers.date',
        'ledgers.document_date',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->columns as $column) {
            [$table, $name] = explode('.', $column);

            DB::table($table)
                ->whereNotNull($name)
                ->whereRaw("LENGTH({$name}) > 10")
                ->update([$name => DB::raw("SUBSTR({$name}, 1, 10)")]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * The time component is not recoverable once trimmed, so this is a no-op.
     */
    public function down(): void
    {
        //
    }
};
