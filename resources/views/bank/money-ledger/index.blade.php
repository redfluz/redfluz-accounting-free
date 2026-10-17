@php
    use App\Models\System\Setting;

    $titles = [
        'cash' => __('money_ledgers.cash.title'),
        'bank' => __('money_ledgers.bank.title'),
        'e-wallet' => __('money_ledgers.e_wallet.title'),
    ];
    $subtitles = [
        'cash' => __('money_ledgers.cash.subtitle'),
        'bank' => __('money_ledgers.bank.subtitle'),
        'e-wallet' => __('money_ledgers.e_wallet.subtitle'),
    ];
    $routes = [
        'cash' => 'bank.cash-ledger.index',
        'bank' => 'bank.bank-ledger.index',
        'e-wallet' => 'bank.e-wallet-ledger.index',
    ];

    $totalDebit = $rows->sum('debit_amount');
    $totalCredit = $rows->sum('credit_amount');
@endphp

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $titles[$ledger] }}</h4>
                <p class="fg-secondary small mb-0">{{ $subtitles[$ledger] }}</p>
            </div>
            <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.banks.index') }}">
                <i class="me-1" data-lucide="landmark"></i>{{ __('money_ledgers.actions.manage_accounts') }}
            </a>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            {{-- What each account holds, so a drawer is read before its movements are. --}}
            @if ($balances->isNotEmpty())
                <div class="row g-3 mb-4">
                    @foreach ($balances as $balance)
                        <div class="md:col-4">
                            <div class="card h-100 border shadow-none {{ $balance->isFiltered ? 'theme-primary' : '' }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="fw-bold">{{ $balance->account->name }}</span>
                                        @if ($balance->account->is_default)
                                            <span class="badge theme-success">{{ __('money_ledgers.default') }}</span>
                                        @endif
                                    </div>
                                    <div class="h4 mb-1">
                                        {{ number_format((float) $balance->balance, 2) }}
                                    </div>
                                    <div class="fg-secondary small">
                                        {{ __('money_ledgers.balance_summary', [
                                            'in' => number_format((float) $balance->money_in, 2),
                                            'out' => number_format((float) $balance->money_out, 2),
                                        ]) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="me-2" data-lucide="info"></i>
                    <span>{{ __('money_ledgers.no_accounts', ['kind' => $kindLabel]) }}</span>
                    <a class="btn btn-sm btn-solid theme-primary ms-auto"
                        href="{{ route('bank.banks.create') }}">{{ __('money_ledgers.actions.create_account') }}</a>
                </div>
            @endif

            <div class="accordion mb-3 accordion-gap">
                <details class="accordion-item" @if ($search || $accountId || $startDate || $endDate) open @endif>
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="list-filter"></i>
                        <span class="fw-bold">{{ __('ledgers.filters.title') }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                        <form autocomplete="off" class="row g-2" method="GET"
                            action="{{ route($routes[$ledger]) }}">
                            <div class="md:col-3">
                                <select class="form-control" name="account_id">
                                    <option value="">{{ __('money_ledgers.all_accounts') }}</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}" @selected($accountId === $account->id)>
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-2">
                                <label class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true">
                                        <i data-lucide="calendar"></i>
                                    </div>
                                    <input class="form-ghost" name="start_date" type="text"
                                        data-bs-toggle="datepicker" autocomplete="off"
                                        value="{{ $startDate }}"
                                        placeholder="{{ __('financial_reports.form.start_date') }}" />
                                </label>
                            </div>
                            <div class="md:col-2">
                                <label class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true">
                                        <i data-lucide="calendar"></i>
                                    </div>
                                    <input class="form-ghost" name="end_date" type="text"
                                        data-bs-toggle="datepicker" autocomplete="off"
                                        value="{{ $endDate }}"
                                        placeholder="{{ __('financial_reports.form.end_date') }}" />
                                </label>
                            </div>
                            <div class="md:col-2">
                                <input class="form-control" name="search" type="text" value="{{ $search }}"
                                    placeholder="{{ __('common.actions.search') }}" />
                            </div>
                            <div class="md:col-3 d-flex gap-1">
                                <button class="btn btn-solid theme-secondary flex-grow-1" type="submit">
                                    <i class="me-1" data-lucide="search"></i> {{ __('common.actions.filter') }}
                                </button>
                                @if ($search || $accountId || $startDate || $endDate)
                                    <a class="btn btn-solid theme-secondary" href="{{ route($routes[$ledger]) }}"
                                        title="{{ __('common.actions.reset') }}">
                                        <i class="" data-lucide="x"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </details>

                <div class="table-responsive rounded-5 overflow-hidden border">
                    <table class="table-hover table-striped mb-0 table align-middle">
                        <thead class="bg-1">
                            <tr>
                                <th>{{ __('money_ledgers.fields.document_number') }}</th>
                                <th>{{ __('money_ledgers.fields.date') }}</th>
                                <th>{{ __('money_ledgers.fields.account') }}</th>
                                <th>{{ __('money_ledgers.fields.reference_number') }}</th>
                                <th>{{ __('money_ledgers.fields.description') }}</th>
                                <th class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                                <th class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row->document_number }}</td>
                                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($row->document_date)->format(Setting::getDefaultDate()) }}</td>
                                    <td>{{ $row->account_name ?? '-' }}</td>
                                    <td>{{ $row->reference_number ?? '-' }}</td>
                                    <td><small>{{ $row->description ?? '-' }}</small></td>
                                    <td class="fw-bold fg-success text-end">
                                        {{ (float) $row->debit_amount > 0 ? number_format((float) $row->debit_amount, 2) : '-' }}
                                    </td>
                                    <td class="fw-bold fg-danger text-end">
                                        {{ (float) $row->credit_amount > 0 ? number_format((float) $row->credit_amount, 2) : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="fg-secondary py-4 text-center" colspan="7">
                                        {{ __('money_ledgers.no_entries') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($rows->count() > 0)
                            <tfoot class="bg-1 fw-bold border-top">
                                <tr>
                                    <td class="text-end" colspan="5">{{ __('common.labels.total') }}:</td>
                                    <td class="text-end">{{ number_format((float) $totalDebit, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $totalCredit, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">{{ $rows->links() }}</div>
            </div>
        </div>
    </div>
@endsection
