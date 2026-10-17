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

namespace App\Models\Bank;

use App\Models\ChartOfAccount\Chart;
use App\Models\Inventory\StockLocation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<CashAccount>
 *
 * @property string $id
 * @property string $chart_id
 * @property string $name
 * @property string $account_name
 * @property string|null $account_type
 * @property string|null $asset_location_id
 * @property numeric-string|null $float_amount
 * @property string $country_code
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $address
 * @property string|null $notes
 * @property bool $is_active
 * @property bool $is_default
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 *
 * @noinspection PhpUnused
 *
 * @property-read Chart $chart
 * @property-read StockLocation|null $assetLocation
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('cash_accounts', key: 'id')]
#[Fillable([
    'chart_id', 'name', 'account_name', 'account_type',
    'asset_location_id', 'float_amount',
    'country_code', 'email', 'phone', 'website', 'address', 'notes',
    'is_active', 'is_default',
])]
class CashAccount extends Model
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
            'chart_id' => 'string',
            'asset_location_id' => 'string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the chart account assigned to this cash account.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'chart_id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the stock location where this cash is kept.
     *
     * @return BelongsTo<StockLocation, $this>
     */
    public function assetLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'asset_location_id');
    }

    /**
     * Get the options for logging activity using Spatie Activitylog.
     *
     * @return LogOptions Returns configured log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
