@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Collection;
@endphp

@props([
    /** @var int */
    'companyCount',
    /** @var int */
    'lastMonthCompanyCount',
    /** @var int */
    'contactCount',
    /** @var int */
    'lastMonthContactCount',
    /** @var int */
    'supplierCount',
    /** @var int */
    'lastMonthSupplierCount',
    /** @var int */
    'customerCount',
    /** @var int */
    'lastMonthCustomerCount',
    /** @var int */
    'invoiceCount',
    /** @var int */
    'lastMonthInvoiceCount',
    /** @var float */
    'payableTotal',
    /** @var float */
    'receivableTotal',
    /** @var Collection<int, array{month: string, label: string, inflow: float, outflow: float}> */
    'cashFlow',
    /** @var Carbon */
    'selectedMonth',
    /** @var string|null */
    'userName',
])

@extends('layouts.app')

@section('content')
    @php
        $payableTotal = $payableTotal ?? 0;
        $receivableTotal = $receivableTotal ?? 0;
        $splitTotal = $payableTotal + $receivableTotal;
        $donutCircumference = 99.9;
        $payableArc = $splitTotal > 0 ? round($payableTotal / $splitTotal * $donutCircumference, 2) : 0.0;
        $receivableArc = $splitTotal > 0 ? round($receivableTotal / $splitTotal * $donutCircumference, 2) : 0.0;

        $hour = now()->hour;
        $greeting = $hour < 12 ? __('crm_dashboard.greeting.morning') : ($hour < 18 ? __('crm_dashboard.greeting.afternoon') : __('crm_dashboard.greeting.evening'));
        $displayName = $userName ?? __('crm_dashboard.greeting.friend');

        $cashFlowMax = max(
            $cashFlow->max(fn (array $row) => max($row['inflow'], $row['outflow'])) ?? 0,
            1
        );

        $trendCompany = $companyCount - $lastMonthCompanyCount;
        $trendContact = $contactCount - $lastMonthContactCount;
        $trendSupplier = $supplierCount - $lastMonthSupplierCount;
        $trendCustomer = $customerCount - $lastMonthCustomerCount;
        $trendInvoice = $invoiceCount - $lastMonthInvoiceCount;

        // Build month options for dropdown (last 12 months + next 3)
        $monthOptions = collect();
        for ($i = 11; $i >= -3; $i--) {
            $m = now()->copy()->subMonths($i)->startOfMonth();
            $monthOptions->push([
                'value' => $m->format('Y-m'),
                'label' => $m->translatedFormat('F Y'),
                'selected' => $m->isSameMonth($selectedMonth),
            ]);
        }
    @endphp

    <div class="container crm-page">

        {{-- ============================================================ --}}
        {{-- Greeting card — pink gradient with handwritten tagline       --}}
        {{-- ============================================================ --}}
        <div class="crm-greeting">
            <div class="crm-greeting-copy">
                <h2 class="crm-greeting-title">{{ $greeting }}, {{ $displayName }} <span class="crm-greeting-wave">👋</span></h2>
                <p class="crm-greeting-sub">{{ __('crm_dashboard.greeting.subtitle') }}</p>
                <span class="crm-month-select-wrap">
                    <select class="crm-month-select" id="crmMonthFilter" aria-label="{{ __('crm_dashboard.chart.select_month') }}">
                        <i data-lucide="calendar"></i>
                        @foreach ($monthOptions as $opt)
                            <option value="{{ $opt['value'] }}" {{ $opt['selected'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="crm-month-select-arrow" aria-hidden="true"></i>
                </span>
            </div>
            <div class="crm-greeting-tagline">{{ __('crm_dashboard.greeting.tagline') }}</div>
        </div>

        {{-- ============================================================ --}}
        {{-- KPI stat cards — 4 across with trend indicators              --}}
        {{-- ============================================================ --}}
        <div class="row g-3 mt-4">
            <div class="md:col-3">
                <div class="crm-stat">
                    <span class="crm-stat-icon"><i data-lucide="building-2"></i></span>
                    <div class="crm-stat-body">
                        <div class="crm-stat-label">{{ __('crm_dashboard.kpi.companies') }}</div>
                        <div class="crm-stat-value">{{ number_format($companyCount) }}</div>
                        @if ($trendCompany !== 0)
                            <div class="crm-stat-trend {{ $trendCompany > 0 ? 'is-up' : 'is-down' }}">
                                <i data-lucide="{{ $trendCompany > 0 ? 'trending-up' : 'trending-down' }}"></i>
                                <span>{{ $trendCompany > 0 ? '+' : '' }}{{ $trendCompany }}</span>
                            </div>
                        @else
                            <div class="crm-stat-trend is-neutral">
                                <span>{{ __('crm_dashboard.trend.no_change') }}</span>
                            </div>
                        @endif
                        <a class="crm-stat-link" href="{{ route('crm.companies.index') }}">
                            {{ __('crm_dashboard.actions.view_companies') }} <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="crm-stat">
                    <span class="crm-stat-icon is-blue"><i data-lucide="users"></i></span>
                    <div class="crm-stat-body">
                        <div class="crm-stat-label">{{ __('crm_dashboard.kpi.customers') }}</div>
                        <div class="crm-stat-value">{{ number_format($customerCount) }}</div>
                        @if ($trendCustomer !== 0)
                            <div class="crm-stat-trend {{ $trendCustomer > 0 ? 'is-up' : 'is-down' }}">
                                <i data-lucide="{{ $trendCustomer > 0 ? 'trending-up' : 'trending-down' }}"></i>
                                <span>{{ $trendCustomer > 0 ? '+' : '' }}{{ $trendCustomer }}</span>
                            </div>
                        @else
                            <div class="crm-stat-trend is-neutral">
                                <span>{{ __('crm_dashboard.trend.no_change') }}</span>
                            </div>
                        @endif
                        <a class="crm-stat-link" href="{{ route('crm.contacts.index') }}">
                            {{ __('crm_dashboard.actions.view_contacts') }} <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="crm-stat">
                    <span class="crm-stat-icon is-orange"><i data-lucide="truck"></i></span>
                    <div class="crm-stat-body">
                        <div class="crm-stat-label">{{ __('crm_dashboard.kpi.suppliers') }}</div>
                        <div class="crm-stat-value">{{ number_format($supplierCount) }}</div>
                        @if ($trendSupplier !== 0)
                            <div class="crm-stat-trend {{ $trendSupplier > 0 ? 'is-up' : 'is-down' }}">
                                <i data-lucide="{{ $trendSupplier > 0 ? 'trending-up' : 'trending-down' }}"></i>
                                <span>{{ $trendSupplier > 0 ? '+' : '' }}{{ $trendSupplier }}</span>
                            </div>
                        @else
                            <div class="crm-stat-trend is-neutral">
                                <span>{{ __('crm_dashboard.trend.no_change') }}</span>
                            </div>
                        @endif
                        <a class="crm-stat-link" href="{{ route('crm.companies.index') }}">
                            {{ __('crm_dashboard.actions.view_companies') }} <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="crm-stat">
                    <span class="crm-stat-icon is-green"><i data-lucide="file-text"></i></span>
                    <div class="crm-stat-body">
                        <div class="crm-stat-label">{{ __('crm_dashboard.kpi.invoices') }}</div>
                        <div class="crm-stat-value">{{ number_format($invoiceCount) }}</div>
                        @if ($trendInvoice !== 0)
                            <div class="crm-stat-trend {{ $trendInvoice > 0 ? 'is-up' : 'is-down' }}">
                                <i data-lucide="{{ $trendInvoice > 0 ? 'trending-up' : 'trending-down' }}"></i>
                                <span>{{ $trendInvoice > 0 ? '+' : '' }}{{ $trendInvoice }}</span>
                            </div>
                        @else
                            <div class="crm-stat-trend is-neutral">
                                <span>{{ __('crm_dashboard.trend.no_change') }}</span>
                            </div>
                        @endif
                        <a class="crm-stat-link" href="{{ route('sales.invoices.index') }}">
                            {{ __('crm_dashboard.actions.view_invoices') }} <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- Chart cards — donut + bar chart                              --}}
        {{-- ============================================================ --}}
        <div class="row g-4 mt-1">
            <div class="lg:col-6">
                <div class="crm-chart-card">
                    <div class="crm-chart-head">
                        <h4 class="crm-chart-title">{{ __('crm_dashboard.chart.receivable_vs_payable') }}</h4>
                        <span class="crm-chart-period">{{ $selectedMonth->translatedFormat('M Y') }}</span>
                    </div>
                    <div class="crm-chart-body crm-donut-layout">
                        <div class="crm-donut-wrap">
                            <svg viewBox="0 0 42 42" aria-hidden="true">
                                <circle class="crm-donut-track" cx="21" cy="21" r="15.9" />
                                @if ($splitTotal > 0)
                                    <circle class="crm-donut-seg seg-pay" cx="21" cy="21" r="15.9"
                                        stroke-dasharray="{{ $payableArc }} {{ round($donutCircumference - $payableArc, 2) }}"
                                        stroke-dashoffset="0" />
                                    <circle class="crm-donut-seg seg-rec" cx="21" cy="21" r="15.9"
                                        stroke-dasharray="{{ $receivableArc }} {{ round($donutCircumference - $receivableArc, 2) }}"
                                        stroke-dashoffset="{{ round(-$payableArc, 2) }}" />
                                @endif
                            </svg>
                            <div class="crm-donut-center">
                                <span class="crm-donut-total">{{ $currencySymbol }} {{ number_format($splitTotal, 0) }}</span>
                                <span class="crm-donut-total-label">{{ __('crm_dashboard.chart.total') }}</span>
                            </div>
                        </div>
                        <div class="crm-legend">
                            <span class="crm-legend-row"><i class="crm-dot crm-dot-rec"></i>{{ __('crm_dashboard.kpi.receivable') }}<b>{{ $currencySymbol }} {{ number_format($receivableTotal, 2) }}</b><small>{{ $splitTotal > 0 ? round($receivableTotal / $splitTotal * 100) : 0 }}%</small></span>
                            <span class="crm-legend-row"><i class="crm-dot crm-dot-pay"></i>{{ __('crm_dashboard.kpi.payable') }}<b>{{ $currencySymbol }} {{ number_format($payableTotal, 2) }}</b><small>{{ $splitTotal > 0 ? round($payableTotal / $splitTotal * 100) : 0 }}%</small></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-6">
                <div class="crm-chart-card">
                    <div class="crm-chart-head">
                        <h4 class="crm-chart-title">{{ __('crm_dashboard.chart.cash_flow') }}</h4>
                        <span class="crm-chart-period">{{ __('crm_dashboard.chart.this_year') }}</span>
                    </div>
                    <div class="crm-chart-body crm-bar-layout">
                        <div class="crm-bar-chart">
                            <div class="crm-bar-chart-area">
                                @foreach ($cashFlow as $row)
                                    @php
                                        $inflowPct = $row['inflow'] > 0 ? max(4, round($row['inflow'] / $cashFlowMax * 100)) : 0;
                                        $outflowPct = $row['outflow'] > 0 ? max(4, round($row['outflow'] / $cashFlowMax * 100)) : 0;
                                    @endphp
                                    <div class="crm-bar-group">
                                        <div class="crm-bar-pair">
                                            @if ($row['inflow'] > 0)
                                                <span class="crm-bar crm-bar-in" style="height: {{ $inflowPct }}%"></span>
                                            @endif
                                            @if ($row['outflow'] > 0)
                                                <span class="crm-bar crm-bar-out" style="height: {{ $outflowPct }}%"></span>
                                            @endif
                                        </div>
                                        <span class="crm-bar-label">{{ $row['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="crm-bar-legend">
                            <span class="crm-bar-legend-row"><i class="crm-dot crm-dot-in"></i>{{ __('crm_dashboard.chart.inflow') }}</span>
                            <span class="crm-bar-legend-row"><i class="crm-dot crm-dot-out"></i>{{ __('crm_dashboard.chart.outflow') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- Tip bar                                                      --}}
        {{-- ============================================================ --}}
        <div class="crm-tip-bar mt-4">
            <div class="crm-tip-left">
                <i data-lucide="lightbulb"></i>
                <span>{{ __('crm_dashboard.tip.message') }}</span>
            </div>
            <a class="crm-tip-right" href="{{ route('crm.companies.index') }}">
                {{ __('crm_dashboard.tip.encouragement') }} <i data-lucide="arrow-right"></i>
            </a>
        </div>
    </div>

    <script>
        document.getElementById('crmMonthFilter').addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('month', this.value);
            window.location.href = url.toString();
        });
    </script>

    <style>
        /* =====================================================================
           CRM dashboard — poster-matched layout:
           greeting card, 4 stat cards with trends, 2 chart cards, tip bar.

           Every colour below is a palette token. Light-only literals (#fff
           surfaces, #9f1239 accents) are what made this page unreadable: the ink
           is var(--fg-body), so on a dark palette the numbers were near-white on
           white, and the surfaces hid the wallpaper instead of joining the glass.
           ===================================================================== */

        .crm-page {
            /* The two "deep" accents are text, so they must clear 4.5:1 on every
               palette. --danger-fg-emphasis looks right but is not theme-flipped:
               only cyberpunk/zelda/red_alert author it, the rest inherit a near-black
               ink (rgb(38 42 53)) that vanishes on the dark palettes. Mixing the
               palette's own colour toward its own ink keeps a readable distance at both
               ends, and light-dark() splits the weight because --success-base is a
               bright mint by design — deepening it is right on light, wrong on dark,
               where the base already reads. The palette mixin already declares
               color-scheme, so light-dark() follows the theme with no extra state. */
            --crm-ink: var(--fg-body);
            /* --fg-2 alone is the palette's muted text: 3.56:1 on pastel_fantasy,
               4.24:1 on electric_blast — under AA for the greeting line, the legend
               rows and the tip bar. Pulling it toward the palette's own ink keeps the
               hierarchy (it still reads lighter than --crm-ink) and clears 4.5:1
               everywhere, in both directions, because --fg-body is always the
               highest-contrast colour of the pair. */
            --crm-ink-soft: color-mix(in srgb, var(--fg-2) 45%, var(--fg-body));
            --crm-rose-deep: light-dark(
                color-mix(in srgb, var(--danger-base) 45%, var(--fg-body)),
                color-mix(in srgb, var(--danger-base) 55%, var(--fg-body))
            );
            --crm-rose: var(--danger-base);
            --crm-green-deep: light-dark(
                color-mix(in srgb, var(--success-base) 30%, var(--fg-body)),
                var(--success-base)
            );
            --crm-green: var(--success-base);
            --crm-green-soft: color-mix(in srgb, var(--success-base) 18%, transparent);
            --crm-green-wash: color-mix(in srgb, var(--success-base) 8%, transparent);
            --crm-blue: var(--info-base);
            --crm-blue-soft: color-mix(in srgb, var(--info-base) 18%, transparent);
            --crm-orange: var(--warning-base);
            --crm-orange-soft: color-mix(in srgb, var(--warning-base) 18%, transparent);
            --crm-hairline: var(--border-color);
            --crm-surface: var(--app-glass-card-bg);
            /* Links and micro-labels follow the same rule as the two deep accents:
               a palette's --primary-base is chosen to sit under white or black
               button text, not to be read as text itself. pastel_fantasy's cyan
               #7bdff2 is 1.33:1 on its own card, and --fg-3 drops to 2.14:1 on the
               same palette. Shifting each toward the theme's own ink keeps the hue
               and clears 4.5:1 on all fifteen palettes. */
            --crm-link: light-dark(
                color-mix(in srgb, var(--primary-base) 30%, var(--fg-body)),
                color-mix(in srgb, var(--primary-base) 65%, var(--fg-body))
            );
            --crm-ink-faint: color-mix(in srgb, var(--fg-2) 55%, var(--fg-body));
            --crm-blue-deep: light-dark(color-mix(in srgb, var(--info-base) 40%, var(--fg-body)), var(--info-base));
            --crm-orange-deep: light-dark(color-mix(in srgb, var(--warning-base) 40%, var(--fg-body)), var(--warning-base));
            --crm-card-shadow: 0 1rem 1.9rem -0.9rem color-mix(in srgb, var(--fg-body) 22%, transparent);
        }

        /* ---------------- Greeting card ---------------- */

        .crm-greeting {
            position: relative;
            overflow: hidden;
            border-radius: 22px;
            padding: clamp(1.5rem, 3vw, 2.4rem);
            background: linear-gradient(155deg, var(--bg-body) 0%, var(--primary-bg-subtle) 45%, var(--primary-bg-muted) 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .crm-greeting-copy {
            flex: 1 1 auto;
            min-width: 0;
        }

        .crm-greeting-title {
            margin: 0 0 0.35rem;
            font-size: clamp(1.5rem, 2.6vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--crm-ink);
        }

        .crm-greeting-wave {
            display: inline-block;
            animation: crm-wave 2s ease-in-out infinite;
        }

        @keyframes crm-wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(20deg); }
            75% { transform: rotate(-10deg); }
        }

        .crm-greeting-sub {
            margin: 0 0 1rem;
            color: var(--crm-ink-soft);
            font-size: 0.92rem;
        }

        .crm-greeting-tagline {
            font-family: 'Segoe Script', 'Comic Sans MS', 'Bradley Hand', cursive;
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            color: var(--crm-rose-deep);
            text-align: right;
            line-height: 1.3;
            white-space: nowrap;
        }

        .crm-month-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.95rem;
            border-radius: 999px;
            background: var(--crm-surface);
            box-shadow: 0 0.35rem 0.9rem -0.4rem color-mix(in srgb, var(--danger-base) 25%, transparent);
            color: var(--crm-rose-deep);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .crm-month-chip svg {
            width: 15px;
            height: 15px;
        }

        /* The select cannot be given a theme-aware arrow through background-image:
           a data URI is a string, so its stroke would have to be another literal.
           The chevron is an element next to it instead and inherits the ink. */
        .crm-month-select-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .crm-month-select-arrow,
        .crm-month-select-wrap > svg {
            position: absolute;
            right: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            color: var(--crm-rose-deep);
            pointer-events: none;
        }

        .crm-month-select {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 2rem 0.45rem 0.95rem;
            border-radius: 999px;
            background: var(--crm-surface);
            box-shadow: 0 0.35rem 0.9rem -0.4rem color-mix(in srgb, var(--danger-base) 25%, transparent);
            color: var(--crm-rose-deep);
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
        }

        .crm-month-select:hover {
            box-shadow: 0 0.35rem 1.2rem -0.3rem color-mix(in srgb, var(--danger-base) 35%, transparent);
        }

        .crm-month-select:focus {
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--danger-base) 25%, transparent), 0 0.35rem 0.9rem -0.4rem color-mix(in srgb, var(--danger-base) 25%, transparent);
        }

        /* ---------------- KPI stat cards ---------------- */

        .crm-stat {
            display: flex;
            gap: 0.9rem;
            align-items: flex-start;
            background: var(--crm-surface);
            -webkit-backdrop-filter: blur(var(--app-glass-blur)) saturate(var(--app-glass-saturate));
            backdrop-filter: blur(var(--app-glass-blur)) saturate(var(--app-glass-saturate));
            border-radius: 16px;
            box-shadow: var(--crm-card-shadow);
            padding: 1.15rem 1.25rem;
            height: 100%;
        }

        .crm-stat-icon {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border-radius: 13px;
            background: var(--primary-bg-subtle);
            color: var(--crm-link);
            flex: 0 0 auto;
        }

        .crm-stat-icon.is-blue {
            background: var(--crm-blue-soft);
            color: var(--crm-blue-deep);
        }

        .crm-stat-icon.is-orange {
            background: var(--crm-orange-soft);
            color: var(--crm-orange-deep);
        }

        .crm-stat-icon.is-green {
            background: var(--crm-green-soft);
            color: var(--crm-green-deep);
        }

        .crm-stat-icon svg {
            width: 21px;
            height: 21px;
        }

        .crm-stat-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1 1 auto;
        }

        .crm-stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--crm-ink-faint);
        }

        .crm-stat-value {
            margin-top: 0.1rem;
            font-size: 1.45rem;
            font-weight: 700;
            line-height: 1.15;
            color: var(--crm-ink);
            font-variant-numeric: tabular-nums;
        }

        .crm-stat-trend {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            margin-top: 0.2rem;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .crm-stat-trend svg {
            width: 13px;
            height: 13px;
        }

        .crm-stat-trend.is-up {
            color: var(--crm-green-deep);
        }

        .crm-stat-trend.is-down {
            color: var(--crm-rose-deep);
        }

        .crm-stat-trend.is-neutral {
            color: var(--crm-ink-faint);
        }

        .crm-stat-link {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: auto;
            padding-top: 0.4rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--crm-link);
            text-decoration: none;
        }

        .crm-stat-link:hover {
            color: var(--crm-rose-deep);
            text-decoration: underline;
        }

        .crm-stat-link svg {
            width: 13px;
            height: 13px;
        }

        /* ---------------- Chart cards ---------------- */

        .crm-chart-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: var(--crm-surface);
            -webkit-backdrop-filter: blur(var(--app-glass-blur)) saturate(var(--app-glass-saturate));
            backdrop-filter: blur(var(--app-glass-blur)) saturate(var(--app-glass-saturate));
            border-radius: 18px;
            box-shadow: var(--crm-card-shadow);
            overflow: hidden;
        }

        .crm-chart-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.2rem 1.4rem 0.8rem;
        }

        .crm-chart-title {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--crm-ink);
        }

        .crm-chart-period {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--crm-ink-faint);
            background: var(--primary-bg-subtle);
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
        }

        .crm-chart-body {
            flex: 1 1 auto;
            padding: 0.5rem 1.4rem 1.2rem;
        }

        /* Donut layout */
        .crm-donut-layout {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .crm-donut-wrap {
            position: relative;
            width: 130px;
            height: 130px;
            flex: 0 0 auto;
        }

        .crm-donut-wrap svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .crm-donut-track,
        .crm-donut-seg {
            fill: none;
            stroke-width: 4.2;
        }

        .crm-donut-track {
            stroke: var(--primary-bg-subtle);
        }

        .crm-donut-seg.seg-pay {
            stroke: var(--crm-rose-deep);
        }

        .crm-donut-seg.seg-rec {
            stroke: var(--crm-green);
        }

        .crm-donut-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .crm-donut-total {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--crm-ink);
            font-variant-numeric: tabular-nums;
        }

        .crm-donut-total-label {
            font-size: 0.65rem;
            color: var(--crm-ink-faint);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .crm-legend {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            flex: 1 1 auto;
        }

        .crm-legend-row {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.82rem;
            color: var(--crm-ink-soft);
            white-space: nowrap;
        }

        .crm-legend-row b {
            margin-left: auto;
            padding-left: 0.75rem;
            color: var(--crm-ink);
            font-variant-numeric: tabular-nums;
            font-weight: 700;
        }

        .crm-legend-row small {
            padding-left: 0.4rem;
            color: var(--crm-ink-faint);
            font-weight: 600;
        }

        .crm-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex: 0 0 auto;
        }

        .crm-dot-pay {
            background: var(--crm-rose-deep);
        }

        .crm-dot-rec {
            background: var(--crm-green);
        }

        .crm-dot-in {
            background: var(--crm-green);
        }

        .crm-dot-out {
            background: var(--crm-rose);
        }

        /* Bar chart layout */
        .crm-bar-layout {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .crm-bar-chart {
            flex: 1 1 auto;
        }

        .crm-bar-chart-area {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 160px;
            padding-top: 0.5rem;
        }

        .crm-bar-group {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
            min-width: 0;
        }

        .crm-bar-pair {
            display: flex;
            align-items: flex-end;
            gap: 3px;
            height: 140px;
            width: 100%;
            justify-content: center;
        }

        .crm-bar {
            display: block;
            width: 45%;
            max-width: 18px;
            height: var(--h, 0%);
            border-radius: 4px 4px 2px 2px;
            min-height: 0;
            transition: height 0.3s ease;
        }

        .crm-bar-in {
            background: linear-gradient(180deg, var(--crm-green), color-mix(in srgb, var(--success-base) 55%, var(--bg-1)));
        }

        .crm-bar-out {
            background: linear-gradient(180deg, var(--crm-rose), color-mix(in srgb, var(--danger-base) 55%, var(--bg-1)));
        }

        .crm-bar-label {
            font-size: 0.62rem;
            font-weight: 600;
            color: var(--crm-ink-faint);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .crm-bar-legend {
            display: flex;
            gap: 1.2rem;
            justify-content: center;
        }

        .crm-bar-legend-row {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            color: var(--crm-ink-soft);
        }

        /* ---------------- Tip bar ---------------- */

        .crm-tip-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: var(--crm-surface);
            -webkit-backdrop-filter: blur(var(--app-glass-blur)) saturate(var(--app-glass-saturate));
            backdrop-filter: blur(var(--app-glass-blur)) saturate(var(--app-glass-saturate));
            border-radius: 16px;
            box-shadow: var(--crm-card-shadow);
            padding: 1rem 1.4rem;
            flex-wrap: wrap;
        }

        .crm-tip-left {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.88rem;
            color: var(--crm-ink-soft);
        }

        .crm-tip-left svg {
            width: 20px;
            height: 20px;
            color: var(--crm-orange-deep);
            flex: 0 0 auto;
        }

        .crm-tip-right {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--crm-link);
            text-decoration: none;
        }

        .crm-tip-right:hover {
            color: var(--crm-rose-deep);
        }

        .crm-tip-right svg {
            width: 15px;
            height: 15px;
        }

        /* ---------------- Responsive ---------------- */

        @media (max-width: 767.98px) {
            .crm-greeting {
                flex-direction: column;
                align-items: flex-start;
            }

            .crm-greeting-tagline {
                text-align: left;
                white-space: normal;
            }

            .crm-donut-layout {
                flex-direction: column;
                align-items: center;
            }

            .crm-bar-chart-area {
                height: 120px;
            }

            .crm-bar-pair {
                height: 100px;
            }
        }
    </style>
@endsection
