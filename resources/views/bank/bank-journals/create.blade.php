@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold m-0">{{ __('bank_journals.form.create_title') }}</h5>
            <span class="fg-secondary small">{{ $documentNumber }}</span>
        </div>

        <form autocomplete="off" action="{{ route('bank.bank-journals.store') }}" method="POST">
            @csrf
            <div class="card-body">
                @include('partials.alerts')

                @include('bank.bank-journals._form', ['journal' => null])
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a class="btn btn-solid theme-secondary" href="{{ route('bank.bank-journals.index') }}">
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
