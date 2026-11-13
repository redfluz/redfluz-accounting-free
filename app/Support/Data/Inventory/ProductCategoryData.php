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

namespace App\Support\Data\Inventory;

use App\Models\Inventory\ProductCategory;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A grouping products are browsed by.
 *
 * Inactive categories are kept for the same reason inactive stock locations are:
 * products still point at them, and a product pointing at a category that has
 * been removed cannot be filed anywhere.
 *
 * All properties are nullable with defaults so the same object is built two ways:
 *
 *   ProductCategoryData::from($request->validated())   a form, to create or edit
 *   ProductCategoryData::fromModel($category)           a saved one, to read it
 */
class ProductCategoryData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('code')]
        public ?string $code = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('description')]
        public ?string $description = null,

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
     * @param  ProductCategory  $model  The product category model instance.
     * @return self The populated DTO.
     */
    public static function fromModel(ProductCategory $model): self
    {
        return new self(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            description: $model->description,
            isActive: $model->is_active,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a product category is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->isActive === null ? null : (int) $this->isActive,
        ];
    }

    /**
     * The row as the product_categories table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->isActive === null ? null : (int) $this->isActive,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
