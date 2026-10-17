@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Finance\JournalEntry;
    use Carbon\Carbon;
    use Illuminate\Pagination\LengthAwarePaginator;

@endphp

@props([
    /** @var LengthAwarePaginator<int, JournalEntry> */
    'entries',
    /** @var string|null */
    'search',
    /** @var string|null */
    'startDate',
    /** @var string|null */
    'endDate',
])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('journal_entries.cancelled_title') }}</h4>
                <span class="fg-secondary small">{{ __('journal_entries.cancelled_subtitle') }}</span>
            </div>
            <div class="d-flex gap-1">
                <a class="btn btn-solid theme-success btn-sm"
                    href="{{ route('finance.journal-entries.cancelled.export-excel', request()->query()) }}">
                    <i class="me-1" data-lucide="file-spreadsheet"></i>{{ __('common.actions.export_excel') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm"
                    href="{{ route('finance.journal-entries.cancelled.export-pdf', request()->query()) }}">
                    <i class="me-1" data-lucide="file-text"></i>{{ __('common.actions.export_pdf') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('finance.journal-entries.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
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
                            <form autocomplete="off" action="{{ route('finance.journal-entries.cancelled.index') }}" method="GET">
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
                                            href="{{ route('finance.journal-entries.cancelled.index') }}">
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
                            <th>{{ __('journal_entries.table.column_document_number') }}</th>
                            <th>{{ __('journal_entries.table.column_reference_number') }}</th>
                            <th>{{ __('journal_entries.table.column_date') }}</th>
                            <th>{{ __('journal_entries.table.column_notes') }}</th>
                            <th class="text-end">{{ __('journal_entries.table.column_debit_amount') }}</th>
                            <th class="text-end">{{ __('journal_entries.table.column_credit_amount') }}</th>
                            <th class="text-center">{{ __('journal_entries.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="fw-bold">{{ $entry->document_number }}</td>
                                <td>{{ $entry->reference_number ?? '-' }}</td>
                                <td>{{ Carbon::parse($entry->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $entry->notes ?? '-' }}</td>
                                <td class="fw-bold fg-success text-end">
                                    {{ number_format((float) $entry->total_debit, 2) }}
                                </td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $entry->total_credit, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-sm btn-solid theme-info"
                                            href="{{ route('finance.journal-entries.cancelled.show', $entry) }}"
                                            title="{{ __('common.actions.view') }}">
                                            <i data-lucide="eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="7">{{ __('ledgers.no_records') }}
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
