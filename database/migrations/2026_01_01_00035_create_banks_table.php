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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the banks table.
     *
     * Holds only real bank accounts: money at a financial institution. Cash
     * drawers and e-wallets are separate tables because neither is a bank
     * account, and sharing one table with a discriminator column made every
     * query carry a filter that the schema itself could enforce.
     */
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chart_id')->unique()->constrained('charts')->restrictOnDelete();
            $table->string('name');
            $table->string('account_name');
            $table->string('account_number')->nullable();
            $table->string('account_type', 100)->nullable();
            $table->string('branch_name')->nullable();
            $table->string('bank_code', 50)->nullable();
            $table->string('swift_code', 11)->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('routing_number', 50)->nullable();
            $table->char('country_code', 2)->default('MY');
            $table->string('email')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_number', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
