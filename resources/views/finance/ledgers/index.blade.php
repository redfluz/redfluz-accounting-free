@php
    use App\Models\System\Setting;
    use App\Enums\Finance\LedgerType;
    use App\Models\Finance\Ledger;

    $totalDebit = $totalDebit ?? $ledgers->sum('debit_amount');
    $totalCredit = $totalCredit ?? $ledgers->sum('credit_amount');
    $totalBalance = $totalBalance ?? bcsub((string) $totalDebit, (string) $totalCredit, 2);
@endphp

@props([
    /** @var Ledger */
    'ledgers',
    'search' => '',
    'startDate' => '',
    'endDate' => '',
    'account' => '',
    'sourceUrls' => [],
])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('ledgers.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('ledgers.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="accordion mb-3 accordion-gap">
                <details class="accordion-item">
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="list-filter"></i>
                        <span class="fw-bold">{{ __('ledgers.filters.title') }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                        <form autocomplete="off" class="row g-2" method="GET"
                            action="{{ route('finance.ledgers.index') }}">
                            @foreach (['account', 'posted_only', 'cash_only', 'exclude_year_end_closing'] as $filter)
                                @if (filled(request($filter)))
                                    <input name="{{ $filter }}" type="hidden" value="{{ request($filter) }}" />
                                @endif
                            @endforeach
                            <input name="sort_by" type="hidden" value="{{ request('sort_by') }}" />
                            <input name="order" type="hidden" value="{{ request('order') }}" />
                            <div class="md:col-3">
                                <input class="form-control" name="search" type="text" value="{{ $search }}"
                                    placeholder="{{ __('common.actions.search') }}" aria-label="{{ __('common.actions.search') }}" />
                            </div>
                            <div class="md:col-2">
                                <select class="form-control" name="type" aria-label="{{ __('ledgers.all_types') }}">
                                    <option value="">{{ __('ledgers.all_types') }}</option>
                                    @foreach (LedgerType::cases() as $ledgerType)
                                        <option value="{{ $ledgerType->value }}" @selected(request('type') === $ledgerType->value)>
                                            {{ __('ledgers.type.' . $ledgerType->value) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-2">
                                <div class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true">
                                        <i data-lucide="calendar"></i>
                                    </div>
                                    <input class="form-ghost" name="start_date" type="text" data-bs-toggle="datepicker"
                                        autocomplete="off" value="{{ $startDate }}"
                                        placeholder="{{ __('financial_reports.form.start_date') }}" />
                                </div>
                            </div>
                            <div class="md:col-2">
                                <div class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true">
                                        <i data-lucide="calendar"></i>
                                    </div>
                                    <input class="form-ghost" name="end_date" type="text" data-bs-toggle="datepicker"
                                        autocomplete="off" value="{{ $endDate }}"
                                        placeholder="{{ __('financial_reports.form.end_date') }}" />
                                </div>
                            </div>
                            <div class="md:col-3 d-flex gap-1">
                                <button class="btn btn-solid theme-secondary flex-grow-1" type="submit">
                                    <i class="me-1" data-lucide="search"></i> {{ __('common.actions.filter') }}
                                </button>
                                @if ($search || $startDate || $endDate || request('type') || request('sort_by'))
                                    <a class="btn btn-solid theme-secondary" href="{{ route('finance.ledgers.index') }}"
                                        title="{{ __('common.actions.reset') }}">
                                        <i class="" data-lucide="x"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                </details>

                @if ($account !== '')
                    <div class="alert d-flex align-items-center justify-content-between small border py-2">
                        <span>
                            <i class="fg-primary me-1" data-lucide="list-filter"></i>
                            {{ __('ledgers.filters.account') }}: <strong>{{ $account }}</strong>
                        </span>
                        <a class="text-decoration-none" href="{{ route('finance.ledgers.index') }}">
                            {{ __('common.actions.reset') }}
                        </a>
                    </div>
                @endif

                <div class="accordion mb-3 accordion-gap">
                    <details class="accordion-item">
                        <summary class="accordion-header">
                            <i class="me-2" data-lucide="bar-chart-3"></i>
                            <span class="fw-bold">Summary</span>
                            <i class="accordion-icon" data-lucide="chevron-down"></i>
                        </summary>
                        <div class="accordion-body py-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-4 gap-lg-5">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-uppercase small fg-secondary">IN</span>
                                        <span class="fs-5 fw-semibold fg-success">{{ number_format((float) $totalDebit, 2) }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-uppercase small fg-secondary">OUT</span>
                                        <span class="fs-5 fw-semibold fg-danger">{{ number_format((float) $totalCredit, 2) }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-uppercase small fg-secondary">BALANCE</span>
                                        <span class="fs-5 fw-semibold">{{ number_format((float) $totalBalance, 2) }}</span>
                                    </div>
                                </div>
                                <div id="ledgers-summary-spark" style="width: 140px; height: 120px;"></div>
                            </div>
                        </div>
                    </details>
                </div>

                <div class="table-responsive rounded-5 overflow-hidden border">
                    <table class="table-hover table-striped mb-0 table align-middle">
                        <thead class="bg-1">
                            <tr>
                                @foreach ([
            'document_number' => 'ledgers.table.column_document_number',
            'date' => 'ledgers.table.column_date',
            'type' => 'ledgers.table.column_type',
        ] as $column => $label)
                                    <th scope="col">
                                        <a class="fg-black text-decoration-none"
                                            href="{{ request()->fullUrlWithQuery(['sort_by' => $column, 'order' => request('sort_by') === $column && request('order') === 'asc' ? 'desc' : 'asc']) }}">
                                            {{ __($label) }}
                                            @if (request('sort_by') === $column)
                                                <i class="ms-1"
                                                    data-lucide="{{ request('order') === 'asc' ? 'arrow-up' : 'arrow-down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                @endforeach
                                <th scope="col">{{ __('ledgers.table.column_company') }}</th>
                                <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                                @foreach (['debit_amount', 'credit_amount'] as $column)
                                    <th scope="col" class="text-end">
                                        <a class="fg-black text-decoration-none"
                                            href="{{ request()->fullUrlWithQuery(['sort_by' => $column, 'order' => request('sort_by') === $column && request('order') === 'asc' ? 'desc' : 'asc']) }}">
                                            {{ __('ledgers.table.column_' . $column) }}
                                            @if (request('sort_by') === $column)
                                                <i class="ms-1"
                                                    data-lucide="{{ request('order') === 'asc' ? 'arrow-up' : 'arrow-down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ledgers as $ledger)
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            @if (isset($sourceUrls[(string) $ledger->id]))
                                                <a href="{{ $sourceUrls[(string) $ledger->id] }}"
                                                    title="{{ __('ledgers.actions.view_source') }}">
                                                    {{ $ledger->document_number }}
                                                    <i class="small ms-1" data-lucide="external-link"></i>
                                                </a>
                                            @else
                                                {{ $ledger->document_number }}
                                            @endif
                                            <br />
                                            @if (filled($ledger->reference_number) && $ledger->reference_number !== $ledger->document_number)
                                                <small class="badge theme-secondary">
                                                    {{ __('ledgers.table.column_reference_number') }}:
                                                    {{ $ledger->reference_number }}
                                                </small>
                                                <br />
                                            @endif
                                            <small class="badge theme-secondary">
                                                {{ $ledger->chart_code ?? '' }} {{ $ledger->chart_name ?? '' }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>{{ $ledger->date->format(Setting::getDefaultDate()) ?? '-' }}</td>
                                    <td>
                                        @php($type = $ledger->type->value ?? $ledger->type)
                                        <span
                                            class="badge {{ $type === 'invoice' ? 'theme-info' : ($type === 'payment' ? 'theme-success' : 'theme-secondary') }}">
                                            {{ __('ledgers.type.' . $type) }} </span><br />
                                        <small
                                            class="badge theme-secondary">{{ __('ledgers.sub_type.' . ($ledger->subtype->value ?? $ledger->subtype)) }}</small>
                                    </td>
                                    <td>
                                        @php($companyName = $ledger->company_name ?? ($ledger->company->name ?? null))
                                        <div class="fw-bold">{{ $companyName ?? '-' }}</div>
                                        <small class="badge theme-secondary">{{ $ledger->contact->name ?? '' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $ledger->description ?? '-' }}</small>
                                    </td>
                                    <td class="fw-bold fg-success text-end">
                                        {{ number_format((float) $ledger->debit_amount, 2) }}
                                    </td>
                                    <td class="fw-bold fg-danger text-end">
                                        {{ number_format((float) $ledger->credit_amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="fg-secondary py-4 text-center" colspan="7">
                                        {{ __('common.labels.not_found_record') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="fw-bold text-end" colspan="5">{{ __('common.labels.total') }}:</td>
                                <td class="fw-bold text-end">{{ number_format($totalDebit, 2) }}</td>
                                <td class="fw-bold text-end">{{ number_format($totalCredit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">{{ $ledgers->links() }}</div>
            </div>
        </div>
    @endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof echarts === 'undefined') return;
            const el = document.getElementById('ledgers-summary-spark');
            if (!el) return;
            const inVal = {{ (float) $totalDebit }};
            const outVal = {{ (float) $totalCredit }};
            const balVal = {{ (float) $totalBalance }};
            const balLabel = balVal >= 0 ? 'BALANCE' : 'BALANCE (-)';
            const chart = echarts.init(el);
            const grad = (c1, c2) => new echarts.graphic.LinearGradient(0, 0, 0, 1, [{ offset: 0, color: c1 }, { offset: 1, color: c2 }]);
            const data = [];
            if (Number(inVal) > 0) data.push({ value: inVal, name: 'IN', itemStyle: { color: grad('#34d399', '#059669') } });
            if (Number(outVal) > 0) data.push({ value: outVal, name: 'OUT', itemStyle: { color: grad('#fb7185', '#e11d48') } });
            if (Number(balVal) !== 0) data.push({ value: Math.abs(balVal), name: balLabel, itemStyle: { color: balVal >= 0 ? grad('#60a5fa', '#2563eb') : grad('#fbbf24', '#ea580c') } });
            if (data.length === 0) data.push({ value: 1, name: 'NO DATA', itemStyle: { color: grad('#e9ecef', '#dee2e6') }, tooltip: { show: false } });
            chart.setOption({
                animation: true,
                tooltip: { trigger: 'item', formatter: (p) => p.name === 'NO DATA' ? '' : `${p.name}: ${p.value}` },
                series: [{
                    type: 'pie',
                    roseType: data.length === 1 && data[0].name === 'NO DATA' ? false : 'area',
                    radius: ['16%', '92%'],
                    center: ['50%', '50%'],
                    avoidLabelOverlap: false,
                    itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2, shadowBlur: 12, shadowColor: 'rgba(0,0,0,0.15)' },
                    emphasis: { scale: true, scaleSize: 6, itemStyle: { shadowBlur: 20, shadowColor: 'rgba(0,0,0,0.25)' } },
                    label: { show: false },
                    labelLine: { show: false },
                    data
                }]
            });
            window.addEventListener('resize', () => chart.resize());
        });
    </script>
@endpush
