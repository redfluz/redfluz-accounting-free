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
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One leg of the double entry a receipt posts to the chart of accounts.
 *
 * A chart row carries either a debit or a credit, never both. The receipt's
 * lines carry what the money was for; this row carries which accounts it
 * moved between, so a printed receipt still names the accounts that were
 * correct when the money arrived even if the chart has been renamed since.
 *
 * @property string $id
 * @property string $receipt_id
 * @property string|null $debit_id
 * @property string|null $credit_id
 * @property string|null $debit_acc
 * @property string|null $credit_acc
 * @property string|null $debit_acc_desc
 * @property string|null $credit_acc_desc
 * @property string|null $document_number
 * @property string|null $reference_number
 * @property CarbonInterface $date
 * @property string|null $description
 * @property numeric-string $debit_amount
 * @property numeric-string $credit_amount
 * @property numeric-string $total_amount
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 *
 * @mixin Builder<self>
 */
#[Table('receipt_charts')]
#[Fillable([
    'receipt_id',
    'debit_id',
    'credit_id',
    'debit_acc',
    'credit_acc',
    'debit_acc_desc',
    'credit_acc_desc',
    'document_number',
    'reference_number',
    'date',
    'description',
    'debit_amount',
    'credit_amount',
    'total_amount',
])]
class ReceiptChart extends Model
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
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * The document this chart row belongs to.
     *
     * @return BelongsTo<Receipt, $this>
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    /**
     * The chart account this row debits.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function debitChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'debit_id');
    }

    /**
     * The chart account this row credits.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function creditChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'credit_id');
    }
}
