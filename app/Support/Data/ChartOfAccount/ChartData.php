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

use App\Enums\ChartReportGroupEnum;
use App\Models\ChartOfAccount\Chart;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A chart of account: one account a figure can be booked against.

    The role flags are kept as three booleans rather than one role column, because
    they answer different questions and an account can answer more than one. An
    account can be a bank account that is also the debtor control account, and the
    posting code asks each question separately: where the money sits, which side
    of the balance sheet it lands on, and whether a figure against it is something
    the entity owes or is owed.

    The type and the role are separate because they change for different reasons.
    Renaming an account or moving it between asset and liability does not change
    whether it is a bank account, and a migration that changed one by accident
    would move cash out of the money ledgers.
 */
class ChartData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('chart_type_id')]
        public ?string $chartTypeId = null,

        #[MapName('report_group')]
        public ?ChartReportGroupEnum $reportGroup = null,

        #[MapName('code')]
        public ?string $code = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('isBank')]
        public ?bool $isBank = false,

        #[MapName('isDebtor')]
        public ?bool $isDebtor = false,

        #[MapName('isCreditor')]
        public ?bool $isCreditor = false,

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
     * The columns a chart account is created from, and no others.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'chart_type_id' => $this->chartTypeId,
            'report_group' => $this->reportGroup?->value,
            'code' => $this->code,
            'name' => $this->name,
            'isBank' => $this->isBank ?? false,
            'isDebtor' => $this->isDebtor ?? false,
            'isCreditor' => $this->isCreditor ?? false,
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
    public static function fromModel(Chart $model): self
    {
        return new self(
            id: $model->id,
            chartTypeId: $model->chart_type_id,
            reportGroup: $model->report_group,
            code: $model->code,
            name: $model->name,
            isBank: $model->isBank,
            isDebtor: $model->isDebtor,
            isCreditor: $model->isCreditor,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The row as the charts table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'chart_type_id' => $this->chartTypeId,
            'report_group' => $this->reportGroup?->value,
            'code' => $this->code,
            'name' => $this->name,
            'isBank' => $this->isBank,
            'isDebtor' => $this->isDebtor,
            'isCreditor' => $this->isCreditor,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
