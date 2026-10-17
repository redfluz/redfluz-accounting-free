@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::BANKS, FileAction::UPDATE);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card xl:col-10 mx-auto shadow-sm">
        <div class="card-header bg-white py-3 d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center rounded-5" style="width:40px;height:40px;background:color-mix(in oklch, var(--primary-base) 10%, transparent)">
                <i class="fs-lg" data-lucide="building-2" style="color:var(--primary-base)"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0">{{ __('banks.edit_title') }}</h4>
                <span class="fg-secondary small">{{ $bank->name ?? '' }}</span>
            </div>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('bank.banks.update', $bank) }}" method="POST">
                @csrf
                @method('PUT')
                <input name="id" type="hidden" value="{{ $bank->id }}" />
                @include('bank.banks._fields', ['bank' => $bank, 'charts' => $charts])
                <div class="d-flex justify-content-between pt-4 border-top mt-4">
                    <a class="btn btn-solid theme-secondary" href="{{ route('bank.banks.show', $bank) }}"
                        ><i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a>
                    @if ($canUpdate)
                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>{{ __('common.actions.save') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
