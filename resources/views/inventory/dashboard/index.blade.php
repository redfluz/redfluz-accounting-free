@php use App\Models\System\Setting; @endphp
@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ __('inventory_dashboard.title') }}</h1>
                <p class="fg-secondary mb-0">{{ __('inventory_dashboard.subtitle') }}</p>
            </div>
            <a class="btn btn-solid theme-primary" href="{{ route('inventory.products.index') }}"><i class="me-1"
                    data-lucide="package"></i>{{ __('inventory_dashboard.view_products') }}</a>
        </div>
        <div class="row g-3 mb-4">
            @foreach ([['icon' => 'package', 'label' => __('inventory_dashboard.kpi.products'), 'value' => number_format($productCount)], ['icon' => 'boxes', 'label' => __('inventory_dashboard.kpi.available_units'), 'value' => number_format($availableUnits, 2)], ['icon' => 'wallet', 'label' => __('inventory_dashboard.kpi.inventory_value'), 'value' => $currencySymbol . ' ' . number_format($inventoryValue, 2)], ['icon' => 'triangle-alert', 'label' => __('inventory_dashboard.kpi.low_stock'), 'value' => number_format($lowStockCount)]] as $kpi)
                <div class="md:col-6 xl:col-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex justify-content-between">
                            <div>
                                <div class="fg-secondary small">{{ $kpi['label'] }}</div>
                                <div class="fs-md fw-bold mt-2">{{ $kpi['value'] }}</div>
                            </div>
                            <i class="fg-primary" data-lucide="{{ $kpi['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="row g-4">
            <div class="xl:col-7">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between bg-white">
                        <h5 class="fw-bold mb-0">{{ __('inventory_dashboard.low_stock_title') }}</h5>
                        <span class="badge theme-warning">{{ $lowStockCount }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table-hover table-striped mb-0 table align-middle">
                            <thead class="bg-1">
                                <tr>
                                    <th>{{ __('inventory_dashboard.product') }}</th>
                                    <th>{{ __('inventory_dashboard.supplier') }}</th>
                                    <th class="text-end">{{ __('inventory_dashboard.available') }}</th>
                                    <th class="text-end">{{ __('inventory_dashboard.reorder_level') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStockProducts as $product)
                                    <tr>
                                        <td>
                                            <a class="fw-semibold"
                                                href="{{ route('inventory.products.show', $product) }}">{{ $product->name }}</a>
                                            <div class="fg-secondary small">{{ $product->sku }}</div>
                                        </td>
                                        <td>{{ $product->supplier?->name ?? '-' }}</td>
                                        <td class="fg-danger fw-bold text-end">
                                            {{ number_format($product->available_quantity, 2) }}
                                        </td>
                                        <td class="text-end">
                                            {{ number_format((float) $product->reorder_level, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="fg-secondary py-4 text-center" colspan="4">
                                            {{ __('inventory_dashboard.no_low_stock') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="xl:col-5">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="fw-bold mb-0">{{ __('inventory_dashboard.recent_movements') }}</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($recentMovements as $movement)
                            <div class="list-group-item px-3">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">{{ $movement->product_name }}</span><span
                                        class="i {{ $movement->direction->value === 'in' ? 'theme-success' : 'theme-danger' }}">{{ $movement->direction->value === 'in' ? '+' : '-' }}{{ number_format((float) $movement->quantity, 2) }}</span>
                                </div>
                                <div class="fg-secondary small">
                                    {{ $movement->document_number }} · {{ $movement->date?->format(Setting::getDefaultDate()) }}
                                </div>
                            </div>
                        @empty
                            <div class="fg-secondary py-4 text-center">{{ __('inventory_dashboard.no_movements') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
