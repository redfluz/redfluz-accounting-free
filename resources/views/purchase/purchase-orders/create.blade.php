@extends('layouts.app')

@section('content')
    <div class="card xl:col-11 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('purchase_orders.form.create_title') }}</h4>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off"
                class="d-flex flex-column"
                action="{{ route('purchase.purchase-orders.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                @include('purchase.purchase-orders._form', ['purchaseOrder' => null])
            </form>
        </div>
    </div>
@endsection
