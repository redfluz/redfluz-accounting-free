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
     * Create the e-wallet accounts table.
     *
     * Money held in a third-party wallet or payment app. Separate from banks
     * because a wallet is not a bank account, and separate from cash because a
     * wallet reconciles against a provider statement rather than a physical
     * count.
     */
    public function up(): void
    {
        Schema::create('e_wallet_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chart_id')->unique()->constrained('charts')->restrictOnDelete();
            $table->string('name');
            $table->string('account_name');
            $table->string('account_number')->nullable();
            $table->string('account_type', 100)->nullable();
            // Which e-wallet provider this account is with. A record rather than
            // an enum value because the set of providers is a fact about the
            // world and differs by market.
            $table->foreignUuid('e_wallet_provider_id')
                ->nullable()
                ->constrained('e_wallet_providers')
                ->nullOnDelete();
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
        Schema::dropIfExists('e_wallet_accounts');
    }
};
