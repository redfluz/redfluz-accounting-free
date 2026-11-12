@php
    use App\Models\System\Setting;
@endphp

@props(['transfers', 'search', 'startDate', 'endDate'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('bank_transfers.history.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('bank_transfers.history.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline border btn-sm" href="{{ route('bank.bank-transfers.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('ledgers.pending_title') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="accordion mb-4 accordion-gap">
                <details class="accordion-item">
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="list-filter"></i>
                        <span class="fw-bold">{{ __('financial_reports.form.filters') }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                        <form autocomplete="off" action="{{ route('bank.bank-transfers.history.index') }}" method="GET">
                            <div class="row g-2 align-items-center">
                                <div class="md:col-3">
                                    <label class="visually-hidden" for="bth-search">{{ __('common.actions.search') }}</label>
                                    <input class="form-control" id="bth-search" name="search" type="text" value="{{ $search }}"
                                        placeholder="{{ __('common.actions.search') }}" />
                                </div>
                                <div class="md:col-3">
                                    <div class="form-control form-adorn">
                                        <div class="form-adorn-icon" aria-hidden="true">
                                            <i data-lucide="calendar"></i>
                                        </div>
                                        <label class="visually-hidden" for="bth-start-date">{{ __('financial_reports.form.start_date') }}</label>
                                        <input class="form-ghost" id="bth-start-date" name="start_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                                            value="{{ $startDate }}" placeholder="{{ __('financial_reports.form.start_date') }}" />
                                    </div>
                                </div>
                                <div class="md:col-3">
                                    <div class="form-control form-adorn">
                                        <div class="form-adorn-icon" aria-hidden="true">
                                            <i data-lucide="calendar"></i>
                                        </div>
                                        <label class="visually-hidden" for="bth-end-date">{{ __('financial_reports.form.end_date') }}</label>
                                        <input class="form-ghost" id="bth-end-date" name="end_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                                            value="{{ $endDate }}" placeholder="{{ __('financial_reports.form.end_date') }}" />
                                    </div>
                                </div>
                                <div class="md:col-3 d-flex gap-2">
                                    <button class="btn btn-solid theme-primary flex-fill" type="submit">
                                        <i class="me-1" data-lucide="filter"></i>
                                        {{ __('common.actions.search') }}
                                    </button>
                                    <a class="btn btn-outline flex-fill border" href="{{ route('bank.bank-transfers.history.index') }}">
                                        <i class="me-1" data-lucide="rotate-ccw"></i>
                                        {{ __('common.actions.reset') }}
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </details>
            </div>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col">{{ __('ledgers.table.column_document_number') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                            <th scope="col">{{ __('bank_transfers.table.column_from') }}</th>
                            <th scope="col">{{ __('bank_transfers.table.column_to') }}</th>
                            <th scope="col" class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th scope="col">{{ __('refunds.audit.approved_by') }}</th>
                            <th scope="col" class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $transfer)
                            <tr>
                                <td class="fw-bold">{{ $transfer->document_number }}</td>
                                <td>{{ $transfer->date->format(Setting::getDefaultDate()) }}</td>
                                <td>
                                    @if ($transfer->transitChart)
                                        {{ $transfer->transitChart->code }} ~ {{ $transfer->transitChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $transfer->bankAccount?->name ?? '-' }}</td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $transfer->amount, 2) }}
                                </td>
                                <td>
                                    {{ $transfer->approved_name ?? '-' }}
                                    @if ($transfer->approved_at)
                                        <span class="fg-secondary small d-block">
                                            {{ $transfer->approved_at->format(Setting::getDefaultDate('long_date_format')) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-sm btn-solid theme-info"
                                            href="{{ route('bank.bank-transfers.history.show', $transfer->document_number) }}"
                                            title="{{ __('common.actions.view') }}">
                                            <i data-lucide="eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ __('ledgers.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($transfers->count() > 0)
                        <tfoot class="bg-1 fw-bold border-top">
                            <tr>
                                <td class="text-end" colspan="4">{{ __('common.labels.total') }}:</td>
                                <td class="fg-primary text-end">{{ number_format((float) $transfers->sum('amount'), 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $transfers->links() }}</div>
        </div>
    </div>
@endsection