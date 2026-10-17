@php use App\Models\System\Setting; @endphp
@inject('dash', App\Services\DashboardService::class)
@extends('layouts.app')

@section('content')
    @php
        $summary = $dash->getSummary();
        $revenueByMonth = $dash->getRevenueByMonth();
        $revenueByStatus = $dash->getRevenueByStatus();
        $revenueByType = $dash->getRevenueByTransactionType();
        $topProducts = $dash->getTopProducts();
        $revenueByCompany = $dash->getRevenueByCompany();
        $recentLedgers = $dash->getRecentLedgers();
    @endphp

    <div class="container py-4">
        <h2 class="mb-4">{{ __('dashboard.title') }}</h2>

        {{-- KPI Cards --}}
        <div class="row mb-4">
            <div class="md:col-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="fg-secondary small">{{ __('dashboard.kpi.revenue_collected') }}</div>
                        <div class="h4">{{ $currencySymbol }} {{ number_format($summary['revenue_paid'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="fg-secondary small">{{ __('dashboard.kpi.pending_revenue') }}</div>
                        <div class="h4">{{ $currencySymbol }} {{ number_format($summary['revenue_pending'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="fg-secondary small">{{ __('dashboard.kpi.total_transactions') }}</div>
                        <div class="h4">{{ $summary['total_transactions'] }}</div>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="fg-secondary small">{{ __('dashboard.kpi.metrics_count') }}</div>
                        <div class="h4">
                            {{ $summary['total_companies'] }} / {{ $summary['total_contacts'] }} /
                            {{ $summary['total_products'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row 1 --}}
        <div class="row mb-4">
            <div class="md:col-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>{{ __('dashboard.charts.revenue_by_month') }}</h5>
                        <canvas id="chart-revenue-month" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="md:col-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>{{ __('dashboard.charts.ledger_status_split') }}</h5>
                        <canvas id="chart-revenue-status" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row 2 --}}
        <div class="row mb-4">
            <div class="md:col-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>{{ __('dashboard.charts.revenue_by_type') }}</h5>
                        <canvas id="chart-revenue-type" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="md:col-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>{{ __('dashboard.charts.top_products') }}</h5>
                        <canvas id="chart-top-products" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="md:col-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>{{ __('dashboard.charts.revenue_by_company') }}</h5>
                        <canvas id="chart-revenue-company" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activity Table --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>{{ __('dashboard.recent.title') }}</h5>
                <table class="table-sm table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('dashboard.recent.table.date') }}</th>
                            <th>{{ __('dashboard.recent.table.company') }}</th>
                            <th>{{ __('dashboard.recent.table.product') }}</th>
                            <th>{{ __('dashboard.recent.table.contact') }}</th>
                            <th>{{ __('dashboard.recent.table.type') }}</th>
                            <th class="text-end">{{ __('dashboard.recent.table.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLedgers as $ledger)
                            @php
                                $subtypeVal = $ledger->subtype->value ?? ($ledger->type->value ?? '—');
                                $statusVal = $ledger->status->value ?? 'pending';

                                $statusBadgeClass = match ($statusVal) {
                                    'paid', 'posted' => 'theme-success',
                                    'pending' => 'theme-warning',
                                    'cancelled' => 'theme-danger',
                                    default => 'theme-secondary',
                                };
                            @endphp
                            <tr>
                                <td>{{ $ledger->date->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $ledger->company->name ?? '—' }}</td>
                                <td>{{ $ledger->product->name ?? '—' }}</td>
                                <td>{{ $ledger->contact->name ?? '—' }}</td>
                                <td>
                                    <span class="badge theme-secondary">
                                        {{ __(
                                            match ($ledger->subtype->value ?? $ledger->subtype) {
                                                'sales_invoice' => 'ledgers.sub_type.sales_invoice',
                                                'purchase_invoice' => 'ledgers.sub_type.purchase_invoice',
                                                'receipt' => 'ledgers.sub_type.receipt',
                                                'payment_voucher' => 'ledgers.sub_type.payment_voucher',
                                                'adjustment' => 'ledgers.sub_type.adjustment',
                                                'increase_adjustment' => 'ledgers.sub_type.increase_adjustment',
                                                'decrease_adjustment' => 'ledgers.sub_type.decrease_adjustment',
                                                'refund' => 'ledgers.sub_type.refund',
                                                'transfer' => 'ledgers.sub_type.transfer',
                                                'withdrawal' => 'ledgers.sub_type.withdrawal',
                                                'journal' => 'ledgers.sub_type.journal',
                                            },
                                        ) }}
                                    </span>
                                </td>
                                <td class="fw-bold text-end">{{ $currencySymbol }} {{ number_format($ledger->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-3 text-center" colspan="7">
                                    {{ __('dashboard.recent.no_entries') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const revenueByMonth = @json($revenueByMonth);
            const revenueByStatus = @json($revenueByStatus);
            const revenueByType = @json($revenueByType);
            const topProducts = @json($topProducts);
            const revenueByCompany = @json($revenueByCompany);
            const chartLabelRevenue = "{{ __('dashboard.charts.revenue') }}";

            // Modern vibrant palette
            const colors = {
                primary: '#9F1239', // Brand crimson
                primaryGradient: 'rgba(159, 18, 57, 0.15)',
                emerald: '#10B981', // Vibrant Mint / Paid
                amber: '#F59E0B', // Vibrant Amber / Pending
                rose: '#EF4444', // Vibrant Red / Cancelled
                purple: '#8B5CF6', // Vivid Violet
                cyan: '#06B6D4', // Bright Cyan
                indigo: '#6366F1', // Bright Indigo
            };

            // 1. Revenue by Month (Line Chart)
            new Chart(document.getElementById('chart-revenue-month'), {
                type: 'line',
                data: {
                    labels: revenueByMonth.labels,
                    datasets: [{
                        label: chartLabelRevenue,
                        data: revenueByMonth.data,
                        borderColor: colors.primary,
                        backgroundColor: colors.primaryGradient,
                        borderWidth: 2.5,
                        pointBackgroundColor: colors.primary,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.35,
                    }, ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        y: {
                            grid: {
                                color: '#F1F5F9',
                            },
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                        },
                    },
                },
            });

            // 2. Ledger Status Split (Doughnut)
            new Chart(document.getElementById('chart-revenue-status'), {
                type: 'doughnut',
                data: {
                    labels: revenueByStatus.labels,
                    datasets: [{
                        data: revenueByStatus.totals,
                        backgroundColor: [colors.emerald, colors.amber, colors.rose],
                        borderWidth: 2,
                        borderColor: '#FFFFFF',
                    }, ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                        },
                    },
                },
            });

            // 3. Revenue by Subtype (Vertical Bar)
            new Chart(document.getElementById('chart-revenue-type'), {
                type: 'bar',
                data: {
                    labels: revenueByType.labels,
                    datasets: [{
                        label: chartLabelRevenue,
                        data: revenueByType.data,
                        backgroundColor: colors.indigo,
                        borderRadius: 6,
                    }, ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        y: {
                            grid: {
                                color: '#F1F5F9',
                            },
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                        },
                    },
                },
            });

            // 4. Top Products (Horizontal Bar)
            new Chart(document.getElementById('chart-top-products'), {
                type: 'bar',
                data: {
                    labels: topProducts.labels,
                    datasets: [{
                        label: chartLabelRevenue,
                        data: topProducts.revenue,
                        backgroundColor: colors.amber,
                        borderRadius: 6,
                    }, ],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                color: '#F1F5F9',
                            },
                        },
                        y: {
                            grid: {
                                display: false,
                            },
                        },
                    },
                },
            });

            // 5. Revenue by Company (Vertical Bar)
            new Chart(document.getElementById('chart-revenue-company'), {
                type: 'bar',
                data: {
                    labels: revenueByCompany.labels,
                    datasets: [{
                        label: chartLabelRevenue,
                        data: revenueByCompany.revenue,
                        backgroundColor: colors.cyan,
                        borderRadius: 6,
                    }, ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        y: {
                            grid: {
                                color: '#F1F5F9',
                            },
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                        },
                    },
                },
            });
        });
    </script>
@endsection
