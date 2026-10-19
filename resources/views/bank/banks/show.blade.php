@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::BANKS, FileAction::UPDATE);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-5"
                    style="width:40px;height:40px;background:color-mix(in oklch, var(--primary-base) 10%, transparent)">
                    <i class="fs-lg" data-lucide="building-2" style="color:var(--primary-base)"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0">{{ $bank->name }}</h4>
                    <span class="fg-secondary small">{{ $bank->account_name }} | {{ $bank->account_number }}</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                @if ($canUpdate)
                    <a class="btn btn-solid theme-warning btn-sm" href="{{ route('bank.banks.edit', $bank) }}"><i
                            class="me-1" data-lucide="square-pen"></i>{{ __('common.actions.edit') }}</a>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.banks.index') }}"><i class="me-1"
                        data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <h6 class="fw-bold mb-3">{{ __('banks.details_title') }}</h6>
            @php
                $details = [
                    __('banks.fields.chart') => ($bank->chart?->code ?? '-') . ' - ' . ($bank->chart?->name ?? '-'),
                    __('banks.fields.account_type') => $bank->account_type,
                    __('banks.fields.branch_name') => $bank->branch_name,
                    __('banks.fields.bank_code') => $bank->bank_code,
                    __('banks.fields.swift_code') => $bank->swift_code,
                    __('banks.fields.iban') => $bank->iban,
                    __('banks.fields.routing_number') => $bank->routing_number,
                    __('banks.fields.country_code') => $bank->country_code,
                    __('banks.fields.email') => $bank->email,
                    __('banks.fields.phone') => $bank->phone,
                    __('banks.fields.website') => $bank->website,
                    __('banks.fields.address') => $bank->address,
                    __('banks.fields.notes') => $bank->notes,
                ];
            @endphp
            <div class="row g-3">
                @foreach ($details as $label => $value)
                    <div class="md:col-6">
                        <div class="rounded-5 h-100 border p-3">
                            <div class="fg-secondary small">{{ $label }}</div>
                            <div class="fw-bold">{{ $value ?: '-' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <hr class="my-4" />
            <div class="d-flex align-items-end justify-content-between mb-3 flex-wrap gap-3">
                <div>
                    <h6 class="fw-bold mb-1">{{ __('banks.statistics_title') }}</h6>
                    <p class="fg-secondary small mb-0">{{ __('banks.statistics_subtitle') }}</p>
                </div>
                <form autocomplete="off" class="d-flex align-items-end gap-2" method="GET" action="{{ route('bank.banks.show', $bank) }}">
                    <div>
                        <label class="form-label small fw-bold mb-1"
                            for="year">{{ __('banks.statistics.year') }}</label>
                        <input class="form-control" id="year" name="year" type="number" min="2000"
                            max="2100" value="{{ $selectedYear }}" required />
                    </div>
                    <button class="btn btn-solid theme-secondary" type="submit">
                        <i class="me-1" data-lucide="list-filter"></i>{{ __('common.actions.filter') }}
                    </button>
                </form>
            </div>

            <div class="row g-3 mb-3">
                @foreach ([['label' => __('banks.statistics.opening_balance'), 'value' => $openingBalance], ['label' => __('banks.statistics.money_in'), 'value' => $yearTotals['debit']], ['label' => __('banks.statistics.money_out'), 'value' => $yearTotals['credit']], ['label' => __('banks.statistics.closing_balance'), 'value' => $yearTotals['closing_balance']]] as $summary)
                    <div class="sm:col-6 xl:col-3">
                        <div class="card bg-2 h-100">
                            <div class="card-body"
                               >
                                <div class="fg-secondary small text-uppercase fw-bold">{{ $summary['label'] }}</div>
                                <div class="fs-xl fw-bold">
                                    {{ number_format((float) $summary['value'], 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('banks.statistics.month') }}</th>
                            <th class="text-center">{{ __('banks.statistics.transactions') }}</th>
                            <th class="text-end">{{ __('banks.statistics.money_in') }}</th>
                            <th class="text-end">{{ __('banks.statistics.money_out') }}</th>
                            <th class="text-end">{{ __('banks.statistics.net_movement') }}</th>
                            <th class="text-end">{{ __('banks.statistics.closing_balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthlyStatistics as $statistic)
                            <tr>
                                <td>
                                    @if ($bank->chart?->code)
                                        <a class="fw-semibold text-decoration-none"
                                            href="{{ route('finance.ledgers.index', ['account' => $bank->chart->code, 'start_date' => $statistic['start_date'], 'end_date' => $statistic['end_date'], 'posted_only' => 1]) }}"
                                            title="{{ __('banks.statistics.view_ledger') }}">
                                            {{ $statistic['month'] }}
                                            <i class="small ms-1" data-lucide="external-link"></i>
                                        </a>
                                    @else
                                        {{ $statistic['month'] }}
                                    @endif
                                </td>
                                {{-- Debit/credit are movement direction, not gain/loss, so they stay
                                     uncoloured. Net movement keeps its sign
                                     because colour used to be its only cue. --}}
                                <td class="text-center">{{ $statistic['transaction_count'] }}</td>
                                <td class="text-end">
                                    {{ number_format((float) $statistic['debit'], 2) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format((float) $statistic['credit'], 2) }}
                                </td>
                                <td class="text-end">
                                    <x-money :value="$statistic['net_movement']" />
                                </td>
                                <td class="fw-bold text-end">
                                    {{ number_format((float) $statistic['closing_balance'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-1 fw-bold">
                        <tr>
                            <td>{{ __('banks.statistics.total') }} {{ $selectedYear }}</td>
                            <td class="text-center">{{ $yearTotals['transaction_count'] }}</td>
                            <td class="text-end">{{ number_format((float) $yearTotals['debit'], 2) }}</td>
                            <td class="text-end">{{ number_format((float) $yearTotals['credit'], 2) }}</td>
                            <td class="text-end">{{ number_format((float) $yearTotals['net_movement'], 2) }}</td>
                            <td class="text-end">{{ number_format((float) $yearTotals['closing_balance'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
