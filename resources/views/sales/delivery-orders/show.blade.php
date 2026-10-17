@php
    use App\Enums\DeliveryOrderStatus;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canPost = auth()->user()->hasFilePermission(FileResources::SALES_DELIVERY_ORDERS, FileAction::APPROVE);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::SALES_DELIVERY_ORDERS, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::SALES_DELIVERY_ORDERS, FileAction::REMOVE);
    $canInvoice = auth()->user()->hasFilePermission(FileResources::SALES_INVOICES, FileAction::ADD);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $deliveryOrder->document_number }}</h4>
                <p class="fg-secondary small mb-0">
                    {{ $deliveryOrder->salesOrder?->company_name }} |
                    {{ __('delivery_orders.status.' . $deliveryOrder->status->value) }}
                </p>
            </div>
            <div class="d-flex gap-2">
                @if ($deliveryOrder->status === DeliveryOrderStatus::DRAFT && $canPost)
                    <form autocomplete="off" action="{{ route('sales.delivery-orders.post', $deliveryOrder) }}" method="POST"
                        data-confirm-message="{{ __('delivery_orders.messages.post_confirm') }}" data-confirm-tone="danger">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm" type="submit">
                            <i class="me-1" data-lucide="circle-check"></i>{{ __('common.actions.post') }}
                        </button>
                    </form>
                @endif
                @if ($deliveryOrder->status !== DeliveryOrderStatus::CANCELLED && $canUpdate)
                    <a class="btn btn-solid theme-warning btn-sm"
                        href="{{ route('sales.delivery-orders.edit', $deliveryOrder) }}"><i class="me-1"
                            data-lucide="square-pen"></i>{{ __('common.actions.edit') }}</a>
                @endif
                @if ($deliveryOrder->status === DeliveryOrderStatus::DRAFT && $canRemove)
                    <form autocomplete="off" action="{{ route('sales.delivery-orders.destroy', $deliveryOrder) }}"
                        method="POST" data-confirm-message="{{ __('delivery_orders.messages.delete_confirm') }}"
                        data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-solid theme-danger btn-sm" type="submit">
                            <i class="me-1" data-lucide="trash-2"></i>{{ __('common.actions.delete') }}
                        </button>
                    </form>
                @endif
                @if ($deliveryOrder->status === DeliveryOrderStatus::POSTED && ! $invoice && $canInvoice)
                    <form autocomplete="off" action="{{ route('sales.delivery-orders.sales-invoice', $deliveryOrder) }}"
                        method="POST">
                        @csrf
                        <button class="btn btn-solid theme-success btn-sm" type="submit">
                            <i class="me-1"
                                data-lucide="receipt-text"></i>{{ __('delivery_orders.actions.create_sales_invoice') }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm text-nowrap"
                    href="{{ route('sales.delivery-orders.pdf', $deliveryOrder) }}" target="_blank">
                    <i class="me-1" data-lucide="printer"></i>
                    {{ __('common.actions.print_pdf') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('sales.delivery-orders.index') }}"><i
                        class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <div class="row g-3 mb-4">
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small class="badge theme-secondary">{{ __('delivery_orders.form.sales_order') }}</small>
                        <div>
                            <a class="fw-bold"
                                href="{{ route('sales.sales-orders.show', $deliveryOrder->salesOrder) }}">{{ $deliveryOrder->salesOrder?->document_number }}</a>
                        </div>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small class="badge theme-secondary">{{ __('delivery_orders.form.stock_location') }}</small>
                        <div class="fw-bold">{{ $deliveryOrder->stock_location_name }}</div>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small class="badge theme-secondary">{{ __('delivery_orders.form.shipping_status') }}</small>
                        <div class="fw-bold">
                            {{ __('delivery_orders.shipping_status.' . $deliveryOrder->shipping_status->value) }}
                        </div>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small class="badge theme-secondary">{{ __('delivery_orders.form.courier') }}</small>
                        <div class="fw-bold">{{ $deliveryOrder->courier ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="mb-4 rounded border p-3">
                <small class="badge theme-secondary">{{ __('delivery_orders.form.delivery_address') }}</small>
                <div class="fw-bold">{{ $deliveryOrder->recipient_name }}</div>
                @if ($deliveryOrder->recipient_phone)
                    <div>{{ $deliveryOrder->recipient_phone }}</div>
                @endif
                <div class="text-break">{!! nl2br(e($deliveryOrder->delivery_address)) !!}</div>
            </div>
            <div class="table-responsive rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('delivery_orders.form.stock_item') }}</th>
                            <th>{{ __('sales_orders.form.description') }}</th>
                            <th class="text-end">{{ __('delivery_orders.form.quantity') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveryOrder->lines as $line)
                            <tr>
                                <td>
                                    <strong>{{ $line->product_name }}</strong><small
                                        class="fg-secondary d-block">{{ $line->product_sku }}</small>
                                </td>
                                <td>{{ $line->description ?? '-' }}</td>
                                <td class="text-end">{{ $line->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($invoice)
                <div class="alert alert theme-success mt-3">
                    {{ __('delivery_orders.messages.invoice_created') }}:
                    <a class="alert-link"
                        href="{{ route('sales.invoices.show', $invoice->document_number) }}">{{ $invoice->document_number }}</a>
                </div>
            @endif
        </div>
    </div>
@endsection
