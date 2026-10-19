@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Enums\SalesOrderStatus;

    $canApprove = auth()->user()->hasFilePermission(FileResources::SALES_ORDERS, FileAction::APPROVE);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::SALES_ORDERS, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::SALES_ORDERS, FileAction::REMOVE);
    $canDeliver = auth()->user()->hasFilePermission(FileResources::SALES_DELIVERY_ORDERS, FileAction::ADD);
    $canInvoice = auth()->user()->hasFilePermission(FileResources::SALES_INVOICES, FileAction::ADD);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $salesOrder->document_number }}</h4>
                <p class="fg-secondary small mb-0">
                    {{ $salesOrder->company_name }} | {{ __('sales_orders.status.' . $salesOrder->status->value) }}
                </p>
            </div>
            <div class="d-flex gap-2">
                @if ($salesOrder->status === SalesOrderStatus::DRAFT && $canApprove)
                    <form autocomplete="off" action="{{ route('sales.sales-orders.approve', $salesOrder) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm" type="submit">
                            <i class="me-1" data-lucide="circle-check"></i>{{ __('common.actions.approve') }}
                        </button>
                    </form>
                @endif
                @if ($salesOrder->status === SalesOrderStatus::DRAFT && $canUpdate)
                    <a class="btn btn-solid theme-warning btn-sm"
                        href="{{ route('sales.sales-orders.edit', $salesOrder) }}"><i class="me-1"
                            data-lucide="square-pen"></i>{{ __('common.actions.edit') }}</a>
                @endif
                @if ($salesOrder->status === SalesOrderStatus::DRAFT && $canRemove)
                    <form autocomplete="off" action="{{ route('sales.sales-orders.destroy', $salesOrder) }}" method="POST"
                        data-confirm-message="{{ __('sales_orders.messages.delete_confirm') }}" data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-solid theme-danger btn-sm" type="submit">
                            <i class="me-1" data-lucide="trash-2"></i>{{ __('common.actions.delete') }}
                        </button>
                    </form>
                @endif
                @if (in_array($salesOrder->status, [SalesOrderStatus::APPROVED, SalesOrderStatus::PARTIALLY_DELIVERED], true) &&
                        !$salesOrder->invoice &&
                        $canDeliver)
                    <a class="btn btn-solid theme-success btn-sm"
                        href="{{ route('sales.delivery-orders.create', ['sales_order' => $salesOrder->id]) }}"><i
                            class="me-1" data-lucide="truck"></i>{{ __('sales_orders.actions.add_delivery_order') }}</a>
                @endif
                @if (
                    $salesOrder->status === SalesOrderStatus::APPROVED &&
                        $salesOrder->deliveryOrders->isEmpty() &&
                        !$salesOrder->invoice &&
                        $canInvoice)
                    <form autocomplete="off" action="{{ route('sales.sales-orders.sales-invoice', $salesOrder) }}"
                        method="POST" data-confirm-message="{{ __('sales_orders.messages.invoice_confirm') }}"
                        data-confirm-tone="danger">
                        @csrf
                        <button class="btn btn-solid theme-primary btn-sm text-nowrap" type="submit">
                            <i class="me-1"
                                data-lucide="file-plus-2"></i>{{ __('sales_orders.actions.create_sales_invoice') }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm text-nowrap"
                    href="{{ route('sales.sales-orders.pdf', $salesOrder) }}" target="_blank" rel="noopener noreferrer">
                    <i class="me-1" data-lucide="printer"></i>
                    {{ __('common.actions.print_pdf') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('sales.sales-orders.index') }}"><i
                        class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            @if ($salesOrder->quotation)
                <div class="alert alert theme-info">
                    <a class="alert-link"
                        href="{{ route('sales.quotations.show', $salesOrder->quotation->document_number) }}">{{ __('sales_orders.messages.source_quotation') }}:
                        {{ $salesOrder->quotation->document_number }}</a>
                </div>
            @endif
            @if ($salesOrder->invoice)
                <div class="alert alert theme-success">
                    {{ __('sales_orders.messages.sales_invoice') }}:
                    <a class="alert-link" href="{{ route('sales.invoices.show', $salesOrder->invoice->document_number) }}">
                        {{ $salesOrder->invoice->document_number }}
                    </a>
                </div>
            @endif
            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('sales_orders.form.product') }}</th>
                            <th>{{ __('sales_orders.form.description') }}</th>
                            <th>{{ __('sales_orders.form.warranty') }}</th>
                            <th class="text-end">{{ __('sales_orders.form.quantity') }}</th>
                            <th class="text-end">{{ __('sales_orders.table.delivered') }}</th>
                            <th class="text-end">{{ __('sales_orders.table.remaining') }}</th>
                            <th class="text-end">{{ __('sales_orders.table.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($salesOrder->lines as $line)
                            @php($remaining = bcsub((string) $line->ordered_quantity, (string) $line->delivered_quantity, 4))
                            <tr>
                                <td>
                                    <strong>{{ $line->product_name }}</strong><small
                                        class="fg-secondary d-block">{{ $line->product_sku }}</small>
                                </td>
                                <td>{{ $line->description ?? '-' }}</td>
                                <td>
                                    {{ $line->warranty_months === null ? __('products.warranty.unlimited') : ($line->warranty_months === 0 ? __('products.warranty.none') : __('products.warranty.months', ['count' => $line->warranty_months])) }}
                                </td>
                                <td class="text-end">{{ $line->ordered_quantity }}</td>
                                <td class="text-end">{{ $line->delivered_quantity }}</td>
                                <td class="fw-bold text-end">{{ $remaining }}</td>
                                <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $line->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <h6 class="fw-bold mt-4">{{ __('sales_orders.messages.deliveries') }}</h6>
            @forelse ($salesOrder->deliveryOrders as $delivery)
                <a class="d-block mb-2"
                    href="{{ route('sales.delivery-orders.show', $delivery) }}">{{ $delivery->document_number }} -
                    {{ __('delivery_orders.status.' . $delivery->status->value) }}</a>
            @empty
                <span class="text-center"><i data-lucide="minus-circle"></i></span>
            @endforelse
        </div>
    </div>
@endsection
