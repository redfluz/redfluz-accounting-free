@php use App\Models\System\Setting; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>{{ __('journal_entries.cancelled_title') }}</title>
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
        .company-header {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .company-info {
            font-size: 9px;
            color: #555;
        }
    </style>
</head>
<body>
    @include('partials.pdf-company-header')
    <div class="header">
        <h1 class="title">{{ __('journal_entries.cancelled_title') }}</h1>
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
                <th style="width: 16%">{{ __('journal_entries.table.column_document_number') }}</th>
                <th style="width: 14%">{{ __('journal_entries.table.column_reference_number') }}</th>
                <th style="width: 12%">{{ __('journal_entries.table.column_date') }}</th>
                <th style="width: 28%">{{ __('journal_entries.table.column_notes') }}</th>
                <th class="text-end" style="width: 15%">{{ __('journal_entries.table.column_debit_amount') }}</th>
                <th class="text-end" style="width: 15%">{{ __('journal_entries.table.column_credit_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td class="fw-bold">{{ $entry->document_number }}</td>
                    <td>{{ $entry->reference_number ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($entry->date)->format(Setting::getDefaultDate()) }}</td>
                    <td>{{ $entry->notes ?? '-' }}</td>
                    <td class="fw-bold text-end">{{ number_format((float) $entry->total_debit, 2) }}</td>
                    <td class="fw-bold text-end">{{ number_format((float) $entry->total_credit, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="py-4 text-center" colspan="6">{{ __('ledgers.no_records') }}</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="fw-bold text-end" colspan="4">{{ __('common.labels.total') }}:</td>
                <td class="fw-bold text-end">{{ number_format($entries->sum('total_debit'), 2) }}</td>
                <td class="fw-bold text-end">{{ number_format($entries->sum('total_credit'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
