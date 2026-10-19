@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold m-0">{{ __('bank_transfers.form.create_title') }}</h5>
            <span class="fg-secondary small">{{ $documentNumber }}</span>
        </div>

        <form autocomplete="off" action="{{ route('bank.bank-transfers.store') }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                @include('partials.alerts')

                @include('bank.bank-transfers._form', ['transfer' => null])
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a class="btn btn-solid theme-secondary" href="{{ route('bank.bank-transfers.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('ledgers.back') }}
                </a>
                <button class="btn btn-solid theme-primary" type="submit">
                    <i class="me-1" data-lucide="save"></i>
                    {{ __('common.actions.save') }}
                </button>
            </div>
        </form>
    </div>
@endsection
