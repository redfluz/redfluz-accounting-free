@php
    use App\Models\Crm\Contact;
@endphp
@props([
    /** @var Contact|null */
    'contact' => null,
])

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
                {{ old('company_id', $contact?->company_id) == $company->id ? 'selected' : '' }}
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
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $contact?->name) }}"
            required
        />
    </div>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="position">
        {{ __('contacts.table.column_position') }}
    </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('position') is-invalid @enderror"
            id="position"
            name="position"
            type="text"
            value="{{ old('position', $contact?->position) }}"
        />
    </div>
    @error('position')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="email">
        {{ __('contacts.table.column_email') }}
    </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('email') is-invalid @enderror"
            id="email"
            name="email"
            type="email"
            value="{{ old('email', $contact?->email) }}"
        />
    </div>
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="phone">
        {{ __('contacts.table.column_phone') }}
    </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('phone') is-invalid @enderror"
            id="phone"
            name="phone"
            type="text"
            value="{{ old('phone', $contact?->phone) }}"
        />
    </div>
    @error('phone')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
