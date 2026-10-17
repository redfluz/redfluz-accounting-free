@php use App\Models\System\Setting; @endphp
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
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */
--}}

@php
    use App\Enums\ChartTotalReportTypeEnum;

    $periodColumns = $periodColumns ?? [];
    $hasPeriodColumns = $periodColumns !== [];
    $hasComparison = ! $hasPeriodColumns && filled($comparisonLabel ?? null);
    $columnCount = $hasPeriodColumns ? 2 + count($periodColumns) : ($hasComparison ? 4 : 3);
@endphp

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
            font-size: 10px;
            color: #333;
            margin: 0;
        }
        .company-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        .company-info {
            font-size: 9px;
            color: #666;
            margin: 2px 0;
        }
        .header {
            margin-bottom: 14px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .title {
            font-size: 17px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .meta {
            color: #666;
            font-size: 9px;
            margin-top: 4px;
        }
        .warning {
            background: #fff8e1;
            border: 1px solid #d6b656;
            padding: 7px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th,
        td {
            border: 1px solid #ccc;
            padding: 5px 6px;
        }
        th {
            background: #f2f2f2;
            text-align: left;
        }
        .heading {
            background: #e9ecef;
            font-weight: bold;
        }
        .subtotal {
            background: #f9f9f9;
            font-weight: bold;
        }
        .subtotal td {
            border-top: 1px solid #333;
            border-bottom: 0;
        }
        .grand-total {
            background: #ececec;
            font-weight: bold;
            font-size: 11px;
        }
        .grand-total td {
            border-top: 1px solid #333;
            border-bottom: 3px double #333;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-muted {
            color: #777;
        }
        .monthly {
            font-size: 7px;
        }
    </style>
</head>
<body>
    @include('partials.pdf-company-header')

    <div class="header">
        <h1 class="title">{{ $reportName }}</h1>
        <div class="meta">
            {{ __('financial_reports.labels.as_of') }} {{ $periodLabel }} | {{ __('financial_reports.labels.generated_at') }}: {{ now()->format(Setting::getDefaultDate('long_date_format')) }}
        </div>
    </div>

    @if ($unknownLedgerAccounts !== [])
        <div class="warning">
            <strong>{{ __('financial_reports.messages.unmatched_accounts') }}:</strong>
            {{ implode(', ', $unknownLedgerAccounts) }}.
        </div>
    @endif

    <table class="{{ $hasPeriodColumns ? 'monthly' : '' }}">
        <thead>
            <tr>
                <th style="width: 22%">{{ __('financial_reports.table.column_account_code') }}</th>
                <th>{{ __('financial_reports.table.column_account') }}</th>
                @if ($hasPeriodColumns)
                    @foreach ($periodColumns as $column)
                        <th class="text-end">{{ $column }}</th>
                    @endforeach
                @else
                    <th class="text-end" style="width: 22%">
                        {{ $hasComparison ? now()->year : __('financial_reports.table.column_amount') }}
                    </th>
                @endif
                @if ($hasComparison)
                    <th class="text-end" style="width: 22%">{{ $comparisonLabel }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @if ($row['kind'] === 'heading')
                    <tr class="heading">
                        <td colspan="{{ $columnCount }}">{{ $row['name'] }}</td>
                    </tr>
                @elseif ($row['kind'] === 'group_heading')
                    <tr class="heading">
                        <td colspan="{{ $columnCount }}">{{ $row['name'] }}</td>
                    </tr>
                @elseif ($row['kind'] === 'note' || $row['kind'] === 'empty')
                    <tr>
                        <td class="text-muted text-center" colspan="{{ $columnCount }}">{{ $row['name'] }}</td>
                    </tr>
                @elseif ($row['kind'] === 'formula')
                    <tr class="{{ $row['total_type'] === ChartTotalReportTypeEnum::GRAND_TOTAL->value ? 'grand-total' : 'subtotal' }}">
                        <td colspan="2">{{ $row['name'] }}</td>
                        @if ($hasPeriodColumns)
                            @foreach ($row['period_amounts'] as $amount)
                                <td class="text-end">{{ number_format((float) ($amount ?? 0), 2) }}</td>
                            @endforeach
                        @else
                            <td class="text-end">{{ number_format((float) $row['amount'], 2) }}</td>
                        @endif
                        @if ($hasComparison)
                            <td class="text-end">{{ number_format((float) ($row['comparison_amount'] ?? 0), 2) }}</td>
                        @endif
                    </tr>
                @else
                    <tr>
                        <td class="text-muted">{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        @if ($hasPeriodColumns)
                            @foreach ($row['period_amounts'] as $amount)
                                <td class="text-end">{{ number_format((float) ($amount ?? 0), 2) }}</td>
                            @endforeach
                        @else
                            <td class="text-end">{{ number_format((float) $row['amount'], 2) }}</td>
                        @endif
                        @if ($hasComparison)
                            <td class="text-end">{{ number_format((float) ($row['comparison_amount'] ?? 0), 2) }}</td>
                        @endif
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</body>
</html>
