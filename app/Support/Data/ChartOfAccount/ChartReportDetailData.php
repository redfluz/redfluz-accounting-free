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
use App\Enums\ChartReportTypeEnum;
use App\Enums\ChartTotalReportTypeEnum;
use App\Enums\ChartTypeEnum;
use App\Models\ChartOfAccount\ChartReportDetail;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One line of a financial statement.

    A statement is a tree: a line is either a heading, a subtotal over the lines
    beneath it, or a figure drawn straight from an account. The parent reference
    is what makes it a tree, and the formula is what makes a subtotal meaningful.

    The account reference is kept on the line rather than resolved when the report
    is printed, so a statement printed years later groups the same way it did when
    it was laid out. A report whose lines resolved the chart on read would change
    shape every time the chart was reorganised.
 */
class ChartReportDetailData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('chart_report_id')]
        public ?string $chartReportId = null,

        #[MapName('parent_id')]
        public ?string $parentId = null,

        #[MapName('chart_id')]
        public ?string $chartId = null,

        #[MapName('code')]
        public ?string $code = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('chart_report_type')]
        public ?ChartReportTypeEnum $chartReportType = null,

        #[MapName('chart_total_report_type')]
        public ?ChartTotalReportTypeEnum $chartTotalReportType = null,

        #[MapName('chart_type')]
        public ?ChartTypeEnum $chartType = null,

        #[MapName('chart_report_group')]
        public ?ChartReportGroupEnum $chartReportGroup = null,

        #[MapName('formula')]
        public ?string $formula = null,

        #[MapName('line')]
        public ?int $line = null,

        #[MapName('notes')]
        public ?string $notes = null,

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
    public static function fromModel(ChartReportDetail $model): self
    {
        return new self(
            id: $model->id,
            chartReportId: $model->chart_report_id,
            parentId: $model->parent_id,
            chartId: $model->chart_id,
            code: $model->code,
            name: $model->name,
            chartReportType: $model->chart_report_type,
            chartTotalReportType: $model->chart_total_report_type,
            chartType: $model->chart_type,
            chartReportGroup: $model->chart_report_group,
            formula: $model->formula,
            line: $model->line,
            notes: $model->notes,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a chart report detail is created from, and no others.
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
            'chart_report_id' => $this->chartReportId,
            'parent_id' => $this->parentId,
            'chart_id' => $this->chartId,
            'code' => $this->code,
            'name' => $this->name,
            'chart_report_type' => $this->chartReportType,
            'chart_total_report_type' => $this->chartTotalReportType,
            'chart_type' => $this->chartType,
            'chart_report_group' => $this->chartReportGroup,
            'formula' => $this->formula,
            'line' => $this->line,
            'notes' => $this->notes,
        ];
    }

    /**
     * The row as the chart_report_details table wants it.
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
}
