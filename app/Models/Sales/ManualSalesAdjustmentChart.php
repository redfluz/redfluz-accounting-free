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

namespace app\Models\Sales;

use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\ChartOfAccount\Chart;
use App\Models\Crm\Company;
use App\Models\Finance\Currency;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * One single-sided entry a manual sales adjustment posts to the chart of
 * accounts.
 *
 * A chart row carries one account and one amount, debit or credit, never
 * both: the debit is its own row and the credit is another. The row carries
 * the same standard columns a ledger row carries, so the adjustment's posting
 * copies each line straight across instead of splitting a two-sided row, and
 * a printed note still names the accounts and codes that were correct when it
 * was raised even if the chart has been renamed since.
 *
 * @property string $id
 * @property string $manual_sales_adjustment_id
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
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read ManualSalesAdjustment|null $adjustment
 * @property-read Chart|null $chart
 * @property-read Company|null $company
 * @property-read Currency|null $currency
 *
 * @mixin Builder<self>
 */
#[Table('manual_sales_adjustment_charts')]
#[Fillable([
    'manual_sales_adjustment_id',
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
class ManualSalesAdjustmentChart extends Model
{
    use HasUuids, SoftDeletes;

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
     * The document this chart row belongs to.
     *
     * @return BelongsTo<ManualSalesAdjustment, $this>
     */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(ManualSalesAdjustment::class, 'manual_sales_adjustment_id');
    }

    /**
     * The chart account this single-sided line posts against.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'chart_id', 'id');
    }

    /**
     * The company the posting belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * The currency the amounts are stated in.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }
}
