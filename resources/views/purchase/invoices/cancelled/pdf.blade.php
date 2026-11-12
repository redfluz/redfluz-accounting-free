@php use App\Models\System\Setting; @endphp
@php
    $firstLine = $lines->first();
    $subtotal = $lines->sum(fn ($line) => (float) $line->subtotal_amount !== 0.0
        ? (float) $line->subtotal_amount
        : (float) $line->quantity * (float) $line->unit_price);
    $discountTotal = $lines->sum('discount_amount');
    $taxTotal = $lines->sum('tax_amount');
    $grandTotal = $lines->sum(fn ($line) => (float) $line->line_total_amount !== 0.0
        ? (float) $line->line_total_amount
        : (float) $line->total_amount);
    $payments = $payments ?? collect();
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

        .cancelled-badge {
            background: #b91c1c;
            color: #fff;
            display: inline-block;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-top: 6px;
            padding: 3px 8px;
            text-transform: uppercase;
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

        .payment-table {
            margin-top: 10px;
            table-layout: fixed;
        }

        .payment-table th {
            background: #e5e7eb;
            font-size: 10px;
            padding: 8px 9px;
            text-align: left;
        }

        .payment-table td {
            border-bottom: 1px solid #d1d5db;
            padding: 8px 9px;
        }

        .payment-table tfoot th {
            border-top: 2px solid #1e3a8a;
            padding: 8px 9px;
            font-size: 10px;
            text-align: left;
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
                        {{ __('invoice_designs.pdf.purchase_title') }}
                    </div>
                    <div>{{ $documentNumber }}</div>
                    <div class="cancelled-badge">{{ __('ledgers.cancelled_title') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table class="summary">
            <tr>
                <td width="65%">
                    <div class="meta-label">{{ __('invoice_designs.pdf.bill_to') }}</div>
                    <div class="customer-name">{{ $invoice->company?->name ?? ($invoice->company_name ?? '-') }}</div>
                    @if ($invoice->contact?->name)
                        <div class="muted">{{ $invoice->contact->name }}</div>
                    @endif
                </td>
                <td>
                    <span class="muted">{{ __('invoice_designs.pdf.date') }}:</span>
                    {{ $invoice->date->format(Setting::getDefaultDate()) }}
                </td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('invoice_designs.pdf.description') }}</th>
                    <th scope="col">{{ __('invoice_designs.pdf.quantity') }}</th>
                    <th scope="col">{{ __('invoice_designs.pdf.unit_price') }}</th>
                    <th scope="col">{{ __('invoice_designs.pdf.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td>{{ $line->product?->name ?? ($line->product_name ?? ($line->description ?? '-')) }}</td>
                        <td>{{ $line->quantity }}</td>
                        <td>{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td>
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
                <td>{{ number_format($subtotal, 2) }}</td>
            </tr>
            @if ($discountTotal > 0)
                <tr>
                    <td>
                        {{ __('ledgers.table.column_discount_amount') }}
                        @if ($firstLine?->discount_value)
                            ({{ number_format((float) $firstLine->discount_value, 2) }})
                        @endif
                    </td>
                    <td>{{ number_format($discountTotal, 2) }}</td>
                </tr>
            @endif
            @if ($taxTotal > 0)
                <tr>
                    <td>{{ __('ledgers.table.column_tax_amount') }}</td>
                    <td>{{ number_format($taxTotal, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td>{{ __('invoice_designs.pdf.total') }}</td>
                <td class="accent">{{ number_format($grandTotal, 2) }}</td>
            </tr>
            <tr>
                <td>{{ __('invoices.payment_received') }}</td>
                <td>{{ number_format((float) ($amountPaid ?? '0.00'), 2) }}</td>
            </tr>
            <tr>
                <td>{{ __('invoices.balance_due') }}</td>
                <td>{{ number_format((float) ($balanceDue ?? $grandTotal), 2) }}</td>
            </tr>
        </table>

        <div class="payment-box">
            <strong>{{ __('invoice_designs.pdf.payment_details') }}</strong>

            <table style="margin-top: 8px; width: 100%;">
                <tr>
                    <td style="width: 50%;"><span class="muted">{{ __('invoices.payment_received') }}</span><br /><strong>{{ number_format((float) ($amountPaid ?? '0.00'), 2) }}</strong></td>
                    <td style="width: 50%;"><span class="muted">{{ __('invoices.balance_due') }}</span><br /><strong class="accent">{{ number_format((float) ($balanceDue ?? $grandTotal), 2) }}</strong></td>
                </tr>
            </table>

            @if ($payments->isNotEmpty())
                <table class="payment-table">
                    <colgroup>
                        <col style="width: 20%" />
                        <col style="width: 25%" />
                        <col style="width: 40%" />
                        <col style="width: 15%" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_document_number') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_type') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            @php
                                $applied = bcsub((string) $payment->debit_amount, (string) $payment->credit_amount, 2);
                                $subtype = $payment->subtype->value ?? $payment->subtype ?? null;
                                $typeLabel = $subtype ? __('ledgers.sub_type.' . $subtype) : '-';
                            @endphp
                            <tr>
                                <td>{{ $payment->date?->format(Setting::getDefaultDate()) ?? '-' }}</td>
                                <td>{{ $payment->document_number }}</td>
                                <td>{{ $typeLabel }}</td>
                                <td>{{ number_format((float) $applied, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row" colspan="3">{{ __('invoices.payment_received') }}</th>
                            <th scope="row">{{ number_format((float) ($amountPaid ?? '0.00'), 2) }}</th>
                        </tr>
                        <tr>
                            <th scope="row" colspan="3">{{ __('invoices.balance_due') }}</th>
                            <th scope="row">{{ number_format((float) ($balanceDue ?? $grandTotal), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            @else
                <div class="muted" style="margin-top: 8px;">{{ __('invoices.no_payments') }}</div>
            @endif
        </div>
    </div>

    </body>
</html>
