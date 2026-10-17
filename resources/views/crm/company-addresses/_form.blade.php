@if ($companyAddress === null)
    <div class="mb-3">
        <label class="form-label fw-bold" for="company_id">{{ __('companies.addresses.fields.company') }}</label>
        @if ($company !== null)
            <label class="form-control form-adorn">
                <input class="form-ghost" value="{{ $company->name }}" readonly />
            </label>
            <input name="company_id" type="hidden" value="{{ $company->id }}" />
        @else
            <select
                class="form-control @error('company_id') is-invalid @enderror"
                id="company_id"
                name="company_id"
                required
            >
                <option value="">{{ __('common.actions.select_option') }}</option>
                @foreach ($companies as $companyOption)
                    <option value="{{ $companyOption->id }}" @selected(old('company_id') === $companyOption->id)>
                        {{ $companyOption->name }}
                    </option>
                @endforeach
            </select>
            @error('company_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
    </div>
@endif
<div class="mb-3">
    <label class="form-label fw-bold" for="name">{{ __('companies.addresses.fields.name') }}</label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            value="{{ old('name', $companyAddress?->name) }}"
            required
        />
    </label>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label class="form-label fw-bold" for="phone">{{ __('companies.addresses.fields.phone') }}</label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('phone') is-invalid @enderror"
            id="phone"
            name="phone"
            value="{{ old('phone', $companyAddress?->phone) }}"
        />
    </label>
    @error('phone')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label class="form-label fw-bold" for="address">{{ __('companies.addresses.fields.address') }}</label>
    <textarea
        class="form-control @error('address') is-invalid @enderror"
        id="address"
        name="address"
        rows="4"
        required
    >{{ old('address', $companyAddress?->address) }}</textarea>
    @error('address')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-4">
<input name="is_default" type="hidden" value="0" />
    <input
        class="check"
        id="is_default"
        name="is_default"
        type="checkbox"
        value="1"
        @checked((bool) old('is_default', $companyAddress?->is_default ?? false))
    />
    <label for="is_default">{{ __('companies.addresses.fields.is_default') }}</label>
</div>
<div class="d-flex justify-content-between">
    <a
        class="btn btn-solid theme-secondary"
        href="{{ route('crm.company-addresses.index', $company ? ['company' => $company->id] : []) }}"
    >
        <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
    </a>
    <button class="btn btn-solid theme-accent" type="submit">{{ __('common.actions.save') }}</button>
</div>
