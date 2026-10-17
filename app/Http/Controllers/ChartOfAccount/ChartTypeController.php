<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
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

namespace App\Http\Controllers\ChartOfAccount;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChartOfAccount\ChartType\StoreChartTypeRequest;
use App\Http\Requests\ChartOfAccount\ChartType\UpdateChartTypeRequest;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartType;
use App\Support\Data\ChartOfAccount\ChartTypeData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChartTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * Display a listing of chart types.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::CHART_TYPES);

        $search = $request->input('search');

        $query = ChartType::query();

        if (is_string($search) && $search !== '') {
            $query->where('name', 'like', "%$search%");
        }

        $chartTypes = $query
            ->latest()
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('chart-of-account.chart-types.index', compact('chartTypes', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     * Show the form for creating a new chart type.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::CHART_TYPES, FileAction::ADD);

        return view('chart-of-account.chart-types.create');
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreChartTypeRequest  $request  The validated chart type being created.
     * @return RedirectResponse Redirects to the index route.
     */
    public function store(StoreChartTypeRequest $request): RedirectResponse
    {
        $data = ChartTypeData::from($request->validated());

        ChartType::query()->create([
            'name' => $data->name,
            'isAsset' => (int) ($data->accountType === 'asset'),
            'isLiability' => (int) ($data->accountType === 'liability'),
            'isEquity' => (int) ($data->accountType === 'equity'),
            'isIncome' => (int) ($data->accountType === 'income'),
            'isExpenses' => (int) ($data->accountType === 'expenses'),
            'isDefault' => (int) ($data->isDefault ?? false),
        ]);

        return redirect()
            ->route('chart-of-account.chart-types.index')
            ->with('success', FlashMessage::created(FileResources::CHART_TYPES));
    }

    /**
     * Show the form for editing an open resource.
     * Show the form for editing the specified chart type.
     *
     * @param  ChartType  $chartType  The chart type resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(ChartType $chartType): View
    {
        $this->allows(FileResources::CHART_TYPES, FileAction::UPDATE);

        return view('chart-of-account.chart-types.edit', compact('chartType'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateChartTypeRequest  $request  The validated chart type being saved.
     * @param  ChartType  $chartType  The chart type resource instance.
     * @return RedirectResponse Redirects to the index route.
     */
    public function update(UpdateChartTypeRequest $request, ChartType $chartType): RedirectResponse
    {
        $data = ChartTypeData::from($request->validated());

        $chartType->update([
            'name' => $data->name,
            'isAsset' => (int) ($data->accountType === 'asset'),
            'isLiability' => (int) ($data->accountType === 'liability'),
            'isEquity' => (int) ($data->accountType === 'equity'),
            'isIncome' => (int) ($data->accountType === 'income'),
            'isExpenses' => (int) ($data->accountType === 'expenses'),
            'isDefault' => (int) ($data->isDefault ?? false),
        ]);

        return redirect()
            ->route('chart-of-account.chart-types.index')
            ->with('success', FlashMessage::updated(FileResources::CHART_TYPES));
    }

    /**
     * Remove a resource by its object.
     * Remove the specified chart type from storage.
     *
     * @param  ChartType  $chartType  The chart type resource instance.
     * @return RedirectResponse Redirects to the index route.
     */
    public function destroy(ChartType $chartType): RedirectResponse
    {
        $this->allows(FileResources::CHART_TYPES, FileAction::REMOVE);

        // An account type is what decides whether its accounts appear under
        // assets, income, expenses and so on. Removing a type that still has
        // accounts assigned makes belongsTo resolve to null, every one of those
        // accounts is then classified as none, and they silently drop out of the
        // statements. Measured on the seeded data, deleting the expense type
        // moved the profit and loss statement from a loss of 75,525 to a profit
        // of 9,530 without any other figure changing. Only an unused type may be
        // removed.
        $assigned = Chart::query()
            ->where('chart_type_id', $chartType->id)
            ->whereNull('deleted_at')
            ->count();

        if ($assigned > 0) {
            throw ValidationException::withMessages([
                'chart_type' => __('chart_types.validation.in_use', ['count' => $assigned]),
            ]);
        }

        $chartType->delete();

        return redirect()
            ->route('chart-of-account.chart-types.index')
            ->with('success', FlashMessage::deleted(FileResources::CHART_TYPES));
    }
}
