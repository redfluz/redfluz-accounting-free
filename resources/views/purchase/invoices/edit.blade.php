{{--
    ============================================================================
    Copyright (c) 2026 redfluz. All Rights Reserved.
    PROPRIETARY AND CONFIDENTIAL
    ============================================================================
--}}

@props([
    'companies',
    'contacts',
    'charts',
    'subtype',
    'indexRoute',
    'documentNumber',
    'invoice' => null,
    'sourcePurchaseOrder' => null,
    'canQuickCreateCompany',
])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">
                    {{ __('common.actions.edit') }} {{ __('ledgers.sub_type.purchase_invoice') }}
                </h4>
                <span class="fg-secondary small"> {{ __('ledgers.workflow.' . $subtype->value . '.subtitle') }} </span>
            </div>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" class="d-flex flex-column" action="{{ route('purchase.invoices.update', $documentNumber) }}" method="POST"
                enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')
                <input name="id" type="hidden" value="{{ $documentNumber }}" />
                @include('purchase.invoices._form', [
                    'purchaseInvoice' => null,
                    'sourcePurchaseOrder' => $sourcePurchaseOrder ?? null,
                ])
            </form>
        </div>
    </div>
@endsection
