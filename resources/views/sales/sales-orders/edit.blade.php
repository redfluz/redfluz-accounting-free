@extends('layouts.app')

@section('content')
    <div class="card xl:col-11 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">
                {{ __('sales_orders.form.edit_title') }}: {{ $salesOrder->document_number }}
            </h4>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off"
                action="{{ route('sales.sales-orders.update', $salesOrder) }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                @method('PUT')
                <input name="id" type="hidden" value="{{ $salesOrder->id }}" />
                @include('sales.sales-orders._form')
            </form>
        </div>
    </div>
@endsection
