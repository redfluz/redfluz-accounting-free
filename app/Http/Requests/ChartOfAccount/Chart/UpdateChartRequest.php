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

namespace App\Http\Requests\ChartOfAccount\Chart;

use App\Enums\ChartReportGroupEnum;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Models\ChartOfAccount\Chart;
use Illuminate\Validation\Rule;

/**
 * The fields a chart account is edited from.
 *
 * The rules are stated in full rather than taken from the create request and
 * adjusted, because the one rule that differs is exactly the rule a reader would
 * want to see. The code must be unique except for the account being edited, and
 * a rule inherited and then deleted is a rule that no longer exists anywhere to
 * be read.
 */
class UpdateChartRequest extends ChartRequest
{
    /**
     * Who may edit a chart account.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * @return bool Whether the authenticated user may perform this action.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::CHARTS, FileAction::UPDATE);
    }

    /**
     * The validation rules for editing a chart account.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Chart $chart */
        $chart = $this->route('chart');

        return [
            'chart_type_id' => [
                'required',
                'string',
                Rule::exists('chart_types', 'id')->whereNull('deleted_at'),
            ],
            'report_group' => ['nullable', Rule::enum(ChartReportGroupEnum::class)],
            'code' => ['required', 'string', 'max:255', 'unique:charts,code,'.$chart->id],
            'name' => ['required', 'string', 'max:255'],
            'isBank' => ['sometimes', 'boolean'],
            'isDebtor' => ['sometimes', 'boolean'],
            'isCreditor' => ['sometimes', 'boolean'],
        ];
    }
}
