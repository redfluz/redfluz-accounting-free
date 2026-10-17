@php
    use App\Models\System\Setting;
    use App\Enums\LedgerType;
    use App\Models\Finance\Ledger;

    $totalDebit = $ledgers->sum('debit_amount');
    $totalCredit = $ledgers->sum('credit_amount');
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
                <a class="btn btn-solid theme-success btn-sm"
                    href="{{ route('finance.ledgers.export-excel', request()->query()) }}">
                    <i class="me-1" data-lucide="file-spreadsheet"></i> {{ __('ledgers.export_excel') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm"
                    href="{{ route('finance.ledgers.export-pdf', request()->query()) }}" target="_blank">
                    <i class="me-1" data-lucide="file-text"></i> {{ __('ledgers.export_pdf') }}
                </a>
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
                                    placeholder="{{ __('common.actions.search') }}" />
                            </div>
                            <div class="md:col-2">
                                <select class="form-control" name="type">
                                    <option value="">{{ __('ledgers.all_types') }}</option>
                                    @foreach (LedgerType::cases() as $ledgerType)
                                        <option value="{{ $ledgerType->value }}" @selected(request('type') === $ledgerType->value)>
                                            {{ __('ledgers.type.' . $ledgerType->value) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-2">
                                <label class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true">
                                        <i data-lucide="calendar"></i>
                                    </div>
                                    <input class="form-ghost" name="start_date" type="text" data-bs-toggle="datepicker"
                                        autocomplete="off" value="{{ $startDate }}"
                                        placeholder="{{ __('financial_reports.form.start_date') }}" />
                                </label>
                            </div>
                            <div class="md:col-2">
                                <label class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true">
                                        <i data-lucide="calendar"></i>
                                    </div>
                                    <input class="form-ghost" name="end_date" type="text" data-bs-toggle="datepicker"
                                        autocomplete="off" value="{{ $endDate }}"
                                        placeholder="{{ __('financial_reports.form.end_date') }}" />
                                </label>
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

                <div class="table-responsive rounded-5 overflow-hidden border">
                    <table class="table-hover table-striped mb-0 table align-middle">
                        <thead class="bg-1">
                            <tr>
                                @foreach ([
            'document_number' => 'ledgers.table.column_document_number',
            'date' => 'ledgers.table.column_date',
            'type' => 'ledgers.table.column_type',
        ] as $column => $label)
                                    <th>
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
                                <th>{{ __('ledgers.table.column_company') }}</th>
                                <th>{{ __('ledgers.table.column_description') }}</th>
                                @foreach (['debit_amount', 'credit_amount'] as $column)
                                    <th class="text-end">
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
                                                {{ $ledger->debit_acc ?? '' }} {{ $ledger->credit_acc ?? '' }}
                                                {{ $ledger->debit_acc_desc ?? '' }} {{ $ledger->credit_acc_desc ?? '' }}
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
                                        {{ $ledger->debit_amount > 0 ? number_format($ledger->debit_amount, 2) : '-' }}
                                    </td>
                                    <td class="fw-bold fg-danger text-end">
                                        {{ $ledger->credit_amount > 0 ? number_format($ledger->credit_amount, 2) : '-' }}
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
