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

namespace App\Models\Sales;

use App\Enums\DiscountType;
use App\Enums\TaxMode;
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
 * @mixin Builder<SalesOrderLine>
 *
 * @property string $id
 * @property string $sales_order_id
 * @property string|null $product_id
 * @property string|null $product_sku
 * @property string $product_name
 * @property string|null $description
 * @property bool $is_service
 * @property numeric-string $ordered_quantity
 * @property numeric-string $delivered_quantity
 * @property numeric-string $unit_price
 * @property int|null $warranty_months
 * @property numeric-string $subtotal_amount
 * @property string|null $discount_id
 * @property string|null $discount_name
 * @property DiscountType|null $discount_type
 * @property numeric-string $discount_value
 * @property numeric-string $discount_rate
 * @property numeric-string $discount_amount
 * @property string|null $tax_id
 * @property string|null $tax_name
 * @property numeric-string $tax_rate
 * @property numeric-string $tax_amount
 * @property TaxMode|null $tax_mode
 * @property numeric-string $line_total_amount
 * @property numeric-string $total_amount
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read SalesOrder|null $salesOrder
 * @property-read Product|null $product
 *
 * @noinspection PhpUnused
 */
#[Table('sales_order_lines', key: 'id')]
#[Fillable([
    'sales_order_id', 'product_id', 'product_sku', 'product_name',
    'description', 'is_service', 'ordered_quantity', 'delivered_quantity',
    'unit_price', 'warranty_months', 'subtotal_amount', 'discount_id', 'discount_name', 'discount_type',
    'discount_value', 'discount_rate', 'discount_amount', 'tax_id', 'tax_name',
    'tax_rate', 'tax_amount', 'tax_mode', 'line_total_amount', 'total_amount',
])]
class SalesOrderLine extends Model
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
            'is_service' => 'boolean',
            'ordered_quantity' => 'decimal:4',
            'delivered_quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'warranty_months' => 'integer',
            'subtotal_amount' => 'decimal:2',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:4',
            'discount_rate' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'tax_mode' => TaxMode::class,
            'line_total_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the sales order for this line.
     *
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the product for this line.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
