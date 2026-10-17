@props(['productCategory' => null])

<div class="mb-3">
    <label class="form-label fw-bold" for="code">
        {{ __('product_categories.form.code') }} <span class="fg-danger">*</span>
    </label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('code') is-invalid @enderror"
            id="code"
            name="code"
            type="text"
            value="{{ old('code', $productCategory?->code) }}"
            required
        />
    </label>
    @error('code')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="name">
        {{ __('product_categories.form.name') }} <span class="fg-danger">*</span>
    </label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $productCategory?->name) }}"
            required
        />
    </label>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-4">
<input name="is_active" type="hidden" value="0" />
    <div class="switch">
    <input
        id="is_active"
        name="is_active"
        type="checkbox"
        value="1"
        @checked(old('is_active', $productCategory?->is_active ?? true))
        role="switch" switch />
    </div>
    <label class="fw-bold"
        for="is_active"
    >{{ __('product_categories.form.is_active') }}</label>
</div>
