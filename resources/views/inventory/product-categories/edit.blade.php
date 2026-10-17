@props(['productCategory'])

@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('product_categories.form.edit_title') }}</h4>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('inventory.product-categories.update', $productCategory) }}" method="POST">
                @csrf
                @method('PUT')
                <input name="id" type="hidden" value="{{ $productCategory->id }}" />
                @include('inventory.product-categories._form', ['productCategory' => $productCategory])
                <div class="d-flex justify-content-between">
                    <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('inventory.product-categories.index') }}"
                        ><i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a>
                    <button class="btn btn-solid theme-accent btn-sm" type="submit">
                        <i class="me-1" data-lucide="save"></i>{{ __('common.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
