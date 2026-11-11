@php use App\Models\System\Setting; @endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <style>
        @page {
            margin: 0;
        }

        body {
            color: #1f2937;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            margin: 0;
        }

        .header {
            background: #1e3a8a;
            color: #fff;
            padding: 40px 48px;
        }

        .content {
            padding: 30px 48px;
        }

        .company-name {
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .title {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1px;
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .meta-label {
            color: #6b7280;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .party-name {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .summary {
            margin: 20px 0 0;
        }

        .amount {
            border: 2px solid #1e3a8a;
            color: #1e3a8a;
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
            padding: 14px;
            text-align: center;
        }

        .detail td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 0;
        }

        .detail .label {
            color: #6b7280;
            width: 42%;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 22px 0 10px;
            text-transform: uppercase;
        }

        .item-table th {
            background: #f3f4f6;
            font-size: 10px;
            padding: 11px 9px;
            text-align: left;
        }

        .item-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 11px 9px;
        }

        .item-table tfoot th {
            border-top: 2px solid #1e3a8a;
            font-size: 11px;
            padding: 11px 9px;
            text-align: right;
        }

        .text-right {
            text-align: right;
        }

        /* The signature cell keeps a fixed height so the printed rows line up
           whether or not a name was recorded against that step. */
        .signature-box {
            height: 34px;
        }

        .signature {
            margin-top: 26px;
        }

        /* The audit block is the last thing on the page and it is short, so it
           is kept whole: a trail split over two pages, with the signature line
           alone on the second, is not something anyone wants to file. */
        .audit-table {
            page-break-inside: avoid;
        }

        .audit-table td {
            padding: 8px 9px;
        }

        .footer {
            background: #f3f4f6;
            bottom: 0;
            color: #6b7280;
            font-size: 9px;
            padding: 14px 48px;
            position: fixed;
            text-align: center;
            width: 100%;
        }
    </style>
    <title></title>
</head>

