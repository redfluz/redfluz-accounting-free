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

        .supplier-name {
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
            font-size: 13px;
            margin-left: auto;
            margin-top: 24px;
            width: 260px;
        }

        .total td {
            padding: 5px;
        }

        .grand {
            font-size: 15px;
            font-weight: bold;
        }

        .accent {
            color: #1e3a8a;
        }

        .notes {
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
                    <div class="title">{{ __('purchase_orders.pdf.title') }}</div>
                    <div class="text-right">{{ $purchaseOrder->document_number }}</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="content">
        <table class="summary">
            <tr>
                <td width="65%">
                    <div class="meta-label">{{ __('purchase_orders.pdf.supplier') }}</div>
                    <div class="supplier-name">{{ $purchaseOrder->company_name }}</div>
                    @if ($purchaseOrder->contact_name)
                        <div class="muted">{{ $purchaseOrder->contact_name }}</div>
                    @endif
                </td>
                <td class="text-right">
                    <span class="muted">{{ __('purchase_orders.form.date') }}:</span>
                    {{ $purchaseOrder->date->format(Setting::getDefaultDate()) }}<br /><span class="muted"
                        >{{ __('purchase_orders.form.expected_date') }}:</span>
                    {{ $purchaseOrder->expected_date?->format(Setting::getDefaultDate()) ?? '-' }}<br /><span class="muted"
                        >{{ __('purchase_orders.form.due_date') }}:</span>
                    {{ $purchaseOrder->due_date?->format(Setting::getDefaultDate()) ?? '-' }}
                </td>
            </tr>
        </table>
        <table class="item-table">
            <thead>
                <tr>
                    <th>{{ __('purchase_orders.form.description') }}</th>
                    <th class="text-right">{{ __('purchase_orders.form.quantity') }}</th>
                    <th class="text-right">{{ __('purchase_orders.form.unit_price') }}</th>
                    <th class="text-right">{{ __('purchase_orders.table.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchaseOrder->lines as $line)
                    <tr>
                        <td>
                            {{ $line->product_name }}
                            @if ($line->description)
                                <br
                                /><span class="muted">{{ $line->description }}</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $line->quantity }}</td>
                        <td class="text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format((float) $line->total_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="total">
            <tr class="grand">
                <td>{{ __('purchase_orders.form.grand_total') }}</td>
                <td class="accent text-right">{{ number_format((float) $purchaseOrder->total_amount, 2) }}</td>
            </tr>
        </table>
        @if ($purchaseOrder->notes)
            <div class="notes">
                <strong>{{ __('purchase_orders.form.notes') }}</strong><br />{{ $purchaseOrder->notes }}
            </div>
        @endif
    </div>
    </body>
</html>
