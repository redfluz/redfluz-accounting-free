@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::USERS, FileAction::UPDATE);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.edit') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('system.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-bold" for="name"> {{ __('users.table.column_name') }} </label>
                    <label class="form-control form-adorn">
                        <input class="form-ghost @error('name') is-invalid @enderror" id="name" name="name"
                            type="text" value="{{ old('name', $user->name) }}" required />
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
                        <input class="form-ghost @error('email') is-invalid @enderror" id="email" name="email"
                            type="email" value="{{ old('email', $user->email) }}" required />
                    </label>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Role Dropdown -->
                <div class="mb-3">
                    <label class="form-label fw-bold" for="role_id">
                        {{ __('users.table.column_role') }}
                    </label>
                    <select class="form-control @error('role_id') is-invalid @enderror" id="role_id" name="role_id"
                        required>
                        <option value="">{{ __('common.actions.select_option') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}"
                                {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="password">
                        {{ __('users.table.column_password') }}
                        <small class="badge theme-secondary"> ( {{ __('common.labels.leave_blank_password') }}) </small>
                    </label>
                    <label class="form-control form-adorn">
                        <input class="form-ghost @error('password') is-invalid @enderror" id="password" name="password"
                            type="password" />
                    </label>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('system.users.index') }}">
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
