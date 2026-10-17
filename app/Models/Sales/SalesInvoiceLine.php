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
 * strictly prohibited without explicit written permission of the copyright owner.
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
 * One line of a sales invoice.
 *
 * Each line records one product sold: the quantity, the price, the discount and
 * tax applied. The accounting entries are handled by the posting services using
 * the chart configuration tables, not stored on the line itself.
 *
 * @property string $id
 * @property string $sales_invoice_id
 * @property string|null $product_id
 * @property string|null $product_name
 * @property int|null $quantity
 * @property numeric-string|null $returned_quantity
 * @property numeric-string|null $unit_price
 * @property numeric-string|null $subtotal_amount
 * @property string|null $discount_id
 * @property string|null $discount_name
 * @property string|null $discount_type
 * @property numeric-string|null $discount_value
 * @property numeric-string|null $discount_rate
 * @property numeric-string|null $discount_amount
 * @property string|null $tax_id
 * @property string|null $tax_name
 * @property numeric-string|null $tax_rate
 * @property numeric-string|null $tax_amount
 * @property string|null $tax_mode
 * @property numeric-string|null $line_total_amount
 * @property numeric-string|null $total_amount
 * @property int|null $warranty_months
 * @property bool $deduct_inventory
 * @property string|null $description
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 *
 * @mixin Builder<self>
 */
#[Table('sales_invoice_lines', key: 'id')]
#[Fillable([
    'sales_invoice_id',
    'product_id',
    'product_name',
    'quantity',
    'returned_quantity',
    'unit_price',
    'subtotal_amount',
    'discount_id',
    'discount_name',
    'discount_type',
    'discount_value',
    'discount_rate',
    'discount_amount',
    'tax_id',
    'tax_name',
    'tax_rate',
    'tax_amount',
    'tax_mode',
    'line_total_amount',
    'total_amount',
    'warranty_months',
    'deduct_inventory',
    'description',
])]
class SalesInvoiceLine extends Model
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
            'returned_quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'subtotal_amount' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'discount_rate' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'line_total_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'warranty_months' => 'integer',
            'deduct_inventory' => 'boolean',
        ];
    }

    /**
     * The invoice this line belongs to.
     *
     * @return BelongsTo<SalesInvoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    /**
     * The product being sold.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
