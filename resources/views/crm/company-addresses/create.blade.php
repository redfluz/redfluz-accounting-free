@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">
                {{ __('companies.addresses.create_title', ['company' => $company?->name ?? __('companies.addresses.company_placeholder')]) }}
            </h4>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off"
                action="{{ $company ? route('crm.company-addresses.store', $company) : route('crm.company-addresses.store-global') }}"
                method="POST"
            >
                @csrf
                @include('crm.company-addresses._form', ['companyAddress' => null])
            </form>
        </div>
    </div>
@endsection
