{{--
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
 */
--}}

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 bg-white py-3">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="me-2" data-lucide="scale"></i>
                    {{ $reportName }}
                </h4>
                <div class="small fg-secondary">
                    {{ __('financial_reports.trial_balance.subtitle', ['period' => $periodLabel]) }}
                </div>
            </div>
            <div class="d-flex gap-1">
                <a class="btn btn-solid theme-success btn-sm"
                    href="{{ route('chart-of-account.trial-balance.export-excel', request()->query()) }}">
                    <i class="me-1" data-lucide="file-spreadsheet"></i>
                    {{ __('financial_reports.actions.excel') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm"
                    href="{{ route('chart-of-account.trial-balance.export-pdf', request()->query()) }}" target="_blank">
                    <i class="me-1" data-lucide="file-text"></i>
                    {{ __('financial_reports.actions.pdf') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            @if ($unknownLedgerAccounts !== [])
                <div class="alert alert theme-warning small">
                    <strong>{{ __('financial_reports.messages.unmatched_accounts') }}:</strong>
                    {{ implode(', ', $unknownLedgerAccounts) }}.
                    {{ __('financial_reports.messages.unmatched_accounts_help') }}
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
                            action="{{ route('chart-of-account.trial-balance.index') }}">
                            <div class="md:col-2">
                                <label class="form-label small fw-bold"
                                    for="period_type">{{ __('financial_reports.form.report_period') }}</label>
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
                                        {{ __('financial_reports.period.custom_range') }}
                                    </option>
                                </select>
                            </div>
                            <div class="md:col-2 filter-field" data-for="year month quarter">
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
                            <div class="md:col-2 filter-field" data-for="month">
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
                            <div class="md:col-2 filter-field" data-for="quarter">
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
                            <div class="md:col-2 filter-field" data-for="custom">
                                <label class="form-label small fw-bold"
                                    for="start_date">{{ __('financial_reports.form.start_date') }}</label>
                                <input class="form-control" id="start_date" name="start_date" type="text"
                                    data-bs-toggle="datepicker" autocomplete="off" value="{{ $startDate }}" />
                            </div>
                            <div class="md:col-2 filter-field" data-for="custom">
                                <label class="form-label small fw-bold"
                                    for="end_date">{{ __('financial_reports.form.end_date') }}</label>
                                <input class="form-control" id="end_date" name="end_date" type="text"
                                    data-bs-toggle="datepicker" autocomplete="off" value="{{ $endDate }}" />
                            </div>
                            <div class="md:col-2 d-flex gap-1">
                                <button class="btn btn-solid theme-secondary flex-grow-1" type="submit">
                                    <i class="me-1" data-lucide="list-filter"></i>
                                    {{ __('financial_reports.actions.apply') }}
                                </button>
                                @if (request()->query())
                                    <a class="btn btn-solid theme-secondary"
                                        href="{{ route('chart-of-account.trial-balance.index') }}"
                                        title="{{ __('financial_reports.actions.reset') }}">
                                        <i class="" data-lucide="x"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                </details>

                <div
                    class="alert {{ $isBalanced ? 'alert theme-success' : 'alert theme-danger' }} d-flex align-items-center justify-content-between">
                    <span>
                        <i class="me-1" data-lucide="{{ $isBalanced ? 'circle-check' : 'triangle-alert' }}"></i>
                        {{ $isBalanced
                            ? __('financial_reports.trial_balance.balanced')
                            : __('financial_reports.trial_balance.not_balanced', ['amount' => number_format(abs($difference), 2)]) }}
                    </span>
                    <strong>{{ number_format($totals['closing_debit'], 2) }} =
                        {{ number_format($totals['closing_credit'], 2) }}</strong>
                </div>

                <div class="table-responsive rounded-5 overflow-hidden border">
                    <table class="table-hover table-striped mb-0 table text-nowrap align-middle">
                        <thead class="bg-1">
                            <tr>
                                <th>{{ __('financial_reports.table.column_account_code') }}</th>
                                <th>{{ __('financial_reports.table.column_account') }}</th>
                                <th class="text-end">{{ __('financial_reports.table.opening_debit') }}</th>
                                <th class="text-end">{{ __('financial_reports.table.opening_credit') }}</th>
                                <th class="text-end">{{ __('financial_reports.table.period_debit') }}</th>
                                <th class="text-end">{{ __('financial_reports.table.period_credit') }}</th>
                                <th class="text-end">{{ __('financial_reports.table.closing_debit') }}</th>
                                <th class="text-end">{{ __('financial_reports.table.closing_credit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="text-center">{{ $row['code'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    @foreach (['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit'] as $column)
                                        @php
                                            $ledgerParameters = [
                                                'account' => $row['code'],
                                                'posted_only' => 1,
                                            ];

                                            if (str_starts_with($column, 'opening_')) {
                                                $ledgerParameters['end_date'] = $ledgerOpeningEndDate;
                                            } elseif (str_starts_with($column, 'period_')) {
                                                $ledgerParameters['start_date'] = $ledgerStartDate;
                                                $ledgerParameters['end_date'] = $ledgerEndDate;
                                            } else {
                                                $ledgerParameters['end_date'] = $ledgerEndDate;
                                            }
                                        @endphp
                                        <td class="text-end">
                                            @if (abs((float) $row[$column]) >= 0.005)
                                                <a class="fw-semibold text-decoration-none"
                                                    href="{{ route('finance.ledgers.index', $ledgerParameters) }}"
                                                    title="{{ __('financial_reports.actions.view_ledger') }}">
                                                    {{ number_format((float) $row[$column], 2) }}
                                                    <i class="small ms-1" data-lucide="external-link"></i>
                                                </a>
                                            @else
                                                {{ number_format((float) $row[$column], 2) }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td class="fg-secondary py-4 text-center" colspan="8">
                                        {{ __('financial_reports.messages.no_trial_balance_rows') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-1 fw-bold">
                            <tr>
                                <td colspan="2">{{ __('financial_reports.table.total') }}</td>
                                @foreach (['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit'] as $column)
                                    <td class="text-end">{{ number_format((float) $totals[$column], 2) }}</td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endsection

    @section('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const periodType = document.getElementById('period_type');
                const fields = document.querySelectorAll('.filter-field');

                function refreshPeriodFields() {
                    fields.forEach(function(field) {
                        field.classList.toggle('d-none', !field.dataset.for.split(' ').includes(periodType
                            .value));
                    });
                }

                periodType.addEventListener('change', refreshPeriodFields);
                refreshPeriodFields();
            });
        </script>
    @endsection
