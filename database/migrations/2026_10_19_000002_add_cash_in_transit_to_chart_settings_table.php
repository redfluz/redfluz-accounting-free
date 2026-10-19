<?php

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
     * Add the cash in transit chart to the chart settings.
     *
     * Cash in transit is the account cash sits in between the moment it leaves a
     * drawer and the moment a bank credits it. Those are different moments: the
     * drawer is short from the first, and the bank is not yet long at the second.
     * Without somewhere for that gap to live, the money is either counted twice
     * or not at all.
     *
     * The account is a plain chart of accounts row rather than a table of its
     * own, because nothing about it is special at the storage level. It holds a
     * balance like any other asset and is set once, like any other default.
     */
    public function up(): void
    {
        Schema::table('chart_settings', function (Blueprint $table) {
            $table->uuid('cash_in_transit_chart_uuid')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('chart_settings', function (Blueprint $table) {
            $table->dropColumn(['cash_in_transit_chart_uuid']);
        });
    }
};
