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

namespace App\Support\Data\Purchase;

use App\Enums\AcquisitionType;
use App\Models\Purchase\PurchaseOrderLine;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One line of a purchase order: a product ordered at a quantity and a price.
 *
 * A line that is a service carries neither a sku nor a quantity to receive, so
 * that is a statement about the line rather than missing data: there is nothing
 * on the line to be delivered against.
 *
 * @param  numeric-string|null  $unitPrice
 * @param  numeric-string|null  $totalAmount
 */
class PurchaseOrderLineData extends Data
{
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('purchase_order_id')]
        public string $purchaseOrderId,

        #[MapName('product_id')]
        public ?string $productId,

        #[MapName('product_sku')]
        public ?string $productSku,

        #[MapName('product_name')]
        public ?string $productName,

        #[MapName('description')]
        public ?string $description,

        #[MapName('is_service')]
        public ?bool $isService,

        // What kind of stock this line puts into the business. It decides how the
        // quantity is counted later, so it is recorded at the point of ordering
        // rather than inferred from the product afterwards.
        #[MapName('acquisition_type')]
        public ?AcquisitionType $acquisitionType,

        #[MapName('debit_chart_id')]
        public ?string $debitChartId,

        #[MapName('quantity')]
        public ?int $quantity,

        #[MapName('unit_price')]
        public ?string $unitPrice,

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
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the
     * model holds, and the two are written in different formats. Taking the
     * model's attributes instead means the cast is what is mapped.
     */
    public static function fromModel(PurchaseOrderLine $model): self
    {
        return new self(
            id: $model->id,
            purchaseOrderId: $model->purchase_order_id,
            productId: $model->product_id,
            productSku: $model->product_sku,
            productName: $model->product_name,
            description: $model->description,
            isService: $model->is_service,
            acquisitionType: $model->acquisition_type,
            debitChartId: $model->debit_chart_id,
            quantity: $model->quantity,
            unitPrice: $model->unit_price,
            totalAmount: $model->total_amount,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The row as the purchase_order_lines table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'is_service' => $this->isService === null ? null : (int) $this->isService,
            'acquisition_type' => $this->acquisitionType?->value,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
