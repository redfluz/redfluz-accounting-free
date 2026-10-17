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

namespace App\Models\Sales;

use App\Models\Inventory\Product;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One leg of a sales adjustment.
 *
 * A line records the product detail being adjusted. The accounting entries are
 * handled by the posting services using the chart configuration tables.
 *
 * @property string $id
 * @property string $sales_adjustment_id
 * @property string|null $product_id
 * @property string|null $product_name
 * @property int|null $quantity
 * @property numeric-string|null $unit_price
 * @property numeric-string|null $subtotal_amount
 * @property numeric-string|null $discount_rate
 * @property numeric-string|null $discount_amount
 * @property numeric-string|null $tax_rate
 * @property numeric-string|null $tax_amount
 * @property string|null $tax_mode
 * @property numeric-string|null $line_total_amount
 * @property numeric-string|null $total_amount
 * @property string|null $description
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 *
 * @mixin Builder<self>
 */
#[Table('sales_adjustment_lines', key: 'id')]
#[Fillable([
    'sales_adjustment_id',
    'product_id',
    'product_name',
    'quantity',
    'unit_price',
    'subtotal_amount',
    'discount_rate',
    'discount_amount',
    'tax_rate',
    'tax_amount',
    'tax_mode',
    'line_total_amount',
    'total_amount',
    'description',
    'created_by', 'created_name',
    'updated_by', 'updated_name',
    'deleted_by', 'deleted_name',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
])]
class SalesAdjustmentLine extends Model
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
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal_amount' => 'decimal:4',
            'discount_rate' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'line_total_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * The adjustment this line belongs to.
     *
     * @return BelongsTo<SalesAdjustment, $this>
     */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(SalesAdjustment::class, 'sales_adjustment_id');
    }

    /**
     * The product being corrected.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
