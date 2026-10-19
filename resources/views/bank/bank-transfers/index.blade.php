@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $resource = FileResources::BANK_TRANSFERS;
    $canAdd = auth()->user()->hasFilePermission($resource, FileAction::ADD);
    $canApprove = auth()->user()->hasFilePermission($resource, FileAction::APPROVE);
    $canCancel = auth()->user()->hasFilePermission($resource, FileAction::CANCEL);
@endphp

@props(['transfers', 'search', 'status'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('bank_transfers.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('bank_transfers.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
                @if ($canAdd)
                    <a class="btn btn-solid theme-success btn-sm" href="{{ route('bank.bank-transfers.create') }}">
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
                            href="{{ route('bank.bank-transfers.index', ['status' => $tab]) }}">
                            {{ __('bank_transfers.tabs.' . $tab) }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <form autocomplete="off" action="{{ route('bank.bank-transfers.index') }}" method="GET" class="mb-4">
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
                            href="{{ route('bank.bank-transfers.index', ['status' => $status]) }}">
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
                            <th>{{ __('bank_transfers.table.column_from') }}</th>
                            <th>{{ __('bank_transfers.table.column_to') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $transfer)
                            <tr>
                                <td class="fw-bold">{{ $transfer->document_number }}</td>
                                <td>{{ $transfer->document_date->format(Setting::getDefaultDate()) }}</td>
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
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-sm btn-solid theme-info me-1"
                                            href="{{ route('bank.bank-transfers.show', $transfer->document_number) }}"
                                            title="{{ __('common.actions.view') }}">
                                            <i data-lucide="eye"></i>
                                        </a>

                                        @if ($transfer->isEditable() && $canApprove)
                                            <form autocomplete="off"
                                                action="{{ route('bank.bank-transfers.approve', $transfer->document_number) }}"
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
                                                action="{{ route('bank.bank-transfers.cancel', $transfer->document_number) }}"
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
                                <td colspan="6" class="text-center">
                                    {{ __('ledgers.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($transfers->count() > 0)
                        <tfoot class="bg-1 fw-bold border-top">
                            <tr>
                                <td class="text-end" colspan="4">
                                    {{ __('common.labels.total') }}:
                                </td>
                                <td class="fg-primary text-end">
                                    {{ number_format((float) $transfers->sum('amount'), 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $transfers->links() }}</div>
        </div>
    </div>
@endsection
