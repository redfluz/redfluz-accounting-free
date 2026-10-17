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
use App\Services\FinancialReportExcelExporter;
use App\Services\FinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  FinancialReportService  $reports  The reports resource instance.
     * @param  FinancialReportExcelExporter  $excel  The excel resource instance.
     */
    public function __construct(
        private readonly FinancialReportService $reports,
        private readonly FinancialReportExcelExporter $excel,
    ) {}

    /**
     * Display a listing of the resource.
     * Display the Trial Balance report for the selected accounting period.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::TRIAL_BALANCE);

        [$startDate, $endDate, $periodLabel, $filters] = $this->resolvePeriod($request);

        return view('chart-of-account.trial-balance.index', [
            ...$this->reports->trialBalance($startDate, $endDate),
            ...$filters,
            'periodLabel' => $periodLabel,
            'ledgerOpeningEndDate' => $startDate->copy()->subDay()->toDateString(),
            'ledgerStartDate' => $startDate->toDateString(),
            'ledgerEndDate' => $endDate->toDateString(),
        ]);
    }

    /**
     * Download the Trial Balance report as PDF.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return Response Returns the HTTP response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::TRIAL_BALANCE);

        [$startDate, $endDate, $periodLabel] = $this->resolvePeriod($request);
        $report = $this->reports->trialBalance($startDate, $endDate);

        $setting = Setting::query()->first();

        $pdf = Pdf::loadView('chart-of-account.trial-balance.pdf', [
            ...$report,
            'periodLabel' => $periodLabel,
            'setting' => $setting,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('trial_balance_'.$endDate->format('Ymd').'.pdf');
    }

    /**
     * Download the Trial Balance report as Excel.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return StreamedResponse Returns the HTTP response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::TRIAL_BALANCE);

        [$startDate, $endDate, $periodLabel] = $this->resolvePeriod($request);

        return $this->excel->trialBalance(
            $this->reports->trialBalance($startDate, $endDate),
            $periodLabel,
        );
    }

    /**
     * Resolve the requested Trial Balance date range.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return array{
     *     0: Carbon,
     *     1: Carbon,
     *     2: string,
     *     3: array{
     *         periodType: string,
     *         year: int,
     *         month: int,
     *         quarter: int,
     *         startDate: ?string,
     *         endDate: ?string
     *     }
     * }
     */
    private function resolvePeriod(Request $request): array
    {
        /** @var array{
         *     period_type?: string,
         *     year?: int,
         *     month?: int,
         *     quarter?: int,
         *     start_date?: string,
         *     end_date?: string
         * } $validated
         */
        $validated = $request->validate([
            'period_type' => ['nullable', Rule::in(['year', 'month', 'quarter', 'custom'])],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'start_date' => ['nullable', 'date', 'required_if:period_type,custom'],
            'end_date' => ['nullable', 'date', 'required_if:period_type,custom', 'after_or_equal:start_date'],
        ]);

        $periodType = $validated['period_type'] ?? 'month';
        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);
        $quarter = (int) ($validated['quarter'] ?? (int) ceil(now()->month / 3));

        if ($periodType === 'custom') {
            abort_unless(isset($validated['start_date'], $validated['end_date']), 422);
            $startDate = Carbon::parse((string) $validated['start_date'])->startOfDay();
            $endDate = Carbon::parse((string) $validated['end_date'])->endOfDay();
            $periodLabel = $startDate->format('d M Y').' to '.$endDate->format('d M Y');
        } elseif ($periodType === 'year') {
            $startDate = Carbon::parse(sprintf('%04d-01-01', $year))->startOfDay();
            $endDate = Carbon::parse(sprintf('%04d-12-31', $year))->endOfDay();
            $periodLabel = (string) $year;
        } elseif ($periodType === 'quarter') {
            $startMonth = (($quarter - 1) * 3) + 1;
            $startDate = Carbon::parse(sprintf('%04d-%02d-01', $year, $startMonth))->startOfDay();
            $endDate = $startDate->copy()->addMonths(2)->endOfMonth()->endOfDay();
            $periodLabel = 'Q'.$quarter.' '.$year;
        } else {
            $startDate = Carbon::parse(sprintf('%04d-%02d-01', $year, $month))->startOfMonth()->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();
            $periodLabel = $startDate->format('F Y');
        }

        return [
            $startDate,
            $endDate,
            $periodLabel,
            [
                'periodType' => $periodType,
                'year' => $year,
                'month' => $month,
                'quarter' => $quarter,
                'startDate' => $validated['start_date'] ?? null,
                'endDate' => $validated['end_date'] ?? null,
            ],
        ];
    }
}
