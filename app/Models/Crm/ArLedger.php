<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

namespace App\Models\Crm;

use App\Models\ChartOfAccount\Chart;
use App\Models\Finance\Currency;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * What a customer owes.
 *
 * This row is not a document. It is the receivable figure a document produced,
 * named by that document's number so a row always drills back up to what caused
 * it, and the figure here is only ever a posted one.
 *
 * Deliberately holds no tax or discount breakdown. A sub ledger row is a figure,
 * and a figure does not carry its own derivation: that belongs to the document,
 * which is where the rates and the names live. Keeping a copy here meant the two
 * could disagree, and the ledger is the one that gets reported from.
 *
 * The account it was booked against is carried three ways: the chart_id is the
 * join, and the code, name and description are what a printed report reads,
 * snapshotted at posting time so they survive the chart being renamed.
 *
 * @property string $id
 * @property string $chart_id
 * @property string $company_id
 * @property string $currency_id
 * @property string $document_number
 * @property string|null $reference_number
 * @property string|null $chart_code
 * @property string|null $chart_name
 * @property string|null $chart_description
 * @property numeric-string $debit_amount
 * @property numeric-string $credit_amount
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 *
 * @mixin Builder<self>
 */
#[Table('ar_ledgers')]
#[Fillable([
    'chart_id',
    'company_id',
    'currency_id',
    'document_number',
    'reference_number',
    'chart_code',
    'chart_name',
    'chart_description',
    'debit_amount',
    'credit_amount',
])]
class ArLedger extends Model
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
            'date' => 'date',
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
        ];
    }

    /**
     * The chart account the figure was booked against.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }

    /**
     * The customer this figure is owed by.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The currency the figure is in.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
