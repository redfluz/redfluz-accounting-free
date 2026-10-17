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
use App\Models\Bank\Bank;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<Chart>
 *
 * @property string $id
 * @property string $chart_type_id
 * @property ChartReportGroupEnum|null $report_group
 * @property string|null $code
 * @property string|null $name
 * @property string|null $description
 * @property bool $isBank
 * @property bool $isDebtor
 * @property bool $isCreditor
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read ChartType|null $chartType
 * @property-read Bank|null $bank
 *
 * @noinspection PhpUnused
 *
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('charts', key: 'id')]
#[Fillable([
    'chart_type_id',
    'report_group',
    'code',
    'name',
    'description',
    'isBank',
    'isDebtor',
    'isCreditor',
])]
class Chart extends Model
{
    use HasActivity,HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chart_type_id' => 'string',
            'report_group' => ChartReportGroupEnum::class,
            'code' => 'string',
            'name' => 'string',
            'description' => 'string',
            'isBank' => 'boolean',
            'isDebtor' => 'boolean',
            'isCreditor' => 'boolean',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the chart type that this chart belongs to.
     *
     * @return BelongsTo<ChartType, $this>
     */
    public function chartType(): BelongsTo
    {
        return $this->belongsTo(ChartType::class, 'chart_type_id');
    }

    /**
     * Define a one-to-one relationship.
     * Define the bank master record assigned to this chart account.
     *
     * @return HasOne<Bank, $this>
     */
    public function bank(): HasOne
    {
        return $this->hasOne(Bank::class, 'chart_id');
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
