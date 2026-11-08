@php use App\Models\System\Role; @endphp
@props([
    /** @var Role|null */
    'role' => null,
])

<div class="mb-3">
    <label class="form-label fw-bold" for="name"> {{ __('roles.table.column_name') }} </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $role?->name) }}"
            placeholder="e.g. Manager, Editor"
            required
        />
    </div>
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
        <option value="" disabled {{ old('dashboard', $role?->dashboard?->value) ? '' : 'selected' }}>
            -- Select Dashboard --
        </option>
        @foreach ($dashboards as $dashboard)
            <option
                value="{{ $dashboard->value }}"
                {{ old('dashboard', $role?->dashboard?->value) === $dashboard->value ? 'selected' : '' }}
            >
                {{ $dashboard->label() }}
            </option>
        @endforeach
    </select>
    @error('dashboard')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
