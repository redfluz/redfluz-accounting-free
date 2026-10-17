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

namespace App\Services;

use App\Enums\ChartReportGroupEnum;
use App\Enums\ChartReportTypeEnum;
use App\Enums\ChartTotalReportTypeEnum;
use App\Enums\ChartTypeEnum;
use App\Enums\LedgerStatus;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartReport;
use App\Models\ChartOfAccount\ChartReportDetail;
use App\Models\Finance\Ledger;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use NXP\MathExecutor;
use RuntimeException;
use Throwable;

class FinancialReportService
{
    /**
     * Generate a formula-driven Profit & Loss report.
     *
     * @param  Carbon  $startDate  The start date of the reporting period.
     * @param  Carbon  $endDate  The end date of the reporting period.
     * @return array<string, mixed>
     */
    public function profitLoss(Carbon $startDate, Carbon $endDate): array
    {
        $report = $this->generateReport('income_statement', $startDate, $endDate);
        /** @var array<string, float> $formulaValues */
        $formulaValues = $report['formulaValues'] ?? [];

        $revenue = $formulaValues['REVENUE'] ?? 0.0;
        $profitLoss = $formulaValues['PROFIT_LOSS'] ?? 0.0;

        $report['summary'] = [
            'income' => $revenue,
            'expenses' => round($revenue - $profitLoss, 2),
            'net_profit' => $profitLoss,
        ];

        return $report;
    }

    /**
     * Generate a formula-driven Balance Sheet report.
     *
     * @param  Carbon  $asOfDate  The date as of which the balance sheet is calculated.
     * @return array<string, mixed>
     */
    public function balanceSheet(Carbon $asOfDate): array
    {
        $report = $this->generateReport('balance_sheet', null, $asOfDate);
        /** @var array<string, float> $formulaValues */
        $formulaValues = $report['formulaValues'] ?? [];
        $liabilities = $formulaValues['TOTAL_LIABILITIES'] ?? 0.0;
        $equity = $formulaValues['TOTAL_EQUITY'] ?? 0.0;

        $report['summary'] = [
            'assets' => $formulaValues['TOTAL_ASSETS'] ?? 0.0,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'liabilities_and_equity' => $formulaValues['TOTAL_EQUITY_AND_LIABILITIES'] ?? round($liabilities + $equity, 2),
            'balance_difference' => $formulaValues['BALANCE_CHECK'] ?? 0.0,
        ];

        return $report;
    }

    /**
     * Add a previous-calendar-year amount to every matching report row.
     *
     * @param  array<string, mixed>  $current  The current period report data.
     * @param  array<string, mixed>  $previous  The previous period report data.
     * @param  string  $comparisonLabel  The label for the comparison column.
     * @return array<string, mixed>
     */
    public function withComparison(array $current, array $previous, string $comparisonLabel): array
    {
        $previousRows = collect($this->reportRows($previous))->keyBy(
            fn (array $row): string => $this->comparisonRowKey($row)
        );

        $current['rows'] = collect($this->reportRows($current))->map(function (array $row) use ($previousRows): array {
            $previousRow = $previousRows->get($this->comparisonRowKey($row));
            $row['comparison_amount'] = $previousRow['amount'] ?? null;

            return $row;
        })->all();

        $current['comparisonLabel'] = $comparisonLabel;
        $current['comparisonSummary'] = $previous['summary'] ?? null;
        $current['comparisonOpeningBalance'] = $previous['openingBalance'] ?? null;
        $current['comparisonNetCashFlow'] = $previous['netCashFlow'] ?? null;
        $current['comparisonClosingBalance'] = $previous['closingBalance'] ?? null;
        /** @var array<int, string> $currentUnknown */
        $currentUnknown = $current['unknownLedgerAccounts'] ?? [];
        /** @var array<int, string> $previousUnknown */
        $previousUnknown = $previous['unknownLedgerAccounts'] ?? [];
        $current['unknownLedgerAccounts'] = array_values(array_unique(array_merge(
            $currentUnknown,
            $previousUnknown
        )));
        sort($current['unknownLedgerAccounts']);

        return $current;
    }