<body>
    @php
        $entries = $refund->relationLoaded('apLedgers') ? $refund->apLedgers : collect();
        $moneyAccountText = $refund->moneyAccount()?->name ?? '-';
    @endphp

    <div class="header">
        <table>
            <tr>
                <td>
                    @if ($setting->logo_path)
                        <img
                            src="{{ public_path('storage/' . $setting->logo_path) }}"
                            alt="{{ $setting->company_name }}"
                            style="max-height: 55px; max-width: 180px; margin-bottom: 8px"
                        />
                    @else
                        <div class="company-name">
                            {{ $setting->company_name ?: __('payments.pdf.company_placeholder') }}
                        </div>
                    @endif

                    <div>{{ $setting->company_email }}</div>
                    @if ($setting->company_phone)
                        <div>{{ $setting->company_phone }}</div>
                    @endif
                    @if ($setting->company_fax)
                        <div>{{ $setting->company_fax }}</div>
                    @endif
                    @if ($setting->company_website)
                        <div>{{ $setting->company_website }}</div>
                    @endif
                    @if ($setting->company_registration_no)
                        <div>{{ $setting->company_registration_no }}</div>
                    @endif
                    @if ($setting->company_tax_no)
                        <div>{{ $setting->company_tax_no }}</div>
                    @endif
                    @if ($setting->address)
                        <div>{{ $setting->address }}</div>
                    @endif
                </td>
                <td>
                    <div class="title">{{ __('payments.sub_type.supplier_refund') }}</div>
                    <div class="text-right">{{ $documentNumber }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <div class="muted">
            {{ $refund->status?->value === 'posted'
                ? __('journal_entries.status_approved')
                : ($refund->status?->value === 'cancelled' ? __('ledgers.status_cancelled') : __('journal_entries.status_pending')) }}
        </div>

        <table class="summary">
            <tr>
                <td width="65%">
                    <div class="meta-label">{{ __('payments.pdf.received_from') }}</div>
                    <div class="party-name">{{ $refund->company_name ?: '-' }}</div>
                </td>
                <td class="text-right">
                    <span class="muted">{{ __('payments.pdf.date') }}:</span>
                    {{ $refund->date->format(Setting::getDefaultDate()) }}
                </td>
            </tr>
        </table>

        <div class="amount">
            {{ __('payments.pdf.amount') }}: {{ $currencySymbol }} {{ number_format((float) $refund->total_amount, 2) }}
        </div>

        <table class="detail">
            <tr>
                <td class="label">{{ __('payments.pdf.linked_invoice') }}</td>
                <td>{{ $refund->reference_number ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('payments.pdf.payment_transfer') }}</td>
                <td>{{ $refund->payment_transfer_type?->label() ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('payments.form.received_into') }}</td>
                <td>{{ $moneyAccountText }}</td>
            </tr>
            @if ($refund->notes)
                <tr>
                    <td class="label">{{ __('ledgers.table.column_notes') }}</td>
                    <td>{{ $refund->notes }}</td>
                </tr>
            @endif
        </table>

        <div class="section-title">{{ __('payments.table.column_entry') }}</div>
        <table class="item-table">
            <thead>
                <tr>
                    <th scope="col" style="width: 28px">#</th>
                    <th scope="col">{{ __('ledgers.table.column_debit_acc') }}</th>
                    <th scope="col">{{ __('ledgers.table.column_credit_acc') }}</th>
                    <th scope="col" class="text-right">{{ __('ledgers.table.column_debit_amount') }}</th>
                    <th scope="col" class="text-right">{{ __('ledgers.table.column_credit_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $line)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ (float) ($line->debit_amount ?? 0) > 0 ? trim(($line->chart_code ?? $line->chart?->code ?? '') . ' ' . ($line->chart_name ?? $line->chart?->name ?? '')) : '-' }}</td>
                        <td>{{ (float) ($line->credit_amount ?? 0) > 0 ? trim(($line->chart_code ?? $line->chart?->code ?? '') . ' ' . ($line->chart_name ?? $line->chart?->name ?? '')) : '-' }}</td>
                        <td class="text-right">{{ (float) ($line->debit_amount ?? 0) > 0 ? number_format((float) $line->debit_amount, 2) : '' }}</td>
                        <td class="text-right">{{ (float) ($line->credit_amount ?? 0) > 0 ? number_format((float) $line->credit_amount, 2) : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-right">{{ $refund->status?->value === 'posted' ? '-' : __('ledgers.no_records') }}</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" scope="row">{{ __('common.labels.total') }}</th>
                    <th scope="row">{{ number_format((float) $refund->total_amount, 2) }}</th>
                    <th scope="row">{{ number_format((float) $refund->total_amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>

        <div class="section-title">{{ __('common.labels.audit_trail') }}</div>
        <table class="item-table audit-table">
            <thead>
                <tr>
                    <th scope="col" style="width: 120px">{{ __('refunds.audit.role') }}</th>
                    <th scope="col" style="width: 150px">{{ __('refunds.audit.name') }}</th>
                    <th scope="col" style="width: 130px">{{ __('refunds.audit.date') }}</th>
                    <th scope="col">{{ __('refunds.audit.signature') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['label' => __('refunds.audit.created_by'), 'name' => $refund->created_name, 'at' => $refund->created_at],
                    ['label' => __('refunds.audit.approved_by'), 'name' => $refund->approved_name, 'at' => $refund->approved_at],
                    ['label' => __('refunds.audit.cancelled_by'), 'name' => $refund->cancelled_name, 'at' => $refund->cancelled_at],
                ] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['name'] ?? '-' }}</td>
                        <td>{{ $row['at']?->format(Setting::getDefaultDate('long_date_format')) ?? '-' }}</td>
                        <td class="signature-box"></td>
                    </tr>
                @endforeach

                @if ($refund->undone_name !== null || $refund->undone_at !== null)
                    <tr>
                        <td>{{ __('refunds.audit.undone_by') }}</td>
                        <td>{{ $refund->undone_name ?? '-' }}</td>
                        <td>{{ $refund->undone_at?->format(Setting::getDefaultDate('long_date_format')) ?? '-' }}</td>
                        <td class="signature-box"></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <p class="signature">
            ____________________________
            <br />
            {{ __('payments.pdf.authorised_signature') }}
        </p>
    </div>

    <div class="footer">
        {{ $refund->document_number }} | {{ now()->format(Setting::getDefaultDate('long_date_format')) }}
    </div>
</body>
</html>
