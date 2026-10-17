<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add e_wallet to the payment_transfer_type enum columns.
     *
     * The PaymentTransferType enum gained an E_WALLET case, but the database
     * enum columns were created before that and only know the original three
     * values. This migration widens them to accept the new value.
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            $table->enum('payment_transfer_type', ['cheque', 'bank_transfer', 'e_wallet', 'cash'])
                ->default('cheque')->change();
        });

        Schema::table('payment_vouchers', function (Blueprint $table): void {
            $table->enum('payment_transfer_type', ['cheque', 'bank_transfer', 'e_wallet', 'cash'])
                ->default('cheque')->change();
        });

        Schema::table('customer_refunds', function (Blueprint $table): void {
            $table->enum('payment_transfer_type', ['cheque', 'bank_transfer', 'e_wallet', 'cash'])
                ->default('cheque')->change();
        });

        Schema::table('supplier_refunds', function (Blueprint $table): void {
            $table->enum('payment_transfer_type', ['cheque', 'bank_transfer', 'e_wallet', 'cash'])
                ->default('cheque')->change();
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            $table->enum('payment_transfer_type', ['cheque', 'bank_transfer', 'cash'])
                ->default('cheque')->change();
        });

        Schema::table('payment_vouchers', function (Blueprint $table): void {
            $table->enum('payment_transfer_type', ['cheque', 'bank_transfer', 'cash'])
                ->default('cheque')->change();
        });

        Schema::table('customer_refunds', function (Blueprint $table): void {
            $table->enum('payment_transfer_type', ['cheque', 'bank_transfer', 'cash'])
                ->default('cheque')->change();
        });

        Schema::table('supplier_refunds', function (Blueprint $table): void {
            $table->enum('payment_transfer_type', ['cheque', 'bank_transfer', 'cash'])
                ->default('cheque')->change();
        });
    }
};
