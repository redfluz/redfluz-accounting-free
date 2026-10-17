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
 * @mixin Builder<DeliveryOrderLine>
 *
 * @property string $id
 * @property string $delivery_order_id
 * @property string $sales_order_line_id
 * @property string $product_id
 * @property string $product_sku
 * @property string $product_name
 * @property string|null $description
 * @property numeric-string $quantity
 * @property numeric-string $unit_price
 * @property numeric-string $unit_cost
 * @property numeric-string $total_amount
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read DeliveryOrder|null $deliveryOrder
 * @property-read SalesOrderLine|null $salesOrderLine
 * @property-read Product|null $product
 *
 * @noinspection PhpUnused
 */
#[Table('delivery_order_lines', key: 'id')]
#[Fillable([
    'delivery_order_id', 'sales_order_line_id', 'product_id',
    'product_sku', 'product_name', 'description', 'quantity', 'unit_price',
    'unit_cost', 'total_amount',
])]
class DeliveryOrderLine extends Model
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
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the delivery order for this line.
     *
     * @return BelongsTo<DeliveryOrder, $this>
     */
    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the sales order line for this line.
     *
     * @return BelongsTo<SalesOrderLine, $this>
     */
    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class);
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
