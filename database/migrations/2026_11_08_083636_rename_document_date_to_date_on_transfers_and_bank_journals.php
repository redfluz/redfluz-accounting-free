<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Standardize document date columns to `date` across the system.
     */
    public function up(): void
    {
        if (Schema::hasColumn('transfers', 'document_date') && ! Schema::hasColumn('transfers', 'date')) {
            Schema::table('transfers', function (Blueprint $table): void {
                $table->renameColumn('document_date', 'date');
            });
        }

        if (Schema::hasColumn('bank_journals', 'document_date') && ! Schema::hasColumn('bank_journals', 'date')) {
            Schema::table('bank_journals', function (Blueprint $table): void {
                $table->renameColumn('document_date', 'date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transfers', 'date') && ! Schema::hasColumn('transfers', 'document_date')) {
            Schema::table('transfers', function (Blueprint $table): void {
                $table->renameColumn('date', 'document_date');
            });
        }

        if (Schema::hasColumn('bank_journals', 'date') && ! Schema::hasColumn('bank_journals', 'document_date')) {
            Schema::table('bank_journals', function (Blueprint $table): void {
                $table->renameColumn('date', 'document_date');
            });
        }
    }
};
