@php use App\Models\System\Setting; @endphp
@inject('dash', App\Services\DashboardFinanceService::class)
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
        $monthTransactionCount = $dash->getCurrentMonthTransactionCount();
        $topSupplierOutstanding = $dash->getTopSupplierOutstanding();
        $topCustomerOutstanding = $dash->getTopCustomerOutstanding();
    @endphp

    <div class="container py-4">
        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold fg-black mb-0">{{ __('dashboard.title') }}</h2>
        </div>

        {{-- KPI Cards --}}
        <div class="row g-3 mb-4">
            <div class="md:col-3">
                <div class="card rounded-9 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="fg-secondary small fw-semibold text-uppercase mb-1">
                            {{ __('dashboard.kpi.revenue_collected') }}
                        </div>
                        <div class="h4 fw-bold mb-0">
                            {{ $currencySymbol }} {{ number_format($summary['revenue_paid'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="card rounded-9 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="fg-secondary small fw-semibold text-uppercase mb-1">
                            {{ __('dashboard.kpi.pending_revenue') }}
                        </div>
                        <div class="h4 fw-bold fg-warning mb-0">
                            {{ $currencySymbol }} {{ number_format($summary['revenue_pending'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="card rounded-9 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="fg-secondary small fw-semibold text-uppercase mb-1">
                            {{ __('dashboard.kpi.total_transactions') }}
                        </div>
                        <div class="h4 fw-bold fg-black mb-0">{{ $summary['total_transactions'] }}</div>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="card rounded-9 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="fg-secondary small fw-semibold text-uppercase mb-1">
                            {{ __('dashboard.kpi.metrics_count') }}
                        </div>
                        <div class="h6 fw-bold fg-black mb-0 mt-2">
                            {{ $summary['total_companies'] }} / {{ $summary['total_contacts'] }} /
                            {{ $summary['total_products'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Monthly Revenue --}}
        <div class="row g-3 mb-4">
            <div class="md:col-12">
                <div class="card rounded-9 h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">{{ __('dashboard.charts.revenue_by_month') }}</h5>
                        <canvas id="chart-revenue-month" height="110"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Current Month Activity List --}}
        <div class="card rounded-9 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">{{ __('dashboard.recent.title', ['month' => now()->format('F Y')]) }}</h5>
                    <span class="badge theme-secondary fg-primary rounded-pill fw-semibold bg-10 px-3 py-2">
                        {{ $monthTransactionCount }} {{ __('dashboard.recent.entries_this_month') }}
                    </span>
                </div>

                <div class="d-flex flex-column gap-3">
                    @forelse ($recentLedgers as $ledger)
                        @php
                            $subtypeVal = $ledger->subtype->value ?? ($ledger->subtype ?? 'sales_invoice');

                            [$iconName, $iconColor, $badgeBg] = match ($subtypeVal) {
                                'receipt' => ['arrow-down-left', 'fg-danger', 'bg-success-subtle'],
                                'payment_voucher' => ['arrow-up-right', 'fg-success', 'bg-danger-subtle'],
                                'sales_invoice' => ['file-text', 'fg-primary', 'bg-subtle-primary'],
                                'purchase_invoice' => ['receipt', 'fg-warning', 'bg-subtle-warning'],
                                default => ['circle', 'fg-secondary', 'bg-2'],
                            };

                            $isPositive = in_array($subtypeVal, ['receipt', 'sales_invoice']);
                            // The sign carries direction; colour stays off the
                            // figure and lives on the badge only (WCAG 1.4.1).
                            $amountPrefix = $isPositive ? '+' : '-';
                        @endphp

                        <div class="bg-2 rounded-9 d-flex align-items-center justify-content-between  border-0 bg-50 p-3">
                            <div class="d-flex align-items-center gap-3">
                                {{-- Icon Box --}}
                                <div class="rounded-circle {{ $badgeBg }} d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px; min-width: 48px">
                                    <i class="{{ $iconColor }} fs-xl" data-lucide="{{ $iconName }}"></i>
                                </div>

                                {{-- Details --}}
                                <div>
                                    <div class="fw-bold fg-black mb-1">
                                        {{ $ledger->company?->name ?? __('dashboard.recent.table.no_title') }}
                                    </div>
                                    <div class="fg-secondary small">
                                        {{ $ledger->company?->name ?? '—' }}
                                        @if ($ledger->contact?->name)
                                            <span class="mx-1">•</span>
                                            {{ $ledger->contact?->name }}
                                        @endif
                                        <span class="badge theme-secondary fg-secondary fw-normal ms-2 bg-10"
                                            style="font-size: 10px">
                                            {{ __(
                                                match ($subtypeVal) {
                                                    'receipt' => 'ledgers.sub_type.receipt',
                                                    'payment_voucher' => 'ledgers.sub_type.payment_voucher',
                                                    'sales_invoice' => 'ledgers.sub_type.sales_invoice',
                                                    'purchase_invoice' => 'ledgers.sub_type.purchase_invoice',
                                                    default => 'ledgers.sub_type.journal',
                                                },
                                            ) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Amount & Date --}}
                            <div class="text-end">
                                <div class="fw-bold fs-2xl">
                                    {{ $amountPrefix }}{{ $currencySymbol }} {{ number_format($ledger->total_amount, 2) }}
                                </div>
                                <div class="badge theme-secondary" style="font-size: 0.75rem">
                                    {{ $ledger->date->format(Setting::getDefaultDate()) }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="fg-secondary py-5 text-center">
                            <i class="fs-sm d-block fg-secondary mb-2 opacity-50" data-lucide="calendar-x"></i>
                            {{ __('dashboard.recent.no_entries_month', ['month' => now()->format('F Y')]) }}
                        </div>
                    @endforelse
                </div>

                {{-- Current Month Total Footer --}}
                @if (count($recentLedgers) > 0)
                    <div class="border-top d-flex justify-content-between align-items-center mt-4 px-2 pt-3">
                        <span
                            class="fg-secondary fw-semibold small text-uppercase">{{ __('dashboard.recent.total_for', ['month' => now()->format('F Y')]) }}</span>
                        <span class="fw-bold fg-black fs-xl">
                            {{ $currencySymbol }} {{ number_format($recentLedgers->sum('total_amount'), 2) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="lg:col-6">
                <div class="card rounded-9 h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">{{ __('dashboard.outstanding.ap_title') }}</h5>
                        @forelse ($topSupplierOutstanding as $invoice)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <div>
                                    <div class="fw-semibold">{{ $invoice->company_name ?: '-' }}</div>
                                    <small class="badge theme-secondary">{{ $invoice->document_number }}</small>
                                </div>
                                <strong>{{ $currencySymbol }} {{ number_format((float) $invoice->balance_amount, 2) }}</strong>
                            </div>
                        @empty
                            <div class="badge theme-secondary">{{ __('dashboard.outstanding.no_ap') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="lg:col-6">
                <div class="card rounded-9 h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">{{ __('dashboard.outstanding.ar_title') }}</h5>
                        @forelse ($topCustomerOutstanding as $invoice)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <div>
                                    <div class="fw-semibold">{{ $invoice->company_name ?: '-' }}</div>
                                    <small class="badge theme-secondary">{{ $invoice->document_number }}</small>
                                </div>
                                <strong>{{ $currencySymbol }} {{ number_format((float) $invoice->balance_amount, 2) }}</strong>
                            </div>
                        @empty
                            <div class="badge theme-secondary">{{ __('dashboard.outstanding.no_ar') }}</div>
                        @endforelse
                    </div>
                </div>
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

            const colors = {
                primary: '#9F1239',
                primaryGradient: 'rgba(159, 18, 57, 0.15)',
                emerald: '#10B981',
                amber: '#F59E0B',
                rose: '#EF4444',
                purple: '#8B5CF6',
                cyan: '#06B6D4',
                indigo: '#6366F1',
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
                        legend: {},
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
