@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::PURCHASE_ADJUSTMENTS, FileAction::ADD);
@endphp

@props(['companies', 'purchaseAdjustment'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('common.actions.edit') }} {{ __('ledgers.sub_type.purchase_debit_note') }}
            </h4>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" class="d-flex flex-column" action="{{ route('purchase.adjustments.update', $purchaseAdjustment->document_number) }}" method="POST"
                enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')
                @include('purchase.adjustments._form', [
                    'purchaseAdjustment' => $purchaseAdjustment,
                ])
            </form>
        </div>
    </div>
@endsection
