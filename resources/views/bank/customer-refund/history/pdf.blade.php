@php use App\Models\System\Setting; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ __('ledgers.history_title') }} - {{ __('refunds.title') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            margin: 0;
            padding: 14px;
        }

        h1 {
            font-size: 14px;
            margin: 0 0 2px;
        }

        .muted {
            color: #6c757d;
            font-size: 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #c8c8c8;
            padding: 4px 5px;
            text-align: left;
        }

        th {
            background: #f1f3f5;
            font-weight: bold;
        }

        .text-end {
            text-align: right;
        }
    </style>
</head>
<body>
    @if ($setting?->company_name)
        <div class="muted">{{ $setting->company_name }}</div>
    @endif

    <h1>{{ __('refunds.title') }} - {{ __('ledgers.history_title') }}</h1>
    <div class="muted">
        @if ($startDate || $endDate)
            {{ $startDate ?? '...' }} - {{ $endDate ?? '...' }} |
        @endif
        {{ now()->format(Setting::getDefaultDate('long_date_format')) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('ledgers.table.column_document_number') }}</th>
                <th>{{ __('ledgers.table.column_reference_number') }}</th>
                <th>{{ __('ledgers.table.column_date') }}</th>
                <th>{{ __('ledgers.table.column_company') }}</th>
                <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                <th>{{ __('refunds.audit.created_by') }}</th>
                <th>{{ __('refunds.audit.approved_by') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($refunds as $refund)
                <tr>
                    <td>{{ $refund->document_number }}</td>
                    <td>{{ $refund->reference_number ?? '-' }}</td>
                    <td>{{ $refund->date->format(Setting::getDefaultDate()) }}</td>
                    <td>{{ $refund->company_name ?: '-' }}</td>
                    <td class="text-end">{{ number_format((float) $refund->total_amount, 2) }}</td>
                    <td>
                        {{ $refund->created_name ?? '-' }}
                        @if ($refund->created_at)
                            <br /><span class="muted">{{ $refund->created_at->format(Setting::getDefaultDate('long_date_format')) }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $refund->approved_name ?? '-' }}
                        @if ($refund->approved_at)
                            <br /><span class="muted">{{ $refund->approved_at->format(Setting::getDefaultDate('long_date_format')) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">{{ __('ledgers.no_records') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>