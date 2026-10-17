@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::ROLES, FileAction::ADD);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.add') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('system.roles.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold" for="name"> {{ __('roles.table.column_name') }} </label>
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            placeholder="e.g. Manager, Editor"
                            required
                        />
                    </label>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="dashboard">
                        {{ __('roles.table.column_dashboard') }}
                    </label>
                    <select
                        class="form-control @error('dashboard') is-invalid @enderror"
                        id="dashboard"
                        name="dashboard"
                        required
                    >
                        <option value="" disabled {{ old('dashboard') ? '' : 'selected' }}>
                            -- Select Dashboard --
                        </option>
                        @foreach ($dashboards as $dashboard)
                            <option
                                value="{{ $dashboard->value }}"
                                {{ old('dashboard') === $dashboard->value ? 'selected' : '' }}
                            >
                                {{ $dashboard->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('dashboard')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('system.roles.index') }}">
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
