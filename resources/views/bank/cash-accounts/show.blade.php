@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::BANKS, FileAction::UPDATE);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-5"
                    style="width:40px;height:40px;background:color-mix(in oklch, var(--primary-base) 10%, transparent)">
                    <i class="fs-lg" data-lucide="wallet" style="color:var(--primary-base)"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0">{{ $cashAccount->name }}</h4>
                </div>
            </div>
            <div class="d-flex gap-2">
                @if ($canUpdate)
                    <a class="btn btn-solid theme-warning btn-sm" href="{{ route('bank.cash-accounts.edit', $cashAccount) }}"><i
                            class="me-1" data-lucide="square-pen"></i>{{ __('common.actions.edit') }}</a>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.cash-accounts.index') }}"><i class="me-1"
                        data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <h6 class="fw-bold mb-3">{{ __('banks.details_title') }}</h6>
            @php
                $details = [
                    __('banks.fields.chart') => ($cashAccount->chart?->code ?? '-') . ' - ' . ($cashAccount->chart?->name ?? '-'),
                    __('banks.fields.asset_location') => $cashAccount->assetLocation?->name,
                ];
            @endphp
            <div class="row g-3">
                @foreach ($details as $label => $value)
                    <div class="md:col-6">
                        <div class="rounded-5 h-100 border p-3">
                            <div class="fg-secondary small">{{ $label }}</div>
                            <div class="fw-bold">{{ $value ?: '-' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
