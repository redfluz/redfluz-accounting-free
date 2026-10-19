@php
    use App\Models\System\Setting;
    use App\Enums\QuotationStatus;

    $quotationLines = $lines;
    $hasPhysicalProduct = $quotationLines->contains(
        fn($line) => $line->product !== null && !$line->product->is_service,
    );
    $convertsToSalesOrder = $hasPhysicalProduct;
    $quotationTotal = $quotationLines->sum('total_amount');
@endphp

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $quotationNumber }}</h4>
                <p class="fg-secondary small mb-0">
                    {{ $quotation->company_name ?? '-' }} | {{ __('quotations.status.' . $quotation->status->value) }}
                </p>
            </div>
            <div class="d-flex gap-2">
                @if (!$quotation->is_converted)
                    <a class="btn btn-solid theme-warning btn-sm"
                        href="{{ route('sales.quotations.edit', $quotationNumber) }}"><i class="me-1"
                            data-lucide="square-pen"></i>{{ __('common.actions.edit') }}</a>
                @endif
                <a class="btn btn-solid theme-info btn-sm"
                    href="{{ route('sales.quotations.pdf', ['quotationNumber' => $quotationNumber]) }}" target="_blank"
                    rel="noopener noreferrer"><i class="me-1"
                        data-lucide="printer"></i>{{ __('common.actions.print_pdf') }}</a>
                @if (!$quotation->is_converted && $quotation->status === QuotationStatus::ACCEPTED)
                    <form autocomplete="off" action="{{ route('sales.quotations.convert', $quotationNumber) }}" method="POST"
                        data-confirm-message="{{ $convertsToSalesOrder ? __('quotations.messages.convert_sales_order_confirmation') : __('quotations.messages.convert_confirmation') }}"
                        data-confirm-tone="danger">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm" type="submit">
                            <i class="me-1" data-lucide="arrow-right-left"></i>
                            {{ $convertsToSalesOrder ? __('quotations.actions.convert_sales_order') : __('quotations.actions.convert') }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('sales.quotations.index') }}"><i
                        class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            @if ($quotation->convertedSalesOrder)
                <div class="alert alert theme-success">
                    {{ __('quotations.table.converted_to') }}:
                    <a class="alert-link"
                        href="{{ route('sales.sales-orders.show', $quotation->convertedSalesOrder) }}">{{ $quotation->convertedSalesOrder->document_number }}</a>
                </div>
            @elseif ($quotation->convertedInvoice)
                <div class="alert alert theme-success">
                    {{ __('quotations.table.converted_to') }}:
                    <a class="alert-link"
                        href="{{ route('sales.invoices.show', $quotation->convertedInvoice->document_number) }}">{{ $quotation->convertedInvoice->document_number }}</a>
                </div>
            @endif
            <div class="row g-3 mb-4">
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small
                            class="fg-secondary d-block">{{ __('quotations.form.date') }}</small><strong>{{ $quotation->date->format(Setting::getDefaultDate()) }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small
                            class="fg-secondary d-block">{{ __('quotations.form.expiry_date') }}</small><strong>{{ $quotation->expiry_date?->format(Setting::getDefaultDate()) ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small
                            class="fg-secondary d-block">{{ __('quotations.form.reference_number') }}</small><strong>{{ $quotation->reference_number ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small
                            class="fg-secondary d-block">{{ __('quotations.form.total_amount') }}</small><strong>{{ number_format((float) $quotationTotal, 2) }}</strong>
                    </div>
                </div>
            </div>
            <div class="table-responsive mb-4 rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('quotations.form.product') }}</th>
                            <th>{{ __('quotations.form.description') }}</th>
                            <th class="text-end">{{ __('quotations.form.quantity') }}</th>
                            <th class="text-end">{{ __('quotations.form.unit_price') }}</th>
                            <th class="text-end">{{ __('quotations.form.total_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotationLines as $line)
                            <tr>
                                <td>{{ $line->product_name ?? '-' }}</td>
                                <td>{{ $line->description ?? '-' }}</td>
                                <td class="text-end">{{ $line->quantity }}</td>
                                <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="fw-bold text-end">{{ number_format((float) $line->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-1">
                        <tr>
                            <th class="text-end" colspan="4">{{ __('common.labels.total') }}</th>
                            <th class="text-end">{{ number_format((float) $quotationTotal, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if ($quotation->attachment_path)
                <p>
                    <strong>{{ __('quotations.form.attachment') }}:</strong> {{ basename($quotation->attachment_path) }}
                </p>
            @endif
            @if ($quotation->notes)
                <div class="rounded border p-3">
                    <small class="fg-secondary d-block">{{ __('quotations.form.notes') }}</small>{{ $quotation->notes }}
                </div>
            @endif
        </div>
    </div>
@endsection
