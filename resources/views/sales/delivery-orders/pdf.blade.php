@php use App\Models\System\Setting; @endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <style>
        @page {
            margin: 30px 38px;
        }
        body {
            color: #1f2937;
            font-family:
                DejaVu Sans,
                sans-serif;
            font-size: 10px;
            margin: 0;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .header {
            border-bottom: 3px solid #315985;
            padding-bottom: 16px;
        }
        .company {
            font-size: 21px;
            font-weight: bold;
        }
        .document-title {
            color: #315985;
            font-size: 24px;
            font-weight: bold;
            text-align: right;
        }
        .document-number {
            font-size: 12px;
            margin-top: 4px;
            text-align: right;
        }
        .section {
            margin-top: 20px;
        }
        .section-title {
            background: #315985;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            padding: 7px 9px;
            text-transform: uppercase;
        }
        .address-box {
            border: 1px solid #d1d5db;
            min-height: 72px;
            padding: 10px;
        }
        .recipient {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .details td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
            width: 25%;
        }
        .label {
            color: #6b7280;
            display: block;
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .items th {
            background: #e9eef5;
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
        }
        .items td {
            border: 1px solid #d1d5db;
            padding: 9px 8px;
            vertical-align: top;
        }
        .text-right {
            text-align: right !important;
        }
        .notes {
            border: 1px solid #d1d5db;
            min-height: 38px;
            padding: 9px;
        }
        .signature-table {
            margin-top: 42px;
        }
        .signature-table td {
            padding-right: 30px;
            vertical-align: bottom;
            width: 50%;
        }
        .signature-line {
            border-top: 1px solid #374151;
            margin-top: 55px;
            padding-top: 6px;
        }
        .signature-subline {
            border-top: 1px solid #9ca3af;
            margin-top: 24px;
            padding-top: 5px;
        }
        .footer {
            bottom: 12px;
            color: #6b7280;
            font-size: 8px;
            position: fixed;
            text-align: center;
            width: 100%;
        }
    </style>
    <title></title>
</head>
<body>
    <table class="header">
        <tr>
            <td width="58%">
                @if ($setting->logo_path && is_file(public_path('storage/'.$setting->logo_path)))
                    <img
                        src="{{ public_path('storage/'.$setting->logo_path) }}"
                        alt=""
                        style="max-height: 50px; max-width: 180px; margin-bottom: 6px"
                    />
                @else
                    <div class="company">
                        {{ $setting->company_name ?: __('invoice_designs.pdf.company_placeholder') }}
                    </div>
                @endif
                @if ($setting->company_registration_no)
                    <div>{{ $setting->company_registration_no }}</div>
                @endif
                @if ($setting->address)
                    <div>{{ $setting->address }}</div>
                @endif
                @if ($setting->company_phone)
                    <div>{{ $setting->company_phone }}</div>
                @endif
                @if ($setting->company_email)
                    <div>{{ $setting->company_email }}</div>
                @endif
            </td>
            <td>
                <div class="document-title">{{ __('delivery_orders.pdf.title') }}</div>
                <div class="document-number">{{ $deliveryOrder->document_number }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">{{ __('delivery_orders.pdf.delivery_to') }}</div>
        <div class="address-box">
            <div class="recipient">{{ $deliveryOrder->recipient_name }}</div>
            @if ($deliveryOrder->recipient_phone)
                <div>{{ $deliveryOrder->recipient_phone }}</div>
            @endif
            <div>{!! nl2br(e($deliveryOrder->delivery_address)) !!}</div>
        </div>
    </div>

    <table class="details section">
        <tr>
            <td>
                <span class="label">{{ __('delivery_orders.pdf.delivery_date') }}</span
                >{{ $deliveryOrder->delivery_date->format(Setting::getDefaultDate()) }}
            </td>
            <td>
                <span class="label">{{ __('delivery_orders.pdf.sales_order') }}</span
                >{{ $deliveryOrder->salesOrder?->document_number ?? '—' }}
            </td>
            <td>
                <span class="label">{{ __('delivery_orders.pdf.dispatch_from') }}</span
                >{{ $deliveryOrder->stock_location_name }}
            </td>
            <td>
                <span class="label">{{ __('delivery_orders.pdf.status') }}</span
                >{{ __('delivery_orders.status.'.$deliveryOrder->status->value) }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">{{ __('delivery_orders.pdf.courier') }}</span>{{ $deliveryOrder->courier ?: '—' }}
            </td>
            <td>
                <span class="label">{{ __('delivery_orders.pdf.tracking_number') }}</span
                >{{ $deliveryOrder->tracking_number ?: '—' }}
            </td>
            <td>
                <span class="label">{{ __('delivery_orders.pdf.shipping_date') }}</span
                >{{ $deliveryOrder->shipping_date?->format(Setting::getDefaultDate()) ?? '—' }}
            </td>
        </tr>
    </table>

    <table class="items section">
        <thead>
            <tr>
                <th width="28%">{{ __('delivery_orders.pdf.item') }}</th>
                <th width="20%">{{ __('delivery_orders.pdf.sku') }}</th>
                <th>{{ __('delivery_orders.pdf.description') }}</th>
                <th class="text-right" width="12%">{{ __('delivery_orders.pdf.quantity') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryOrder->lines as $line)
                <tr>
                    <td><strong>{{ $line->product_name }}</strong></td>
                    <td>{{ $line->product_sku ?: '—' }}</td>
                    <td>{{ $line->description ?: '—' }}</td>
                    <td class="text-right">{{ number_format((float) $line->quantity, 4) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($deliveryOrder->notes)
        <div class="section">
            <div class="section-title">{{ __('delivery_orders.pdf.notes') }}</div>
            <div class="notes">{!! nl2br(e($deliveryOrder->notes)) !!}</div>
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line">{{ __('delivery_orders.pdf.prepared_by') }}</div>
            </td>
            <td>
                <div class="signature-line">{{ __('delivery_orders.pdf.received_by') }}</div>
                <div class="signature-subline">{{ __('delivery_orders.pdf.signature') }}</div>
                <div class="signature-subline">{{ __('delivery_orders.pdf.received_date') }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">{{ $deliveryOrder->document_number }}</div>
</body>
</html>
