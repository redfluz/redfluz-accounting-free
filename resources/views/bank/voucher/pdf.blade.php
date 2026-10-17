@php use App\Models\System\Setting; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ $documentNumber }}</title>
    <style>
        /* A4 with room for the audit table at the foot. The page margin is set
           on the Pdf object rather than with @page here so the browser preview
           and the download agree. */
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 18px;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 2px;
        }

        h2 {
            font-size: 13px;
            margin: 18px 0 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #d0d0d0;
        }

        .muted {
            color: #6c757d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th,
        td {
            border: 1px solid #c8c8c8;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f1f3f5;
            font-weight: bold;
        }

        .text-end {
            text-align: right;
        }

        .summary td {
            width: 25%;
        }

        .summary .label {
            color: #6c757d;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .summary .value {
            font-weight: bold;
            font-size: 12px;
        }

        /* The signature box is a fixed height so the rows line up whether or not
           a name was recorded. An empty cell of a variable height would make the
           printed table look unfinished. */
        .audit-signature {
            height: 34px;
        }

        .footer {
            margin-top: 18px;
            font-size: 9px;
            color: #6c757d;
            border-top: 1px solid #d0d0d0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    @if ($setting?->company_name)
        <div class="muted">{{ $setting->company_name }}</div>
    @endif

    <h1>{{ __('payment_vouchers.title') }} {{ $documentNumber }}</h1>
    <div class="muted">
        {{ $voucher->status->value === 'posted' ? __('journal_entries.status_approved') : __('ledgers.status_cancelled') }}
        | {{ __('payment_vouchers.title') }}
    </div>

    <h2>{{ __('payment_vouchers.title') }}</h2>
    <table class="summary">
        <tr>
            <td>
                <div class="label">{{ __('ledgers.table.column_reference_number') }}</div>
                <div class="value">{{ $voucher->reference_number ?? '-' }}</div>
            </td>
            <td>
                <div class="label">{{ __('ledgers.table.column_date') }}</div>
                <div class="value">{{ $voucher->date->format(Setting::getDefaultDate()) }}</div>
            </td>
            <td>
                <div class="label">{{ __('ledgers.table.column_company') }}</div>
                <div class="value">{{ $voucher->company_name ?: '-' }}</div>
            </td>
            <td>
                <div class="label">{{ __('ledgers.table.column_total_amount') }}</div>
                <div class="value">{{ number_format((float) $voucher->total_amount, 2) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ __('payments.form.payment_transfer') }}</div>
                <div class="value">{{ $voucher->payment_transfer_type?->label() ?? '-' }}</div>
            </td>
            <td colspan="3"></td>
        </tr>
    </table>

    <h2>{{ __('payments.table.column_entry') }}</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 28px">#</th>
                <th>{{ __('ledgers.table.column_debit_acc') }}</th>
                <th>{{ __('ledgers.table.column_credit_acc') }}</th>
                <th class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                <th class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($voucher->charts as $line)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $line->debit_acc ?? '-' }}</td>
                    <td>{{ $line->credit_acc ?? '-' }}</td>
                    <td class="text-end">
                        {{ $line->debit_id !== null ? number_format((float) $line->debit_amount, 2) : '' }}
                    </td>
                    <td class="text-end">
                        {{ $line->credit_id !== null ? number_format((float) $line->credit_amount, 2) : '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">{{ __('common.labels.total') }}:</th>
                <th class="text-end">
                    {{ number_format((float) $voucher->charts->whereNotNull('debit_id')->sum('debit_amount'), 2) }}
                </th>
                <th class="text-end">
                    {{ number_format((float) $voucher->charts->whereNotNull('credit_id')->sum('credit_amount'), 2) }}
                </th>
            </tr>
        </tfoot>
    </table>

    @if ($voucher->notes)
        <h2>{{ __('ledgers.table.column_notes') }}</h2>
        <div>{{ $voucher->notes }}</div>
    @endif

    {{-- The audit trail, printed on every copy: who prepared it, who approved
         it, who cancelled it, and who brought a cancellation back, each with a
         box to sign. --}}
    <h2>{{ __('payment_vouchers.audit.role') }}</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 130px">{{ __('payment_vouchers.audit.role') }}</th>
                <th style="width: 160px">{{ __('payment_vouchers.audit.name') }}</th>
                <th style="width: 110px">{{ __('payment_vouchers.audit.date') }}</th>
                <th>{{ __('payment_vouchers.audit.signature') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['label' => __('payment_vouchers.audit.created_by'), 'name' => $voucher->created_name, 'at' => $voucher->created_at],
                ['label' => __('payment_vouchers.audit.approved_by'), 'name' => $voucher->approved_name, 'at' => $voucher->approved_at],
                ['label' => __('payment_vouchers.audit.cancelled_by'), 'name' => $voucher->cancelled_name, 'at' => $voucher->cancelled_at],
            ] as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['name'] ?? '-' }}</td>
                    <td>{{ $row['at']?->format(Setting::getDefaultDate('long_date_format')) ?? '-' }}</td>
                    <td class="audit-signature"></td>
                </tr>
            @endforeach

            @if ($voucher->undone_name !== null || $voucher->undone_at !== null)
                <tr>
                    <td>{{ __('payment_vouchers.audit.undone_by') }}</td>
                    <td>{{ $voucher->undone_name ?? '-' }}</td>
                    <td>{{ $voucher->undone_at?->format(Setting::getDefaultDate('long_date_format')) ?? '-' }}</td>
                    <td class="audit-signature"></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        {{ $voucher->document_number }} | {{ now()->format(Setting::getDefaultDate('long_date_format')) }}
    </div>
</body>
</html>