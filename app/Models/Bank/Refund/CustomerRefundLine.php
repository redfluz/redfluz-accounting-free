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

namespace App\Models\Bank\Refund;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One allocation of a customer refund's figure.
 *
 * A line carries what the money was for and how much of it, and nothing about
 * which accounts it moved between. The accounts live on the refund's chart
 * rows, so a line can never disagree with the double entry and the chart of
 * accounts is named in exactly one place.
 *
 * The line can only belong to a customer refund. A supplier refund's lines are a
 * different model on a different table, so no line row can name the wrong parent
 * and no query has to filter to stay on the right kind.
 *
 * @property string $id
 * @property string $refund_id
 * @property numeric-string $amount
 * @property string|null $description
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 *
 * @mixin Builder<self>
 */
#[Table('customer_refund_lines')]
#[Fillable(['refund_id', 'amount', 'description'])]
class CustomerRefundLine extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    /**
     * The document this line belongs to.
     *
     * @return BelongsTo<CustomerRefund, $this>
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(CustomerRefund::class);
    }
}
