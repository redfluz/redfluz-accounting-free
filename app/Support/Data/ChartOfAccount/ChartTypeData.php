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

namespace App\Support\Data\ChartOfAccount;

use App\Models\ChartOfAccount\ChartType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A class of account: asset, liability, equity, income or expense.

    The five flags are kept apart rather than collapsed into one kind, because the
    five are read independently by the reports and by the posting rules, and a
    single column could only say one of them. An account is in exactly one class,
    but nothing in the schema stops it claiming two, so the application checks
    rather than the database.

    The default flag marks the type a new account starts as, and delete marks one
    the user may not remove. Both are flags rather than behaviour because they are
    statements about what the setup offers, not rules the row enforces.
 */
class ChartTypeData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('account_type')]
        public ?string $accountType = null,

        #[MapName('isDelete')]
        public ?bool $isDelete = null,

        #[MapName('isDefault')]
        public ?bool $isDefault = null,

        #[MapName('isAsset')]
        public ?bool $isAsset = null,

        #[MapName('isLiability')]
        public ?bool $isLiability = null,

        #[MapName('isEquity')]
        public ?bool $isEquity = null,

        #[MapName('isIncome')]
        public ?bool $isIncome = null,

        #[MapName('isExpenses')]
        public ?bool $isExpenses = null,

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
     * @param  CarbonInterface|null  $value  The moment to normalise.
     * @return CarbonImmutable|null The moment as an immutable, or null.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * The columns a chart type is created from, and no others.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'name' => $this->name,
            'isDelete' => $this->isDelete,
            'isDefault' => $this->isDefault,
            'isAsset' => $this->isAsset,
            'isLiability' => $this->isLiability,
            'isEquity' => $this->isEquity,
            'isIncome' => $this->isIncome,
            'isExpenses' => $this->isExpenses,
        ];
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the model
     * holds, and the two are written in different formats. Taking the model's
     * attributes instead means the cast is what is mapped.
     */
    public static function fromModel(ChartType $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            isDelete: $model->isDelete,
            isDefault: $model->isDefault,
            isAsset: $model->isAsset,
            isLiability: $model->isLiability,
            isEquity: $model->isEquity,
            isIncome: $model->isIncome,
            isExpenses: $model->isExpenses,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The row as the chart_types table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isDelete' => $this->isDelete,
            'isDefault' => $this->isDefault,
            'isAsset' => $this->isAsset,
            'isLiability' => $this->isLiability,
            'isEquity' => $this->isEquity,
            'isIncome' => $this->isIncome,
            'isExpenses' => $this->isExpenses,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
