@extends('layouts.app')

@section('content')
    <div class="card xl:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('taxes.form.edit_title') }}: {{ $tax->code }}</h4>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('finance.taxes.update', $tax) }}" method="POST">
                @csrf
                @method('PUT')
                <input name="id" type="hidden" value="{{ $tax->id }}" />
                @include('finance.taxes._form')
            </form>
        </div>
    </div>
@endsection
