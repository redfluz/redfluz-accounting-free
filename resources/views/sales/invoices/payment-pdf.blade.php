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
            padding: 42px 48px;
        }

        .title {
            font-size: 26px;
            font-weight: bold;
            text-align: right;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .amount {
            border: 2px solid #1e3a8a;
            color: #1e3a8a;
            font-size: 24px;
            font-weight: bold;
            margin: 30px 0;
            padding: 18px;
            text-align: center;
        }

        .detail td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 0;
        }

        .label {
            color: #6b7280;
            width: 42%;
        }

        .footer {
            background: #f3f4f6;
            bottom: 0;
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
        $paymentSubtype = $payment->subtype->value ?? $payment->subtype;
        $isReceipt = $paymentSubtype === 'receipt';
        $bankAccount = $isReceipt
            ? $paymentEntries->firstWhere('debit_acc', '!=', null)->debit_acc
            : $paymentEntries->firstWhere('credit_acc', '!=', null)->credit_acc;
    @endphp

    <div class="header">
        <table>
            <tr>
                <td>
                    @if ($setting->logo_path)
                        <img
                            src="{{ public_path('storage/' . $setting->logo_path) }}"
                            alt="{{ $setting->company_name }}"
                            style="max-height: 55px; max-width: 180px"
                        />
                        <br />
                    @else
                        <strong>{{ $setting->company_name ?: __('payment_designs.pdf.company_placeholder') }}</strong>
                        <br />
                    @endif

                    <small>{{ $setting->company_email }}</small>
                    @if ($setting->company_phone)
                        <br
                        /><small>{{ $setting->company_phone }}</small>
                    @endif
                    @if ($setting->company_fax)
                        <br
                        /><small>{{ $setting->company_fax }}</small>
                    @endif
                    @if ($setting->company_website)
                        <br
                        /><small>{{ $setting->company_website }}</small>
                    @endif
                    @if ($setting->company_registration_no)
                        <br
                        /><small>{{ $setting->company_registration_no }}</small>
                    @endif
                    @if ($setting->company_tax_no)
                        <br
                        /><small>{{ $setting->company_tax_no }}</small>
                    @endif
                    @if ($setting->address)
                        <br
                        /><small>{{ $setting->address }}</small>
                    @endif
                </td>
                <td>
                    <div class="title">
                        {{ $isReceipt ? __('payment_designs.pdf.title') : __('ledgers.sub_type.payment_voucher') }}
                    </div>
                    <div style="text-align: right">{{ $payment->document_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <p>
            {{ $isReceipt ? __('payment_designs.pdf.received_from') : __('ledgers.table.column_company') }}:
            <strong>{{ $payment->company->name ?? '-' }}</strong>
        </p>

        <div class="amount">
            {{ $isReceipt ? __('payment_designs.pdf.received_amount') : __('ledgers.table.column_amount') }}: {{ number_format((float) $payment->total_amount, 2) }}
        </div>

        <table class="detail">
            <tr>
                <td class="label">{{ __('payment_designs.pdf.receipt_date') }}</td>
                <td>{{ $payment->date->format(Setting::getDefaultDate()) }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('payment_designs.pdf.linked_invoice') }}</td>
                <td>{{ $payment->reference_number ?? '-' }}</td>
            </tr>
            @if ($payment->notes)
                <tr>
                    <td class="label">{{ __('ledgers.table.column_notes') }}</td>
                    <td>{{ $payment->notes }}</td>
                </tr>
            @endif
            @if (true)
                <tr>
                    <td class="label">{{ __('payment_designs.pdf.bank_account') }}</td>
                    <td>{{ $bankAccount ?? '-' }}</td>
                </tr>
            @endif
        </table>

        <p style="margin-top: 70px">
                ____________________________
                <br />
                {{ __('payment_designs.pdf.authorised_signature') }}
            </p>
    </div>

    </body>
</html>
