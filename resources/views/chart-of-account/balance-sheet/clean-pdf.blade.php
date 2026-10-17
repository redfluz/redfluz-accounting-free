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
    $company = $setting ?? null;
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
            font-size: 11px;
            color: #222;
            margin: 0;
            padding: 20px 30px;
        }
        .company-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        .company-info {
            font-size: 10px;
            color: #666;
            margin: 2px 0;
        }
        .report-header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 8px 0;
            text-transform: uppercase;
        }
        .report-meta {
            font-size: 10px;
            color: #666;
            margin: 3px 0;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 18px 0 8px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #999;
        }
        .subsection-title {
            font-size: 11px;
            font-weight: bold;
            margin: 12px 0 5px 0;
        }
        .rows-table {
            width: 100%;
            border-collapse: collapse;
        }
        .rows-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .col-code {
            width: 55px;
            color: #888;
            font-size: 10px;
            padding-right: 8px;
        }
        .col-name {
            padding-left: 5px;
        }
        .col-amount {
            width: 130px;
            text-align: right;
            white-space: nowrap;
        }
        .indent-1 .col-name {
            padding-left: 20px;
        }
        .indent-2 .col-name {
            padding-left: 35px;
        }
        .row-subtotal td {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-weight: bold;
        }
        .row-grand-total td {
            border-top: 1px solid #333;
            border-bottom: 3px double #333;
            padding-top: 6px;
            padding-bottom: 6px;
            font-weight: bold;
            font-size: 12px;
        }
        .empty-row td {
            color: #999;
            font-style: italic;
            padding: 3px 0;
        }
    </style>
</head>
<body>
    @include('partials.pdf-company-header')

    <div class="report-header">
        <h1 class="report-title">{{ $reportName }}</h1>
        <div class="report-meta">{{ __('financial_reports.labels.as_of') }} {{ $periodLabel }}</div>
        <div class="report-meta">
            {{ __('financial_reports.labels.generated_at') }}: {{ now()->format(Setting::getDefaultDate('long_date_format')) }}
        </div>
    </div>

    @if ($unknownLedgerAccounts !== [])
        <div style="background: #fff8e1; border: 1px solid #d6b656; padding: 8px; margin: 15px 0">
            <strong>{{ __('financial_reports.messages.unmatched_accounts') }}:</strong>
            {{ implode(', ', $unknownLedgerAccounts) }}.
        </div>
    @endif

    @foreach ($rows as $row)
        @if ($row['kind'] === 'heading')
            <div class="section-title">{{ $row['name'] }}</div>
        @elseif ($row['kind'] === 'group_heading')
            <div class="subsection-title">{{ $row['name'] }}</div>
        @elseif ($row['kind'] === 'note' || $row['kind'] === 'empty')
            <table class="rows-table">
                <tr class="empty-row">
                    <td class="col-code"></td>
                    <td class="col-name" style="padding-left: 35px">{{ $row['name'] }}</td>
                    <td class="col-amount"></td>
                </tr>
            </table>
        @elseif ($row['kind'] === 'formula')
            @php
                $isGrandTotal = $row['total_type'] === ChartTotalReportTypeEnum::GRAND_TOTAL->value;
                $rowClass = $isGrandTotal ? 'row-grand-total' : 'row-subtotal';
            @endphp
            <table class="rows-table">
                <tr class="{{ $rowClass }}">
                    <td class="col-code"></td>
                    <td class="col-name" style="padding-left: 5px">{{ $row['name'] }}</td>
                    <td class="col-amount">
                        @if ($hasPeriodColumns)
                            @foreach ($row['period_amounts'] as $amount)
                                {{ number_format((float) ($amount ?? 0), 2) }}
                                @if (! $loop->last) &nbsp;&nbsp; @endif
                            @endforeach
                        @else
                            {{ number_format((float) $row['amount'], 2) }}
                        @endif
                    </td>
                </tr>
            </table>
        @else
            <table class="rows-table">
                <tr class="indent-1">
                    <td class="col-code">{{ $row['code'] }}</td>
                    <td class="col-name">{{ $row['name'] }}</td>
                    <td class="col-amount">
                        @if ($hasPeriodColumns)
                            @foreach ($row['period_amounts'] as $amount)
                                {{ number_format((float) ($amount ?? 0), 2) }}
                                @if (! $loop->last) &nbsp;&nbsp; @endif
                            @endforeach
                        @else
                            {{ number_format((float) $row['amount'], 2) }}
                        @endif
                    </td>
                </tr>
            </table>
        @endif
    @endforeach
</body>
</html>
