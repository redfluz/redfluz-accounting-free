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

namespace App\Support\Data\Finance;

use App\Enums\DiscountType;
use App\Models\Finance\Discount;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A discount: a reduction applied to a line or a document.
 *
 * Each discount has a code, a name, a type (percentage or fixed) and a value.
 * The date range decides when the discount may be selected; outside that range
 * the form does not offer it. The active flag marks one the user may still
 * select on a form.
 */
class DiscountData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('code')]
        public ?string $code = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('type')]
        public ?DiscountType $type = null,

        #[MapName('value')]
        public ?string $value = null,

        #[MapName('starts_at')]
        public ?CarbonImmutable $startsAt = null,

        #[MapName('ends_at')]
        public ?CarbonImmutable $endsAt = null,

        #[MapName('is_active')]
        public ?bool $isActive = null,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt = null,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt = null,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt = null,
    ) {}

    /**
     * The same moment, as the type this object holds.
     *
     * A model hands back whatever Carbon its cast produced; this object holds one
     * concrete type so that a form and a model produce the same thing. The value
     * is the same either way, and only its class differs.
     *
     * @param  CarbonInterface|null  $value  The carbon instance to convert.
     * @return CarbonImmutable|null The converted immutable carbon instance.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the model
     * holds, and the two are written in different formats. Taking the model's
     * attributes instead means the cast is what is mapped.
     *
     * @param  Discount  $model  The discount model instance.
     * @return self The populated data object.
     */
    public static function fromModel(Discount $model): self
    {
        return new self(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            type: $model->type,
            value: $model->value,
            startsAt: self::moment($model->starts_at),
            endsAt: self::moment($model->ends_at),
            isActive: $model->is_active,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a discount is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made. Creating from the full row
     * would insert null for timestamps the model fills in for itself.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type?->value,
            'value' => $this->value,
            'starts_at' => $this->startsAt?->format('Y-m-d'),
            'ends_at' => $this->endsAt?->format('Y-m-d'),
            'is_active' => $this->isActive,
        ];
    }

    /**
     * The row as the discounts table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type?->value,
            'value' => $this->value,
            'starts_at' => $this->startsAt?->format('Y-m-d'),
            'ends_at' => $this->endsAt?->format('Y-m-d'),
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
