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
use App\Enums\ChartReportTypeEnum;
use App\Enums\ChartTotalReportTypeEnum;
use App\Enums\ChartTypeEnum;
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
        Schema::create('chart_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('calculation_basis')->default('period');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug']);
        });

        Schema::create('chart_report_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chart_report_id')
                ->constrained('chart_reports')
                ->cascadeOnDelete();
            $table->foreignUuid('parent_id')
                ->nullable()
                ->constrained('chart_report_details')
                ->nullOnDelete();
            $table->foreignUuid('chart_id')
                ->nullable()
                ->constrained('charts')
                ->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->enum(
                'chart_report_type',
                array_column(ChartReportTypeEnum::cases(), 'value')
            )->default(ChartReportTypeEnum::NONE->value);
            $table->enum(
                'chart_total_report_type',
                array_column(ChartTotalReportTypeEnum::cases(), 'value')
            )->default(ChartTotalReportTypeEnum::NONE->value);
            $table->enum(
                'chart_type',
                array_column(ChartTypeEnum::cases(), 'value')
            )->nullable();
            $table->enum(
                'chart_report_group',
                array_column(ChartReportGroupEnum::cases(), 'value')
            )->nullable();
            $table->text('formula')->nullable();
            $table->unsignedInteger('line')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['chart_report_id', 'code']);
            $table->index(['chart_report_id', 'line']);
            $table->index(['chart_report_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_report_details');
        Schema::dropIfExists('chart_reports');
    }
};
