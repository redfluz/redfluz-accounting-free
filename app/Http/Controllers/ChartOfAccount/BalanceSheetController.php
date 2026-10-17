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

class BalanceSheetController extends Controller
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
     * Display the formula-driven Balance Sheet.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::LEDGERS);

        [$asOfDate, $periodLabel, $filters, $comparison] = $this->periods->asOf($request);
        $report = $this->reports->balanceSheet($asOfDate);

        if ($comparison !== null) {
            $report = $this->reports->withComparison(
                $report,
                $this->reports->balanceSheet($comparison['date']),
                $comparison['label'],
            );
        }
        $reportView = $filters['reportView'];
        $report = $this->addPeriodColumns($report, is_string($reportView) ? $reportView : '');

        return view('chart-of-account.balance-sheet.index', [
            ...$report,
            ...$filters,
            'periodLabel' => $periodLabel,
            'ledgerEndDate' => $asOfDate->toDateString(),
        ]);
    }

    /**
     * Download the formula-driven Balance Sheet as PDF.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return Response Returns the HTTP response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::LEDGERS);

        [$asOfDate, $periodLabel, $filters, $comparison] = $this->periods->asOf($request);
        $report = $this->reports->balanceSheet($asOfDate);

        if ($comparison !== null) {
            $report = $this->reports->withComparison(
                $report,
                $this->reports->balanceSheet($comparison['date']),
                $comparison['label'],
            );
        }
        $reportView = $filters['reportView'];
        $report = $this->addPeriodColumns($report, is_string($reportView) ? $reportView : '');
        $report = $this->withoutBalanceCheck($report);

        $setting = Setting::query()->first();

        $pdf = Pdf::loadView('chart-of-account.balance-sheet.pdf', [
            ...$report,
            'periodLabel' => $periodLabel,
            'setting' => $setting,
        ])->setPaper('a4', isset($report['periodColumns']) ? 'landscape' : 'portrait');

        return $pdf->download('balance_sheet_'.$asOfDate->format('Ymd').'.pdf');
    }

    /**
     * Download the clean Balance Sheet as PDF.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return Response Returns the HTTP response.
     */
    public function exportCleanPdf(Request $request): Response
    {
        $this->allows(FileResources::LEDGERS);

        [$asOfDate, $periodLabel, $filters, $comparison] = $this->periods->asOf($request);
        $report = $this->reports->balanceSheet($asOfDate);

        if ($comparison !== null) {
            $report = $this->reports->withComparison(
                $report,
                $this->reports->balanceSheet($comparison['date']),
                $comparison['label'],
            );
        }
        $report = $this->withoutBalanceCheck($report);

        $setting = Setting::query()->first();

        $pdf = Pdf::loadView('chart-of-account.balance-sheet.clean-pdf', [
            ...$report,
            'periodLabel' => $periodLabel,
            'setting' => $setting,
        ])->setPaper('a4');

        return $pdf->download('balance_sheet_clean_'.$asOfDate->format('Ymd').'.pdf');
    }

    /**
     * Download the formula-driven Balance Sheet as Excel.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return StreamedResponse Returns the HTTP response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::LEDGERS);

        [$asOfDate, $periodLabel, $filters, $comparison] = $this->periods->asOf($request);
        $report = $this->reports->balanceSheet($asOfDate);

        if ($comparison !== null) {
            $report = $this->reports->withComparison(
                $report,
                $this->reports->balanceSheet($comparison['date']),
                $comparison['label'],
            );
        }
        $reportView = $filters['reportView'];
        $report = $this->addPeriodColumns($report, is_string($reportView) ? $reportView : '');
        $report = $this->withoutBalanceCheck($report);

        return $this->excel->balanceSheet(
            $report,
            $periodLabel,
        );
    }

    /** @param array<string, mixed> $report  The report data structure.
     * @param  string  $reportView  The report view period identifier.
     * @return array<string, mixed> Returns the report with period columns added.
     */
    private function addPeriodColumns(array $report, string $reportView): array
    {
        $ranges = $this->periods->monthRanges($reportView);
        if ($ranges === []) {
            return $report;
        }

        $periods = array_map(fn (array $range): array => [
            'label' => $range['label'],
            'report' => $this->reports->balanceSheet($range['end']),
        ], $ranges);

        return $this->reports->withPeriodColumns($report, $periods, null);
    }

    /** @param array<string, mixed> $report  The report data structure.
     * @return array<string, mixed> Returns the report without the balance check row.
     */
    private function withoutBalanceCheck(array $report): array
    {
        $rows = [];
        $reportRows = $report['rows'] ?? [];
        if (is_array($reportRows)) {
            foreach ($reportRows as $row) {
                if (is_array($row) && ($row['code'] ?? null) !== 'BALANCE_CHECK') {
                    $rows[] = $row;
                }
            }
        }
        $report['rows'] = $rows;

        return $report;
    }
}
