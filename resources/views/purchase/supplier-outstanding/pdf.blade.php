@php use App\Models\System\Setting; @endphp
@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>{{ __('supplier_outstanding.title') }}</title>
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
    </style>
</head>
<body>
    @include('partials.pdf-company-header')
    <h2>{{ __('supplier_outstanding.title') }}</h2>
    <table width="100%" cellspacing="0" cellpadding="5" border="1">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Supplier</th>
                <th>Items Received</th>
                <th>Invoice Date</th>
                <th>Credit Term</th>
                <th>Payment Due</th>
                <th>Invoice Amount</th>
                <th>Allocated Paid</th>
                <th>Outstanding Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($exportInvoices as $invoice)
                <tr>
                    <td>{{ $invoice->document_number }}</td>
                    <td>{{ $invoice->company_name }}</td>
                    <td>{{ $invoice->received_quantity }}/{{ $invoice->ordered_quantity }}</td>
                    <td>{{ optional($invoice->invoice_date)->format(Setting::getDefaultDate()) }}</td>
                    <td>
                        {{ $invoice->due_date ? Carbon::parse($invoice->invoice_date)->diffInDays(Carbon::parse($invoice->due_date)) . ' days' : '-' }}
                    </td>
                    <td>{{ optional($invoice->due_date)->format(Setting::getDefaultDate()) }}</td>
                    <td align="right">{{ $currencySymbol }} {{ number_format((float) $invoice->invoice_amount, 2) }}</td>
                    <td align="right">{{ $currencySymbol }} {{ number_format((float) $invoice->paid_amount, 2) }}</td>
                    <td align="right">{{ $currencySymbol }} {{ number_format((float) $invoice->balance_amount, 2) }}</td>
                    <td>Outstanding</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6">{{ __('supplier_outstanding.grand_total') }}</th>
                <th align="right">{{ $currencySymbol }} {{ number_format((float) $exportInvoices->sum('invoice_amount'), 2) }}</th>
                <th align="right">{{ $currencySymbol }} {{ number_format((float) $exportInvoices->sum('paid_amount'), 2) }}</th>
                <th align="right">{{ $currencySymbol }} {{ number_format((float) $exportInvoices->sum('balance_amount'), 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
