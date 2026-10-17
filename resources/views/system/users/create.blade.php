@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::USERS, FileAction::ADD);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.add') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('system.users.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold" for="name"> {{ __('users.table.column_name') }} </label>
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
                    <label class="form-label fw-bold" for="email">
                        {{ __('users.table.column_email') }}
                    </label>
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                        />
                    </label>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert theme-warning" data-testid="default-password-notice" role="alert">
                    <i class="me-1" data-lucide="triangle-alert"></i>
                    {!! __('users.default_password_notice', ['password' => '<code>' . e($defaultPassword) . '</code>']) !!}
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('system.users.index') }}">
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
