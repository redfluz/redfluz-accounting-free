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

use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Models\System\Setting;
use App\Services\CalendarFinancialReportPeriodResolver;
use App\Services\FinancialReportExcelExporter;
use App\Services\FinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  FinancialReportService  $reports  The reports resource instance.
     * @param  FinancialReportExcelExporter  $excel  The excel resource instance.
     * @param  CalendarFinancialReportPeriodResolver  $periods  The reporting-period resolver.
     */
    public function __construct(
        private readonly FinancialReportService $reports,
        private readonly FinancialReportExcelExporter $excel,
        private readonly CalendarFinancialReportPeriodResolver $periods,
    ) {}

    /**
     * Display a listing of the resource.
     * Display the formula-driven Profit & Loss report.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::LEDGERS);

        [$startDate, $endDate, $periodLabel, $filters, $comparison] = $this->periods->range($request, 'year');
        $report = $this->reports->profitLoss($startDate, $endDate);

        if ($comparison !== null) {
            $report = $this->reports->withComparison(
                $report,
                $this->reports->profitLoss($comparison['start'], $comparison['end']),
                $comparison['label'],
            );
        }
        $reportView = $filters['reportView'];
        $report = $this->addPeriodColumns($report, is_string($reportView) ? $reportView : '', $periodLabel);

        return view('chart-of-account.profit-loss.index', [
            ...$report,
            ...$filters,
            'periodLabel' => $periodLabel,
            'ledgerStartDate' => $startDate->toDateString(),
            'ledgerEndDate' => $endDate->toDateString(),
        ]);
    }

    /**
     * Download the formula-driven Profit & Loss report as PDF.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return Response Returns the HTTP response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::LEDGERS);

        [$startDate, $endDate, $periodLabel, $filters, $comparison] = $this->periods->range($request, 'year');
        $report = $this->reports->profitLoss($startDate, $endDate);

        if ($comparison !== null) {
            $report = $this->reports->withComparison(
                $report,
                $this->reports->profitLoss($comparison['start'], $comparison['end']),
                $comparison['label'],
            );
        }
        $reportView = $filters['reportView'];
        $report = $this->addPeriodColumns($report, is_string($reportView) ? $reportView : '', $periodLabel);

        $setting = Setting::query()->first();

        $pdf = Pdf::loadView('chart-of-account.profit-loss.pdf', [
            ...$report,
            'periodLabel' => $periodLabel,
            'setting' => $setting,
        ])->setPaper('a4', isset($report['periodColumns']) ? 'landscape' : 'portrait');

        return $pdf->download('profit_loss_'.$endDate->format('Ymd').'.pdf');
    }

    /**
     * Download the clean Profit & Loss report as PDF.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return Response Returns the HTTP response.
     */
    public function exportCleanPdf(Request $request): Response
    {
        $this->allows(FileResources::LEDGERS);

        [$startDate, $endDate, $periodLabel, $filters, $comparison] = $this->periods->range($request, 'year');
        $report = $this->reports->profitLoss($startDate, $endDate);

        if ($comparison !== null) {
            $report = $this->reports->withComparison(
                $report,
                $this->reports->profitLoss($comparison['start'], $comparison['end']),
                $comparison['label'],
            );
        }

        $setting = Setting::query()->first();

        $pdf = Pdf::loadView('chart-of-account.profit-loss.clean-pdf', [
            ...$report,
            'periodLabel' => $periodLabel,
            'setting' => $setting,
        ])->setPaper('a4');

        return $pdf->download('profit_loss_clean_'.$endDate->format('Ymd').'.pdf');
    }

    /**
     * Download the formula-driven Profit & Loss report as Excel.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return StreamedResponse Returns the HTTP response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::LEDGERS);

        [$startDate, $endDate, $periodLabel, $filters, $comparison] = $this->periods->range($request, 'year');
        $report = $this->reports->profitLoss($startDate, $endDate);

        if ($comparison !== null) {
            $report = $this->reports->withComparison(
                $report,
                $this->reports->profitLoss($comparison['start'], $comparison['end']),
                $comparison['label'],
            );
        }
        $reportView = $filters['reportView'];
        $report = $this->addPeriodColumns($report, is_string($reportView) ? $reportView : '', $periodLabel);

        return $this->excel->profitLoss(
            $report,
            $periodLabel,
        );
    }

    /** @param array<string, mixed> $report  The report data structure.
     * @param  string  $reportView  The report view period identifier.
     * @param  string  $periodLabel  The human-readable period label.
     * @return array<string, mixed> Returns the report with period columns added.
     */
    private function addPeriodColumns(array $report, string $reportView, string $periodLabel): array
    {
        $ranges = $this->periods->monthRanges($reportView);
        if ($ranges === []) {
            return $report;
        }

        $periods = array_map(fn (array $range): array => [
            'label' => $range['label'],
            'report' => $this->reports->profitLoss($range['start'], $range['end']),
        ], $ranges);

        return $this->reports->withPeriodColumns($report, $periods, $periodLabel.' Total');
    }
}
