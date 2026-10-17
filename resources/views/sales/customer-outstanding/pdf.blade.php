@php use App\Models\System\Setting; @endphp
@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>{{ __('customer_outstanding.title') }}</title>
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

        .as-of {
            font-size: 9px;
            color: #555;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    @include('partials.pdf-company-header')
    <h2>{{ __('customer_outstanding.title') }}</h2>
    <div class="as-of">{{ __('customer_outstanding.as_of_date') }}: {{ Carbon::parse($asOfDate)->format(Setting::getDefaultDate()) }}</div>
    <table width="100%" cellspacing="0" cellpadding="5" border="1">
        <thead>
            <tr>
                <th>{{ __('customer_outstanding.reference') }}</th>
                <th>{{ __('customer_outstanding.customer') }}</th>
                <th>{{ __('customer_outstanding.items_delivered') }}</th>
                <th>{{ __('customer_outstanding.delivery_completion') }}</th>
                <th>{{ __('customer_outstanding.invoice_date') }}</th>
                <th>{{ __('customer_outstanding.credit_term') }}</th>
                <th>{{ __('customer_outstanding.due_date') }}</th>
                <th>{{ __('customer_outstanding.invoice_amount') }}</th>
                <th>{{ __('customer_outstanding.paid_amount') }}</th>
                <th>{{ __('customer_outstanding.balance') }}</th>
                <th>{{ __('customer_outstanding.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($exportInvoices as $invoice)
                <tr>
                    <td>{{ $invoice->document_number }}</td>
                    <td>{{ $invoice->company_name }}</td>
                    <td>{{ $invoice->delivered_quantity }}/{{ $invoice->ordered_quantity }}</td>
                    <td>{{ number_format((float) $invoice->delivery_completion, 2) }}%</td>
                    <td>{{ optional($invoice->invoice_date)->format(Setting::getDefaultDate()) }}</td>
                    <td>
                        {{ $invoice->due_date ? Carbon::parse($invoice->invoice_date)->diffInDays(Carbon::parse($invoice->due_date)) . ' ' . __('customer_outstanding.days') : '-' }}
                    </td>
                    <td>{{ optional($invoice->due_date)->format(Setting::getDefaultDate()) }}</td>
                    <td align="right">{{ $currencySymbol }} {{ number_format((float) $invoice->invoice_amount, 2) }}</td>
                    <td align="right">{{ $currencySymbol }} {{ number_format((float) $invoice->paid_amount, 2) }}</td>
                    <td align="right">{{ $currencySymbol }} {{ number_format((float) $invoice->balance_amount, 2) }}</td>
                    <td>
                        @if ($invoice->days_overdue > 0)
                            {{ __('customer_outstanding.days_overdue') }}: {{ $invoice->days_overdue }}
                        @else
                            {{ __('customer_outstanding.statuses.outstanding') }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="7">{{ __('customer_outstanding.grand_total') }}</th>
                <th align="right">{{ $currencySymbol }} {{ number_format((float) $exportInvoices->sum('invoice_amount'), 2) }}</th>
                <th align="right">{{ $currencySymbol }} {{ number_format((float) $exportInvoices->sum('paid_amount'), 2) }}</th>
                <th align="right">{{ $currencySymbol }} {{ number_format((float) $exportInvoices->sum('balance_amount'), 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