    /**
     * Add month-by-month amounts to an aggregate quarter or calendar-year report.
     *
     * @param  array<string, mixed>  $aggregate  The aggregate report data.
     * @param  array<int, array{label: string, report: array<string, mixed>}>  $periods  The individual period reports.
     * @param  string|null  $totalLabel  The optional label for the total column.
     * @return array<string, mixed>
     */
    public function withPeriodColumns(array $aggregate, array $periods, ?string $totalLabel): array
    {
        $lookups = collect($periods)->map(fn (array $period) => collect($this->reportRows($period['report']))->keyBy(
            fn (array $row): string => $this->comparisonRowKey($row)
        ));

        $aggregate['rows'] = collect($this->reportRows($aggregate))->map(function (array $row) use ($lookups, $totalLabel): array {
            $key = $this->comparisonRowKey($row);
            $row['period_amounts'] = $lookups->map(
                fn ($lookup) => $lookup->get($key)['amount'] ?? null
            )->all();

            if ($totalLabel !== null) {
                $row['period_amounts'][] = $row['amount'];
            }

            return $row;
        })->all();

        $aggregate['periodColumns'] = collect($periods)->pluck('label')->all();
        if ($totalLabel !== null) {
            $aggregate['periodColumns'][] = $totalLabel;
        }

        $aggregate['periodCashSummaries'] = collect($periods)->map(fn (array $period): array => [
            'opening_balance' => $period['report']['openingBalance'] ?? null,
            'net_cash_flow' => $period['report']['netCashFlow'] ?? null,
            'closing_balance' => $period['report']['closingBalance'] ?? null,
        ])->all();
        if ($totalLabel !== null) {
            $aggregate['periodCashSummaries'][] = [
                'opening_balance' => $aggregate['openingBalance'] ?? null,
                'net_cash_flow' => $aggregate['netCashFlow'] ?? null,
                'closing_balance' => $aggregate['closingBalance'] ?? null,
            ];
        }

        /** @var array<int, string> $aggregateUnknown */
        $aggregateUnknown = $aggregate['unknownLedgerAccounts'] ?? [];
        /** @var array<int, string> $periodUnknownAccounts */
        $periodUnknownAccounts = collect($periods)->flatMap(fn (array $period) => $this->unknownLedgerAccounts($period['report']))->all();
        $aggregate['unknownLedgerAccounts'] = array_values(array_unique(array_merge(
            $aggregateUnknown,
            $periodUnknownAccounts
        )));
        sort($aggregate['unknownLedgerAccounts']);

        return $aggregate;
    }

