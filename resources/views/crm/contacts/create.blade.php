@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::CONTACTS, FileAction::ADD);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.add') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('crm.contacts.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold" for="company_id">
                        {{ __('contacts.table.column_company') }}
                    </label>
                    <select
                        class="form-control @error('company_id') is-invalid @enderror"
                        id="company_id"
                        name="company_id"
                        required
                    >
                        <option value="">-- Select Company --</option>
                        @foreach ($companies as $company)
                            <option
                                value="{{ $company->id }}"
                                {{ old('company_id', $selectedCompanyId) == $company->id ? 'selected' : '' }}
                            >
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="name">
                        {{ __('contacts.table.column_name') }}
                    </label>
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            required
                        />
                    </label>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="position">
                        {{ __('contacts.table.column_position') }}
                    </label>
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('position') is-invalid @enderror"
                            id="position"
                            name="position"
                            type="text"
                            value="{{ old('position') }}"
                        />
                    </label>
                    @error('position')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="email">
                        {{ __('contacts.table.column_email') }}
                    </label>
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                        />
                    </label>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="phone">
                        {{ __('contacts.table.column_phone') }}
                    </label>
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('phone') is-invalid @enderror"
                            id="phone"
                            name="phone"
                            type="text"
                            value="{{ old('phone') }}"
                        />
                    </label>
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('crm.contacts.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>
                    @if ($canCreate)
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
