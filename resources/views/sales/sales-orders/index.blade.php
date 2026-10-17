@php use App\Models\System\Setting; @endphp
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('sales_orders.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('sales_orders.subtitle') }}</p>
            </div>
            <a class="btn btn-solid theme-success btn-sm" href="{{ route('sales.sales-orders.create') }}">
                <i class="me-1" data-lucide="plus"></i>
                {{ __('common.actions.add') }}
            </a>
        </div>
        <div class="card-body">
            @include('partials.date-range-filter', [
                'action' => route('sales.sales-orders.index'),
                'search' => $search,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])
            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('sales_orders.table.number') }}</th>
                            <th>{{ __('sales_orders.table.date') }}</th>
                            <th>{{ __('sales_orders.table.customer') }}</th>
                            <th>{{ __('sales_orders.table.status') }}</th>
                            <th class="text-end">{{ __('sales_orders.table.total') }}</th>
                            <th class="text-center">{{ __('sales_orders.table.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salesOrders as $order)
                            <tr>
                                <td class="fw-bold">{{ $order->document_number }}</td>
                                <td>{{ $order->order_date->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $order->company_name }}</td>
                                <td>
                                    <span
                                        class="badge theme-secondary">{{ __('sales_orders.status.' . $order->status->value) }}</span>
                                </td>
                                <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $order->total_amount, 2) }}</td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-solid theme-info me-1"
                                        href="{{ route('sales.sales-orders.show', $order) }}"><i data-lucide="eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="6">
                                    {{ __('sales_orders.messages.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $salesOrders->links() }}</div>
        </div>
    </div>
@endsection
