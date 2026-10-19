@props(['transfer', 'banks', 'transit', 'transitBalance'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold m-0">{{ __('bank_transfers.form.edit_title') }}</h5>
            <span class="fg-secondary small">{{ $transfer->document_number }}</span>
        </div>

        <form autocomplete="off" action="{{ route('bank.bank-transfers.update', $transfer->document_number) }}"
            method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('partials.alerts')

                @include('bank.bank-transfers._form')
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a class="btn btn-solid theme-secondary"
                    href="{{ route('bank.bank-transfers.show', $transfer->document_number) }}">
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
