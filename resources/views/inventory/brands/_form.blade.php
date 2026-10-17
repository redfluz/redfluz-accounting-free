<div class="mb-3">
    <label class="form-label fw-bold" for="name">
        {{ __('brands.form.name') }} <span class="fg-danger">*</span>
    </label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', isset($brand) ? $brand->name : '') }}"
            required
        />
    </label>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="description">{{ __('brands.form.description') }}</label>
    <textarea
        class="form-control @error('description') is-invalid @enderror"
        id="description"
        name="description"
        rows="4"
    >{{ old('description', isset($brand) ? $brand->description : '') }}</textarea>
    @error('description')
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
        @checked(old('is_active', isset($brand) ? $brand->is_active : true))
        role="switch" switch />
    </div>
    <label class="fw-bold" for="is_active">{{ __('brands.form.is_active') }}</label>
</div>
