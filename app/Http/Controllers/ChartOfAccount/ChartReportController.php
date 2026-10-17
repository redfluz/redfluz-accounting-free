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
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChartOfAccount\ChartReport\StoreChartReportRequest;
use App\Http\Requests\ChartOfAccount\ChartReport\UpdateChartReportRequest;
use App\Models\ChartOfAccount\ChartReport;
use App\Support\Data\ChartOfAccount\ChartReportData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ChartReportController extends Controller
{
    /**
     * Display a listing of the resource.
     * Display a listing of financial report templates.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::CHART_REPORTS);

        $search = $request->string('search')->trim()->toString();

        $chartReports = ChartReport::query()
            ->withCount('details')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%$search%")
                        ->orWhere('name', 'like', "%$search%")
                        ->orWhere('slug', 'like', "%$search%");
                });
            })
            ->orderBy('slug')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('chart-of-account.chart-reports.index', compact('chartReports', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     * Show the form for creating a financial report template.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::CHART_REPORTS, FileAction::ADD);

        return view('chart-of-account.chart-reports.create');
    }

    /**
     * Handle the incoming request to create a new resource.
     * Store a newly created financial report template.
     *
     * @param  StoreChartReportRequest  $request  The validated report being created.
     * @return RedirectResponse Redirects to the edit route.
     *
     * @throws Throwable
     */
    public function store(StoreChartReportRequest $request): RedirectResponse
    {
        $data = ChartReportData::from($request->validated());

        $chartReport = DB::transaction(function () use ($data): ChartReport {
            $isDefault = $data->isDefault ?? false;

            if ($isDefault) {
                $this->clearDefaultForSlug($data->slug ?? '');
            }

            return ChartReport::query()->create([
                'code' => $data->code,
                'name' => $data->name,
                'slug' => $data->slug,
                'calculation_basis' => $data->calculationBasis,
                'is_default' => $isDefault,
            ]);
        });

        return redirect()
            ->route('chart-of-account.chart-reports.edit', $chartReport)
            ->with('success', FlashMessage::created(FileResources::CHART_REPORTS));
    }

    /**
     * Show the form for editing an open resource.
     * Show the form for editing a financial report template and its rows.
     *
     * @param  ChartReport  $chartReport  The report template instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(ChartReport $chartReport): View
    {
        $this->allows(FileResources::CHART_REPORTS, FileAction::UPDATE);

        $chartReport->load([
            'details.chart',
            'details.parent',
        ]);

        return view('chart-of-account.chart-reports.edit', compact('chartReport'));
    }

    /**
     * Update an existing resource by its object.
     * Update the specified financial report template.
     *
     * @param  UpdateChartReportRequest  $request  The validated report being saved.
     * @param  ChartReport  $chartReport  The report template instance.
     * @return RedirectResponse Redirects to the edit route.
     *
     * @throws Throwable
     */
    public function update(UpdateChartReportRequest $request, ChartReport $chartReport): RedirectResponse
    {
        $data = ChartReportData::from($request->validated());

        DB::transaction(function () use ($chartReport, $data): void {
            $isDefault = $data->isDefault ?? false;

            if ($isDefault) {
                $this->clearDefaultForSlug($data->slug ?? '', $chartReport);
            }

            $chartReport->update([
                'code' => $data->code,
                'name' => $data->name,
                'slug' => $data->slug,
                'calculation_basis' => $data->calculationBasis,
                'is_default' => $isDefault,
            ]);
        });

        return redirect()
            ->route('chart-of-account.chart-reports.edit', $chartReport)
            ->with('success', FlashMessage::updated(FileResources::CHART_REPORTS));
    }

    /**
     * Remove a resource by its object.
     * Remove the specified financial report template and its rows.
     *
     * @param  ChartReport  $chartReport  The report template instance.
     * @return RedirectResponse Redirects to the index route.
     *
     * @throws Throwable
     */
    public function destroy(ChartReport $chartReport): RedirectResponse
    {
        $this->allows(FileResources::CHART_REPORTS, FileAction::REMOVE);

        DB::transaction(function () use ($chartReport): void {
            $slug = $chartReport->slug;
            $wasDefault = $chartReport->is_default;

            $chartReport->delete();

            if ($wasDefault) {
                ChartReport::query()
                    ->where('slug', $slug)
                    ->oldest()
                    ->first()
                    ?->update(['is_default' => true]);
            }
        });

        return redirect()
            ->route('chart-of-account.chart-reports.index')
            ->with('success', FlashMessage::deleted(FileResources::CHART_REPORTS));
    }

    /**
     * Clear the current default template for a report slug.
     *
     * @param  string  $slug  The report slug.
     * @param  ChartReport|null  $except  Report that must not be changed.
     * @return void Returns nothing.
     */
    private function clearDefaultForSlug(string $slug, ?ChartReport $except = null): void
    {
        $exceptKey = $except?->getKey();
        ChartReport::query()
            ->where('slug', $slug)
            ->when($exceptKey !== null, fn (Builder $query) => $query->whereKeyNot($exceptKey))
            ->update(['is_default' => false]);
    }
}
