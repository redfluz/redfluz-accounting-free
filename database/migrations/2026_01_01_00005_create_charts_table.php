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
use App\Enums\ChartReportGroupEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('charts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chart_type_id')
                ->constrained('chart_types')
                ->onDelete('cascade');
            $table->enum(
                'report_group',
                array_column(ChartReportGroupEnum::cases(), 'value')
            )->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            // What the account is for, in the accountant's own words. Carried by
            // the chart so a report can print it beside the code and name, and
            // snapshotted onto the sub ledger rows so those keep saying it after
            // the account has been rewritten.
            $table->string('description')->nullable();
            $table->boolean('isBank')->default(false);
            $table->boolean('isDebtor')->default(false);
            $table->boolean('isCreditor')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['report_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charts');
    }
};
