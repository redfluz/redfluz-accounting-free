@php use App\Models\System\Setting; @endphp
@props([
    'entries',
    /** @var string|null */
    'search',
    /** @var string|null */
    'startDate',
    /** @var string|null */
    'endDate',
    /** @var string|null */
    'postingDateMin',
    /** @var string|null */
    'postingDateMax',
])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('ledgers.history_title') }}</h4>
                <span class="fg-secondary small">{{ __('ledgers.history_subtitle') }}</span>
            </div>
            <div class="d-flex gap-1">
                <a class="btn btn-solid theme-success btn-sm"
                    href="{{ route('sales.invoices.history.export-excel', request()->query()) }}">
                    <i class="me-1" data-lucide="file-spreadsheet"></i>{{ __('common.actions.export_excel') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm"
                    href="{{ route('sales.invoices.history.export-pdf', request()->query()) }}">
                    <i class="me-1" data-lucide="file-text"></i>{{ __('common.actions.export_pdf') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('sales.invoices.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route('sales.invoices.history.index') }}">
                <div class="md:col-3">
                    <input class="form-control" name="search" type="text" value="{{ $search }}"
                        placeholder="{{ __('common.actions.search') }}" />
                </div>
                <div class="md:col-2">
                    <label class="form-control form-adorn">
                        <div class="form-adorn-icon" aria-hidden="true">
                            <i data-lucide="calendar"></i>
                        </div>
                        <input class="form-ghost" name="start_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                            value="{{ $startDate }}" placeholder="{{ __('financial_reports.form.start_date') }}" />
                    </label>
                </div>
                <div class="md:col-2">
                    <label class="form-control form-adorn">
                        <div class="form-adorn-icon" aria-hidden="true">
                            <i data-lucide="calendar"></i>
                        </div>
                        <input class="form-ghost" name="end_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                            value="{{ $endDate }}" placeholder="{{ __('financial_reports.form.end_date') }}" />
                    </label>
                </div>
                <div class="md:col-3 d-flex gap-1">
                    <button class="btn btn-solid theme-secondary flex-fill" type="submit">
                        <i class="me-1" data-lucide="search"></i>{{ __('common.actions.search') }}
                    </button>
                    @if ($search || $startDate || $endDate)
                        <a class="btn btn-solid theme-danger" href="{{ route('sales.invoices.history.index') }}">
                            <i class="" data-lucide="x"></i>
                        </a>
                    @endif
                </div>
            </form>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('ledgers.table.column_document_number') }}</th>
                            <th>{{ __('ledgers.table.column_reference_number') }}</th>
                            <th>{{ __('ledgers.table.column_date') }}</th>
                            <th>{{ __('ledgers.table.column_company') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th class="text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="fw-bold">{{ $entry->document_number }}</td>
                                <td>{{ $entry->reference_number ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($entry->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $entry->company_name ?? '-' }}</td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $entry->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-solid theme-info"
                                        href="{{ route('sales.invoices.history.show', $entry->document_number) }}"
                                        title="{{ __('common.actions.view') }}">
                                        <i data-lucide="eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="6">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $entries->links() }}</div>
        </div>
    </div>
@endsection
