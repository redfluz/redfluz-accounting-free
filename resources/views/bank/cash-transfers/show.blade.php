@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $resource = FileResources::CASH_TRANSFERS;
    $canUpdate = auth()->user()->hasFilePermission($resource, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission($resource, FileAction::REMOVE);
    $canApprove = auth()->user()->hasFilePermission($resource, FileAction::APPROVE);
    $canCancel = auth()->user()->hasFilePermission($resource, FileAction::CANCEL);
@endphp

@props(['transfer'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $transfer->document_number }}</h4>
                <span class="fg-secondary small">
                    {{ $transfer->cashAccount?->name ?? '-' }}
                    &rarr;
                    {{ $transfer->transitChart?->code }} ~ {{ $transfer->transitChart?->name }}
                </span>
            </div>
            <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
                @if ($transfer->isPosted())
                    <span class="badge theme-success d-flex align-items-center">
                        {{ __('journal_entries.status_approved') }}
                    </span>
                @elseif ($transfer->isCancelled())
                    <span class="badge theme-danger d-flex align-items-center">
                        {{ __('ledgers.status_cancelled') }}
                    </span>
                @else
                    @if ($canApprove)
                        <form autocomplete="off" action="{{ route('bank.cash-transfers.approve', $transfer->document_number) }}"
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
                            href="{{ route('bank.cash-transfers.edit', $transfer->document_number) }}">
                            <i class="me-1" data-lucide="pencil"></i>
                            {{ __('common.actions.edit') }}
                        </a>
                    @endif
                    @if ($canCancel)
                        <form autocomplete="off" action="{{ route('bank.cash-transfers.cancel', $transfer->document_number) }}"
                            method="POST">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-solid theme-warning btn-sm text-nowrap" type="submit">
                                <i class="me-1" data-lucide="ban"></i>
                                {{ __('common.actions.cancel') }}
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="row g-3">
                <div class="md:col-6">
                    <div class="fw-bold small fg-secondary">{{ __('cash_transfers.form.date') }}</div>
                    <div>{{ $transfer->document_date->format(Setting::getDefaultDate()) }}</div>
                </div>
                <div class="md:col-6">
                    <div class="fw-bold small fg-secondary">{{ __('cash_transfers.form.cash_account') }}</div>
                    <div>
                        {{ $transfer->cashAccount?->name ?? '-' }}
                        @if ($transfer->cashAccount?->chart)
                            <span class="fg-secondary">
                                ({{ $transfer->cashAccount->chart->code }})
                            </span>
                        @endif
                    </div>
                </div>
                <div class="md:col-6">
                    <div class="fw-bold small fg-secondary">{{ __('cash_transfers.form.to') }}</div>
                    <div>{{ $transfer->transitChart?->code }} ~ {{ $transfer->transitChart?->name }}</div>
                </div>
                <div class="md:col-6">
                    <div class="fw-bold small fg-secondary">{{ __('cash_transfers.form.amount') }}</div>
                    <div class="fw-bold fg-primary">
                        {{ number_format((float) $transfer->amount, 2) }}
                    </div>
                </div>

                @if ($transfer->reference_number)
                    <div class="md:col-6">
                        <div class="fw-bold small fg-secondary">{{ __('cash_transfers.form.reference_number') }}</div>
                        <div>{{ $transfer->reference_number }}</div>
                    </div>
                @endif

                @if ($transfer->description)
                    <div class="md:col-6">
                        <div class="fw-bold small fg-secondary">{{ __('cash_transfers.form.description') }}</div>
                        <div>{{ $transfer->description }}</div>
                    </div>
                @endif

                @if ($transfer->notes)
                    <div class="col-12">
                        <div class="fw-bold small fg-secondary">{{ __('cash_transfers.form.notes') }}</div>
                        <div>{{ $transfer->notes }}</div>
                    </div>
                @endif

                <div class="col-12">
                    <hr />
                    <div class="fw-bold small fg-secondary">{{ __('cash_transfers.show.posting') }}</div>
                    @if ($transfer->isPosted())
                        <div class="small">
                            {{ __('cash_transfers.show.posted_explanation') }}
                        </div>
                        <div class="mt-2 small fg-secondary">
                            {{ __('cash_transfers.show.debit') }}:
                            {{ $transfer->transitChart?->name }},
                            {{ __('cash_transfers.show.credit') }}:
                            {{ $transfer->cashAccount?->name }}
                        </div>
                    @else
                        <div class="small fg-secondary">{{ __('cash_transfers.show.not_posted') }}</div>
                    @endif
                </div>

                @if ($transfer->isPosted() && $transfer->approved_at)
                    <div class="col-12">
                        <hr />
                        @include('bank.voucher.partials.audit', ['document' => $transfer])
                    </div>
                @endif
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a class="btn btn-solid theme-secondary" href="{{ route('bank.cash-transfers.index') }}">
                <i class="me-1" data-lucide="arrow-left"></i>
                {{ __('ledgers.back') }}
            </a>
            @if ($transfer->status === \App\Enums\LedgerStatus::PENDING && $canRemove)
                <form autocomplete="off" action="{{ route('bank.cash-transfers.destroy', $transfer->document_number) }}"
                    method="POST" data-confirm-message="{{ __('common.alerts.confirm_delete') }}"
                    data-confirm-tone="danger">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-solid theme-danger" type="submit">
                        <i class="me-1" data-lucide="trash-2"></i>
                        {{ __('common.actions.delete') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
