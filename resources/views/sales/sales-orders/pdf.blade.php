@php use App\Models\System\Setting; @endphp
@php
    $subtotal = $salesOrder->lines->sum(fn ($line) => (float) $line->subtotal_amount !== 0.0
        ? (float) $line->subtotal_amount
        : (float) $line->ordered_quantity * (float) $line->unit_price);
    $discountTotal = (float) $salesOrder->lines->sum('discount_amount');
    $taxTotal = (float) $salesOrder->lines->sum('tax_amount');
    $grandTotal = $salesOrder->lines->sum(fn ($line) => (float) $line->line_total_amount !== 0.0
        ? (float) $line->line_total_amount
        : (float) $line->total_amount);
@endphp
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
        .customer-box {
            border: 1px solid #d1d5db;
            min-height: 52px;
            padding: 10px;
        }
        .customer-name {
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
        .totals {
            margin-left: auto;
            margin-top: 14px;
            width: 265px;
        }
        .totals td {
            border-bottom: 1px solid #e5e7eb;
            padding: 5px 7px;
        }
        .totals .grand-total td {
            border-top: 2px solid #315985;
            color: #315985;
            font-size: 13px;
            font-weight: bold;
        }
        .notes {
            border: 1px solid #d1d5db;
            min-height: 38px;
            padding: 9px;
        }
        .signature-table {
            margin-top: 40px;
        }
        .signature-table td {
            padding-right: 30px;
            vertical-align: bottom;
            width: 50%;
        }
        .signature-line {
            border-top: 1px solid #374151;
            margin-top: 50px;
            padding-top: 6px;
        }
        .signature-subline {
            border-top: 1px solid #9ca3af;
            margin-top: 22px;
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
                <div class="document-title">{{ __('sales_orders.pdf.title') }}</div>
                <div class="document-number">{{ $salesOrder->document_number }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">{{ __('sales_orders.pdf.customer') }}</div>
        <div class="customer-box">
            <div class="customer-name">{{ $salesOrder->company_name }}</div>
            @if ($salesOrder->contact_name)
                <div>{{ $salesOrder->contact_name }}</div>
            @endif
        </div>
    </div>

    <table class="details section">
        <tr>
            <td>
                <span class="label">{{ __('sales_orders.pdf.order_date') }}</span
                >{{ $salesOrder->order_date->format(Setting::getDefaultDate()) }}
            </td>
            <td>
                <span class="label">{{ __('sales_orders.pdf.expected_delivery_date') }}</span
                >{{ $salesOrder->expected_delivery_date?->format(Setting::getDefaultDate()) ?? '—' }}
            </td>
            <td>
                <span class="label">{{ __('sales_orders.pdf.customer_reference') }}</span
                >{{ $salesOrder->customer_reference_number ?: '—' }}
            </td>
            <td>
                <span class="label">{{ __('sales_orders.pdf.status') }}</span
                >{{ __('sales_orders.status.'.$salesOrder->status->value) }}
            </td>
        </tr>
        @if ($salesOrder->quotation)
            <tr>
                <td colspan="4">
                    <span class="label">{{ __('sales_orders.pdf.quotation_number') }}</span
                    >{{ $salesOrder->quotation->document_number }}
                </td>
            </tr>
        @endif
    </table>

    <table class="items section">
        <thead>
            <tr>
                <th width="18%">{{ __('sales_orders.pdf.item') }}</th>
                <th width="13%">{{ __('sales_orders.pdf.sku') }}</th>
                <th>{{ __('sales_orders.pdf.description') }}</th>
                <th width="11%">{{ __('sales_orders.pdf.warranty') }}</th>
                <th class="text-right" width="10%">{{ __('sales_orders.pdf.quantity') }}</th>
                <th class="text-right" width="13%">{{ __('sales_orders.pdf.unit_price') }}</th>
                <th class="text-right" width="13%">{{ __('sales_orders.pdf.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($salesOrder->lines as $line)
                <tr>
                    <td><strong>{{ $line->product_name }}</strong></td>
                    <td>{{ $line->product_sku ?: '—' }}</td>
                    <td>{{ $line->description ?: '—' }}</td>
                    <td>
                        {{ $line->warranty_months === null ? __('products.warranty.unlimited') : ($line->warranty_months === 0 ? __('products.warranty.none') : __('products.warranty.months', ['count' => $line->warranty_months])) }}
                    </td>
                    <td class="text-right">{{ number_format((float) $line->ordered_quantity, 4) }}</td>
                    <td class="text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="text-right">
                        {{ number_format((float) ($line->line_total_amount ?: $line->total_amount), 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>{{ __('sales_orders.pdf.subtotal') }}</td>
            <td class="text-right">{{ $currencySymbol }} {{ number_format($subtotal, 2) }}</td>
        </tr>
        @if ($discountTotal > 0)
            <tr>
                <td>{{ __('sales_orders.pdf.discount') }}</td>
                <td class="text-right">{{ $currencySymbol }} {{ number_format($discountTotal, 2) }}</td>
            </tr>
        @endif
        @if ($taxTotal > 0)
            <tr>
                <td>{{ __('sales_orders.pdf.tax') }}</td>
                <td class="text-right">{{ $currencySymbol }} {{ number_format($taxTotal, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>{{ __('sales_orders.pdf.total') }}</td>
            <td class="text-right">{{ $currencySymbol }} {{ number_format($grandTotal, 2) }}</td>
        </tr>
    </table>

    @if ($salesOrder->notes)
        <div class="section">
            <div class="section-title">{{ __('sales_orders.pdf.notes') }}</div>
            <div class="notes">{!! nl2br(e($salesOrder->notes)) !!}</div>
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td><div class="signature-line">{{ __('sales_orders.pdf.prepared_by') }}</div></td>
            <td>
                <div class="signature-line">{{ __('sales_orders.pdf.customer_acceptance') }}</div>
                <div class="signature-subline">{{ __('sales_orders.pdf.signature') }}</div>
                <div class="signature-subline">{{ __('sales_orders.pdf.acceptance_date') }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">{{ $salesOrder->document_number }}</div>
</body>
</html>
