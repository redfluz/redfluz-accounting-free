@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::CURRENCIES, FileAction::ADD);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.add') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('finance.currencies.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold" for="code">
                        {{ __('currencies.table.column_code') }}
                    </label>
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('code') is-invalid @enderror"
                            id="code"
                            name="code"
                            type="text"
                            value="{{ old('code') }}"
                            placeholder="e.g. USD"
                            required
                        />
                    </label>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="name">
                        {{ __('currencies.table.column_name') }}
                    </label>
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            placeholder="e.g. US Dollar"
                            required
                        />
                    </label>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="symbol">
                        {{ __('currencies.table.column_symbol') }}
                    </label>
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('symbol') is-invalid @enderror"
                            id="symbol"
                            name="symbol"
                            type="text"
                            value="{{ old('symbol') }}"
                            placeholder="e.g. $"
                        />
                    </label>
                    @error('symbol')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
<input
                        class="check"
                        id="is_default"
                        name="is_default"
                        type="checkbox"
                        value="1"
                        {{ old('is_default') ? 'checked' : '' }}
                    />
                    <label for="is_default">
                        {{ __('currencies.table.column_default') }}
                    </label>
                </div>

                <div class="mb-3">
<input
                        class="check"
                        id="is_active"
                        name="is_active"
                        type="checkbox"
                        value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}
                    />
                    <label for="is_active"> {{ __('currencies.table.column_active') }} </label>
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('finance.currencies.index') }}">
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
