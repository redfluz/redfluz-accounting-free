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

use App\Models\Sales\QuotationLine;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One line of a quotation: an offered product, at a quantity and a price.
 *
 * A quotation posts nothing until it converts, so the line carries no chart of
 * accounts: only the product, the quantity and the price. The accounts it will
 * post to are decided at conversion time, not stored here.
 *
 * @param  numeric-string  $unitPrice
 * @param  numeric-string  $totalAmount
 */
class QuotationLineData extends Data
{
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('quotation_id')]
        public string $quotationId,

        #[MapName('product_id')]
        public ?string $productId,

        #[MapName('product_name')]
        public ?string $productName,

        #[MapName('quantity')]
        public int $quantity,

        #[MapName('unit_price')]
        public string $unitPrice,

        #[MapName('total_amount')]
        public string $totalAmount,

        #[MapName('notes')]
        public ?string $notes,

        #[MapName('description')]
        public ?string $description,

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
     * The row as the quotation_lines table wants it.
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
    public static function fromModel(QuotationLine $model): self
    {
        return new self(
            id: $model->id,
            quotationId: $model->quotation_id,
            productId: $model->product_id,
            productName: $model->product_name,
            quantity: $model->quantity,
            unitPrice: $model->unit_price,
            totalAmount: $model->total_amount,
            notes: $model->notes,
            description: $model->description,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }
}
