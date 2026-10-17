@extends('layouts.app')

@section('content')
    <div class="card xl:col-11 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">
                {{ __('purchase_orders.form.edit_title') }}: {{ $purchaseOrder->document_number }}
            </h4>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off"
                class="d-flex flex-column"
                action="{{ route('purchase.purchase-orders.update', $purchaseOrder) }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                @method('PUT')
                @include('purchase.purchase-orders._form')
            </form>
        </div>
    </div>
@endsection
