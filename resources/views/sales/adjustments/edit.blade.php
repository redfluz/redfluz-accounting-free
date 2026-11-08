@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::SALES_ADJUSTMENTS, FileAction::ADD);
@endphp

@props(['companies', 'salesAdjustment'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('common.actions.edit') }}
            </h4>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" class="d-flex flex-column" action="{{ route('sales.adjustments.update', $salesAdjustment->document_number) }}" method="POST"
                enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')
                @include('sales.adjustments._form', [
                    'salesAdjustment' => $salesAdjustment,
                ])
            </form>
        </div>
    </div>
@endsection
