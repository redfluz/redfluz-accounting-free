@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canApprove = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);
@endphp
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
                <h4 class="fw-bold mb-0">{{ __('journal_entries.history_title') }}</h4>
                <span class="fg-secondary small">{{ __('journal_entries.history_subtitle') }}</span>
            </div>
            <div class="d-flex gap-1">
                <a class="btn btn-solid theme-success btn-sm"
                    href="{{ route('finance.journal-entries.history.export-excel', request()->query()) }}">
                    <i class="me-1" data-lucide="file-spreadsheet"></i>{{ __('common.actions.export_excel') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm"
                    href="{{ route('finance.journal-entries.history.export-pdf', request()->query()) }}">
                    <i class="me-1" data-lucide="file-text"></i>{{ __('common.actions.export_pdf') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('finance.journal-entries.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="accordion mb-3 accordion-gap">
                <details class="accordion-item">
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="list-filter"></i>
                        <span class="fw-bold">{{ __('financial_reports.form.filters') }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                            <form autocomplete="off" class="row g-2" method="GET"
                                action="{{ route('finance.journal-entries.history.index') }}">
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
                                        <a class="btn btn-solid theme-danger"
                                            href="{{ route('finance.journal-entries.history.index') }}">
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
                            <th>{{ __('journal_entries.table.column_document_number') }}</th>
                            <th>{{ __('journal_entries.table.column_reference_number') }}</th>
                            <th>{{ __('journal_entries.table.column_date') }}</th>
                            <th>{{ __('journal_entries.table.column_notes') }}</th>
                            <th>{{ __('journal_entries.table.column_status') }}</th>
                            <th class="text-end">{{ __('journal_entries.table.column_debit_amount') }}</th>
                            <th class="text-end">{{ __('journal_entries.table.column_credit_amount') }}</th>
                            <th class="text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php
                                $isReversal = $entry->reversal_of_id !== null;
                                $isReversed = $entry->reversal !== null;
                                $canReverse = $canApprove && !$isReversal && !$isReversed;
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $entry->document_number }}</td>
                                <td>{{ $entry->reference_number ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($entry->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $entry->notes ?? '-' }}</td>
                                <td>
                                    @if ($isReversal)
                                        <span class="badge theme-info">{{ __('journal_entries.reversal_status') }}</span>
                                    @elseif ($isReversed)
                                        <span class="badge theme-danger">{{ __('journal_entries.reversed_status') }}</span>
                                    @else
                                        <span
                                            class="badge theme-success">{{ __('journal_entries.status_approved') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $entry->total_debit, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $entry->total_credit, 2) }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-sm btn-solid theme-info"
                                            href="{{ route('finance.journal-entries.history.show', $entry) }}"
                                            title="{{ __('common.actions.view') }}">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        @if ($canReverse)
                                            <button class="btn btn-sm btn-solid theme-warning" data-bs-toggle="dialog"
                                                data-bs-target="#reverseModal-{{ $entry->id }}"
                                                title="{{ __('journal_entries.reverse_button') }}" type="button">
                                                <i data-lucide="undo-2"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="8">
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

    @foreach ($entries as $entry)
        @php
            $isReversal = $entry->reversal_of_id !== null;
            $isReversed = $entry->reversal !== null;
            $canReverse = $canApprove && !$isReversal && !$isReversed;
        @endphp
        @if ($canReverse)
            <dialog class="dialog">
                <form autocomplete="off" method="POST" action="{{ route('finance.journal-entries.reverse', $entry) }}">
                    @csrf
                    @method('PATCH')
                    <div class="dialog-header">
                        <h5 class="dialog-title" id="reverseModalLabel-{{ $entry->id }}">
                            {{ __('journal_entries.reverse_title') }}
                        </h5>
                        <button class="btn-close" data-bs-dismiss="dialog" type="button"
                            aria-label="{{ __('common.actions.close') }}"></button>
                    </div>
                    <div class="dialog-body">
                        <div class="alert alert theme-warning">{{ __('journal_entries.reverse_explanation') }}</div>
                        <div class="mb-3">
                            <label class="form-label" for="reversal_date-{{ $entry->id }}">
                                {{ __('journal_entries.reversal_date') }}
                            </label>
                            <input class="form-control @error('reversal_date') is-invalid @enderror"
                                id="reversal_date-{{ $entry->id }}" name="reversal_date" type="text"
                                data-bs-toggle="datepicker" autocomplete="off" value="{{ old('reversal_date', now()->toDateString()) }}"
                                min="{{ $postingDateMin ?? '' }}" max="{{ $postingDateMax ?? '' }}" required />
                            @error('reversal_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label" for="reason-{{ $entry->id }}">
                                {{ __('journal_entries.reversal_reason') }}
                            </label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" id="reason-{{ $entry->id }}" name="reason"
                                rows="4" maxlength="1000" placeholder="{{ __('journal_entries.reversal_reason_placeholder') }}" required>{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="dialog-footer">
                        <button class="btn btn-solid theme-secondary" data-bs-dismiss="dialog" type="button">
                            {{ __('common.actions.cancel') }}
                        </button>
                        <button class="btn btn-solid theme-warning" type="submit"
                            data-confirm-message="{{ __('journal_entries.confirm_reverse') }}">
                            <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                        </button>
                    </div>
                </form>
            </dialog>
            @if ($errors->has('reversal_date') || $errors->has('reason'))
                <script>
                    document.addEventListener('DOMContentLoaded', () =>
                        bootstrap.Dialog.getOrCreateInstance(document.getElementById('reverseModal-{{ $entry->id }}')).show(),
                    );
                </script>
            @endif
        @endif
    @endforeach
@endsection
