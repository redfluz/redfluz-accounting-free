@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Enums\Finance\DocumentStatus;

    $canApprove = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);
    $isPosted = $journalEntry->status === DocumentStatus::POSTED;
    $isReversal = $journalEntry->document_original !== null;
    $isReversed = $journalEntry->undone_at !== null;
    $canReverse = $canApprove && $isPosted && !$isReversal && !$isReversed;
    $statusLabel = $isReversal
        ? __('journal_entries.reversal_status')
        : ($isReversed
            ? __('journal_entries.reversed_status')
            : __('journal_entries.status_approved'));
@endphp
@props(['journalEntry'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $journalEntry->document_number }}</h4>
                <small class="badge theme-secondary">{{ __('journal_entries.show_title') }}</small>
            </div>
            <div class="d-flex gap-2">
                @if ($isReversal)
                    <span
                        class="badge theme-info d-flex align-items-center">{{ __('journal_entries.reversal_status') }}</span>
                @elseif ($isReversed)
                    <span
                        class="badge theme-danger d-flex align-items-center">{{ __('journal_entries.reversed_status') }}</span>
                @else
                    <span
                        class="badge theme-success d-flex align-items-center">{{ __('journal_entries.status_approved') }}</span>
                    @if ($canReverse)
                        <button class="btn btn-solid theme-warning btn-sm" data-bs-toggle="dialog"
                            data-bs-target="#reverseJournalModal" type="button">
                            <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                        </button>
                    @endif
                @endif
                <a class="btn btn-solid theme-primary btn-sm"
                    href="{{ route('finance.journal-entries.history.pdf', $journalEntry->document_number) }}" target="_blank"><i
                        data-lucide="file-text"></i> {{ __('journal_entries.pdf_button') }}</a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('finance.journal-entries.history.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i> {{ __('common.actions.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            @if ($isReversal)
                <div class="alert alert theme-info">
                    {{ __('journal_entries.reversal_of') }}:
                    <a class="alert-link"
                        href="{{ route('finance.journal-entries.history.show', $journalEntry->document_original) }}">{{ $journalEntry->document_original }}</a>
                </div>
            @elseif ($isReversed && $reversalNumber !== null)
                <div class="alert alert theme-warning">
                    {{ __('journal_entries.reversed_by') }}:
                    <a class="alert-link"
                        href="{{ route('finance.journal-entries.history.show', $reversalNumber) }}">{{ $reversalNumber }}</a>
                </div>
            @endif
            <div class="row g-3 mb-4">
                <div class="md:col-3">
                    <div class="bg-2 rounded p-3">
                        <small class="fg-secondary d-block">{{ __('journal_entries.table.column_date') }}</small>
                        <strong>{{ $journalEntry->date->format(Setting::getDefaultDate()) }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="bg-2 rounded p-3">
                        <small
                            class="fg-secondary d-block">{{ __('journal_entries.table.column_reference_number') }}</small>
                        <strong>{{ $journalEntry->reference_number ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="bg-2 rounded p-3">
                        <small class="fg-secondary d-block">{{ __('journal_entries.table.column_status') }}</small>
                        <strong>{{ $statusLabel }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="bg-2 rounded p-3">
                        <small class="fg-secondary d-block">{{ __('journal_entries.table.column_notes') }}</small>
                        <strong>{{ $journalEntry->notes ?? '-' }}</strong>
                    </div>
                </div>
            </div>
            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('journal_entries.table.column_account') }}</th>
                            <th scope="col">{{ __('journal_entries.table.column_description') }}</th>
                            <th scope="col" class="text-end">{{ __('journal_entries.table.column_debit_amount') }}</th>
                            <th scope="col" class="text-end">{{ __('journal_entries.table.column_credit_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($journalEntry->charts as $item)
                            @if (filled($item->chart_name) ||
                                    filled($item->description) ||
                                    (float) $item->debit_amount > 0 ||
                                    (float) $item->credit_amount > 0)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $item->chart_code ?? '' }} {{ $item->chart_name ?? '-' }}
                                    </td>
                                    <td>{{ $item->description ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((float) $item->debit_amount, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $item->credit_amount, 2) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot class="bg-1 fw-bold">
                        <tr>
                            <td class="text-end" colspan="3">{{ __('common.labels.total') }}</td>
                            <td class="text-end">{{ number_format((float) $journalEntry->total_debit, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $journalEntry->total_credit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @if ($canReverse)
        <dialog class="dialog" id="reverseJournalModal" aria-labelledby="reverseJournalModalLabel">
            <form method="POST" action="{{ route('finance.journal-entries.reverse', $journalEntry) }}">
                @csrf
                @method('PATCH')
                <div class="dialog-header">
                    <h5 class="dialog-title" id="reverseJournalModalLabel">
                        {{ __('journal_entries.reverse_title') }}
                    </h5>
                    <button class="btn-close" data-bs-dismiss="dialog" type="button"
                        aria-label="{{ __('common.actions.close') }}"></button>
                </div>
                <div class="dialog-body">
                    <div class="alert alert theme-warning">{{ __('journal_entries.reverse_explanation') }}</div>
                    @error('reversal_date')
                        <div class="alert alert theme-danger">{{ $message }}</div>
                    @enderror
                    <div>
                        <label class="form-label" for="reason">{{ __('journal_entries.reversal_reason') }}</label>
                        <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="4"
                            maxlength="1000" placeholder="{{ __('journal_entries.reversal_reason_placeholder') }}" required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="dialog-footer">
                    <button class="btn btn-solid theme-secondary" data-bs-dismiss="dialog" type="button">
                        {{ __('common.actions.cancel') }}</button><button class="btn btn-solid theme-warning"
                        type="submit" data-confirm-message="{{ __('journal_entries.confirm_reverse') }}">
                        <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                    </button>
                </div>
            </form>
        </dialog>
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    lucide.createIcons();
                });
            </script>
        @endpush
    @endif
@endsection
