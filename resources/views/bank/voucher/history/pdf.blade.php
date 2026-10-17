@php use App\Models\System\Setting; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ __('ledgers.history_title') }} - {{ __('payment_vouchers.title') }}</title>
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

    <h1>{{ __('payment_vouchers.title') }} - {{ __('ledgers.history_title') }}</h1>
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
                <th>{{ __('payment_vouchers.audit.created_by') }}</th>
                <th>{{ __('payment_vouchers.audit.approved_by') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vouchers as $voucher)
                <tr>
                    <td>{{ $voucher->document_number }}</td>
                    <td>{{ $voucher->reference_number ?? '-' }}</td>
                    <td>{{ $voucher->date->format(Setting::getDefaultDate()) }}</td>
                    <td>{{ $voucher->company_name ?: '-' }}</td>
                    <td class="text-end">{{ number_format((float) $voucher->total_amount, 2) }}</td>
                    <td>
                        {{ $voucher->created_name ?? '-' }}
                        @if ($voucher->created_at)
                            <br /><span class="muted">{{ $voucher->created_at->format(Setting::getDefaultDate('long_date_format')) }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $voucher->approved_name ?? '-' }}
                        @if ($voucher->approved_at)
                            <br /><span class="muted">{{ $voucher->approved_at->format(Setting::getDefaultDate('long_date_format')) }}</span>
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