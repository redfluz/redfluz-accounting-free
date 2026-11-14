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

namespace App\Http\Controllers\ChartOfAccount\Setting;

use App\Constants\FlashMessage;
use App\Enums\ChartOfAccount\ChartReportGroupEnum;
use App\Enums\ChartOfAccount\ChartTypeEnum;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChartOfAccount\Chart\StoreChartRequest;
use App\Http\Requests\ChartOfAccount\Chart\UpdateChartRequest;
use App\Models\Bank\Bank;
use App\Models\Bank\CashAccount;
use App\Models\Bank\EWalletAccount;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartType;
use App\Models\Finance\Ledger;
use App\Models\Inventory\ProductLedger;
use App\Support\Data\ChartOfAccount\ChartData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChartController extends Controller
{
    /**
     * Display a listing of the resource.
     * Display a listing of chart accounts.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::CHARTS);

        $search = $request->input('search');
        $chartTypeFilter = $request->input('chart_type');
        $reportGroupFilter = $request->input('report_group');

        $charts = $this->buildFilteredQuery($request)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('chart-of-account.charts.index', compact(
            'charts',
            'search',
            'chartTypeFilter',
            'reportGroupFilter'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * Show the form for creating a new chart account.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::CHARTS, FileAction::ADD);

        /** @var Collection<int, ChartType> $chartTypes */
        $chartTypes = ChartType::query()->orderBy('name')->get();
        $reportGroupsByChartType = $this->reportGroupsByChartType($chartTypes);

        return view(
            'chart-of-account.charts.create',
            compact('chartTypes', 'reportGroupsByChartType')
        );
    }

    /**
     * Handle the incoming request to create a new resource.
     * Store a newly created chart account in storage.
     *
     * @param  StoreChartRequest  $request  The validated chart being created.
     * @return RedirectResponse Redirects to the index route.
     */
    public function store(StoreChartRequest $request): RedirectResponse
    {
        $data = ChartData::from($request->validated());
        $this->validateReportGroup($data);

        Chart::query()->create($data->toCreateRow());

        return redirect()
            ->route('chart-of-account.charts.index')
            ->with('success', FlashMessage::created(FileResources::CHARTS));
    }

    /**
     * Show the form for editing an open resource.
     * Show the form for editing the specified chart account.
     *
     * @param  Chart  $chart  The chart resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Chart $chart): View
    {
        $this->allows(FileResources::CHARTS, FileAction::UPDATE);

        /** @var Collection<int, ChartType> $chartTypes */
        $chartTypes = ChartType::query()->orderBy('name')->get();
        $reportGroupsByChartType = $this->reportGroupsByChartType($chartTypes);

        return view(
            'chart-of-account.charts.edit',
            compact('chart', 'chartTypes', 'reportGroupsByChartType')
        );
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateChartRequest  $request  The validated chart being saved.
     * @param  Chart  $chart  The chart resource instance.
     * @return RedirectResponse Redirects to the index route.
     */
    public function update(UpdateChartRequest $request, Chart $chart): RedirectResponse
    {
        $data = ChartData::from($request->validated());
        $this->validateReportGroup($data);

        $chart->update([
            'chart_type_id' => $data->chartTypeId,
            'report_group' => $data->reportGroup,
            'code' => $data->code,
            'name' => $data->name,
            'isBank' => $data->isBank ?? false,
            'isDebtor' => $data->isDebtor ?? false,
            'isCreditor' => $data->isCreditor ?? false,
        ]);

        return redirect()
            ->route('chart-of-account.charts.index')
            ->with('success', FlashMessage::updated(FileResources::CHARTS));
    }

    /**
     * Remove a resource by its object.
     * Remove the specified chart account from storage.
     *
     * @param  Chart  $chart  The chart resource instance.
     * @return RedirectResponse Redirects to the index route.
     */
    public function destroy(Chart $chart): RedirectResponse
    {
        $this->allows(FileResources::CHARTS, FileAction::REMOVE);

        // An account is the spine of the ledger: every journal line names its
        // code. Deleting one that already carries entries leaves those entries
        // pointing at an account that no longer exists, which silently corrupts
        // the trial balance, the profit and loss statement and the balance sheet.
        // Only an unused account may be removed.
        $entries = Ledger::query()
            ->where(function (Builder $query) use ($chart): void {
                $query->where('chart_code', $chart->code);
            })
            ->count();

        $movements = ProductLedger::query()
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($chart): void {
                $query->where('chart_code', $chart->code);
            })
            ->count();

        if ($entries > 0 || $movements > 0) {
            throw ValidationException::withMessages([
                'chart' => __('charts.validation.in_use', [
                    'count' => $entries + $movements,
                ]),
            ]);
        }

        // A chart linked to a money account cannot go either: the account
        // screens read the chart for its code and name, and a null link
        // turns every one of those lists into a 500.
        $linkedAccounts = Bank::query()
            ->where('chart_id', $chart->getKey())
            ->whereNull('deleted_at')
            ->count()
            + CashAccount::query()
                ->where('chart_id', $chart->getKey())
                ->whereNull('deleted_at')
                ->count()
            + EWalletAccount::query()
                ->where('chart_id', $chart->getKey())
                ->whereNull('deleted_at')
                ->count();

        if ($linkedAccounts > 0) {
            throw ValidationException::withMessages([
                'chart' => __('charts.validation.linked_bank', [
                    'count' => $linkedAccounts,
                ]),
            ]);
        }

        $chart->delete();

        return redirect()
            ->route('chart-of-account.charts.index')
            ->with('success', FlashMessage::deleted(FileResources::CHARTS));
    }

    /**
     * Build the chart account query shared by the index and Excel export.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return Builder<Chart> Returns the filtered chart query.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        $search = $request->input('search');
        $searchString = is_string($search) ? $search : '';
        $chartTypeFilter = $request->input('chart_type');
        $reportGroupFilter = $request->input('report_group');

        return Chart::query()
            ->with('chartType')
            ->when($searchString !== '', function (Builder $query) use ($searchString): void {
                $query->where(function (Builder $query) use ($searchString): void {
                    $query->where('name', 'like', "%$searchString%")
                        ->orWhere('code', 'like', "%$searchString%");
                });
            })
            ->when($chartTypeFilter !== null && $chartTypeFilter !== '', function (Builder $query) use ($chartTypeFilter): void {
                $query->where('chart_type_id', $chartTypeFilter);
            })
            ->when($reportGroupFilter !== null && $reportGroupFilter !== '', function (Builder $query) use ($reportGroupFilter): void {
                $query->where('report_group', $reportGroupFilter);
            })
            ->latest();
    }

    /**
     * Build the report groups allowed for every configured chart type.
     *
     * @param  Collection<int, ChartType>  $chartTypes  The configured chart type instances.
     * @return array<string, array<int, string>> Returns the allowed report groups keyed by chart type ID.
     */
    private function reportGroupsByChartType(Collection $chartTypes): array
    {
        $reportGroups = ChartReportGroupEnum::cases();
        $groupsByChartType = [];

        /** @var ChartType $chartType */
        foreach ($chartTypes as $chartType) {
            $fundamentalType = $this->fundamentalChartType($chartType);
            $groupsByChartType[(string) $chartType->id] = collect($reportGroups)
                ->filter(
                    fn (ChartReportGroupEnum $reportGroup): bool => $reportGroup->chartType() === $fundamentalType
                )
                ->map(
                    fn (ChartReportGroupEnum $reportGroup): string => $reportGroup->value
                )
                ->values()
                ->all();
        }

        return $groupsByChartType;
    }

    /**
     * Validate that the selected report group belongs to the selected chart type.
     *
     * @param  ChartData  $data  The validated chart data.
     * @return void Returns nothing.
     *
     * @throws ValidationException
     */
    private function validateReportGroup(ChartData $data): void
    {
        $reportGroupValue = $data->reportGroup?->value;

        if ($reportGroupValue === null) {
            return;
        }
        /** @var ChartType|null $chartType */
        $chartType = ChartType::query()->findOrFail($data->chartTypeId);
        if ($chartType == null) {
            return;
        }
        $reportGroup = ChartReportGroupEnum::from($reportGroupValue);

        if ($reportGroup->chartType() === $this->fundamentalChartType($chartType)) {
            return;
        }

        throw ValidationException::withMessages([
            'report_group' => __('charts.validation.report_group_type_mismatch', [
                'type' => $chartType->name,
            ]),
        ]);
    }

    /**
     * Resolve the fundamental enum type represented by a ChartType record.
     *
     * @param  ChartType  $chartType  The chart type resource instance.
     * @return ChartTypeEnum Returns the resulting value.
     */
    private function fundamentalChartType(ChartType $chartType): ChartTypeEnum
    {
        return match (true) {
            $chartType->isAsset => ChartTypeEnum::ASSET,
            $chartType->isLiability => ChartTypeEnum::LIABILITY,
            $chartType->isEquity => ChartTypeEnum::EQUITY,
            $chartType->isIncome => ChartTypeEnum::INCOME,
            $chartType->isExpenses => ChartTypeEnum::EXPENSES,
            default => ChartTypeEnum::NONE,
        };
    }
}
