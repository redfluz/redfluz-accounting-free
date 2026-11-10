{{--
 ============================================================================
 Copyright (c) 2026 redfluz. All Rights Reserved.
 PROPRIETARY AND CONFIDENTIAL
 ============================================================================
--}}

@props(['companies', 'contacts', 'charts'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-1 py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('manual_sales_adjustments.create_title') }}</h4>
                <span class="fg-secondary small">{{ __('manual_sales_adjustments.index_subtitle') }}</span>
            </div>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('sales.manual-adjustments.store') }}" method="POST"
                enctype="multipart/form-data" novalidate>
                @csrf

                @include('sales.manual-adjustments._form')

                <div class="d-flex justify-content-end gap-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('sales.manual-adjustments.index') }}">
                        {{ __('common.actions.cancel') }}
                    </a>
                    <button class="btn btn-solid theme-primary" type="submit">
                        <i class="me-1" data-lucide="save"></i>{{ __('common.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('sales.manual-adjustments._coa-picker')
@endsection
