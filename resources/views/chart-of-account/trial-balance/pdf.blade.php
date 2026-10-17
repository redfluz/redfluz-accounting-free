@php use App\Models\System\Setting; @endphp
{{--
/**
 * ============================================================================
 *
 * @author      redfluz
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 */
--}}

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8" />
    <title>{{ $reportName }}</title>
    <style>
        body {
            font-family:
                DejaVu Sans,
                sans-serif;
            font-size: 8px;
            color: #333;
            margin: 0;
        }
        .company-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .company-info {
            font-size: 8px;
            color: #666;
            margin: 2px 0;
        }
        .header {
            margin-bottom: 12px;
            border-bottom: 2px solid #333;
            padding-bottom: 7px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .meta {
            color: #666;
            margin-top: 4px;
        }
        .status {
            padding: 6px;
            margin: 8px 0;
            border: 1px solid #999;
        }
        .warning {
            background: #fff8e1;
            border: 1px solid #d6b656;
            padding: 6px;
            margin: 8px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th,
        td {
            border: 1px solid #ccc;
            padding: 4px;
        }
        th {
            background: #f2f2f2;
        }
        tfoot {
            background: #ececec;
            font-weight: bold;
        }
        tfoot td {
            border-top: 2px solid #333;
            border-bottom: 3px double #333;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    @include('partials.pdf-company-header')

    <div class="header">
        <h1 class="title">{{ $reportName }}</h1>
        <div class="meta">
            {{ __('financial_reports.labels.period') }}: {{ $periodLabel }} | {{ __('financial_reports.labels.generated_at') }}: {{ now()->format(Setting::getDefaultDate('long_date_format')) }}
        </div>
    </div>

    @if ($unknownLedgerAccounts !== [])
        <div class="warning">
            <strong>{{ __('financial_reports.messages.unmatched_accounts') }}:</strong>
            {{ implode(', ', $unknownLedgerAccounts) }}.
        </div>
    @endif

    <div class="status">
        {{
            $isBalanced
            ? __('financial_reports.trial_balance.balanced')
            : __('financial_reports.trial_balance.not_balanced', ['amount' => number_format(abs($difference), 2)])
        }}
    </div>

    <table>
        <thead>
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
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    @foreach (['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit'] as $column)
                        <td class="text-end">{{ number_format((float) $row[$column], 2) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="8">
                        {{ __('financial_reports.messages.no_trial_balance_rows') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">{{ __('financial_reports.table.total') }}</td>
                @foreach (['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit'] as $column)
                    <td class="text-end">{{ number_format((float) $totals[$column], 2) }}</td>
                @endforeach
            </tr>
        </tfoot>
    </table>
</body>
</html>
