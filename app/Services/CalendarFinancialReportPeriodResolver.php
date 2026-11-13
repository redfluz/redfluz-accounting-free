<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use LogicException;

class CalendarFinancialReportPeriodResolver
{
    private const array REPORT_VIEWS = ['normal', 'q1', 'q2', 'q3', 'q4', 'whole_year', 'whole_year_compare'];

    private function date(int $year, int $month, int $day = 1): Carbon
    {
        return Carbon::create($year, $month, $day) ?? throw new LogicException('Invalid report period.');
    }

    /**
     * Return the calendar-month ranges represented by a quarter or whole-year view.
     *
     * @param  string  $reportView  The report view identifier.
     * @return array<int, array{start: Carbon, end: Carbon, label: string}>
     */
    public function monthRanges(string $reportView): array
    {
        $months = match ($reportView) {
            'q1' => range(1, 3),
            'q2' => range(4, 6),
            'q3' => range(7, 9),
            'q4' => range(10, 12),
            'whole_year' => range(1, 12),
            default => [],
        };

        return array_map(function (int $month): array {
            $start = $this->date(now()->year, $month)->startOfDay();

            return [
                'start' => $start,
                'end' => $start->copy()->endOfMonth()->endOfDay(),
                'label' => $start->translatedFormat('M'),
            ];
        }, $months);
    }

    /**
     * @param  Request  $request  The HTTP request containing period parameters.
     * @param  string  $defaultPeriodType  The fallback period type when none is specified.
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: array<string, mixed>, 4: ?array{start: Carbon, end: Carbon, label: string}}
     */
    public function range(Request $request, string $defaultPeriodType): array
    {
        /** @var array{report_view?: string, period_type?: string, year?: int, month?: int, quarter?: int, start_date?: string, end_date?: string} $validated */
        $validated = $request->validate([
            'report_view' => ['nullable', Rule::in(self::REPORT_VIEWS)],
            'period_type' => ['nullable', Rule::in(['year', 'month', 'quarter', 'custom'])],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'start_date' => ['nullable', 'date', 'required_if:period_type,custom'],
            'end_date' => ['nullable', 'date', 'required_if:period_type,custom', 'after_or_equal:start_date'],
        ]);

        $reportView = (string) ($validated['report_view'] ?? 'normal');
        $calendarYear = now()->year;
        $comparison = null;

        if (preg_match('/^q([1-4])$/', $reportView, $matches) === 1) {
            $quarter = (int) $matches[1];
            $startDate = $this->date($calendarYear, (($quarter - 1) * 3) + 1)->startOfDay();
            $endDate = $startDate->copy()->addMonths(2)->endOfMonth()->endOfDay();
            $periodLabel = 'Q'.$quarter.' '.$calendarYear;
            $periodType = 'quarter';
            $year = $calendarYear;
            $month = now()->month;
        } elseif (in_array($reportView, ['whole_year', 'whole_year_compare'], true)) {
            $startDate = $this->date($calendarYear, 1)->startOfDay();
            $endDate = $this->date($calendarYear, 12, 31)->endOfDay();
            $periodLabel = (string) $calendarYear;
            $periodType = 'year';
            $year = $calendarYear;
            $month = now()->month;
            $quarter = (int) ceil(now()->month / 3);

            if ($reportView === 'whole_year_compare') {
                $comparisonYear = $calendarYear - 1;
                $comparison = [
                    'start' => $this->date($comparisonYear, 1)->startOfDay(),
                    'end' => $this->date($comparisonYear, 12, 31)->endOfDay(),
                    'label' => (string) $comparisonYear,
                ];
            }
        } else {
            $periodType = (string) ($validated['period_type'] ?? $defaultPeriodType);
            $year = (int) ($validated['year'] ?? now()->year);
            $month = (int) ($validated['month'] ?? now()->month);
            $quarter = (int) ($validated['quarter'] ?? (int) ceil(now()->month / 3));

            if ($periodType === 'custom') {
                abort_unless(isset($validated['start_date'], $validated['end_date']), 422);
                $startDate = Carbon::parse((string) $validated['start_date'])->startOfDay();
                $endDate = Carbon::parse((string) $validated['end_date'])->endOfDay();
                $periodLabel = $startDate->format('d M Y').' to '.$endDate->format('d M Y');
            } elseif ($periodType === 'month') {
                $startDate = $this->date($year, $month)->startOfDay();
                $endDate = $startDate->copy()->endOfMonth()->endOfDay();
                $periodLabel = $startDate->format('F Y');
            } elseif ($periodType === 'quarter') {
                $startDate = $this->date($year, (($quarter - 1) * 3) + 1)->startOfDay();
                $endDate = $startDate->copy()->addMonths(2)->endOfMonth()->endOfDay();
                $periodLabel = 'Q'.$quarter.' '.$year;
            } else {
                $startDate = $this->date($year, 1)->startOfDay();
                $endDate = $this->date($year, 12, 31)->endOfDay();
                $periodLabel = (string) $year;
            }
        }

        return [$startDate, $endDate, $periodLabel, [
            'reportView' => $reportView,
            'periodType' => $periodType,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'startDate' => $validated['start_date'] ?? null,
            'endDate' => $validated['end_date'] ?? null,
            'comparisonLabel' => $comparison['label'] ?? null,
        ], $comparison];
    }

