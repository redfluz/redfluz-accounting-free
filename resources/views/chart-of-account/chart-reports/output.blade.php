@php
    use App\Enums\ChartTotalReportTypeEnum;

    $rows = $report['rows'];
    $periodColumns = $report['periodColumns'] ?? [];
    $hasPeriodColumns = $periodColumns !== [];
    $hasComparison = !$hasPeriodColumns && filled($report['comparisonLabel'] ?? null);
    $columnCount = $hasPeriodColumns ? 2 + count($periodColumns) : ($hasComparison ? 4 : 3);
    $isAsOf = $chartReport->calculation_basis === 'as_of';
@endphp

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 bg-white py-3">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="me-2" data-lucide="file-text"></i>
                    {{ $report['reportName'] }}
                </h4>
                <div class="small fg-secondary">
                    {{ __('chart_reports.output_title') }} · {{ $periodLabel }} ·
                    {{ __('chart_reports.calculation_basis.' . $chartReport->calculation_basis) }}
                </div>
            </div>

            <div class="d-flex gap-1">
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('chart-of-account.chart-reports.index') }}">
                    <i class="me-1" data-lucide="list"></i>
                    {{ __('chart_reports.title') }}
                </a>
                <a class="btn btn-solid theme-success btn-sm"
                    href="{{ route('chart-of-account.chart-reports.output.excel', [$chartReport, ...request()->query()]) }}">
                    <i class="me-1" data-lucide="file-spreadsheet"></i>
                    {{ __('financial_reports.actions.excel') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm"
                    href="{{ route('chart-of-account.chart-reports.output.pdf', [$chartReport, ...request()->query()]) }}"
                    target="_blank">
                    <i class="me-1" data-lucide="file-text"></i>
                    {{ __('financial_reports.actions.pdf') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @if ($report['unknownLedgerAccounts'] !== [])
                <div class="alert alert theme-warning small">
                    <strong>{{ __('financial_reports.messages.unmatched_accounts') }}:</strong>
                    {{ implode(', ', $report['unknownLedgerAccounts']) }}.
                </div>
            @endif

            <div class="accordion mb-4 accordion-gap">
                <details class="accordion-item">
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="list-filter"></i>
                        <span class="fw-bold">{{ __('financial_reports.form.filters') }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                        <form autocomplete="off" class="row g-2 align-items-end" method="GET"
                            action="{{ route('chart-of-account.chart-reports.output', $chartReport) }}">
                            <div class="md:col-3">
                                <label class="form-label small fw-bold" for="report_view">
                                    {{ __('financial_reports.form.main_view') }}
                                </label>
                                <select class="form-control" id="report_view" name="report_view">
                                    @foreach (['normal', 'q1', 'q2', 'q3', 'q4', 'whole_year', 'whole_year_compare'] as $view)
                                        <option value="{{ $view }}" @selected($reportView === $view)>
                                            {{ __('financial_reports.report_view.' . $view) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-2 normal-period-field">
                                <label class="form-label small fw-bold" for="period_type">
                                    {{ __('financial_reports.form.report_period') }}
                                </label>
                                <select class="form-control" id="period_type" name="period_type">
                                    <option value="year" @selected($periodType === 'year')>
                                        {{ __('financial_reports.period.year') }}
                                    </option>
                                    <option value="month" @selected($periodType === 'month')>
                                        {{ __('financial_reports.period.month') }}
                                    </option>
                                    <option value="quarter" @selected($periodType === 'quarter')>
                                        {{ __('financial_reports.period.quarter') }}
                                    </option>
                                    <option value="custom" @selected($periodType === 'custom')>
                                        {{ $isAsOf ? __('financial_reports.period.custom_date') : __('financial_reports.period.custom_range') }}
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-2 filter-field normal-period-field" data-for="year month quarter">
                                <label class="form-label small fw-bold"
                                    for="year">{{ __('financial_reports.form.year') }}</label>
                                <select class="form-control" id="year" name="year">
                                    @for ($reportYear = now()->year; $reportYear >= now()->year - 10; $reportYear--)
                                        <option value="{{ $reportYear }}" @selected($year === $reportYear)>
                                            {{ $reportYear }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="md:col-2 filter-field normal-period-field" data-for="month">
                                <label class="form-label small fw-bold"
                                    for="month">{{ __('financial_reports.form.month') }}</label>
                                <select class="form-control" id="month" name="month">
                                    @foreach (range(1, 12) as $reportMonth)
                                        <option value="{{ $reportMonth }}" @selected($month === $reportMonth)>
                                            {{ \Carbon\Carbon::create(null, $reportMonth, 1)->translatedFormat('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-2 filter-field normal-period-field" data-for="quarter">
                                <label class="form-label small fw-bold"
                                    for="quarter">{{ __('financial_reports.form.quarter') }}</label>
                                <select class="form-control" id="quarter" name="quarter">
                                    @foreach (range(1, 4) as $reportQuarter)
                                        <option value="{{ $reportQuarter }}" @selected($quarter === $reportQuarter)>
                                            Q{{ $reportQuarter }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($isAsOf)
                                <div class="md:col-2 filter-field normal-period-field" data-for="custom">
                                    <label class="form-label small fw-bold" for="as_of_date">
                                        {{ __('financial_reports.form.as_of_date') }}
                                    </label>
                                    <input class="form-control" id="as_of_date" name="as_of_date" type="text"
                                        data-bs-toggle="datepicker" autocomplete="off" value="{{ $asOfDate }}" />
                                </div>
                            @else
                                <div class="md:col-2 filter-field normal-period-field" data-for="custom">
                                    <label class="form-label small fw-bold"
                                        for="start_date">{{ __('financial_reports.form.start_date') }}</label>
                                    <input class="form-control" id="start_date" name="start_date" type="text"
                                        data-bs-toggle="datepicker" autocomplete="off" value="{{ $startDate }}" />
                                </div>
                                <div class="md:col-2 filter-field normal-period-field" data-for="custom">
                                    <label class="form-label small fw-bold"
                                        for="end_date">{{ __('financial_reports.form.end_date') }}</label>
                                    <input class="form-control" id="end_date" name="end_date" type="text"
                                        data-bs-toggle="datepicker" autocomplete="off" value="{{ $endDate }}" />
                                </div>
                            @endif

                            <div class="md:col-2 d-flex gap-1">
                                <button class="btn btn-solid theme-secondary flex-grow-1" type="submit">
                                    <i class="me-1" data-lucide="list-filter"></i>
                                    {{ __('financial_reports.actions.apply') }}
                                </button>
                                @if (request()->query())
                                    <a class="btn btn-solid theme-secondary"
                                        href="{{ route('chart-of-account.chart-reports.output', $chartReport) }}">
                                        <i class="" data-lucide="x"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                </details>

                @if ($hasPeriodColumns)
                    <div class="mb-2">
                        <label class="small fg-secondary" for="custom-report-month-scroll">
                            {{ __('financial_reports.labels.horizontal_scroll') }}
                        </label>
                        <div class="form-range">
                            <input id="custom-report-month-scroll" class="report-scroll-range form-range-input"
                                type="range" min="0" max="100" value="0" />
                        </div>
                    </div>
                @endif

                <div class="report-table-scroll table-responsive rounded-5 border">
                    <table class="table-hover mb-0 table align-middle" @style(['min-width: 1200px' => $hasPeriodColumns])>
                        <thead class="bg-1">
                            <tr>
                                <th>{{ __('financial_reports.table.column_account_code') }}</th>
                                <th>{{ __('financial_reports.table.column_account') }}</th>
                                @if ($hasPeriodColumns)
                                    @foreach ($periodColumns as $column)
                                        <th class="text-nowrap text-end">{{ $column }}</th>
                                    @endforeach
                                @else
                                    <th class="text-end">
                                        {{ $hasComparison ? $periodLabel : __('financial_reports.table.column_amount') }}
                                    </th>
                                @endif
                                @if ($hasComparison)
                                    <th class="text-end">{{ $report['comparisonLabel'] }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @if (in_array($row['kind'], ['heading', 'group_heading'], true))
                                    <tr class="{{ $row['kind'] === 'heading' ? 'bg-subtle-secondary' : 'bg-1' }}">
                                        <td class="fw-bold" colspan="{{ $columnCount }}">{{ $row['name'] }}</td>
                                    </tr>
                                @elseif (in_array($row['kind'], ['note', 'empty'], true))
                                    <tr>
                                        <td class="fg-secondary text-center" colspan="{{ $columnCount }}">
                                            {{ $row['name'] }}
                                        </td>
                                    </tr>
                                @else
                                    <tr @class([
                                        'fw-bold' => $row['kind'] === 'formula',
                                        // 红 = 涨 (gain), 绿 = 跌 (loss). Row tint only.
                                        'bg-danger-subtle' =>
                                            $row['kind'] === 'formula' &&
                                            $row['total_type'] === ChartTotalReportTypeEnum::GRAND_TOTAL->value &&
                                            (float) $row['amount'] < 0,
                                        'bg-success-subtle' =>
                                            $row['kind'] === 'formula' &&
                                            $row['total_type'] === ChartTotalReportTypeEnum::GRAND_TOTAL->value &&
                                            (float) $row['amount'] >= 0,
                                    ])>
                                        <td class="badge theme-secondary">{{ $row['code'] }}</td>
                                        <td>
                                            {{ $row['name'] }}
                                            @if ($row['kind'] === 'formula')
                                                <div class="small fw-normal fg-secondary">{{ $row['formula'] }}</div>
                                            @endif
                                        </td>
                                        @if ($hasPeriodColumns)
                                            @foreach ($row['period_amounts'] as $amount)
                                                <td class="text-nowrap text-end">
                                                    {{ number_format((float) ($amount ?? 0), 2) }}
                                                </td>
                                            @endforeach
                                        @else
                                            <td class="text-end">{{ number_format((float) $row['amount'], 2) }}</td>
                                        @endif
                                        @if ($hasComparison)
                                            <td class="text-end">
                                                {{ number_format((float) ($row['comparison_amount'] ?? 0), 2) }}
                                            </td>
                                        @endif
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td class="fg-secondary py-4 text-center" colspan="{{ $columnCount }}">
                                        {{ __('chart_reports.rows_empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endsection

    @section('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const periodType = document.getElementById('period_type');
                const reportView = document.getElementById('report_view');
                const fields = document.querySelectorAll('.filter-field');
                const normalFields = document.querySelectorAll('.normal-period-field');

                function refreshPeriodFields() {
                    const isNormal = reportView.value === 'normal';
                    normalFields.forEach((field) => field.classList.toggle('d-none', !isNormal));
                    fields.forEach((field) => {
                        const allowed = field.dataset.for.split(' ');
                        field.classList.toggle('d-none', !isNormal || !allowed.includes(periodType.value));
                    });
                }

                periodType.addEventListener('change', refreshPeriodFields);
                reportView.addEventListener('change', refreshPeriodFields);
                refreshPeriodFields();

                const scrollRange = document.querySelector('.report-scroll-range');
                const tableScroller = document.querySelector('.report-table-scroll');
                if (scrollRange && tableScroller) {
                    const maxScroll = () => Math.max(0, tableScroller.scrollWidth - tableScroller.clientWidth);
                    const syncRange = () => {
                        const maximum = maxScroll();
                        scrollRange.disabled = maximum === 0;
                        scrollRange.value = maximum === 0 ? 0 : Math.round((tableScroller.scrollLeft / maximum) *
                            100);
                    };
                    scrollRange.addEventListener(
                        'input',
                        () => (tableScroller.scrollLeft = maxScroll() * (scrollRange.value / 100)),
                    );
                    tableScroller.addEventListener('scroll', syncRange);
                    window.addEventListener('resize', syncRange);
                    requestAnimationFrame(syncRange);
                }
            });
        </script>
    @endsection
