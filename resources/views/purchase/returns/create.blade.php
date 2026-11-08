@props([
    'groupedReceipts',
    'postingDateMin' => null,
    'postingDateMax' => null,
])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('purchase_returns.title') }}</h4>
            <p class="fg-secondary small mb-0">{{ __('purchase_returns.subtitle') }}</p>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" method="POST" action="{{ route('purchase.returns.store') }}">
                @csrf
                @include('purchase.returns._form')
            </form>
        </div>
    </div>
@endsection
