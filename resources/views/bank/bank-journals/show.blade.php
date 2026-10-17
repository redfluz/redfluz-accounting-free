@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $resource = FileResources::BANK_JOURNALS;
    $canUpdate = auth()->user()->hasFilePermission($resource, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission($resource, FileAction::REMOVE);
    $canApprove = auth()->user()->hasFilePermission($resource, FileAction::APPROVE);
    $canCancel = auth()->user()->hasFilePermission($resource, FileAction::CANCEL);
@endphp

@props(['journal', 'documentNumber'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">
                    {{ $journal->direction === 'in' ? __('bank_journals.form.direction_in') : __('bank_journals.form.direction_out') }}
                    | {{ $journal->moneyAccount()?->name ?? '-' }}
                </span>
            </div>
            <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
                @if ($journal->isPosted())
                    <span class="badge theme-success d-flex align-items-center">
                        {{ __('journal_entries.status_approved') }}
                    </span>
                @elseif ($journal->isCancelled())
                    <span class="badge theme-danger d-flex align-items-center">
                        {{ __('ledgers.status_cancelled') }}
                    </span>
                    @if ($canUpdate)
                        <form autocomplete="off"
                            action="{{ route('bank.bank-journals.restore', $documentNumber) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-solid theme-warning btn-sm text-nowrap" type="submit">
                                <i class="me-1" data-lucide="undo-2"></i>
                                {{ __('bank_journals.show.restore_button') }}
                            </button>
                        </form>
                    @endif
                @else
                    @if ($canApprove)
                        <form autocomplete="off" action="{{ route('bank.bank-journals.approve', $documentNumber) }}"
                            method="POST">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-solid theme-success btn-sm text-nowrap" type="submit">
                                <i class="me-1" data-lucide="book-check"></i>
                                {{ __('journal_entries.approve_button') }}
                            </button>
                        </form>
                    @endif
                    @if ($canUpdate)
                        <a class="btn btn-solid theme-info btn-sm"
                            href="{{ route('bank.bank-journals.edit', $documentNumber) }}">
                            <i class="me-1" data-lucide="pencil"></i>
                            {{ __('common.actions.edit') }}
                        </a>
                    @endif
                    @if ($canCancel)
                        <form autocomplete="off" action="{{ route('bank.bank-journals.cancel', $documentNumber) }}"
                            method="POST">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-solid theme-warning btn-sm text-nowrap" type="submit">
                                <i class="me-1" data-lucide="ban"></i>
                                {{ __('common.actions.cancel') }}
                            </button>
                        </form>
                    @endif
                    @if ($canRemove)
                        <form autocomplete="off" action="{{ route('bank.bank-journals.destroy', $documentNumber) }}"
                            method="POST" data-confirm-message="{{ __('common.alerts.confirm_delete') }}"
                            data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-solid theme-danger btn-sm text-nowrap" type="submit">
                                <i class="me-1" data-lucide="trash-2"></i>
                                {{ __('common.actions.delete') }}
                            </button>
                        </form>
                    @endif
                @endif

                <a class="btn btn-solid theme-secondary btn-sm"
                    href="{{ route('bank.bank-journals.index', ['status' => $journal->status->value]) }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('ledgers.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="row g-3 mb-4">
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('bank_journals.form.date') }}</span>
                        <strong>{{ $journal->document_date->format(Setting::getDefaultDate()) }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('bank_journals.table.column_account') }}</span>
                        <strong>{{ $journal->moneyAccount()?->name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('bank_journals.table.column_offset') }}</span>
                        <strong>
                            @if ($journal->offsetChart)
                                {{ $journal->offsetChart->code }} ~ {{ $journal->offsetChart->name }}
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_total_amount') }}</span>
                        <strong class="fg-primary">{{ number_format((float) $journal->amount, 2) }}</strong>
                    </div>
                </div>
            </div>

            {{-- The posting preview. Money in debits the money account and credits
                 the offset; money out is the mirror. --}}
            <h6 class="fw-bold mb-1">{{ __('bank_journals.show.posting_title') }}</h6>
            <p class="fg-secondary small">{{ __('bank_journals.show.posting_hint') }}</p>
            <div class="table-responsive rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('bank_journals.show.column_side') }}</th>
                            <th>{{ __('bank_journals.show.column_account') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($journal->direction === 'in')
                            <tr>
                                <td>{{ __('bank_journals.show.money_in') }}</td>
                                <td>
                                    @if ($journal->moneyAccount()?->chart)
                                        {{ $journal->moneyAccount()->chart->code }} ~
                                        {{ $journal->moneyAccount()->chart->name }}
                                    @else
                                        {{ $journal->moneyAccount()?->name ?? '-' }}
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $journal->amount, 2) }}</td>
                                <td class="text-end"></td>
                            </tr>
                            <tr>
                                <td>{{ __('bank_journals.show.money_in') }}</td>
                                <td>
                                    @if ($journal->offsetChart)
                                        {{ $journal->offsetChart->code }} ~ {{ $journal->offsetChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end"></td>
                                <td class="text-end">{{ number_format((float) $journal->amount, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ __('bank_journals.show.money_out') }}</td>
                                <td>
                                    @if ($journal->offsetChart)
                                        {{ $journal->offsetChart->code }} ~ {{ $journal->offsetChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $journal->amount, 2) }}</td>
                                <td class="text-end"></td>
                            </tr>
                            <tr>
                                <td>{{ __('bank_journals.show.money_out') }}</td>
                                <td>
                                    @if ($journal->moneyAccount()?->chart)
                                        {{ $journal->moneyAccount()->chart->code }} ~
                                        {{ $journal->moneyAccount()->chart->name }}
                                    @else
                                        {{ $journal->moneyAccount()?->name ?? '-' }}
                                    @endif
                                </td>
                                <td class="text-end"></td>
                                <td class="text-end">{{ number_format((float) $journal->amount, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot class="bg-1 fw-bold border-top">
                        <tr>
                            <td colspan="2" class="text-end">{{ __('common.labels.total') }}:</td>
                            <td class="text-end">{{ number_format((float) $journal->amount, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $journal->amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($journal->description)
                <div class="mt-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('bank_journals.form.description') }}</span>
                    {{ $journal->description }}
                </div>
            @endif

            @if ($journal->notes)
                <div class="mt-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('ledgers.table.column_notes') }}</span>
                    {{ $journal->notes }}
                </div>
            @endif
        </div>
    </div>
@endsection
