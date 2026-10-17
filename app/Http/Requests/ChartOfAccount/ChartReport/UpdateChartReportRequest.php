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

namespace App\Http\Requests\ChartOfAccount\ChartReport;

use App\Enums\FileAction;
use App\Enums\FileResources;
use Illuminate\Validation\Rule;

class UpdateChartReportRequest extends ChartReportRequest
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
        return [
            'code' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:255'],
            'calculation_basis' => ['required', Rule::in(['period', 'as_of'])],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
