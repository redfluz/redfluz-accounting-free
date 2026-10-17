@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('companies.addresses.edit_title', ['company' => $company->name]) }}</h4>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('crm.company-addresses.update', $companyAddress) }}" method="POST">
                @csrf
                @method('PUT')
                @include('crm.company-addresses._form')
            </form>
        </div>
    </div>
@endsection
