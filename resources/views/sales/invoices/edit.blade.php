{{--
    ============================================================================
    Copyright (c) 2026 redfluz. All Rights Reserved.
    PROPRIETARY AND CONFIDENTIAL
    ============================================================================
--}}

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('invoices.form.edit_title') }}: {{ $documentNumber }}</h4>
                <span class="fg-secondary small"> {{ __('ledgers.workflow.' . $subtype->value . '.subtitle') }} </span>
            </div>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('sales.invoices.update', $documentNumber) }}" method="POST"
                  enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')
                @include('sales.invoices._form')
            </form>
        </div>
    </div>

    @include('sales.invoices._product-picker')
@endsection
