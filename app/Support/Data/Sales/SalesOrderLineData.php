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

use App\Enums\DiscountType;
use App\Enums\TaxMode;
use App\Models\Sales\SalesOrderLine;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One line of a sales order: a product committed at a quantity and a price.
 *
 * A sales order line carries what has been ordered and what has been delivered as
 * separate figures, because they differ. Ordered is what the customer agreed to;
 * delivered is what has actually gone out. Collapsing them into one number is how
 * an order silently reports itself as complete when it is not, so both are here
 * and neither is derived from the other.
 *
 * The discount and tax are carried the same way as they are on the invoice line
 * that will eventually mirror this one, so a reader can see the arithmetic rather
 * than being handed the result of it.
 *
 * @param  numeric-string  $unitPrice
 * @param  numeric-string  $subtotalAmount
 * @param  numeric-string  $discountValue
 * @param  numeric-string  $discountRate
 * @param  numeric-string  $discountAmount
 * @param  numeric-string  $taxRate
 * @param  numeric-string  $taxAmount
 * @param  numeric-string  $lineTotalAmount
 * @param  numeric-string  $totalAmount
 */
class SalesOrderLineData extends Data
{
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('sales_order_id')]
        public string $salesOrderId,

        #[MapName('product_id')]
        public ?string $productId,

        #[MapName('product_sku')]
        public ?string $productSku,

        #[MapName('product_name')]
        public ?string $productName,

        #[MapName('description')]
        public ?string $description,

        // A service has no stock to check, so a line carrying neither quantity nor
        // sku is not missing data: it is not a thing that could be delivered.
        #[MapName('is_service')]
        public ?bool $isService,

        #[MapName('ordered_quantity')]
        public ?string $orderedQuantity,

        #[MapName('delivered_quantity')]
        public ?string $deliveredQuantity,

        #[MapName('unit_price')]
        public ?string $unitPrice,

        #[MapName('warranty_months')]
        public ?int $warrantyMonths,

        #[MapName('subtotal_amount')]
        public ?string $subtotalAmount,

        #[MapName('discount_id')]
        public ?string $discountId,

        #[MapName('discount_name')]
        public ?string $discountName,

        #[MapName('discount_type')]
        public ?DiscountType $discountType,

        #[MapName('discount_value')]
        public ?string $discountValue,

        #[MapName('discount_rate')]
        public ?string $discountRate,

        #[MapName('discount_amount')]
        public ?string $discountAmount,

        #[MapName('tax_id')]
        public ?string $taxId,

        #[MapName('tax_name')]
        public ?string $taxName,

        #[MapName('tax_rate')]
        public ?string $taxRate,

        #[MapName('tax_amount')]
        public ?string $taxAmount,

        #[MapName('tax_mode')]
        public ?TaxMode $taxMode,

        #[MapName('line_total_amount')]
        public ?string $lineTotalAmount,

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
     * The row as the sales_order_lines table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'is_service' => $this->isService === null ? null : (int) $this->isService,
            'discount_type' => $this->discountType?->value,
            'tax_mode' => $this->taxMode?->value,
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
    public static function fromModel(SalesOrderLine $model): self
    {
        return new self(
            id: $model->id,
            salesOrderId: $model->sales_order_id,
            productId: $model->product_id,
            productSku: $model->product_sku,
            productName: $model->product_name,
            description: $model->description,
            isService: $model->is_service,
            orderedQuantity: $model->ordered_quantity,
            deliveredQuantity: $model->delivered_quantity,
            unitPrice: $model->unit_price,
            warrantyMonths: $model->warranty_months,
            subtotalAmount: $model->subtotal_amount,
            discountId: $model->discount_id,
            discountName: $model->discount_name,
            discountType: $model->discount_type,
            discountValue: $model->discount_value,
            discountRate: $model->discount_rate,
            discountAmount: $model->discount_amount,
            taxId: $model->tax_id,
            taxName: $model->tax_name,
            taxRate: $model->tax_rate,
            taxAmount: $model->tax_amount,
            taxMode: $model->tax_mode,
            lineTotalAmount: $model->line_total_amount,
            totalAmount: $model->total_amount,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }
}
