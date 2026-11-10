{{--
 ============================================================================
 Copyright (c) 2026 redfluz. All Rights Reserved.
 PROPRIETARY AND CONFIDENTIAL
 ============================================================================
--}}

@php
    use App\Models\Finance\ManualPurchaseAdjustment;
@endphp

@props(['adjustment', 'companies', 'contacts', 'charts'])

@extends('layouts.app')

@section('content')
    @php
        /** @var ManualPurchaseAdjustment $adjustment */
        $documentNumber = $adjustment->document_number;
    @endphp
    <div class="card shadow-sm">
        <div class="card-header bg-1 py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('manual_purchase_adjustments.edit_title') }} - {{ $documentNumber }}</h4>
                <span class="fg-secondary small">{{ __('manual_purchase_adjustments.index_subtitle') }}</span>
            </div>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('purchase.manual-adjustments.update', $documentNumber) }}" method="POST"
                enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                @include('purchase.manual-adjustments._form')

                <div class="d-flex justify-content-end gap-2">
                    <a class="btn btn-solid theme-secondary"
                        href="{{ route('purchase.manual-adjustments.show', $documentNumber) }}">
                        {{ __('common.actions.cancel') }}
                    </a>
                    <button class="btn btn-solid theme-primary" type="submit">
                        <i class="me-1" data-lucide="save"></i>{{ __('common.actions.update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('purchase.manual-adjustments._coa-picker')
@endsection
