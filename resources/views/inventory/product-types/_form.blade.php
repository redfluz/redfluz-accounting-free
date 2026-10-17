@props(['productType' => null, 'categories'])

<div class="mb-3">
    <label class="form-label fw-bold" for="product_category_id">
        {{ __('product_types.form.category') }} <span class="fg-danger">*</span>
    </label>
    <select
        class="form-control @error('product_category_id') is-invalid @enderror"
        id="product_category_id"
        name="product_category_id"
        required
    >
        <option value="">{{ __('product_types.form.select_category') }}</option>
        @foreach ($categories as $category)
            <option
                value="{{ $category->id }}"
                @selected(old('product_category_id', $productType?->product_category_id) === $category->id)
            >
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('product_category_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="code">
        {{ __('product_types.form.code') }} <span class="fg-danger">*</span>
    </label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('code') is-invalid @enderror"
            id="code"
            name="code"
            type="text"
            value="{{ old('code', $productType?->code) }}"
            required
        />
    </label>
    @error('code')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="name">
        {{ __('product_types.form.name') }} <span class="fg-danger">*</span>
    </label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $productType?->name) }}"
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
        @checked(old('is_active', $productType?->is_active ?? true))
        role="switch" switch />
    </div>
    <label class="fw-bold" for="is_active">{{ __('product_types.form.is_active') }}</label>
</div>
