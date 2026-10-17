@php use App\Models\System\Setting; @endphp
@php
    $subtotal = $lines->sum(fn ($line) => (float) $line->subtotal_amount !== 0.0
        ? (float) $line->subtotal_amount
        : (float) $line->quantity * (float) $line->unit_price);
    $discountTotal = $lines->sum('discount_amount');
    $discountType = $invoice->discount_type?->value;
    $taxTotal = $lines->sum('tax_amount');
    $grandTotal = $lines->sum(fn ($line) => (float) $line->line_total_amount !== 0.0
        ? (float) $line->line_total_amount
        : (float) $line->total_amount);
    $taxMode = $invoice->tax_mode->value ?? $invoice->tax_mode;
@endphp
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
            padding: 42px 48px;
        }

        .content {
            padding: 40px 48px;
        }

        .company-name {
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .title {
            font-size: 28px;
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

        .customer-name {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .summary {
            margin: 26px 0 30px;
        }

        .item-table {
            margin-top: 28px;
        }

        .item-table th {
            background: #f3f4f6;
            font-size: 10px;
            padding: 11px 9px;
            text-align: left;
        }

        .item-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 13px 9px;
        }

        .text-right {
            text-align: right;
        }

        .total {
            border-top: 2px solid #1e3a8a;
            font-size: 15px;
            font-weight: bold;
            margin-left: auto;
            margin-top: 24px;
            width: 230px;
        }

        .accent {
            color: #1e3a8a;
        }

        .payment-box {
            background: #f3f4f6;
            margin-top: 30px;
            padding: 15px;
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
                            {{ $setting->company_name ?: __('invoice_designs.pdf.company_placeholder') }}
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
                    <div class="title">
                        {{ __('invoice_designs.pdf.sales_title') }}
                    </div>
                    <div class="text-right">{{ $documentNumber }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table class="summary">
            <tr>
                <td width="65%">
                    <div class="meta-label">{{ __('invoice_designs.pdf.bill_to') }}</div>
                    <div class="customer-name">{{ $invoice->company?->name ?? '-' }}</div>
                    @if ($invoice->contact?->name)
                        <div class="muted">{{ $invoice->contact->name }}</div>
                    @endif
                </td>
                <td class="text-right">
                    <span class="muted">{{ __('invoice_designs.pdf.date') }}:</span>
                    {{ $invoice->date->format(Setting::getDefaultDate()) }}
                </td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th>{{ __('invoice_designs.pdf.description') }}</th>
                    <th class="text-right">{{ __('invoice_designs.pdf.quantity') }}</th>
                    <th class="text-right">{{ __('invoice_designs.pdf.unit_price') }}</th>
                    <th class="text-right">{{ __('invoice_designs.pdf.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td>{{ $line->product?->name ?? ($line->description ?? '-') }}</td>
                        <td class="text-right">{{ $line->quantity }}</td>
                        <td class="text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="text-right">
                            {{
                                number_format(
                                    (float) $line->line_total_amount !== 0.0
                                    ? (float) $line->line_total_amount
                                    : (float) $line->total_amount,
                                    2
                                )
                            }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="total">
            <tr>
                <td>{{ __('ledgers.table.column_subtotal_amount') }}</td>
                <td class="text-right">{{ number_format($subtotal, 2) }}</td>
            </tr>
            @if ($discountTotal > 0)
                <tr>
                    <td>
                        {{ __('ledgers.table.column_discount_amount') }}
                        @if ($invoice->discount_name) ({{ $invoice->discount_name }}-{{ $discountType === 'percent' ? number_format((float) $invoice->discount_value, 4).'%' : $currencySymbol . ' ' . number_format((float) $invoice->discount_value, 2) }})@endif
                    </td>
                    <td class="text-right">{{ number_format($discountTotal, 2) }}</td>
                </tr>
            @endif
            @if ($taxTotal > 0)
                <tr>
                    <td>{{ __('ledgers.table.column_tax_amount') }}</td>
                    <td class="text-right">{{ number_format($taxTotal, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td>{{ __('invoice_designs.pdf.total') }}</td>
                <td class="accent text-right">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>

        @if ($taxMode)
            <div class="muted">
                @if ($invoice->tax_name) {{ $invoice->tax_name }}| @endif
                {{ __('ledgers.table.column_tax_rate') }}: {{ number_format((float) $invoice->tax_rate, 4) }}% | {{ __('ledgers.tax_mode.' . $taxMode) }}
            </div>
        @endif

        @if (true)
            <div class="payment-box">
                <strong>{{ __('invoice_designs.pdf.payment_details') }}</strong>
                <br />
                {{ __('invoice_designs.pdf.bank_details_placeholder') }}
            </div>
        @endif
    </div>

    </body>
</html>
