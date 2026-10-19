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

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use NXP\MathExecutor;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;
use Throwable;

/**
 * @mixin Builder<ChartSetting>
 *
 * @property string $id
 * @property string|null $cogs_chart_uuid
 * @property string|null $income_chart_uuid
 * @property string|null $bank_chart_uuid
 * @property string|null $cash_chart_uuid
 * @property string|null $cash_in_transit_chart_uuid
 * @property string|null $e_wallet_chart_uuid
 * @property string|null $client_chart_uuid
 * @property string|null $vendor_chart_uuid
 * @property string|null $inventory_chart_uuid
 * @property string|null $stock_shortage_chart_uuid
 * @property string|null $stock_surplus_chart_uuid
 * @property string|null $deposit_chart_uuid
 * @property string|null $input_tax_chart_uuid
 * @property string|null $output_tax_chart_uuid
 * @property string|null $retained_earnings_chart_uuid
 * @property string|null $disposal_gain_chart_uuid
 * @property string|null $disposal_loss_chart_uuid
 * @property string|null $employee_advance_chart_uuid
 * @property string|null $retained_earnings_formula
 * @property CarbonInterface|null $closing_date
 * @property int $start_finance_period_month
 * @property bool $strict_journal_posting
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Chart|null $cogsChart
 * @property-read Chart|null $incomeChart
 * @property-read Chart|null $bankChart
 * @property-read Chart|null $cashChart
 * @property-read Chart|null $cashInTransitChart
 * @property-read Chart|null $eWalletChart
 * @property-read Chart|null $clientChart
 * @property-read Chart|null $vendorChart
 * @property-read Chart|null $inventoryChart
 * @property-read Chart|null $stockShortageChart
 * @property-read Chart|null $stockSurplusChart
 * @property-read Chart|null $depositChart
 * @property-read Chart|null $inputTaxChart
 * @property-read Chart|null $outputTaxChart
 * @property-read Chart|null $retainedEarningsChart
 * @property-read Chart|null $disposalGainChart
 * @property-read Chart|null $disposalLossChart
 * @property-read Chart|null $employeeAdvanceChart
 *
 * @noinspection PhpUnused
 *
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('chart_settings', key: 'id')]
#[Fillable([
    'cogs_chart_uuid',
    'income_chart_uuid',
    'bank_chart_uuid',
    'cash_chart_uuid',
    'cash_in_transit_chart_uuid',
    'e_wallet_chart_uuid',
    'client_chart_uuid',
    'vendor_chart_uuid',
    'inventory_chart_uuid',
    'stock_shortage_chart_uuid',
    'stock_surplus_chart_uuid',
    'deposit_chart_uuid',
    'input_tax_chart_uuid',
    'output_tax_chart_uuid',
    'retained_earnings_chart_uuid',
    'disposal_gain_chart_uuid',
    'disposal_loss_chart_uuid',
    'employee_advance_chart_uuid',
    'retained_earnings_formula',
    'closing_date',
    'start_finance_period_month',
    'strict_journal_posting',
])]
class ChartSetting extends Model
{
    use HasActivity, HasUuids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // UUIDs
            'cogs_chart_uuid' => 'string',
            'income_chart_uuid' => 'string',
            'bank_chart_uuid' => 'string',
            'cash_chart_uuid' => 'string',
            'cash_in_transit_chart_uuid' => 'string',
            'e_wallet_chart_uuid' => 'string',
            'client_chart_uuid' => 'string',
            'vendor_chart_uuid' => 'string',
            'inventory_chart_uuid' => 'string',
            'stock_shortage_chart_uuid' => 'string',
            'stock_surplus_chart_uuid' => 'string',
            'deposit_chart_uuid' => 'string',
            'input_tax_chart_uuid' => 'string',
            'output_tax_chart_uuid' => 'string',
            'retained_earnings_chart_uuid' => 'string',
            'disposal_gain_chart_uuid' => 'string',
            'disposal_loss_chart_uuid' => 'string',
            'employee_advance_chart_uuid' => 'string',
            'retained_earnings_formula' => 'string',

            // Standard Types
            'closing_date' => 'date',
            'start_finance_period_month' => 'integer',
            'strict_journal_posting' => 'boolean',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the COGS chart associated with the setting.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function cogsChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'cogs_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the income chart associated with the setting.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function incomeChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'income_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function bankChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'bank_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function cashChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'cash_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the chart that cash occupies while it is in transit.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function cashInTransitChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'cash_in_transit_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function eWalletChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'e_wallet_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function clientChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'client_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function vendorChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'vendor_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function inventoryChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'inventory_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define the stock-shortage expense account relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function stockShortageChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'stock_shortage_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define the stock-surplus income account relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function stockSurplusChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'stock_surplus_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function depositChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'deposit_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define the input-tax chart relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function inputTaxChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'input_tax_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define the output-tax chart relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function outputTaxChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'output_tax_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function retainedEarningsChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'retained_earnings_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function disposalGainChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'disposal_gain_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function disposalLossChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'disposal_loss_chart_uuid');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function employeeAdvanceChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'employee_advance_chart_uuid');
    }

    /**
     * Validate the retained-earnings formula syntax without evaluating account data.
     *
     * @param  string|null  $formula  The retained-earnings formula to validate.
     * @return bool Returns true when the formula is valid.
     */
    public function validateRetainedEarningsFormula(?string $formula): bool
    {
        if ($formula === null || mb_trim($formula) === '') {
            return false;
        }

        $expression = preg_replace_callback(
            '/\{(\w+):([^{}]+)}/',
            function (array $matches): string {
                $supportedTags = [
                    'CHART',
                    'TYPE',
                    'SUM_TYPE',
                    'GROUP',
                    'SUM_GROUP',
                    'BEGINNING',
                    'MOVEMENT',
                ];

                return in_array(mb_strtoupper($matches[1]), $supportedTags, true)
                    ? '1'
                    : 'INVALID';
            },
            $formula
        );

        if (
            $expression === null
            || str_contains($expression, 'INVALID')
            || preg_match('/[^0-9+\-*\/().\s]/', $expression) === 1
        ) {
            return false;
        }

        try {
            $result = (new MathExecutor)->execute($expression);

            return is_numeric($result);
        } catch (Throwable) {
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