    /**
     * Extract the report rows from a configured report array.
     *
     * @param  array<string, mixed>  $report  The configured report data.
     * @return array<int, array<string, mixed>>
     */
    private function reportRows(array $report): array
    {
        $rows = [];
        /** @var array<int, mixed> $reportRows */
        $reportRows = $report['rows'] ?? [];
        foreach ($reportRows as $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Extract unknown ledger account references from a configured report.
     *
     * @param  array<string, mixed>  $report  The configured report data.
     * @return array<int, string>
     */
    private function unknownLedgerAccounts(array $report): array
    {
        /** @var array<int, mixed> $unknownAccounts */
        $unknownAccounts = $report['unknownLedgerAccounts'] ?? [];

        return array_values(array_filter($unknownAccounts, 'is_string'));
    }

    /**
     * Build a unique comparison key from a report row.
     *
     * @param  array<string, mixed>  $row  The report row data.
     * @return string The pipe-delimited comparison key.
     */
    private function comparisonRowKey(array $row): string
    {
        $kind = is_string($row['kind'] ?? null) ? (string) $row['kind'] : '';
        $code = is_string($row['code'] ?? null) ? (string) $row['code'] : '';
        $name = is_string($row['name'] ?? null) ? (string) $row['name'] : '';

        return implode('|', [$kind, $code, $name]);
    }

    /**
     * Generate a Trial Balance with opening, period, and closing debit/credit columns.
     *
     * @param  Carbon  $startDate  The start date of the reporting period.
     * @param  Carbon  $endDate  The end date of the reporting period.
     * @return array{
     *     reportName: string,
     *     rows: array<int, array<string, float|string>>,
     *     totals: array<string, float>,
     *     difference: float,
     *     isBalanced: bool,
     *     unknownLedgerAccounts: array<int, string>
     * }
     */
    public function trialBalance(Carbon $startDate, Carbon $endDate): array
    {
        $charts = $this->charts();
        $openingMovements = $this->emptyMovements($charts);
        $periodMovements = $this->emptyMovements($charts);
        $openingEndDate = $startDate->copy()->subDay()->endOfDay();

        $openingUnknownAccounts = $this->applyLedgerMovements(
            $openingMovements,
            $charts,
            $this->ledgers(null, $openingEndDate, false)
        );
        $periodUnknownAccounts = $this->applyLedgerMovements(
            $periodMovements,
            $charts,
            $this->ledgers($startDate, $endDate, false)
        );

        $rows = [];
        $totals = [
            'opening_debit' => 0.0,
            'opening_credit' => 0.0,
            'period_debit' => 0.0,
            'period_credit' => 0.0,
            'closing_debit' => 0.0,
            'closing_credit' => 0.0,
        ];

        foreach ($charts as $chart) {
            $opening = $openingMovements[$chart->id];
            $period = $periodMovements[$chart->id];
            $openingNet = round($opening['debit'] - $opening['credit'], 2);
            $closingNet = round(
                $openingNet + $period['debit'] - $period['credit'],
                2
            );

            $row = [
                'code' => (string) $chart->code,
                'name' => (string) $chart->name,
                'opening_debit' => max($openingNet, 0.0),
                'opening_credit' => max(-$openingNet, 0.0),
                'period_debit' => round($period['debit'], 2),
                'period_credit' => round($period['credit'], 2),
                'closing_debit' => max($closingNet, 0.0),
                'closing_credit' => max(-$closingNet, 0.0),
            ];

            if ($this->trialBalanceRowIsZero($row)) {
                continue;
            }

            foreach (array_keys($totals) as $column) {
                $totals[$column] = round($totals[$column] + $row[$column], 2);
            }

            $rows[] = $row;
        }

        $difference = round($totals['closing_debit'] - $totals['closing_credit'], 2);
        $unknownLedgerAccounts = array_values(array_unique([
            ...$openingUnknownAccounts,
            ...$periodUnknownAccounts,
        ]));
        sort($unknownLedgerAccounts);

        return [
            'reportName' => __('financial_reports.trial_balance.title'),
            'rows' => $rows,
            'totals' => $totals,
            'difference' => $difference,
            'isBalanced' => abs($difference) < 0.01,
            'unknownLedgerAccounts' => $unknownLedgerAccounts,
        ];
    }

    /**
     * Load one report template, calculate balances, and render ordered output rows.
     *
     * @param  string  $slug  The report template slug to load.
     * @param  Carbon|null  $startDate  The optional start date of the reporting period.
     * @param  Carbon  $endDate  The end date of the reporting period.
     * @return array{
     *     reportName: string,
     *     reportSlug: string,
     *     rows: array<int, array<string, mixed>>,
     *     formulaValues: array<string, float>,
     *     typeTotals: array<string, float>,
     *     groupTotals: array<string, float>,
     *     unknownLedgerAccounts: array<int, string>
     * }
     */
    /** @return array<string, mixed> */
    private function generateReport(
        string $slug,
        ?Carbon $startDate,
        Carbon $endDate
    ): array {
        $template = ChartReport::query()
            ->with(['details.chart'])
            ->where('slug', $slug)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->first();

        if (! $template) {
            throw new RuntimeException(
                __('financial_reports.errors.template_missing', ['slug' => $slug])
            );
        }

        return $this->configuredReport($template, $startDate, $endDate);
    }

    /**
     * Generate one exact saved report design instead of resolving the default by slug.
     *
     * @param  ChartReport  $template  The report template to generate.
     * @param  Carbon|null  $startDate  The optional start date of the reporting period.
     * @param  Carbon  $endDate  The end date of the reporting period.
     * @return array<string, mixed>
     */
    public function report(ChartReport $template, ?Carbon $startDate, Carbon $endDate): array
    {
        $template->loadMissing(['details.chart']);

        return $this->configuredReport($template, $startDate, $endDate);
    }

    /**
     * Build a fully configured report from a loaded template, calculating all amounts.
     *
     * @param  ChartReport  $template  The loaded report template with details.
     * @param  Carbon|null  $startDate  The optional start date of the reporting period.
     * @param  Carbon  $endDate  The end date of the reporting period.
     * @return array<string, mixed>
     */
    private function configuredReport(
        ChartReport $template,
        ?Carbon $startDate,
        Carbon $endDate,
    ): array {

        $charts = $this->charts();
        $movements = $this->emptyMovements($charts);
        $ledgers = $this->ledgers($startDate, $endDate);
        $unknownLedgerAccounts = $this->applyLedgerMovements($movements, $charts, $ledgers);

        $chartAmounts = [];
        $typeAccounts = [];
        $typeTotals = [];
        $groupAccounts = [];
        $groupTotals = [];

        foreach (ChartTypeEnum::cases() as $chartType) {
            $typeAccounts[$chartType->value] = [];
            $typeTotals[$chartType->value] = 0.0;
        }

        foreach (ChartReportGroupEnum::cases() as $reportGroup) {
            $groupAccounts[$reportGroup->value] = [];
            $groupTotals[$reportGroup->value] = 0.0;
        }

        foreach ($charts as $chart) {
            $chartType = $this->resolveChartType($chart);
            $movement = $movements[$chart->id] ?? ['debit' => 0.0, 'credit' => 0.0];
            $amount = $this->normalBalance($chartType, $movement);

            $chartAmounts[$chart->id] = $amount;
            $typeTotals[$chartType->value] = round(
                $typeTotals[$chartType->value] + $amount,
                2
            );

            if ($chartType !== ChartTypeEnum::NONE) {
                $typeAccounts[$chartType->value][] = [
                    'code' => (string) $chart->code,
                    'name' => (string) $chart->name,
                    'amount' => $amount,
                ];
            }

            $reportGroup = $chart->report_group;
            $reportGroupValue = $this->enumValue($reportGroup);

            if ($reportGroupValue !== null && isset($groupAccounts[$reportGroupValue])) {
                $groupAccounts[$reportGroupValue][] = [
                    'code' => (string) $chart->code,
                    'name' => (string) $chart->name,
                    'amount' => $amount,
                ];
                $groupTotals[$reportGroupValue] = round(
                    ($groupTotals[$reportGroupValue] ?? 0.0) + $amount,
                    2
                );
            }
        }

        $chartReferenceLookup = $this->chartReferenceLookup($charts);
        $rows = [];
        $formulaValues = [];

        foreach ($template->details as $detail) {
            $this->appendDetailRows(
                $rows,
                $formulaValues,
                $detail,
                $typeAccounts,
                $typeTotals,
                $groupAccounts,
                $groupTotals,
                $chartAmounts,
                $chartReferenceLookup
            );
        }

        return [
            'reportName' => $template->name,
            'reportSlug' => $template->slug,
            'rows' => $rows,
            'formulaValues' => $formulaValues,
            'typeTotals' => $typeTotals,
            'groupTotals' => $groupTotals,
            'unknownLedgerAccounts' => $unknownLedgerAccounts,
        ];
    }

    /**
     * Convert one configured report detail into one or more display rows.
     *
     * @param  array<int, array<string, mixed>>  $rows  The accumulated output rows (modified by reference).
     * @param  array<string, float>  $formulaValues  The accumulated formula values (modified by reference).
     * @param  ChartReportDetail  $detail  The report detail to convert.
     * @param  array<string, array<int, array{code: string, name: string, amount: float}>>  $typeAccounts  The accounts grouped by chart type.
     * @param  array<string, float>  $typeTotals  The totals by chart type.
     * @param  array<string, array<int, array{code: string, name: string, amount: float}>>  $groupAccounts  The accounts grouped by report group.
     * @param  array<string, float>  $groupTotals  The totals by report group.
     * @param  array<string, float>  $chartAmounts  The amounts keyed by chart ID.
     * @param  array<string, string>  $chartReferenceLookup  The chart reference lookup map.
     * @return void Returns nothing after appending rows by reference.
     */
    private function appendDetailRows(
        array &$rows,
        array &$formulaValues,
        ChartReportDetail $detail,
        array $typeAccounts,
        array $typeTotals,
        array $groupAccounts,
        array $groupTotals,
        array $chartAmounts,
        array $chartReferenceLookup
    ): void {
        if ($detail->chart_report_type === ChartReportTypeEnum::NONE) {
            $rows[] = $this->presentationRow('heading', $detail);

            return;
        }

        if ($detail->chart_report_type === ChartReportTypeEnum::NOTES) {
            $rows[] = $this->presentationRow('note', $detail);

            return;
        }

        if ($detail->chart_report_type === ChartReportTypeEnum::TYPES) {
            $chartType = $detail->chart_type ?? ChartTypeEnum::NONE;
            $accounts = $typeAccounts[$chartType->value] ?? [];

            if ($accounts === []) {
                $rows[] = [
                    'kind' => 'empty',
                    'code' => '',
                    'name' => __('financial_reports.messages.no_accounts'),
                    'amount' => null,
                    'total_type' => ChartTotalReportTypeEnum::NONE->value,
                    'formula' => null,
                ];

                return;
            }

            foreach ($accounts as $account) {
                $rows[] = [
                    'kind' => 'account',
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'amount' => $account['amount'],
                    'total_type' => ChartTotalReportTypeEnum::NONE->value,
                    'formula' => null,
                ];
            }

            return;
        }

        if ($detail->chart_report_type === ChartReportTypeEnum::GROUPS) {
            $reportGroup = $detail->chart_report_group;
            $reportGroupValue = $this->enumValue($reportGroup);
            $accounts = $reportGroupValue === null
                ? []
                : ($groupAccounts[$reportGroupValue] ?? []);

            $rows[] = [
                'kind' => 'group_heading',
                'code' => $detail->code,
                'name' => $detail->name,
                'amount' => null,
                'total_type' => $detail->chart_total_report_type->value,
                'formula' => null,
            ];

            if ($accounts === []) {
                $rows[] = [
                    'kind' => 'empty',
                    'code' => '',
                    'name' => __('financial_reports.messages.no_accounts'),
                    'amount' => null,
                    'total_type' => ChartTotalReportTypeEnum::NONE->value,
                    'formula' => null,
                ];
            } else {
                foreach ($accounts as $account) {
                    $rows[] = [
                        'kind' => 'account',
                        'code' => $account['code'],
                        'name' => $account['name'],
                        'amount' => $account['amount'],
                        'total_type' => ChartTotalReportTypeEnum::NONE->value,
                        'formula' => null,
                    ];
                }
            }

            $formulaValues[$detail->code] = $reportGroupValue !== null ? ($groupTotals[$reportGroupValue] ?? 0.0) : 0.0;

            return;
        }

        if ($detail->chart_report_type === ChartReportTypeEnum::CHART) {
            $chart = $detail->chart;
            if ($chart === null) {
                return;
            }

            $rows[] = [
                'kind' => 'account',
                'code' => (string) $chart->code,
                'name' => $detail->name,
                'amount' => round($chartAmounts[(string) $detail->chart_id] ?? 0.0, 2),
                'total_type' => $detail->chart_total_report_type->value,
                'formula' => null,
            ];

            return;
        }

        if ($detail->chart_report_type === ChartReportTypeEnum::CATEGORIES) {
            throw new InvalidArgumentException(
                __('financial_reports.errors.category_not_supported', ['code' => $detail->code])
            );
        }

        if (! $detail->validateFormula($detail->formula)) {
            throw new InvalidArgumentException(
                __('financial_reports.errors.invalid_formula', ['code' => $detail->code])
            );
        }

        $amount = $this->evaluateFormula(
            (string) $detail->formula,
            $typeTotals,
            $groupTotals,
            $chartAmounts,
            $chartReferenceLookup,
            $formulaValues
        );

        $formulaValues[$detail->code] = $amount;
        $rows[] = [
            'kind' => 'formula',
            'code' => $detail->code,
            'name' => $detail->name,
            'amount' => $amount,
            'total_type' => $detail->chart_total_report_type->value,
            'formula' => $detail->formula,
        ];
    }

    /**
     * Build a presentation row for headings or notes.
     *
     * @param  string  $kind  The row kind (heading or note).
     * @param  ChartReportDetail  $detail  The report detail containing code and name.
     * @return array<string, mixed>
     */
    private function presentationRow(string $kind, ChartReportDetail $detail): array
    {
        return [
            'kind' => $kind,
            'code' => $detail->code,
            'name' => $detail->name,
            'amount' => null,
            'total_type' => $detail->chart_total_report_type->value,
            'formula' => $detail->formula,
        ];
    }

    /**
     * Evaluate a formula after replacing all supported report tags with numbers.
     *
     * @param  string  $formula  The formula expression to evaluate.
     * @param  array<string, float>  $typeTotals  The totals by chart type.
     * @param  array<string, float>  $groupTotals  The totals by report group.
     * @param  array<string, float>  $chartAmounts  The amounts keyed by chart ID.
     * @param  array<string, string>  $chartReferenceLookup  The chart reference lookup map.
     * @param  array<string, float>  $formulaValues  The previously calculated formula values.
     * @return float The evaluated formula result rounded to two decimals.
     */
    private function evaluateFormula(
        string $formula,
        array $typeTotals,
        array $groupTotals,
        array $chartAmounts,
        array $chartReferenceLookup,
        array $formulaValues
    ): float {
        $mathematicalExpression = preg_replace_callback(
            '/\{(\w+):([^{}]+)}/',
            function (array $matches) use ($typeTotals, $groupTotals, $chartAmounts, $chartReferenceLookup, $formulaValues): string {
                $group = mb_strtoupper(mb_trim($matches[1]));
                $reference = mb_trim($matches[2]);

                $amount = match ($group) {
                    'CHART' => $this->resolveChartFormulaAmount(
                        $reference,
                        $chartAmounts,
                        $chartReferenceLookup
                    ),
                    'TYPE', 'SUM_TYPE' => $this->resolveTypeFormulaAmount($reference, $typeTotals),
                    'GROUP', 'SUM_GROUP' => $this->resolveGroupFormulaAmount($reference, $groupTotals),
                    'ROW' => $this->resolveRowFormulaAmount($reference, $formulaValues),
                    'CAT' => throw new InvalidArgumentException(
                        __('financial_reports.errors.category_token_not_supported', ['category' => $reference])
                    ),
                    default => throw new InvalidArgumentException(
                        __('financial_reports.errors.unknown_formula_group', ['group' => $group])
                    ),
                };

                return number_format($amount, 2, '.', '');
            },
            $formula
        );

        if (
            $mathematicalExpression === null
            || preg_match('/[^0-9+\-*\/().\s]/', $mathematicalExpression) === 1
        ) {
            throw new InvalidArgumentException(
                __('financial_reports.errors.invalid_resolved_formula', ['formula' => $formula])
            );
        }

        try {
            $executor = new MathExecutor;
            $result = $executor->execute($mathematicalExpression);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                __('financial_reports.errors.formula_execution_failed', ['formula' => $formula]),
                previous: $exception
            );
        }

        if (! is_numeric($result)) {
            throw new InvalidArgumentException(
                __('financial_reports.errors.formula_not_numeric', ['formula' => $formula])
            );
        }

        return round((float) $result, 2);
    }

    /**
     * Resolve a previously calculated formula row by its configured row code.
     *
     * @param  string  $reference  The row reference to resolve.
     * @param  array<string, float>  $formulaValues  The previously calculated formula values.
     * @return float The resolved row formula amount.
     */
    private function resolveRowFormulaAmount(string $reference, array $formulaValues): float
    {
        foreach ($formulaValues as $code => $amount) {
            if ($this->normalizeReference($code) === $this->normalizeReference($reference)) {
                return $amount;
            }
        }

        throw new InvalidArgumentException(
            __('financial_reports.errors.row_token_not_found', ['row' => $reference])
        );
    }

    /**
     * Resolve a chart formula amount by its reference code or name.
     *
     * @param  string  $reference  The chart reference to resolve.
     * @param  array<string, float>  $chartAmounts  The amounts keyed by chart ID.
     * @param  array<string, string>  $chartReferenceLookup  The chart reference lookup map.
     * @return float The resolved chart amount or zero if not found.
     */
    private function resolveChartFormulaAmount(
        string $reference,
        array $chartAmounts,
        array $chartReferenceLookup
    ): float {
        $chartId = $chartReferenceLookup[$this->normalizeReference($reference)] ?? null;

        if ($chartId === null) {
            throw new InvalidArgumentException(
                __('financial_reports.errors.chart_token_not_found', ['chart' => $reference])
            );
        }

        return $chartAmounts[$chartId] ?? 0.0;
    }

    /**
     * Resolve a report group formula amount by its group reference.
     *
     * @param  string  $reference  The group reference to resolve.
     * @param  array<string, float>  $groupTotals  The totals by report group.
     * @return float The resolved group total or zero if not found.
     */
    private function resolveGroupFormulaAmount(string $reference, array $groupTotals): float
    {
        $group = ChartReportGroupEnum::tryFrom($this->normalizeReference($reference));

        if ($group === null) {
            throw new InvalidArgumentException(
                __('financial_reports.errors.group_token_not_found', ['group' => $reference])
            );
        }

        return $groupTotals[$group->value] ?? 0.0;
    }

    /**
     * Resolve a chart type formula amount by its type reference.
     *
     * @param  string  $reference  The chart type reference to resolve.
     * @param  array<string, float>  $typeTotals  The totals by chart type.
     * @return float The resolved type total or zero if not found.
     */
    private function resolveTypeFormulaAmount(string $reference, array $typeTotals): float
    {
        $chartType = match (mb_strtoupper($reference)) {
            'ASSET', 'ASSETS' => ChartTypeEnum::ASSET,
            'LIABILITY', 'LIABILITIES' => ChartTypeEnum::LIABILITY,
            'EQUITY' => ChartTypeEnum::EQUITY,
            'INCOME' => ChartTypeEnum::INCOME,
            'EXPENSE', 'EXPENSES' => ChartTypeEnum::EXPENSES,
            default => throw new InvalidArgumentException(
                __('financial_reports.errors.chart_type_token_not_found', ['type' => $reference])
            ),
        };

        return $typeTotals[$chartType->value] ?? 0.0;
    }

    /**
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
     * Retrieve posted ledger entries within the specified date range.
     *
     * @param  Carbon|null  $startDate  The optional start date filter.
     * @param  Carbon  $endDate  The end date filter.
     * @param  bool  $excludeYearEndClosing  Whether to exclude year-end closing entries.
     * @return Collection<int, Ledger>
     */
    private function ledgers(
        ?Carbon $startDate,
        Carbon $endDate,
        bool $excludeYearEndClosing = true
    ): Collection {
        $query = Ledger::query()
            ->where('status', LedgerStatus::POSTED);

        if ($startDate !== null) {
            $query->whereDate('date', '>=', $startDate->toDateString());
        }

        if ($startDate !== null && $excludeYearEndClosing) {
            $query->where('document_number', 'not like', 'YEC%');
        }

        return $query
            ->whereDate('date', '<=', $endDate->toDateString())
            ->oldest('date')
            ->get();
    }

    /**
     * Determine whether one Trial Balance row has no opening, period, or closing value.
     *
     * @param  array<string, float|string>  $row  The trial balance row data.
     * @return bool True when every amount column is effectively zero.
     */
    private function trialBalanceRowIsZero(array $row): bool
    {
        return array_all([
            'opening_debit',
            'opening_credit',
            'period_debit',
            'period_credit',
            'closing_debit',
            'closing_credit',
        ], fn ($column) => abs((float) $row[$column]) < 0.005);

    }

    /**
     * Initialize empty debit/credit movement arrays for all charts.
     *
     * @param  Collection<int, Chart>  $charts  The charts collection to initialize movements for.
     * @return array<string, array{debit: float, credit: float}>
     */
    private function emptyMovements(Collection $charts): array
    {
        $movements = [];

        foreach ($charts as $chart) {
            $movements[(string) $chart->id] = [
                'debit' => 0.0,
                'credit' => 0.0,
            ];
        }

        return $movements;
    }

    /**
     * Apply ledger rows using immutable account IDs first and historical codes second.
     *
     * @param  array<string, array{debit: float, credit: float}>  $movements  The movements to update (modified by reference).
     * @param  Collection<int, Chart>  $charts  The charts collection for ID resolution.
     * @param  Collection<int, Ledger>  $ledgers  The ledger entries to apply.
     * @return array<int, string>
     */
    private function applyLedgerMovements(
        array &$movements,
        Collection $charts,
        Collection $ledgers
    ): array {
        /** @var array<string, bool> $chartIds */
        $chartIds = array_fill_keys($charts->modelKeys(), true);
        $referenceLookup = $this->chartReferenceLookup($charts);
        $unknownReferences = [];

        foreach ($ledgers as $ledger) {
            $debitReference = $this->ledgerReference($ledger->debit_id, $ledger->debit_acc);
            $creditReference = $this->ledgerReference($ledger->credit_id, $ledger->credit_acc);

            $debitChartId = $this->resolveLedgerChartId(
                $ledger->debit_id,
                $ledger->debit_acc,
                $chartIds,
                $referenceLookup
            );
            $creditChartId = $this->resolveLedgerChartId(
                $ledger->credit_id,
                $ledger->credit_acc,
                $chartIds,
                $referenceLookup
            );

            if ($debitChartId !== null) {
                $movements[$debitChartId] ??= ['debit' => 0.0, 'credit' => 0.0];
                $movements[$debitChartId]['debit'] += $this->ledgerSideAmount($ledger, 'debit');
            } elseif ($debitReference !== null) {
                $unknownReferences[] = $debitReference;
            }

            if ($creditChartId !== null) {
                $movements[$creditChartId] ??= ['debit' => 0.0, 'credit' => 0.0];
                $movements[$creditChartId]['credit'] += $this->ledgerSideAmount($ledger, 'credit');
            } elseif ($creditReference !== null) {
                $unknownReferences[] = $creditReference;
            }
        }

        $unknownReferences = array_values(array_unique($unknownReferences));
        sort($unknownReferences);

        return $unknownReferences;
    }

    /**
     * Resolve a chart ID from an immutable account ID or historical account code.
     *
     * @param  string|null  $chartId  The immutable chart account ID.
     * @param  string|null  $accountCode  The historical account code.
     * @param  array<string, bool>  $chartIds  The known chart IDs lookup.
     * @param  array<string, string>  $referenceLookup  The chart reference lookup map.
     * @return string|null The resolved chart ID, or null if no match is found.
     */
    private function resolveLedgerChartId(
        ?string $chartId,
        ?string $accountCode,
        array $chartIds,
        array $referenceLookup
    ): ?string {
        if ($chartId !== null && isset($chartIds[$chartId])) {
            return $chartId;
        }

        if ($accountCode === null || mb_trim($accountCode) === '') {
            return null;
        }

        $normalizedReference = $this->normalizeReference($accountCode);
        $matchedChartId = $referenceLookup[$normalizedReference] ?? null;

        if ($matchedChartId !== null) {
            return $matchedChartId;
        }

        $historicalCode = $this->extractHistoricalAccountCode($accountCode);

        if ($historicalCode === null) {
            return null;
        }

        return $referenceLookup[$this->normalizeReference($historicalCode)] ?? null;
    }

    private function ledgerSideAmount(Ledger $ledger, string $side): float
    {
        $sideAmountRaw = data_get($ledger, $side.'_amount', 0);
        $sideAmount = is_numeric($sideAmountRaw) ? (float) $sideAmountRaw : 0.0;

        if ($sideAmount > 0.0) {
            return $sideAmount;
        }

        $totalAmountRaw = data_get($ledger, 'total_amount', 0);
        $unitPriceRaw = data_get($ledger, 'unit_price', 0);
        $totalAmount = is_numeric($totalAmountRaw) ? (float) $totalAmountRaw : 0.0;
        $unitPrice = is_numeric($unitPriceRaw) ? (float) $unitPriceRaw : 0.0;

        return max($totalAmount, $unitPrice);
    }

    private function ledgerReference(?string $chartId, ?string $accountCode): ?string
    {
        if ($accountCode !== null && mb_trim($accountCode) !== '') {
            return $accountCode;
        }

        return $chartId;
    }

    /**
     * Extract the immutable account code from a historical "code - description" label.
     *
     * @param  string  $accountReference  The raw account reference string.
     * @return string|null The extracted account code, or null when none is found.
     */
    private function extractHistoricalAccountCode(string $accountReference): ?string
    {
        $matched = preg_match(
            '/^([A-Za-z0-9][A-Za-z0-9._\/-]*)\s+-\s+.+$/u',
            mb_trim($accountReference),
            $matches
        );

        if ($matched !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Build a normalized reference lookup map for all charts.
     *
     * @param  Collection<int, Chart>  $charts  The charts collection to build the lookup for.
     * @return array<string, string>
     */
    private function chartReferenceLookup(Collection $charts): array
    {
        $lookup = [];

        foreach ($charts as $chart) {
            $id = (string) $chart->id;
            $code = mb_trim((string) $chart->code);
            $name = mb_trim((string) $chart->name);

            $lookup[$this->normalizeReference($id)] = $id;

            if ($code !== '') {
                $lookup[$this->normalizeReference($code)] = $id;
            }

            if ($name !== '') {
                $lookup[$this->normalizeReference($name)] ??= $id;
            }

            if ($code !== '' && $name !== '') {
                $lookup[$this->normalizeReference($code.' - '.$name)] = $id;
            }
        }

        return $lookup;
    }

    private function resolveChartType(Chart $chart): ChartTypeEnum
    {
        $chartType = $chart->chartType;

        if ($chartType === null) {
            return ChartTypeEnum::NONE;
        }

        return match (true) {
            $chartType->isAsset => ChartTypeEnum::ASSET,
            $chartType->isLiability => ChartTypeEnum::LIABILITY,
            $chartType->isEquity => ChartTypeEnum::EQUITY,
            $chartType->isIncome => ChartTypeEnum::INCOME,
            $chartType->isExpenses => ChartTypeEnum::EXPENSES,
            default => ChartTypeEnum::NONE,
        };
    }

    /**
     * Return a positive normal balance for the selected chart type.
     *
     * @param  ChartTypeEnum  $chartType  The chart type determining debit or credit normal.
     * @param  array{debit: float, credit: float}  $movement  The debit and credit movement amounts.
     * @return float The signed normal balance rounded to two decimals.
     */
    private function normalBalance(ChartTypeEnum $chartType, array $movement): float
    {
        $amount = match ($chartType) {
            ChartTypeEnum::ASSET, ChartTypeEnum::EXPENSES => $movement['debit'] - $movement['credit'],
            ChartTypeEnum::LIABILITY,
            ChartTypeEnum::EQUITY,
            ChartTypeEnum::INCOME => $movement['credit'] - $movement['debit'],
            ChartTypeEnum::NONE => 0.0,
        };

        return round($amount, 2);
    }

    private function normalizeReference(string $reference): string
    {
        return mb_strtolower(
            preg_replace('/\s+/', ' ', mb_trim($reference)) ?? mb_trim($reference)
        );
    }

    /**
     * Read a backed-enum value while remaining compatible with raw database strings.
     *
     * @param  BackedEnum|string|null  $value  The value to resolve.
     * @return string|null The resolved scalar value, or null when the input is null.
     */
    private function enumValue(BackedEnum|string|null $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value === null || mb_trim($value) === '') {
            return null;
        }

        return $value;
    }
}
