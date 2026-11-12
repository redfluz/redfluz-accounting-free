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
        $entries = $receipt->relationLoaded('arLedgers') ? $receipt->arLedgers : collect();

        /* A receipt carries no money account of its own, so the account the money
           landed in is read back from the ledger: the money side of a receipt is
           its debit, the bank, cash or e-wallet account paid into. Where a
           receipt's ledger holds only the receivable side there is nothing to
           name, and the row prints empty rather than calling the receivable
           account a bank account. */
        $moneyEntry = $entries->first(fn ($line) => (float) ($line->debit_amount ?? 0) > 0);
        $moneyAccountText = $moneyEntry === null
            ? ''
            : trim(($moneyEntry->chart_code ?? $moneyEntry->chart?->code ?? '') . ' ' . ($moneyEntry->chart_name ?? $moneyEntry->chart?->name ?? ''));
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
                    <div class="title">{{ __('payments.pdf.receipt_title') }}</div>
                    <div class="text-right">{{ $documentNumber }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <div class="muted">
            {{ $receipt->status->value === 'posted'
                ? __('journal_entries.status_approved')
                : ($receipt->status->value === 'cancelled' ? __('ledgers.status_cancelled') : __('journal_entries.status_pending')) }}
        </div>

        <table class="summary">
            <tr>
                <td width="65%">
                    <div class="meta-label">{{ __('payments.pdf.received_from') }}</div>
                    <div class="party-name">{{ $receipt->company_name ?: '-' }}</div>
                </td>
                <td class="text-right">
                    <span class="muted">{{ __('payments.pdf.date') }}:</span>
                    {{ $receipt->date->format(Setting::getDefaultDate()) }}
                </td>
            </tr>
        </table>

        <div class="amount">
            {{ __('payments.pdf.amount') }}: {{ $currencySymbol }} {{ number_format((float) $receipt->total_amount, 2) }}
        </div>

        <table class="detail">
            <tr>
                <td class="label">{{ __('payments.pdf.linked_invoice') }}</td>
                <td>{{ $receipt->reference_number ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('payments.pdf.payment_transfer') }}</td>
                <td>{{ $receipt->payment_transfer_type?->label() ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('payments.pdf.bank_account') }}</td>
                <td>{{ $moneyAccountText !== '' ? $moneyAccountText : '-' }}</td>
            </tr>
            @if ($receipt->notes)
                <tr>
                    <td class="label">{{ __('ledgers.table.column_notes') }}</td>
                    <td>{{ $receipt->notes }}</td>
                </tr>
            @endif
        </table>

        <div class="section-title">{{ __('common.labels.audit_trail') }}</div>
        <table class="item-table audit-table">
            <thead>
                <tr>
                    <th scope="col" style="width: 120px">{{ __('receipts.audit.role') }}</th>
                    <th scope="col" style="width: 150px">{{ __('receipts.audit.name') }}</th>
                    <th scope="col" style="width: 130px">{{ __('receipts.audit.date') }}</th>
                    <th scope="col">{{ __('receipts.audit.signature') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['label' => __('receipts.audit.created_by'), 'name' => $receipt->created_name, 'at' => $receipt->created_at],
                    ['label' => __('receipts.audit.approved_by'), 'name' => $receipt->approved_name, 'at' => $receipt->approved_at],
                    ['label' => __('receipts.audit.cancelled_by'), 'name' => $receipt->cancelled_name, 'at' => $receipt->cancelled_at],
                ] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['name'] ?? '-' }}</td>
                        <td>{{ $row['at']?->format(Setting::getDefaultDate('long_date_format')) ?? '-' }}</td>
                        <td class="signature-box"></td>
                    </tr>
                @endforeach

                @if ($receipt->undone_name !== null || $receipt->undone_at !== null)
                    <tr>
                        <td>{{ __('receipts.audit.undone_by') }}</td>
                        <td>{{ $receipt->undone_name ?? '-' }}</td>
                        <td>{{ $receipt->undone_at?->format(Setting::getDefaultDate('long_date_format')) ?? '-' }}</td>
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
        {{ $receipt->document_number }} | {{ now()->format(Setting::getDefaultDate('long_date_format')) }}
    </div>
</body>
</html>
