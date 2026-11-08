@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::SALES_ADJUSTMENTS, FileAction::ADD);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.add') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" id="adjustmentForm" action="{{ route('sales.adjustments.store') }}" method="POST"
                enctype="multipart/form-data" novalidate>
                @csrf
                @include('sales.adjustments._form')
            </form>
        </div>
    </div>
@endsection
