@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    $canApprove = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);
    use App\Enums\LedgerStatus;
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
                <span class="badge theme-danger d-flex align-items-center"> {{ __('ledgers.status_cancelled') }} </span>
                @if ($canApprove)
                    <form autocomplete="off" action="{{ route('finance.journal-entries.cancelled.uncancel', $journalEntry) }}"
                        method="POST" data-confirm-message="{{ __('common.messages.uncancel_confirm') }}"
                        data-confirm-tone="danger">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-solid theme-warning"
                            title="{{ __('common.actions.undo') }}">
                            <i data-lucide="undo-2"></i>{{ __('common.actions.undo') }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm"
                    href="{{ route('finance.journal-entries.cancelled.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
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
                        <strong>{{ __('ledgers.status_cancelled') }}</strong>
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
                            <th>#</th>
                            <th>{{ __('journal_entries.table.column_account') }}</th>
                            <th>{{ __('journal_entries.table.column_description') }}</th>
                            <th class="text-end">{{ __('journal_entries.table.column_debit_amount') }}</th>
                            <th class="text-end">{{ __('journal_entries.table.column_credit_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($journalEntry->charts as $item)
                            @if (filled($item->debit_acc) ||
                                    filled($item->credit_acc) ||
                                    filled($item->description) ||
                                    (float) $item->debit_amount > 0 ||
                                    (float) $item->credit_amount > 0)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $item->debit_acc ?? ($item->credit_acc ?? '-') }} -
                                        {{ $item->debit_acc_desc ?? ($item->credit_acc_desc ?? '-') }}
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
@endsection
