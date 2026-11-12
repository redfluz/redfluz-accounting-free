<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add money-account columns to receipts.
     *
     * A receipt records money received into a specific bank, cash, or e-wallet
     * account depending on the transfer type, matching payment vouchers/refunds.
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            $table->foreignUuid('bank_account_id')
                ->nullable()
                ->after('company_id')
                ->constrained('banks')
                ->restrictOnDelete();
            $table->foreignUuid('cash_account_id')
                ->nullable()
                ->after('bank_account_id')
                ->constrained('cash_accounts')
                ->restrictOnDelete();
            $table->foreignUuid('e_wallet_account_id')
                ->nullable()
                ->after('cash_account_id')
                ->constrained('e_wallet_accounts')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('e_wallet_account_id');
            $table->dropConstrainedForeignId('cash_account_id');
            $table->dropConstrainedForeignId('bank_account_id');
        });
    }
};
