@props(['product' => null])

@php
    $selectedCategoryId = old('product_category_id', $product?->product_category_id);
    $selectedTypeId = old('product_type_id', $product?->product_type_id);
@endphp

<div class="row">
    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="product_category_id">
            {{ __('products.form.category') }} <span class="fg-danger">*</span>
        </label>
        <select
            class="form-control @error('product_category_id') is-invalid @enderror"
            id="product_category_id"
            name="product_category_id"
            required
        >
            <option value="">{{ __('products.form.select_category') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($selectedCategoryId === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('product_category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="product_type_id">
            {{ __('products.form.product_type') }} <span class="fg-danger">*</span>
        </label>
        <select
            class="form-control @error('product_type_id') is-invalid @enderror"
            id="product_type_id"
            name="product_type_id"
            required
        >
            <option value="">{{ __('products.form.select_type') }}</option>
            @foreach ($categories as $category)
                @foreach ($category->types as $type)
                    <option
                        data-category-id="{{ $category->id }}"
                        value="{{ $type->id }}"
                        @selected($selectedTypeId === $type->id)
                    >
                        {{ $type->name }}
                    </option>
                @endforeach
            @endforeach
        </select>
        @error('product_type_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="fg-secondary small mt-1">{{ __('products.form.classification_hint') }}</div>
        <div class="small mt-1">
            <a href="{{ route('inventory.product-categories.index') }}">{{ __('products.form.manage_categories') }}</a>
            ·
            <a href="{{ route('inventory.product-types.index') }}">{{ __('products.form.manage_types') }}</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const category = document.getElementById('product_category_id');
        const type = document.getElementById('product_type_id');

        function filterProductTypes() {
            Array.from(type.options).forEach((option) => {
                if (!option.dataset.categoryId) return;
                const belongsToCategory = option.dataset.categoryId === category.value;
                option.hidden = !belongsToCategory;
                option.disabled = !belongsToCategory;
            });

            if (type.selectedOptions[0]?.hidden) type.value = '';
            type.disabled = category.value === '';
        }

        category.addEventListener('change', filterProductTypes);
        filterProductTypes();
    });
</script>
