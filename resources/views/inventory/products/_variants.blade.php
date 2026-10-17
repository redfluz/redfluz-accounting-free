<?php

$savedVariants = isset($product)
    ? $product->variants->map(static fn ($variant): array => [
        'id' => $variant->id,
        'variant_name' => $variant->variant_name,
        'sku' => $variant->sku,
        'variant_details' => $variant->variant_details,
        'base_price' => $variant->base_price,
        'inherits_selling_price' => $variant->inherits_selling_price ? 1 : 0,
        'selling_price' => $variant->selling_price,
    ])->all()
    : [];
$variants = old('variants', $savedVariants);

?>

<div class="mb-3">
    <h6 class="fw-bold mb-1">{{ __('products.variants.title') }}</h6>
    <div class="fg-secondary small">{{ __('products.variants.hint') }}</div>
</div>

@for ($variantIndex = 0; $variantIndex < 10; $variantIndex++)
    <?php $variant = $variants[$variantIndex] ?? []; ?>
    <div class="variant-row mb-3 rounded border p-3" data-variant-row>
        @if (! empty($variant['id']))
            <input name="variants[{{ $variantIndex }}][id]" type="hidden" value="{{ $variant['id'] }}" />
        @endif

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="fw-bold">{{ __('products.variants.row', ['number' => $variantIndex + 1]) }}</div>
            <div class="fg-secondary small">{{ __('products.variants.optional') }}</div>
        </div>

        <div class="row">
            <div class="md:col-4 mb-3">
                <label class="form-label" for="variant_name_{{ $variantIndex }}">
                    {{ __('products.variants.name') }}
                </label>
                <input
                    class="form-control @error("variants.$variantIndex.variant_name") is-invalid @enderror"
                    id="variant_name_{{ $variantIndex }}"
                    name="variants[{{ $variantIndex }}][variant_name]"
                    type="text"
                    value="{{ $variant['variant_name'] ?? '' }}"
                    placeholder="{{ __('products.variants.name_example') }}"
                />
                @error("variants.$variantIndex.variant_name")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-4 mb-3">
                <label class="form-label" for="variant_sku_{{ $variantIndex }}">
                    {{ __('products.table.column_sku') }}
                </label>
                <input
                    class="form-control @error("variants.$variantIndex.sku") is-invalid @enderror"
                    id="variant_sku_{{ $variantIndex }}"
                    name="variants[{{ $variantIndex }}][sku]"
                    type="text"
                    value="{{ $variant['sku'] ?? '' }}"
                />
                @error("variants.$variantIndex.sku")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-4 mb-3">
                <label class="form-label" for="variant_details_{{ $variantIndex }}">
                    {{ __('products.variants.details') }}
                </label>
                <input
                    class="form-control @error("variants.$variantIndex.variant_details") is-invalid @enderror"
                    id="variant_details_{{ $variantIndex }}"
                    name="variants[{{ $variantIndex }}][variant_details]"
                    type="text"
                    value="{{ $variant['variant_details'] ?? '' }}"
                    placeholder="{{ __('products.variants.details_example') }}"
                />
                @error("variants.$variantIndex.variant_details")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row align-items-end">
            <div class="md:col-4 mb-3">
                <label class="form-label" for="variant_base_price_{{ $variantIndex }}">
                    {{ __('products.table.column_base_price') }} (RM)
                </label>
                <input
                    class="form-control @error("variants.$variantIndex.base_price") is-invalid @enderror"
                    id="variant_base_price_{{ $variantIndex }}"
                    name="variants[{{ $variantIndex }}][base_price]"
                    type="number"
                    value="{{ $variant['base_price'] ?? '' }}"
                    min="0"
                    step="0.01"
                />
                @error("variants.$variantIndex.base_price")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-4 mb-3">
                <label class="form-label" for="variant_selling_price_{{ $variantIndex }}">
                    {{ __('products.table.column_selling_price') }} (RM)
                </label>
                <input
                    class="form-control @error("variants.$variantIndex.selling_price") is-invalid @enderror"
                    data-variant-selling-price
                    id="variant_selling_price_{{ $variantIndex }}"
                    name="variants[{{ $variantIndex }}][selling_price]"
                    type="number"
                    value="{{ $variant['selling_price'] ?? '' }}"
                    min="0"
                    step="0.01"
                />
                @error("variants.$variantIndex.selling_price")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-4 mb-3">
                <input name="variants[{{ $variantIndex }}][inherits_selling_price]" type="hidden" value="0" />
                <div class="pb-2">
<div class="switch">
<input
    data-inherit-selling-price
    id="variant_inherits_selling_price_{{ $variantIndex }}"
    name="variants[{{ $variantIndex }}][inherits_selling_price]"
    type="checkbox"
    value="1"
    @checked((bool) ($variant['inherits_selling_price'] ?? true))
    role="switch" switch />
</div>
                    <label for="variant_inherits_selling_price_{{ $variantIndex }}">
                        {{ __('products.variants.use_main_selling_price') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
@endfor
