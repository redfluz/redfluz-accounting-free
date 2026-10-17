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
use App\Http\Requests\ChartOfAccount\ChartSetting\UpdateChartSettingRequest;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Support\Data\ChartOfAccount\ChartSettingData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class ChartSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * Display Chart Settings.
     *
     * Update-only grid.
     *
     * @return View Returns the rendered Blade view.
     */
    public function index(): View
    {
        $this->allows(FileResources::CHART_SETTINGS);

        $chartSettings = ChartSetting::query()
            ->with([
                'cogsChart',
                'incomeChart',
                'bankChart',
                'cashChart',
                'eWalletChart',
                'clientChart',
                'vendorChart',
                'inventoryChart',
                'stockShortageChart',
                'stockSurplusChart',
                'depositChart',
                'inputTaxChart',
                'outputTaxChart',
                'retainedEarningsChart',
                'disposalGainChart',
                'disposalLossChart',
            ])
            ->latest()
            ->paginate(self::PER_PAGE_GRID_NORMAL);

        return view(
            'chart-of-account.chart-settings.index',
            compact('chartSettings')
        );
    }

    /**
     * Show the form for editing an open resource.
     * Show Chart Settings edit page.
     *
     * @param  ChartSetting  $chartSetting  The chart setting resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(ChartSetting $chartSetting): View
    {
        $this->allows(
            FileResources::CHART_SETTINGS,
            FileAction::UPDATE
        );

        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()
            ->with('chartType')
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        $cogsCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isExpenses === true
            )
            ->values();

        $incomeCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isIncome === true
            )
            ->values();

        // Each money-account dropdown is scoped to the charts that actually
        // have an account row in the corresponding table. A chart that only
        // has a bank account must not appear in the cash or e-wallet dropdown,
        // and vice versa — the three tables are independent after the STI
        // split, so the `isBank` flag on charts is no longer specific enough.
        $bankChartIds = DB::table('banks')
            ->whereNull('deleted_at')
            ->pluck('chart_id');

        $cashChartIds = DB::table('cash_accounts')
            ->whereNull('deleted_at')
            ->pluck('chart_id');

        $eWalletChartIds = DB::table('e_wallet_accounts')
            ->whereNull('deleted_at')
            ->pluck('chart_id');

        $bankCharts = $charts
            ->filter(
                fn (Chart $chart) => $bankChartIds->contains($chart->id)
            )
            ->values();

        $cashCharts = $charts
            ->filter(
                fn (Chart $chart) => $cashChartIds->contains($chart->id)
            )
            ->values();

        $eWalletCharts = $charts
            ->filter(
                fn (Chart $chart) => $eWalletChartIds->contains($chart->id)
            )
            ->values();

        $clientCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isAsset === true
            )
            ->values();

        $vendorCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isLiability === true
            )
            ->values();

        $inventoryCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isAsset === true
            )
            ->values();

        $stockShortageCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isExpenses === true
            )
            ->values();

        $stockSurplusCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isIncome === true
            )
            ->values();

        $depositCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isAsset === true
                    || $chart->chartType?->isLiability === true
            )
            ->values();

        $inputTaxCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isAsset === true
            )
            ->values();

        $outputTaxCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isLiability === true
            )
            ->values();

        $retainedEarningsCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isEquity === true
            )
            ->values();

        $disposalGainCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isIncome === true
            )
            ->values();

        $disposalLossCharts = $charts
            ->filter(
                fn (Chart $chart) => $chart->chartType?->isExpenses === true
            )
            ->values();

        return view(
            'chart-of-account.chart-settings.edit',
            compact(
                'chartSetting',
                'cogsCharts',
                'incomeCharts',
                'bankCharts',
                'cashCharts',
                'eWalletCharts',
                'clientCharts',
                'vendorCharts',
                'inventoryCharts',
                'stockShortageCharts',
                'stockSurplusCharts',
                'depositCharts',
                'inputTaxCharts',
                'outputTaxCharts',
                'retainedEarningsCharts',
                'disposalGainCharts',
                'disposalLossCharts',
            )
        );
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateChartSettingRequest  $request  The validated chart setting being saved.
     * @param  ChartSetting  $chartSetting  The chart setting resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(
        UpdateChartSettingRequest $request,
        ChartSetting $chartSetting
    ): RedirectResponse {
        $data = ChartSettingData::from($request->validated());

        $chartSetting->update([
            'cogs_chart_uuid' => $data->cogsChartUuid,
            'income_chart_uuid' => $data->incomeChartUuid,
            'bank_chart_uuid' => $data->bankChartUuid,
            'cash_chart_uuid' => $data->cashChartUuid,
            'e_wallet_chart_uuid' => $data->eWalletChartUuid,
            'client_chart_uuid' => $data->clientChartUuid,
            'vendor_chart_uuid' => $data->vendorChartUuid,
            'inventory_chart_uuid' => $data->inventoryChartUuid,
            'stock_shortage_chart_uuid' => $data->stockShortageChartUuid,
            'stock_surplus_chart_uuid' => $data->stockSurplusChartUuid,
            'deposit_chart_uuid' => $data->depositChartUuid,
            'input_tax_chart_uuid' => $data->inputTaxChartUuid,
            'output_tax_chart_uuid' => $data->outputTaxChartUuid,
            'retained_earnings_chart_uuid' => $data->retainedEarningsChartUuid,
            'disposal_gain_chart_uuid' => $data->disposalGainChartUuid,
            'disposal_loss_chart_uuid' => $data->disposalLossChartUuid,
            'retained_earnings_formula' => $data->retainedEarningsFormula,
            'closing_date' => $data->closingDate?->format('Y-m-d'),
            'start_finance_period_month' => $data->startFinancePeriodMonth,
            'strict_journal_posting' => $data->strictJournalPosting ?? false,
        ]);

        return redirect()
            ->route('chart-of-account.chart-settings.index')
            ->with(
                'success',
                FlashMessage::updated(
                    FileResources::CHART_SETTINGS
                )
            );
    }
}