    /**
     * @param  Request  $request  The HTTP request containing period parameters.
     * @return array{0: Carbon, 1: string, 2: array<string, mixed>, 3: ?array{date: Carbon, label: string}}
     */
    public function asOf(Request $request): array
    {
        /** @var array{report_view?: string, period_type?: string, year?: int, month?: int, quarter?: int, as_of_date?: string} $validated */
        $validated = $request->validate([
            'report_view' => ['nullable', Rule::in(self::REPORT_VIEWS)],
            'period_type' => ['nullable', Rule::in(['year', 'month', 'quarter', 'custom'])],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'as_of_date' => ['nullable', 'date', 'required_if:period_type,custom'],
        ]);

        $reportView = (string) ($validated['report_view'] ?? 'normal');
        $calendarYear = now()->year;
        $comparison = null;

        if (preg_match('/^q([1-4])$/', $reportView, $matches) === 1) {
            $quarter = (int) $matches[1];
            $asOfDate = $this->date($calendarYear, $quarter * 3)->endOfMonth()->endOfDay();
            $periodType = 'quarter';
            $year = $calendarYear;
            $month = now()->month;
        } elseif (in_array($reportView, ['whole_year', 'whole_year_compare'], true)) {
            $asOfDate = $this->date($calendarYear, 12, 31)->endOfDay();
            $periodType = 'year';
            $year = $calendarYear;
            $month = now()->month;
            $quarter = (int) ceil(now()->month / 3);

            if ($reportView === 'whole_year_compare') {
                $comparisonYear = $calendarYear - 1;
                $comparison = [
                    'date' => $this->date($comparisonYear, 12, 31)->endOfDay(),
                    'label' => (string) $comparisonYear,
                ];
            }
        } else {
            $periodType = (string) ($validated['period_type'] ?? 'month');
            $year = (int) ($validated['year'] ?? now()->year);
            $month = (int) ($validated['month'] ?? now()->month);
            $quarter = (int) ($validated['quarter'] ?? (int) ceil(now()->month / 3));

            if ($periodType === 'custom') {
                abort_unless(isset($validated['as_of_date']), 422);
                $asOfDate = Carbon::parse((string) $validated['as_of_date'])->endOfDay();
            } elseif ($periodType === 'quarter') {
                $asOfDate = $this->date($year, $quarter * 3)->endOfMonth()->endOfDay();
            } elseif ($periodType === 'year') {
                $asOfDate = $this->date($year, 12, 31)->endOfDay();
            } else {
                $asOfDate = $this->date($year, $month)->endOfMonth()->endOfDay();
            }
        }

        return [$asOfDate, $asOfDate->format('d M Y'), [
            'reportView' => $reportView,
            'periodType' => $periodType,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'asOfDate' => $asOfDate->toDateString(),
            'comparisonLabel' => $comparison['label'] ?? null,
        ], $comparison];
    }
}
