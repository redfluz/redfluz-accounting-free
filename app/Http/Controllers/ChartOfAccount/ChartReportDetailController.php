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

namespace App\Http\Controllers\ChartOfAccount;

use App\Constants\FlashMessage;
use App\Enums\ChartReportTypeEnum;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChartOfAccount\ChartReportDetail\StoreChartReportDetailRequest;
use App\Http\Requests\ChartOfAccount\ChartReportDetail\UpdateChartReportDetailRequest;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartReport;
use App\Models\ChartOfAccount\ChartReportDetail;
use App\Support\Data\ChartOfAccount\ChartReportDetailData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChartReportDetailController extends Controller
{
    /**
     * Show the form for creating a new resource.
     * Show the form for creating a report row.
     *
     * @param  ChartReport  $chartReport  The report template instance.
     * @return View Returns the rendered Blade view.
     */
    public function create(ChartReport $chartReport): View
    {
        $this->allows(FileResources::CHART_REPORTS, FileAction::ADD);

        $charts = $this->charts();
        /** @var Collection<int, ChartReportDetail> $parents */
        $parents = $chartReport->details()->get();
        $maxLine = $chartReport->details()->max('line');
        $nextLine = (is_numeric($maxLine) ? (int) $maxLine : 0) + 1;

        return view(
            'chart-of-account.chart-reports.details.create',
            compact('chartReport', 'charts', 'parents', 'nextLine')
        );
    }

    /**
     * Handle the incoming request to create a new resource.
     * Store a newly created report row.
     *
     * @param  StoreChartReportDetailRequest  $request  The validated report detail being created.
     * @param  ChartReport  $chartReport  The report template instance.
     * @return RedirectResponse Redirects to the report edit route.
     */
    public function store(StoreChartReportDetailRequest $request, ChartReport $chartReport): RedirectResponse
    {
        $data = ChartReportDetailData::from($request->validated());
        $this->validateParent($chartReport, $data->parentId);
        $this->validateFormula($data);

        ChartReportDetail::query()->create([
            'chart_report_id' => $chartReport->id,
            ...$this->attributes($data),
        ]);

        return redirect()
            ->route('chart-of-account.chart-reports.edit', $chartReport)
            ->with('success', FlashMessage::created(FileResources::CHART_REPORTS));
    }

    /**
     * Show the form for editing an open resource.
     * Show the form for editing a report row.
     *
     * @param  ChartReport  $chartReport  The report template instance.
     * @param  ChartReportDetail  $chartReportDetail  The report row instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(ChartReport $chartReport, ChartReportDetail $chartReportDetail): View
    {
        $this->allows(FileResources::CHART_REPORTS, FileAction::UPDATE);
        $this->ensureDetailBelongsToReport($chartReport, $chartReportDetail);

        $charts = $this->charts();
        /** @var Collection<int, ChartReportDetail> $parents */
        $parents = $chartReport->details()
            ->whereKeyNot($chartReportDetail->getKey())
            ->get();

        return view(
            'chart-of-account.chart-reports.details.edit',
            compact('chartReport', 'chartReportDetail', 'charts', 'parents')
        );
    }

    /**
     * Update an existing resource by its object.
     * Update the specified report row.
     *
     * @param  UpdateChartReportDetailRequest  $request  The validated report detail being saved.
     * @param  ChartReport  $chartReport  The report template instance.
     * @param  ChartReportDetail  $chartReportDetail  The report row instance.
     * @return RedirectResponse Redirects to the report edit route.
     */
    public function update(
        UpdateChartReportDetailRequest $request,
        ChartReport $chartReport,
        ChartReportDetail $chartReportDetail
    ): RedirectResponse {
        $this->ensureDetailBelongsToReport($chartReport, $chartReportDetail);

        $data = ChartReportDetailData::from($request->validated());
        $this->validateParent($chartReport, $data->parentId, $chartReportDetail);
        $this->validateFormula($data);

        $chartReportDetail->update($this->attributes($data));

        return redirect()
            ->route('chart-of-account.chart-reports.edit', $chartReport)
            ->with('success', FlashMessage::updated(FileResources::CHART_REPORTS));
    }

    /**
     * Remove a resource by its object.
     * Remove the specified report row.
     *
     * @param  ChartReport  $chartReport  The report template instance.
     * @param  ChartReportDetail  $chartReportDetail  The report row instance.
     * @return RedirectResponse Redirects to the report edit route.
     *
     * @throws Throwable
     */
    public function destroy(
        ChartReport $chartReport,
        ChartReportDetail $chartReportDetail
    ): RedirectResponse {
        $this->allows(FileResources::CHART_REPORTS, FileAction::REMOVE);
        $this->ensureDetailBelongsToReport($chartReport, $chartReportDetail);

        DB::transaction(function () use ($chartReportDetail): void {
            ChartReportDetail::query()
                ->where('parent_id', $chartReportDetail->id)
                ->update(['parent_id' => null]);
            ChartReportDetail::query()->whereKey($chartReportDetail->id)->delete();
        });

        return redirect()
            ->route('chart-of-account.chart-reports.edit', $chartReport)
            ->with('success', FlashMessage::deleted(FileResources::CHART_REPORTS));
    }

    /**
     * Get the available chart accounts for the report row form.
     *
     * @return Collection<int, Chart>
     */
    private function charts(): Collection
    {
        return Chart::query()
            ->with('chartType')
            ->orderBy('code')
            ->orderBy('name')
            ->get();
    }

    /**
     * Normalize row values so hidden form fields cannot retain stale data.
     *
     * @param  ChartReportDetailData  $data  Validated request values.
     * @return array<string, mixed>
     */
    private function attributes(ChartReportDetailData $data): array
    {
        $reportType = $data->chartReportType?->value;

        return [
            'parent_id' => $data->parentId,
            'chart_id' => $reportType === ChartReportTypeEnum::CHART->value
                ? $data->chartId
                : null,
            'code' => $data->code,
            'name' => $data->name,
            'chart_report_type' => $reportType,
            'chart_total_report_type' => $data->chartTotalReportType?->value,
            'chart_type' => in_array(
                $reportType,
                [ChartReportTypeEnum::TYPES->value, ChartReportTypeEnum::FORMULA->value],
                true
            ) ? $data->chartType?->value : null,
            'formula' => $reportType === ChartReportTypeEnum::FORMULA->value
                ? $data->formula
                : null,
            'line' => $data->line,
            'notes' => $data->notes,
        ];
    }

    /**
     * Ensure a selected parent belongs to this report and cannot create a cycle.
     *
     * @param  ChartReport  $chartReport  The report template instance.
     * @param  string|null  $parentId  Selected parent row ID.
     * @param  ChartReportDetail|null  $detail  Existing row when updating.
     * @return void Returns nothing.
     *
     * @throws ValidationException
     */
    private function validateParent(
        ChartReport $chartReport,
        ?string $parentId,
        ?ChartReportDetail $detail = null
    ): void {
        if ($parentId === null) {
            return;
        }

        /** @var Collection<string, ChartReportDetail> $rows */
        $rows = $chartReport->details()->get()->keyBy('id');
        $parent = $rows->get($parentId);

        if ($parent === null) {
            throw ValidationException::withMessages([
                'parent_id' => __('chart_reports.validation.parent_same_report'),
            ]);
        }

        if ($detail === null) {
            return;
        }

        $visited = [];
        $current = $parent;

        while ($current !== null) {
            if ($current->id === $detail->id || isset($visited[$current->id])) {
                throw ValidationException::withMessages([
                    'parent_id' => __('chart_reports.validation.parent_cycle'),
                ]);
            }

            $visited[$current->id] = true;
            $current = $current->parent_id === null
                ? null
                : $rows->get($current->parent_id);
        }
    }

    /**
     * Validate a formula row with the model's secure parser.
     *
     * @param  ChartReportDetailData  $data  Validated request values.
     * @return void Returns nothing.
     *
     * @throws ValidationException
     */
    private function validateFormula(ChartReportDetailData $data): void
    {
        if ($data->chartReportType?->value !== ChartReportTypeEnum::FORMULA->value) {
            return;
        }

        $detail = new ChartReportDetail;

        if (! $detail->validateFormula($data->formula)) {
            throw ValidationException::withMessages([
                'formula' => __('chart_reports.validation.invalid_formula'),
            ]);
        }
    }

    /**
     * Ensure nested route resources belong to each other.
     *
     * @param  ChartReport  $chartReport  The chart report resource instance.
     * @param  ChartReportDetail  $chartReportDetail  The chart report detail resource instance.
     * @return void Returns nothing.
     */
    private function ensureDetailBelongsToReport(
        ChartReport $chartReport,
        ChartReportDetail $chartReportDetail
    ): void {
        abort_unless($chartReportDetail->chart_report_id === $chartReport->id, 404);
    }
}
