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

use App\Models\Finance\Currency;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A currency: the unit of money a document is denominated in.
 *
 * Each currency has a code (such as MYR), a human-readable name and a symbol
 * used on forms and reports. The default flag marks the one currency the system
 * falls back to when no other is chosen; the active flag marks one the user may
 * still select. Both are flags rather than behaviour because they are statements
 * about what the setup offers, not rules the row enforces.
 */
class CurrencyData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('code')]
        public ?string $code = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('symbol')]
        public ?string $symbol = null,

        #[MapName('is_default')]
        public ?bool $isDefault = null,

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
     * @param  Currency  $model  The currency model instance.
     * @return self The populated data object.
     */
    public static function fromModel(Currency $model): self
    {
        return new self(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            symbol: $model->symbol,
            isDefault: $model->is_default,
            isActive: $model->is_active,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a currency is created from, and no others.
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
            'symbol' => $this->symbol,
            'is_default' => $this->isDefault,
            'is_active' => $this->isActive,
        ];
    }

    /**
     * The row as the currencies table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'is_default' => $this->isDefault,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
