@php use App\Models\System\User; @endphp
@props([
    /** @var User|null */
    'user' => null,
])

<div class="mb-3">
    <label class="form-label fw-bold" for="name"> {{ __('users.table.column_name') }} </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $user?->name) }}"
            required
        />
    </div>
    @error('name')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="email">
        {{ __('users.table.column_email') }}
    </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('email') is-invalid @enderror"
            id="email"
            name="email"
            type="email"
            value="{{ old('email', $user?->email) }}"
            required
        />
    </div>
    @error('email')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@if (!$user)
    <div class="alert alert theme-warning" data-testid="default-password-notice" role="alert">
        <i class="me-1" data-lucide="triangle-alert"></i>
        {!! __('users.default_password_notice', ['password' => '<code>' . e($defaultPassword) . '</code>']) !!}
    </div>
@endif

@if (isset($roles))
    <div class="mb-3">
        <label class="form-label fw-bold" for="role_id">
            {{ __('users.table.column_role') }}
        </label>
        <select class="form-control @error('role_id') is-invalid @enderror" id="role_id" name="role_id"
                required>
            <option value="">{{ __('common.actions.select_option') }}</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}"
                    {{ old('role_id', $user?->role_id) == $role->id ? 'selected' : '' }}>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
        @error('role_id')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
@endif

<div class="mb-3">
    <label class="form-label fw-bold" for="password">
        {{ __('users.table.column_password') }}
        @if ($user)
            <small class="badge theme-secondary"> ( {{ __('common.labels.leave_blank_password') }}) </small>
        @endif
    </label>
    <div class="form-control form-adorn">
        <input class="form-ghost @error('password') is-invalid @enderror" id="password" name="password"
               type="password" @if (!$user) required @endif />
    </div>
    @error('password')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
