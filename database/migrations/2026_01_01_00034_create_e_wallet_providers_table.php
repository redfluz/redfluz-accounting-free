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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the e-wallet providers table.
     *
     * The wallets a company can hold money in are a fact about the world, not
     * about this installation. Malaysia has Touch 'n Go, Boost and GrabPay;
     * another country has its own entirely, and a business operating in more
     * than one market uses several. Naming the providers in an enum would mean
     * that adding a wallet any customer already uses required a code change and
     * a release, which is the wrong shape for a list that grows on its own.
     *
     * So the provider is a record and an account points at it. Adding one is
     * data entry.
     *
     * This is deliberately not a general payment provider table. Card networks
     * and bank transfer rails are a different concern from a wallet balance the
     * company holds money in, and folding them together would make the ledger
     * for a stored balance indistinguishable from a settlement rail.
     */
    public function up(): void
    {
        Schema::create('e_wallet_providers', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            // Shown wherever the provider is picked, so it is required and named
            // rather than a code that has to be remembered.
            $table->string('name');

            // Free text rather than a country code or a fixed set. Providers
            // operate across borders and the same wallet may be usable in more
            // than one, so constraining this would be wrong more often than
            // right. It is kept because "which market" is the first question
            // asked about a new provider.
            $table->string('country', 100)->nullable();

            // Whatever the provider calls an account or wallet identifier.
            $table->string('identifier_label', 50)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // A company will not keep two records of the same wallet under
            // different names, and the name is what a person picks from.
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_wallet_providers');
    }
};
