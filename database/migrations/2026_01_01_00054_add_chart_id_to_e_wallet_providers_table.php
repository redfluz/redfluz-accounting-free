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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bind an e-wallet provider to its chart of account.
     *
     * A provider is the issuer of wallets the company holds money in, and that
     * balance has to sit somewhere on the ledger. The binding is the chart the
     * provider's wallets are reported under, so a provider without one is a
     * name with no account behind it.
     *
     * The column is nullable because the providers were seeded before any
     * binding existed, and removing a chart must not remove the provider that
     * pointed at it: its accounts are still on the ledger and still have to be
     * readable. So the reference is set to null instead.
     */
    public function up(): void
    {
        Schema::table('e_wallet_providers', function (Blueprint $table): void {
            $table->foreignUuid('chart_id')
                ->nullable()
                ->constrained('charts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('e_wallet_providers', function (Blueprint $table): void {
            $table->dropForeign(['chart_id']);
            $table->dropColumn('chart_id');
        });
    }
};
