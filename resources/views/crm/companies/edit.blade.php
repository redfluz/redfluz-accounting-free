@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::UPDATE);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.edit') }}</h4>
            <a
                class="btn btn-solid theme-primary btn-sm"
                href="{{ route('crm.company-addresses.index', ['company' => $company->id]) }}"
            >
                <i class="me-1" data-lucide="map-pin-house"></i>
                {{ __('companies.addresses.actions.manage') }}
            </a>
        </div>

        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('crm.companies.update', $company) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <input name="id" type="hidden" value="{{ $company->id }}" />

                @include('crm.companies._form')

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('crm.companies.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>
                    @if ($canUpdate)
                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>
                            {{ __('common.actions.save') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
