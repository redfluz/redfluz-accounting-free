@php use App\Models\System\Setting; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>{{ __('ledgers.title') }}</title>
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

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-muted {
            color: #666;
            font-size: 8px;
        }

        .badge {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
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
        <h1 class="title">{{ __('ledgers.title') }}</h1>
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
                <th style="width: 24%">{{ __('ledgers.table.column_document_number') }}</th>
                <th style="width: 12%">{{ __('ledgers.table.column_date') }}</th>
                <th style="width: 18%">{{ __('ledgers.table.column_type') }}</th>
                <th style="width: 24%">{{ __('ledgers.table.column_company') }}</th>
                <th class="text-end" style="width: 11%">{{ __('ledgers.table.column_debit_amount') }}</th>
                <th class="text-end" style="width: 11%">{{ __('ledgers.table.column_credit_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ledgers as $ledger)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $ledger->document_number }}</div>
                        @if (filled($ledger->reference_number) && $ledger->reference_number !== $ledger->document_number)
                            <div class="text-muted">
                                {{ __('ledgers.table.column_reference_number') }}: {{ $ledger->reference_number }}
                            </div>
                        @endif
                        <div class="text-muted">
                            {{ $ledger->debit_acc ?? '' }} {{ $ledger->credit_acc ?? '' }} {{ $ledger->debit_acc_desc ?? '' }} {{ $ledger->credit_acc_desc ?? '' }}
                        </div>
                    </td>
                    <td>{{ $ledger->date->format(Setting::getDefaultDate()) ?? '-' }}</td>
                    <td>
                        @php($type = $ledger->type->value ?? $ledger->type)
                        <span class="badge">{{ __('ledgers.type.' . $type) }}</span>
                        <br />
                        <span class="text-muted">
                            {{ __('ledgers.sub_type.' . ($ledger->subtype->value ?? $ledger->subtype)) }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold">
                            {{ $ledger->company_name ?? $ledger->company->name ?? '-' }}
                        </div>
                        <div class="text-muted">{{ $ledger->contact->name ?? '' }}</div>
                    </td>
                    <td class="fw-bold text-end">
                        {{ $ledger->debit_amount > 0 ? number_format($ledger->debit_amount, 2) : '-' }}
                    </td>
                    <td class="fw-bold text-end">
                        {{ $ledger->credit_amount > 0 ? number_format($ledger->credit_amount, 2) : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="py-4 text-center" colspan="6">{{ __('common.labels.not_found_record') }}</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="fw-bold text-end" colspan="4">{{ __('common.labels.total') }}:</td>
                <td class="fw-bold text-end">{{ number_format($ledgers->sum('debit_amount'), 2) }}</td>
                <td class="fw-bold text-end">{{ number_format($ledgers->sum('credit_amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
