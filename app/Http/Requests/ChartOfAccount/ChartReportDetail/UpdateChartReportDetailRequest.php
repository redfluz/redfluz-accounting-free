<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://github.com/redfluz
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

namespace App\Http\Requests\ChartOfAccount\ChartReportDetail;

use App\Enums\ChartReportTypeEnum;
use App\Enums\ChartTotalReportTypeEnum;
use App\Enums\ChartTypeEnum;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Models\ChartOfAccount\ChartReport;
use App\Models\ChartOfAccount\ChartReportDetail;
use Illuminate\Validation\Rule;

class UpdateChartReportDetailRequest extends ChartReportDetailRequest
{
    public function authorize(): bool
    {
        return $this->allows(FileResources::CHART_REPORTS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ChartReport $chartReport */
        $chartReport = $this->route('chartReport');
        /** @var ChartReportDetail|null $detail */
        $detail = $this->route('chartReportDetail');

        $supportedTypes = [
            ChartReportTypeEnum::CHART->value,
            ChartReportTypeEnum::FORMULA->value,
            ChartReportTypeEnum::NOTES->value,
            ChartReportTypeEnum::NONE->value,
            ChartReportTypeEnum::TYPES->value,
        ];

        return [
            'parent_id' => ['nullable', 'uuid', 'exists:chart_report_details,id'],
            'chart_id' => [
                Rule::requiredIf(fn (): bool => request('chart_report_type') === ChartReportTypeEnum::CHART->value),
                'nullable',
                'uuid',
                'exists:charts,id',
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('chart_report_details', 'code')
                    ->where('chart_report_id', $chartReport->id)
                    ->whereNull('deleted_at')
                    ->ignore($detail?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'chart_report_type' => ['required', Rule::in($supportedTypes)],
            'chart_total_report_type' => [
                'required',
                Rule::in(array_column(ChartTotalReportTypeEnum::cases(), 'value')),
            ],
            'chart_type' => [
                Rule::requiredIf(fn (): bool => request('chart_report_type') === ChartReportTypeEnum::TYPES->value),
                'nullable',
                Rule::in(array_column(ChartTypeEnum::cases(), 'value')),
            ],
            'formula' => [
                Rule::requiredIf(fn (): bool => request('chart_report_type') === ChartReportTypeEnum::FORMULA->value),
                'nullable',
                'string',
                'max:5000',
            ],
            'line' => ['required', 'integer', 'min:1', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
