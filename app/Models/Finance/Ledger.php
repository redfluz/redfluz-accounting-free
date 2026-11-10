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

namespace App\Models\Finance;

use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\ChartOfAccount\Chart;
use App\Models\Crm\Company;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<self>
 *
 * @property string $id
 * @property string|null $chart_id
 * @property string|null $company_id
 * @property string|null $currency_id
 * @property LedgerType $type
 * @property LedgerSubType $subtype
 * @property string $document_number
 * @property string|null $reference_number
 * @property CarbonInterface $date
 * @property string|null $chart_code
 * @property string|null $chart_name
 * @property string|null $company_name
 * @property string|null $description
 * @property string|null $line_description
 * @property numeric-string $debit_amount
 * @property numeric-string $credit_amount
 * @property CarbonInterface $created_at
 * @property-read Chart|null $chart
 * @property-read Company|null $company
 */
#[Table('ledgers', key: 'id')]
#[Fillable([
    'chart_id',
    'company_id',
    'currency_id',
    'type',
    'subtype',
    'document_number',
    'reference_number',
    'date',
    'chart_code',
    'chart_name',
    'company_name',
    'description',
    'line_description',
    'debit_amount',
    'credit_amount',
])]
class Ledger extends Model
{
    use HasActivity, HasUuids;

    /**
     * Disable updated_at: ledgers are immutable history.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerType::class,
            'subtype' => LedgerSubType::class,
            'date' => 'date',
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
        ];
    }

    /**
     * The chart account this posting moved.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'chart_id', 'id');
    }

    /**
     * The company this posting is for.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * The currency the figures are in.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
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
