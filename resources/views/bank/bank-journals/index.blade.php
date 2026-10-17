@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $resource = FileResources::BANK_JOURNALS;
    $canAdd = auth()->user()->hasFilePermission($resource, FileAction::ADD);
    $canApprove = auth()->user()->hasFilePermission($resource, FileAction::APPROVE);
    $canCancel = auth()->user()->hasFilePermission($resource, FileAction::CANCEL);
@endphp

@props(['journals', 'search', 'status'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('bank_journals.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('bank_journals.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
                @if ($canAdd)
                    <a class="btn btn-solid theme-success btn-sm" href="{{ route('bank.bank-journals.create') }}">
                        <i class="me-1" data-lucide="plus"></i>
                        {{ __('common.actions.add') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <ul class="nav nav-tabs mb-4">
                @foreach (['pending', 'posted', 'cancelled'] as $tab)
                    <li class="nav-item">
                        <a class="nav-link {{ $status === $tab ? 'active' : '' }}"
                            href="{{ route('bank.bank-journals.index', ['status' => $tab]) }}">
                            {{ __('bank_journals.tabs.' . $tab) }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <form autocomplete="off" action="{{ route('bank.bank-journals.index') }}" method="GET" class="mb-4">
                <input name="status" type="hidden" value="{{ $status }}" />
                <div class="row g-2 align-items-center">
                    <div class="md:col-9">
                        <input class="form-control" name="search" type="text" value="{{ $search }}"
                            placeholder="{{ __('common.actions.search') }}" />
                    </div>
                    <div class="md:col-3 d-flex gap-2">
                        <button class="btn btn-solid theme-primary flex-fill" type="submit">
                            <i class="me-1" data-lucide="filter"></i>
                            {{ __('common.actions.search') }}
                        </button>
                        <a class="btn btn-outline flex-fill border"
                            href="{{ route('bank.bank-journals.index', ['status' => $status]) }}">
                            <i class="me-1" data-lucide="rotate-ccw"></i>
                            {{ __('common.actions.reset') }}
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('ledgers.table.column_document_number') }}</th>
                            <th>{{ __('ledgers.table.column_date') }}</th>
                            <th>{{ __('bank_journals.table.column_direction') }}</th>
                            <th>{{ __('bank_journals.table.column_account') }}</th>
                            <th>{{ __('bank_journals.table.column_offset') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($journals as $journal)
                            <tr>
                                <td class="fw-bold">{{ $journal->document_number }}</td>
                                <td>{{ $journal->document_date->format(Setting::getDefaultDate()) }}</td>
                                <td>
                                    @if ($journal->direction === 'in')
                                        <span class="badge theme-success d-inline-flex align-items-center">
                                            {{ __('bank_journals.form.direction_in') }}
                                        </span>
                                    @else
                                        <span class="badge theme-warning d-inline-flex align-items-center">
                                            {{ __('bank_journals.form.direction_out') }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $journal->moneyAccount()?->name ?? '-' }}</td>
                                <td>
                                    @if ($journal->offsetChart)
                                        {{ $journal->offsetChart->code }} ~ {{ $journal->offsetChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $journal->amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-sm btn-solid theme-info me-1"
                                            href="{{ route('bank.bank-journals.show', $journal->document_number) }}"
                                            title="{{ __('common.actions.view') }}">
                                            <i data-lucide="eye"></i>
                                        </a>

                                        @if ($journal->isEditable() && $canApprove)
                                            <form autocomplete="off"
                                                action="{{ route('bank.bank-journals.approve', $journal->document_number) }}"
                                                method="POST"
                                                data-confirm-message="{{ __('common.alerts.confirm_batch_approve') }}"
                                                data-confirm-tone="danger">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-solid theme-success" type="submit"
                                                    title="{{ __('common.actions.approve') }}">
                                                    <i data-lucide="book-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if ($status === 'pending' && $canCancel)
                                            <form autocomplete="off"
                                                action="{{ route('bank.bank-journals.cancel', $journal->document_number) }}"
                                                method="POST"
                                                data-confirm-message="{{ __('common.alerts.confirm_batch_cancel') }}"
                                                data-confirm-tone="danger">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-solid theme-warning" type="submit"
                                                    title="{{ __('common.actions.cancel') }}">
                                                    <i data-lucide="ban"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    {{ __('ledgers.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($journals->count() > 0)
                        <tfoot class="bg-1 fw-bold border-top">
                            <tr>
                                <td class="text-end" colspan="5">
                                    {{ __('common.labels.total') }}:
                                </td>
                                <td class="fg-primary text-end">
                                    {{ number_format((float) $journals->sum('amount'), 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $journals->links() }}</div>
        </div>
    </div>
@endsection
