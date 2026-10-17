@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Enums\LedgerStatus;
    use App\Models\Finance\JournalEntry;
    use Carbon\Carbon;

    $canAdd = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::UPDATE);
    $canApprove = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);
    $canCancel = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::CANCEL);
    $canRemove = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::REMOVE);
@endphp

@props([
    /** @var JournalEntry */
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
    <style>
        .entry-selection-checkbox {
            width: 1.15rem;
            height: 1.15rem;
            margin-top: 0;
            cursor: pointer;
            background-color: #fff;
            border: 2px solid #6c757d;
            opacity: 1;
        }

        .entry-selection-checkbox:hover:not(:disabled),
        .entry-selection-checkbox:focus-visible {
            border-color: var(--danger-base);
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--danger-base) 20%, transparent);
        }

        .entry-selection-checkbox:checked,
        .entry-selection-checkbox:indeterminate {
            background-color: var(--danger-base);
            border-color: var(--danger-base);
        }

        .entry-selection-checkbox:disabled {
            cursor: not-allowed;
            background-color: #dee2e6;
            border-color: #adb5bd;
            opacity: 0.75;
        }
    </style>

    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('journal_entries.journal_entries_title') }}</h4>
            <div class="d-flex gap-2">
                <a class="btn btn-solid theme-danger btn-sm" href="{{ route('finance.journal-entries.cancelled.index') }}">
                    <i class="me-1" data-lucide="x-circle"></i>{{ __('journal_entries.cancelled_title') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm" href="{{ route('finance.journal-entries.history.index') }}">
                    <i class="me-1" data-lucide="history"></i>{{ __('journal_entries.history_title') }}
                </a>
                @if ($canAdd)
                    <a class="btn btn-solid theme-success btn-sm" href="{{ route('finance.journal-entries.create') }}">
                        <i class="me-1" data-lucide="plus"></i>
                        {{ __('common.actions.add') }}
                    </a>
                @endif
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
                            <form autocomplete="off" action="{{ route('finance.journal-entries.index') }}" method="GET">
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
                                            href="{{ route('finance.journal-entries.index') }}">
                                            <i class="me-1" data-lucide="rotate-ccw"></i>
                                            {{ __('common.actions.reset') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

</details>

            <div class="d-flex mb-3 gap-2">
                @if ($canApprove)
                    <button class="btn btn-solid theme-success batch-action-button"
                        data-confirm-message="{{ __('common.alerts.confirm_batch_approve') }}" data-action="approve"
                        type="button">
                        <i class="me-1" data-lucide="check-check"></i> {{ __('common.actions.batch_approve') }}
                    </button>
                @endif

                @if ($canCancel)
                    <button class="btn btn-solid theme-warning batch-action-button"
                        data-confirm-message="{{ __('common.alerts.confirm_batch_cancel') }}" data-action="cancel"
                        type="button">
                        <i class="me-1" data-lucide="ban"></i> {{ __('common.actions.batch_cancel') }}
                    </button>
                @endif
            </div>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            @if ($canApprove)
                                <th class="text-center" style="width: 40px">
                                    <input class="check entry-selection-checkbox" id="checkAll" type="checkbox" />
                                </th>
                            @endif
                            <th>{{ __('journal_entries.table.column_document_number') }}</th>
                            <th>{{ __('journal_entries.table.column_reference_number') }}</th>
                            <th>{{ __('journal_entries.table.column_date') }}</th>
                            <th>{{ __('journal_entries.table.column_notes') }}</th>
                            <th>{{ __('journal_entries.table.column_status') }}</th>
                            <th class="text-end">{{ __('journal_entries.table.column_debit_amount') }}</th>
                            <th class="text-end">{{ __('journal_entries.table.column_credit_amount') }}</th>
                            <th class="text-center">{{ __('journal_entries.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php
                                $isPosted = $entry->status === LedgerStatus::POSTED;
                                $isReversal = $entry->reversal_of_id !== null;
                                $isReversed = $entry->reversal !== null;
                                $statusLabel = $isReversal
                                    ? __('journal_entries.reversal_status')
                                    : ($isReversed
                                        ? __('journal_entries.reversed_status')
                                        : ($isPosted
                                            ? __('journal_entries.status_approved')
                                            : __('journal_entries.status_pending')));
                                $statusClass = $isReversal
                                    ? 'bg-info'
                                    : ($isReversed
                                        ? 'bg-danger'
                                        : ($isPosted
                                            ? 'bg-success'
                                            : 'bg-warning fg-black'));
                            @endphp
                            <tr>
                                @if ($canApprove)
                                    <td class="text-center">
                                        @if (!$isPosted)
                                            <input class="check entry-checkbox entry-selection-checkbox" form="batch-form"
                                                name="entry_ids[]" type="checkbox" value="{{ $entry->id }}" />
                                        @endif
                                    </td>
                                @endif
                                <td class="fw-bold">{{ $entry->document_number }}</td>
                                <td>{{ $entry->reference_number ?? '-' }}</td>
                                <td>{{ Carbon::parse($entry->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $entry->notes ?? '-' }}</td>
                                <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td class="fw-bold fg-success text-end">
                                    {{ number_format((float) $entry->total_debit, 2) }}
                                </td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $entry->total_credit, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a class="btn btn-sm btn-solid theme-info me-1"
                                            href="{{ route('finance.journal-entries.show', $entry) }}"
                                            title="{{ __('common.actions.view') }}">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <a class="btn btn-sm btn-solid theme-secondary"
                                            href="{{ route('finance.journal-entries.pdf', $entry) }}"
                                            title="{{ __('journal_entries.pdf_button') }}" target="_blank">
                                            <i data-lucide="file-text"></i>
                                        </a>
                                        @if (!$isPosted && $canUpdate)
                                            <a class="btn btn-sm btn-solid theme-warning me-1"
                                                href="{{ route('finance.journal-entries.edit', $entry) }}"
                                                title="{{ __('common.actions.edit') }}">
                                                <i data-lucide="square-pen"></i>
                                            </a>
                                        @endif
                                        @if (!$isPosted && $canApprove)
                                            <form autocomplete="off" class="d-inline"
                                                action="{{ route('finance.journal-entries.approve', $entry) }}"
                                                method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-solid theme-success" type="submit"
                                                    title="{{ __('journal_entries.approve_button') }}"
                                                    data-confirm-message="{{ __('journal_entries.confirm_approve') }}">
                                                    <i data-lucide="book-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if (!$isPosted && $canRemove)
                                            <form autocomplete="off" class="d-inline"
                                                action="{{ route('finance.journal-entries.destroy', $entry) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-solid theme-danger" type="submit"
                                                    title="{{ __('common.actions.delete') }}"
                                                    data-confirm-message="{{ __('journal_entries.confirm_delete') }}">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="{{ $canApprove ? 9 : 8 }}">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($entries->count() > 0)
                        <tfoot class="bg-1 fw-bold border-top">
                            <tr>
                                <td class="text-end" colspan="{{ $canApprove ? 7 : 6 }}">
                                    {{ __('common.labels.total') }}:
                                </td>
                                <td class="fg-success text-end">
                                    {{ number_format((float) $entries->sum('total_debit'), 2) }}
                                </td>
                                <td class="fg-primary text-end">
                                    {{ number_format((float) $entries->sum('total_credit'), 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <form autocomplete="off" class="d-none" id="batch-form" method="POST">
                @csrf
            </form>

            <div class="d-flex justify-content-end mt-3">{{ $entries->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = Array.from(document.querySelectorAll('.entry-checkbox'));

            const syncSelectionControls = function() {
                const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

                if (checkAll) {
                    checkAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
                    checkAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
                    checkAll.disabled = checkboxes.length === 0;
                }
            };

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = checkAll.checked;
                    });
                    syncSelectionControls();
                });
            }

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', syncSelectionControls);
            });

            syncSelectionControls();

            document.querySelectorAll('.batch-action-button').forEach(function(button) {
                button.addEventListener('click', function() {
                    const checked = document.querySelectorAll('.entry-checkbox:checked');
                    if (checked.length === 0) {
                        confirmDialog({
                            text: @json(__('common.alerts.no_selection')),
                            icon: 'warning',
                        })
                        return;
                    }

                    const action = button.dataset.action;
                    const routeUrl =
                        action === 'approve' ? @json(route('finance.journal-entries.batch-approve')) :
                        @json(route('finance.journal-entries.batch-cancel'));

                    confirmDialog({
                        text: button.dataset.confirmMessage,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: @json(__('common.actions.approve')),
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            const form = document.getElementById('batch-form');
                            form.action = routeUrl;
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endsection
