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

declare(strict_types=1);

namespace App\Support\Data\Sales;

use App\Models\Sales\DeliveryOrderLine;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One line of a delivery order: a quantity of a product leaving the premises.
 *
 * The line names the sales order line it is fulfilling rather than only the
 * product, because a product can be ordered more than once on the same order and
 * two lines of the same product are not interchangeable. Tracing which order line
 * this delivery discharges is what lets the ordered and delivered quantities be
 * compared rather than assumed to match.
 *
 * The cost is snapshotted here as well as the price. A margin report asks what a
 * delivered item was worth and what it cost at the moment it left; resolving
 * either from the product now would answer with today's figures, not the ones
 * that were true when the goods went out.
 *
 * @param  numeric-string  $unitPrice
 * @param  numeric-string  $unitCost
 * @param  numeric-string  $totalAmount
 */
class DeliveryOrderLineData extends Data
{
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('delivery_order_id')]
        public string $deliveryOrderId,

        // Which ordered line this discharges, so ordered and delivered can be
        // compared per line rather than per product.
        #[MapName('sales_order_line_id')]
        public ?string $salesOrderLineId,

        #[MapName('product_id')]
        public ?string $productId,

        #[MapName('product_sku')]
        public ?string $productSku,

        #[MapName('product_name')]
        public ?string $productName,

        #[MapName('description')]
        public ?string $description,

        #[MapName('quantity')]
        public ?string $quantity,

        #[MapName('unit_price')]
        public ?string $unitPrice,

        #[MapName('unit_cost')]
        public ?string $unitCost,

        #[MapName('total_amount')]
        public ?string $totalAmount,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt,
    ) {}

    /**
     * The same moment, as the type this object holds.
     *
     * A model hands back whatever Carbon its cast produced; this object holds one
     * concrete type so that a form and a model produce the same thing. The value
     * is the same either way, and only its class differs.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * The row as the delivery_order_lines table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the
     * model holds, and the two are written in different formats. Taking the
     * model's attributes instead means the cast is what is mapped, so a column
     * cast to a date, an enum or a decimal is mapped as the type it actually is.
     */
    public static function fromModel(DeliveryOrderLine $model): self
    {
        return new self(
            id: $model->id,
            deliveryOrderId: $model->delivery_order_id,
            salesOrderLineId: $model->sales_order_line_id,
            productId: $model->product_id,
            productSku: $model->product_sku,
            productName: $model->product_name,
            description: $model->description,
            quantity: $model->quantity,
            unitPrice: $model->unit_price,
            unitCost: $model->unit_cost,
            totalAmount: $model->total_amount,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }
}
