@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canRestore = auth()->user()->hasFilePermission(FileResources::BANK_TRANSFERS_CANCELLED, FileAction::ADD);
@endphp

@props(['transfer', 'documentNumber'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">
                    {{ $transfer->transitChart?->code }} ~ {{ $transfer->transitChart?->name }}
                    &rarr;
                    {{ $transfer->bankAccount?->name ?? '-' }}
                </span>
            </div>
            <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
                <span class="badge theme-danger d-flex align-items-center">
                    {{ __('ledgers.status_cancelled') }}
                </span>
                @if ($canRestore)
                    <form autocomplete="off"
                        action="{{ route('bank.bank-transfers.cancelled.uncancel', $documentNumber) }}"
                        method="POST"
                        data-confirm-message="{{ __('common.messages.uncancel_confirm') }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm text-nowrap" type="submit">
                            <i class="me-1" data-lucide="rotate-ccw"></i>
                            {{ __('common.messages.uncancelled') }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.bank-transfers.cancelled.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('ledgers.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="row g-3">
                <div class="md:col-6">
                    <div class="fw-bold small fg-secondary">{{ __('bank_transfers.form.date') }}</div>
                    <div>{{ $transfer->date->format(Setting::getDefaultDate()) }}</div>
                </div>
                <div class="md:col-6">
                    <div class="fw-bold small fg-secondary">{{ __('bank_transfers.form.bank_account') }}</div>
                    <div>
                        {{ $transfer->bankAccount?->name ?? '-' }}
                        @if ($transfer->bankAccount?->chart)
                            <span class="fg-secondary">
                                ({{ $transfer->bankAccount->chart->code }})
                            </span>
                        @endif
                    </div>
                </div>
                <div class="md:col-6">
                    <div class="fw-bold small fg-secondary">{{ __('bank_transfers.form.from') }}</div>
                    <div>{{ $transfer->transitChart?->code }} ~ {{ $transfer->transitChart?->name }}</div>
                </div>
                <div class="md:col-6">
                    <div class="fw-bold small fg-secondary">{{ __('bank_transfers.form.amount') }}</div>
                    <div class="fw-bold fg-primary">
                        {{ number_format((float) $transfer->amount, 2) }}
                    </div>
                </div>

                @if ($transfer->reference_number)
                    <div class="md:col-6">
                        <div class="fw-bold small fg-secondary">{{ __('bank_transfers.form.reference_number') }}</div>
                        <div>{{ $transfer->reference_number }}</div>
                    </div>
                @endif

                @if ($transfer->description)
                    <div class="md:col-6">
                        <div class="fw-bold small fg-secondary">{{ __('bank_transfers.form.description') }}</div>
                        <div>{{ $transfer->description }}</div>
                    </div>
                @endif

                @if ($transfer->attachment_path)
                    <div class="col-12">
                        <hr />
                        <div class="fw-bold small fg-secondary">{{ __('bank_transfers.show.attachment') }}</div>
                        <div class="d-flex align-items-center justify-content-between rounded border p-2 mt-1">
                            <div class="text-truncate me-2">
                                <i class="me-1" data-lucide="paperclip"></i>
                                <span class="fw-semibold">{{ $transfer->attachment_name }}</span>
                            </div>
                            <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                                href="{{ route('bank.bank-transfers.attachment', $documentNumber) }}">
                                <i class="me-1" data-lucide="download"></i>
                                {{ __('common.actions.download') }}
                            </a>
                        </div>
                    </div>
                @endif

                @if ($transfer->notes)
                    <div class="col-12">
                        <div class="fw-bold small fg-secondary">{{ __('bank_transfers.form.notes') }}</div>
                        <div>{{ $transfer->notes }}</div>
                    </div>
                @endif

                <div class="col-12">
                    <hr />
                    <div class="fw-bold small fg-secondary">{{ __('bank_transfers.show.posting') }}</div>
                    <div class="small fg-secondary">{{ __('bank_transfers.show.not_posted') }}</div>
                </div>

                <div class="col-12">
                    <hr />
                    @include('bank.voucher.partials.audit', ['document' => $transfer])
                </div>
            </div>
        </div>
    </div>
@endsection