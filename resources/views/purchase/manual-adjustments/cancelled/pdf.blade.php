{{--
 ============================================================================
 Copyright (c) 2026 redfluz. All Rights Reserved.
 PROPRIETARY AND CONFIDENTIAL
 ============================================================================
--}}

@php use App\Models\System\Setting; @endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>{{ __('manual_purchase_adjustments.cancelled_title') }}</title>
    <style>
        body {
            font-family:
                DejaVu Sans,
                sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }

        .meta {
            font-size: 9px;
            color: #666;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        tfoot td {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    @include('partials.pdf-company-header')
    <div class="header">
        <h1 class="title">{{ __('manual_purchase_adjustments.cancelled_title') }}</h1>
        <div class="meta">
            Generated Date: {{ now()->format(Setting::getDefaultDate('long_date_format')) }}
            @if ($startDate || $endDate)
                | Date Range: {{ $startDate ?? 'Start' }} to {{ $endDate ?? 'End' }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th scope="col" style="width: 16%">{{ __('ledgers.table.column_document_number') }}</th>
                <th scope="col" style="width: 12%">{{ __('ledgers.table.column_reference_number') }}</th>
                <th scope="col" style="width: 14%">{{ __('manual_purchase_adjustments.form.note_type') }}</th>
                <th scope="col" style="width: 12%">{{ __('ledgers.table.column_date') }}</th>
                <th scope="col" style="width: 26%">{{ __('ledgers.table.column_company') }}</th>
                <th scope="col" class="text-end" style="width: 20%">{{ __('ledgers.table.column_total_amount') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td class="fw-bold">{{ $entry->document_number }}</td>
                    <td>{{ $entry->reference_number ?? '-' }}</td>
                    <td>{{ __('ledgers.sub_type.' . $entry->subtype->value) }}</td>
                    <td>{{ \Carbon\Carbon::parse($entry->date)->format(Setting::getDefaultDate()) }}</td>
                    <td>{{ $entry->company_name ?? '-' }}</td>
                    <td class="fw-bold text-end">{{ number_format((float) $entry->total_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="py-4 text-center" colspan="6">{{ __('common.labels.not_found_record') }}</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="fw-bold text-end" colspan="5">{{ __('common.labels.total') }}:</td>
                <td class="fw-bold text-end">{{ number_format((float) $entries->sum('total_amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
