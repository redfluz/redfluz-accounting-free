<?php

namespace app\Http\Controllers\ChartOfAccount;

use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount\ChartReport;
use App\Services\CalendarFinancialReportPeriodResolver;
use App\Services\FinancialReportExcelExporter;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ChartReportOutputController extends Controller
{
    /**
     * Create a new configured chart report output controller instance.
     *
     * @param  FinancialReportService  $reports  The financial report service instance.
     * @param  FinancialReportExcelExporter  $excel  The financial report spreadsheet exporter.
     * @param  CalendarFinancialReportPeriodResolver  $periods  The report period resolver.
     */
    public function __construct(
        private readonly FinancialReportService $reports,
        private readonly FinancialReportExcelExporter $excel,
        private readonly CalendarFinancialReportPeriodResolver $periods,
    ) {}

    /**
     * Display the specified resource.
     * Display a configured chart report.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  ChartReport  $chartReport  The configured chart report resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(Request $request, ChartReport $chartReport): View
    {
        $this->allows(FileResources::CHART_REPORTS);

        return view('chart-of-account.chart-reports.output', [
            ...$this->output($request, $chartReport),
            'chartReport' => $chartReport,
        ]);
    }

    /**
     * Build the configured report output data.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  ChartReport  $chartReport  The configured chart report resource instance.
     * @return array<string, mixed> Returns the configured report output data.
     */
    private function output(Request $request, ChartReport $chartReport): array
    {
        if ($chartReport->calculation_basis === 'as_of') {
            [$endDate, $periodLabel, $filters, $comparison] = $this->periods->asOf($request);
            $startDate = null;
        } else {
            [$startDate, $endDate, $periodLabel, $filters, $comparison] = $this->periods->range(
                $request,
                'year',
            );
        }

        $report = $this->calculate($chartReport, $startDate, $endDate);

        if ($comparison !== null) {
            if (isset($comparison['date'])) {
                $comparisonStart = null;
                $comparisonEnd = $comparison['date'];
            } else {
                $comparisonStart = $comparison['start'];
                $comparisonEnd = $comparison['end'];
            }
            $report = $this->reports->withComparison(
                $report,
                $this->calculate($chartReport, $comparisonStart, $comparisonEnd),
                $comparison['label'],
            );
        }

        $reportView = $filters['reportView'];
        $ranges = $this->periods->monthRanges(is_string($reportView) ? $reportView : '');
        if ($ranges !== []) {
            $periodReports = collect(array_map(fn (array $range): array => [
                'label' => $range['label'],
                'report' => $this->calculate(
                    $chartReport,
                    $chartReport->calculation_basis === 'as_of' ? null : $range['start'],
                    $range['end'],
                ),
            ], $ranges));

            $report = $this->reports->withPeriodColumns(
                $report,
                $periodReports->all(),
                $chartReport->calculation_basis === 'as_of' ? null : $periodLabel.' Total',
            );
        }

        return [
            ...$filters,
            'report' => $report,
            'periodLabel' => $periodLabel,
        ];
    }

    /**
     * Calculate the report output for the requested date range.
     *
     * @param  ChartReport  $chartReport  The chart report definition.
     * @param  mixed  $startDate  The report start date.
     * @param  mixed  $endDate  The report end date.
     * @return array<string, mixed> The calculated report output.
     */
    private function calculate(ChartReport $chartReport, mixed $startDate, mixed $endDate): array
    {
        return $this->reports->report(
            $chartReport,
            $startDate instanceof Carbon ? $startDate : null,
            $endDate instanceof Carbon ? $endDate : new Carbon,
        );
    }
}
