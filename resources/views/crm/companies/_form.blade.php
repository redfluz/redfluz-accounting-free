@php
    use App\Enums\UploadType;
    use App\Models\Crm\Company;
@endphp
@props([
    /** @var Company|null */
    'company' => null,
])

<style>
    .company-form-top {
        display: grid;
        grid-template-columns: 220px minmax(0, 1fr);
        gap: 0 1.5rem;
    }
    .company-form-top .company-logo-field {
        grid-column: 1;
        grid-row: 1 / span 3;
    }
    .company-form-top .company-info-field {
        grid-column: 2;
    }
    @media (max-width: 767.98px) {
        .company-form-top {
            display: block;
        }
        .company-form-top .company-logo-field {
            margin-bottom: 1rem;
        }
    }
</style>

<div class="company-form-top">
    <div class="company-info-field mb-3">
        <label class="form-label fw-bold" for="name">
            {{ __('companies.table.column_name') }}
        </label>
        <div class="form-control form-adorn">
            <input
                class="form-ghost @error('name') is-invalid @enderror"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $company?->name) }}"
                required
            />
        </div>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="company-logo-field mb-3">
        <label class="form-label fw-bold" for="logo">{{ __('companies.logo.label') }}</label>
        <div
            class="rounded-5 bg-2 d-flex align-items-center justify-content-center mb-2 border p-2"
            id="logo-preview-container"
            style="height: 150px"
        >
            <span
                id="logo-preview-placeholder"
                class="fg-secondary small {{ $company?->logo_path ? 'd-none' : '' }}"
            ><i class="me-1" data-lucide="image"></i>{{ __('companies.logo.preview') }}</span>
            <img
                id="logo-preview"
                class="img-fluid {{ $company?->logo_path ? '' : 'd-none' }}"
                src="{{ $company?->logo_path ? asset('storage/' . $company->logo_path) : '#' }}"
                alt="{{ $company?->logo_path ? __('companies.logo.alt', ['company' => $company?->name]) : __('companies.logo.preview') }}"
                style="max-height: 100%; object-fit: contain"
            />
        </div>
        <input
            class="form-control @error('logo') is-invalid @enderror"
            id="logo"
            name="logo"
            type="file"
            accept="{{ UploadType::IMAGES->accept() }}"
        />
        <div class="form-text">{{ $company ? __('companies.logo.replace_hint') : __('companies.logo.upload_hint') }}</div>
        @error('logo')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="company-info-field mb-3">
        <label class="form-label fw-bold" for="email">
            {{ __('companies.table.column_email') }}
        </label>
        <div class="form-control form-adorn">
            <input
                class="form-ghost @error('email') is-invalid @enderror"
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $company?->email) }}"
            />
        </div>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="company-info-field mb-3">
        <label class="form-label fw-bold" for="phone">
            {{ __('companies.table.column_phone') }}
        </label>
        <div class="form-control form-adorn">
            <input
                class="form-ghost @error('phone') is-invalid @enderror"
                id="phone"
                name="phone"
                type="text"
                value="{{ old('phone', $company?->phone) }}"
            />
        </div>
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="address">
        {{ __('companies.table.column_address') }}
    </label>
    <textarea
        class="form-control @error('address') is-invalid @enderror"
        id="address"
        name="address"
        rows="3"
    >{{ old('address', $company?->address) }}</textarea>
    @error('address')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<fieldset class="mb-4" style="border: none; padding: 0; margin: 0;">
    <legend class="form-label fw-bold">{{ __('companies.roles.title') }}</legend>
    <div class="d-flex flex-wrap gap-4">
        @foreach (['is_debtor', 'is_creditor', 'is_courier'] as $role)
            <input name="{{ $role }}" type="hidden" value="0" />
            <input
                class="check"
                id="{{ $role }}"
                name="{{ $role }}"
                type="checkbox"
                value="1"
                @checked((bool) old($role, $company?->{$role}))
            />
            <label
                for="{{ $role }}"
            >{{ __('companies.roles.'.$role) }}</label>
        @endforeach
    </div>
</fieldset>

<script>
    document.getElementById('logo')?.addEventListener('change', function (event) {
        const file = event.target.files?.[0];
        const preview = document.getElementById('logo-preview');
        if (!file) return;
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
        document.getElementById('logo-preview-placeholder').classList.add('d-none');
    });
</script>
