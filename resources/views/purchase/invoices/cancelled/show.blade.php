@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    $canApprove = auth()->user()->hasFilePermission(FileResources::PURCHASE_INVOICES, FileAction::APPROVE);
    use App\Enums\LedgerSubType;
    use Carbon\Carbon;
@endphp

@props(['documentNumber', 'lines'])

@extends('layouts.app')

@section('content')
    @php
        $invoice = $lines->first();
        $subtotal = $lines->sum(
            fn($line) => (float) $line->subtotal_amount !== 0.0
                ? (float) $line->subtotal_amount
                : (float) $line->quantity * (float) $line->unit_price,
        );
        $discountTotal = $lines->sum('discount_amount');
        $taxTotal = $lines->sum('tax_amount');
        $grandTotal = $lines->sum(
            fn($line) => (float) $line->line_total_amount !== 0.0
                ? (float) $line->line_total_amount
                : (float) $line->total_amount,
        );
    @endphp
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">{{ $invoice->company?->name ?? '-' }} |
                    {{ Carbon::parse($invoice->date)->format(Setting::getDefaultDate()) }}</span>
            </div>
            <div class="d-flex gap-2">
                <span class="badge theme-danger d-flex align-items-center"> {{ __('ledgers.status_cancelled') }} </span>
                @if ($canApprove)
                    <form autocomplete="off" action="{{ route('purchase.invoices.cancelled.uncancel', $documentNumber) }}" method="POST"
                        data-confirm-message="{{ __('common.messages.uncancel_confirm') }}" data-confirm-tone="danger">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-solid theme-warning"
                            title="{{ __('common.actions.undo') }}">
                            <i data-lucide="undo-2"></i>{{ __('common.actions.undo') }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('purchase.invoices.cancelled.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive mb-4 rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('ledgers.table.column_product') }}</th>
                            <th>{{ __('ledgers.table.column_description') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_quantity') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_unit_price') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_discount_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_tax_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            <tr>
                                <td>{{ $line->product?->name ?? '-' }}</td>
                                <td>{{ $line->description ?? '-' }}</td>
                                <td class="text-end">{{ $line->quantity }}</td>
                                <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->discount_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->tax_amount, 2) }}</td>
                                <td class="fw-bold text-end">
                                    {{ number_format((float) $line->line_total_amount !== 0.0 ? (float) $line->line_total_amount : (float) $line->total_amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-1">
                        <tr>
                            <th class="text-end" colspan="6">{{ __('ledgers.table.column_subtotal_amount') }}</th>
                            <th class="text-end">{{ number_format($subtotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">{{ __('ledgers.table.column_discount_amount') }}</th>
                            <th class="text-end">{{ number_format($discountTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">{{ __('ledgers.table.column_tax_amount') }}</th>
                            <th class="text-end">{{ number_format($taxTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">{{ __('common.labels.total') }}</th>
                            <th class="fg-primary text-end">{{ number_format($grandTotal, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
