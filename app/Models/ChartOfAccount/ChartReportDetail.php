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

namespace App\Models\ChartOfAccount;

use App\Enums\ChartReportGroupEnum;
use App\Enums\ChartReportTypeEnum;
use App\Enums\ChartTotalReportTypeEnum;
use App\Enums\ChartTypeEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use NXP\MathExecutor;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;
use Throwable;

/**
 * @mixin Builder<ChartReportDetail>
 *
 * @property string $id
 * @property string $chart_report_id
 * @property string|null $parent_id
 * @property string|null $chart_id
 * @property string $code
 * @property string $name
 * @property ChartReportTypeEnum $chart_report_type
 * @property ChartTotalReportTypeEnum $chart_total_report_type
 * @property ChartTypeEnum|null $chart_type
 * @property ChartReportGroupEnum|null $chart_report_group
 * @property string|null $formula
 * @property int $line
 * @property string|null $notes
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read ChartReport|null $report
 * @property-read ChartReportDetail|null $parent
 * @property-read Collection<int, ChartReportDetail> $children
 * @property-read Chart|null $chart
 *
 * @noinspection PhpUnused
 *
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('chart_report_details', key: 'id')]
#[Fillable([
    'chart_report_id',
    'parent_id',
    'chart_id',
    'code',
    'name',
    'chart_report_type',
    'chart_total_report_type',
    'chart_type',
    'chart_report_group',
    'formula',
    'line',
    'notes',
])]
class ChartReportDetail extends Model
{
    use HasActivity, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chart_report_id' => 'string',
            'parent_id' => 'string',
            'chart_id' => 'string',
            'code' => 'string',
            'name' => 'string',
            'chart_report_type' => ChartReportTypeEnum::class,
            'chart_total_report_type' => ChartTotalReportTypeEnum::class,
            'chart_type' => ChartTypeEnum::class,
            'chart_report_group' => ChartReportGroupEnum::class,
            'formula' => 'string',
            'line' => 'integer',
            'notes' => 'string',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define an inverse relationship with the report template.
     *
     * @return BelongsTo<ChartReport, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(ChartReport::class, 'chart_report_id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define an inverse relationship with the parent report row.
     *
     * @return BelongsTo<ChartReportDetail, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Define a one-to-many relationship.
     * Define a one-to-many relationship with child report rows.
     *
     * @return HasMany<ChartReportDetail, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('line');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define an inverse relationship with a specific chart account.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'chart_id');
    }

    /**
     * Validate tag syntax and mathematical integrity without using eval.
     *
     * @param  string|null  $formula  The report formula to validate.
     * @return bool Returns true when the formula is valid.
     */
    public function validateFormula(?string $formula): bool
    {
        if ($formula === null || mb_trim($formula) === '') {
            return false;
        }

        $temporaryFormula = preg_replace_callback(
            '/\{(\w+):([^{}]+)}/',
            function (array $matches): string {
                $allowedGroups = ['CHART', 'CAT', 'TYPE', 'SUM_TYPE', 'GROUP', 'SUM_GROUP', 'ROW'];

                return in_array(mb_strtoupper($matches[1]), $allowedGroups, true)
                    ? '1'
                    : 'INVALID';
            },
            $formula
        );

        if ($temporaryFormula === null || str_contains($temporaryFormula, 'INVALID')) {
            return false;
        }

        if (preg_match('/[^0-9+\-*\/().\s]/', $temporaryFormula) === 1) {
            return false;
        }

        try {
            $executor = new MathExecutor;
            $executor->execute($temporaryFormula);

            return true;
        } catch (Throwable $exception) {
            logger()->error($exception);

            return false;
        }
    }

    /**
     * Get the options for logging activity using Spatie Activitylog.
     *
     * @return LogOptions Returns configured log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }
}
