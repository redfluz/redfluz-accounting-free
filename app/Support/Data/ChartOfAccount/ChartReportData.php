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

use App\Models\ChartOfAccount\ChartReport;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A financial statement: a balance sheet, an income statement, a trial balance.

    The basis is recorded on the report because the same statement can be produced
    two ways, and the two do not agree. Cash and accrual figures differ for
    anything not yet settled, and a report that does not say which basis it used
    cannot be compared with one that chose differently.
 */
class ChartReportData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('code')]
        public ?string $code = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('slug')]
        public ?string $slug = null,

        #[MapName('calculation_basis')]
        public ?string $calculationBasis = null,

        #[MapName('is_default')]
        public ?bool $isDefault = null,

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
     */
    public static function fromModel(ChartReport $model): self
    {
        return new self(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            slug: $model->slug,
            calculationBasis: $model->calculation_basis,
            isDefault: $model->is_default,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a chart report is created from, and no others.
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
            'slug' => $this->slug,
            'calculation_basis' => $this->calculationBasis,
            'is_default' => $this->isDefault,
        ];
    }

    /**
     * The row as the chart_reports table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'is_default' => $this->isDefault === null ? null : (int) $this->isDefault,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
