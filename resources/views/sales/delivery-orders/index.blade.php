@php use App\Models\System\Setting; @endphp
@extends('layouts.app')

@section('content')
    @php
        use App\Enums\DeliveryOrderStatus;
        use App\Enums\FileAction;
        use App\Enums\FileResources;

        $canUpdate = auth()->user()->hasFilePermission(FileResources::SALES_DELIVERY_ORDERS, FileAction::UPDATE);
    @endphp
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('delivery_orders.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('delivery_orders.subtitle') }}</p>
            </div>
        </div>
        <div class="card-body">
            @include('partials.date-range-filter', [
                'action' => route('sales.delivery-orders.index'),
                'search' => $search,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])
            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('delivery_orders.table.number') }}</th>
                            <th>{{ __('delivery_orders.table.sales_order') }}</th>
                            <th>{{ __('delivery_orders.table.customer') }}</th>
                            <th>{{ __('delivery_orders.table.stock_location') }}</th>
                            <th>{{ __('delivery_orders.table.date') }}</th>
                            <th>{{ __('delivery_orders.table.status') }}</th>
                            <th class="text-center">{{ __('delivery_orders.table.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deliveryOrders as $delivery)
                            <tr>
                                <td class="fw-bold">{{ $delivery->document_number }}</td>
                                <td>{{ $delivery->salesOrder?->document_number ?? '-' }}</td>
                                <td>{{ $delivery->salesOrder?->company_name ?? '-' }}</td>
                                <td>{{ $delivery->stock_location_name }}</td>
                                <td>{{ $delivery->delivery_date->format(Setting::getDefaultDate()) }}</td>
                                <td>
                                    <span
                                        class="badge theme-secondary">{{ __('delivery_orders.status.' . $delivery->status->value) }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a class="btn btn-sm btn-solid theme-info me-1"
                                            href="{{ route('sales.delivery-orders.show', $delivery) }}"><i
                                                data-lucide="eye"></i></a>
                                        @if ($delivery->status !== DeliveryOrderStatus::CANCELLED && $canUpdate)
                                            <a class="btn btn-sm btn-solid theme-warning me-1"
                                                href="{{ route('sales.delivery-orders.edit', $delivery) }}"
                                                title="{{ __('common.actions.edit') }}"><i
                                                    data-lucide="square-pen"></i></a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="7">
                                    {{ __('delivery_orders.messages.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $deliveryOrders->links() }}</div>
        </div>
    </div>
@endsection
