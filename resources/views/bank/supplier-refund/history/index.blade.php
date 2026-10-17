@php use App\Models\System\Setting; @endphp
@props(['refunds', 'search', 'startDate', 'endDate'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('ledgers.history_title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('refunds.title') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline border btn-sm" href="{{ route('bank.supplier-refunds.index') }}">
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
                            <form autocomplete="off" action="{{ route('bank.supplier-refunds.history.index') }}" method="GET">
                                <div class="row g-2 align-items-center">
                                    <div class="md:col-3">
                                        <input class="form-control" name="search" type="text"
                                            value="{{ $search }}" placeholder="{{ __('common.actions.search') }}" />
                                    </div>
                                    <div class="md:col-3">
                                        <label class="form-control form-adorn">
                                            <div class="form-adorn-icon" aria-hidden="true">
                                                <i data-lucide="calendar"></i>
                                            </div>
                                            <input class="form-ghost" name="start_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                                                value="{{ $startDate }}" placeholder="{{ __('financial_reports.form.start_date') }}" />
                                        </label>
                                    </div>
                                    <div class="md:col-3">
                                        <label class="form-control form-adorn">
                                            <div class="form-adorn-icon" aria-hidden="true">
                                                <i data-lucide="calendar"></i>
                                            </div>
                                            <input class="form-ghost" name="end_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                                                value="{{ $endDate }}" placeholder="{{ __('financial_reports.form.end_date') }}" />
                                        </label>
                                    </div>
                                    <div class="md:col-3 d-flex gap-2">
                                        <button class="btn btn-solid theme-primary flex-fill" type="submit">
                                            <i class="me-1" data-lucide="filter"></i>
                                            {{ __('common.actions.search') }}
                                        </button>
                                        <a class="btn btn-outline flex-fill border"
                                            href="{{ route('bank.supplier-refunds.history.index') }}">
                                            <i class="me-1" data-lucide="rotate-ccw"></i>
                                            {{ __('common.actions.reset') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

</details>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('ledgers.table.column_document_number') }}</th>
                            <th>{{ __('ledgers.table.column_reference_number') }}</th>
                            <th>{{ __('ledgers.table.column_date') }}</th>
                            <th>{{ __('ledgers.table.column_company') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th>{{ __('refunds.audit.created_by') }}</th>
                            <th>{{ __('refunds.audit.approved_by') }}</th>
                            <th class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($refunds as $refund)
                            <tr>
                                <td class="fw-bold">{{ $refund->document_number }}</td>
                                <td>{{ $refund->reference_number ?? '-' }}</td>
                                <td>{{ $refund->date->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $refund->company_name ?: '-' }}</td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $refund->total_amount, 2) }}
                                </td>
                                <td>
                                    {{ $refund->created_name ?? '-' }}
                                    @if ($refund->created_at)
                                        <span class="fg-secondary small d-block">
                                            {{ $refund->created_at->format(Setting::getDefaultDate('long_date_format')) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    {{ $refund->approved_name ?? '-' }}
                                    @if ($refund->approved_at)
                                        <span class="fg-secondary small d-block">
                                            {{ $refund->approved_at->format(Setting::getDefaultDate('long_date_format')) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-sm btn-solid theme-info"
                                            href="{{ route('bank.supplier-refunds.history.show', $refund->document_number) }}"
                                            title="{{ __('payments.actions.view_and_print') }}">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <a class="btn btn-sm btn-solid theme-primary"
                                            href="{{ route('bank.supplier-refunds.history.pdf', $refund->document_number) }}"
                                            target="_blank" title="{{ __('payments.actions.print_pdf') }}">
                                            <i data-lucide="file-type-2"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">{{ __('ledgers.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $refunds->links() }}</div>
        </div>
    </div>
@endsection
