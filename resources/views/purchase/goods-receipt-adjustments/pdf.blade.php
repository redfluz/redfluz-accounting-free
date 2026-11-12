@php
    use App\Models\System\Setting;
    use App\Enums\Inventory\ProductMovementDirection;
    use Carbon\Carbon;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8" />
    <title>{{ $documentNumber }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 11px; }
        .header { border-bottom: 3px solid #1f4e79; padding-bottom: 14px; margin-bottom: 20px; }
        .brand { font-size: 20px; font-weight: bold; color: #1f4e79; }
        .title { font-size: 22px; font-weight: bold; text-align: right; }
        .muted { color: #64748b; }
        .meta { width: 100%; margin-bottom: 18px; }
        .meta td { width: 33%; padding: 8px; background: #f4f7fa; border: 1px solid #d9e1e8; }
        .label { font-size: 9px; text-transform: uppercase; color: #64748b; display: block; margin-bottom: 3px; }
        table.lines { width: 100%; border-collapse: collapse; }
        .lines th { background: #1f4e79; color: #fff; padding: 8px; text-align: left; }
        .lines td { border-bottom: 1px solid #d9e1e8; padding: 8px; }
        .amount { text-align: right; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; color: #64748b; font-size: 9px; }
        .company-header { text-align: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #ddd; }
        .company-name { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
        .company-info { font-size: 9px; color: #555; }
    </style>
</head>
<body>
    @include('partials.pdf-company-header')
    <table class="header" style="width: 100%">
        <tr>
            <td>
                @if ($setting->logo_path)
                    <img alt="" src="{{ public_path('storage/' . $setting->logo_path) }}" style="max-height: 48px; max-width: 150px" /><br />
                @endif
                <span class="brand">{{ $setting->company_name ?? config('app.name') }}</span><br />
                <span class="muted">{{ $setting->address }}</span>
            </td>
            <td class="title">
                {{ __('goods_receipt_adjustments.cancelled_title') }}<br />
                <span class="muted" style="font-size: 12px; font-weight: normal;">{{ __('ledgers.status_cancelled') }}</span>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <span class="label">{{ __('goods_receipts.table.column_document_number') }}</span>
                <strong>{{ $documentNumber }}</strong>
            </td>
            <td>
                <span class="label">{{ __('goods_receipts.table.column_date') }}</span>
                <strong>{{ $entries->isNotEmpty() ? Carbon::parse($entries->first()->date)->format(Setting::getDefaultDate()) : '-' }}</strong>
            </td>
            <td>
                <span class="label">{{ __('goods_receipts.table.column_company') }}</span>
                <strong>{{ $entries->first()->company_name ?? '-' }}</strong>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th scope="col">{{ __('goods_receipts.table.column_sku') }}</th>
                <th scope="col">{{ __('goods_receipts.table.column_product') }}</th>
                <th scope="col" class="amount">{{ __('goods_receipts.adjustment.column_current') }}</th>
                <th scope="col" class="amount">{{ __('goods_receipts.adjustment.column_difference') }}</th>
                <th scope="col">{{ __('goods_receipts.line_state.column_state') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                @php($isIncrease = $entry->direction === ProductMovementDirection::IN)
                <tr>
                    <td><code>{{ $entry->product_sku }}</code></td>
                    <td>{{ $entry->product_name }}</td>
                    <td class="amount">{{ number_format((float) $entry->quantity, 4) }}</td>
                    <td class="amount">{{ $isIncrease ? '+' : '-' }}{{ number_format((float) $entry->quantity, 4) }}</td>
                    <td>{{ __('goods_receipts.line_state.pending_correction') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">{{ __('goods_receipt_adjustments.cancelled_empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">{{ __('journal_entries.generated_at', ['date' => now()->format(Setting::getDefaultDate('long_date_format'))]) }}</div>
</body>
</html>
