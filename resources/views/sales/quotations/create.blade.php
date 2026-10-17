@extends('layouts.app')

@section('content')
    <div class="card xl:col-11 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('quotations.form.create_title') }}</h4>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('sales.quotations.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                @include('sales.quotations._form')
            </form>
        </div>
    </div>
@endsection
