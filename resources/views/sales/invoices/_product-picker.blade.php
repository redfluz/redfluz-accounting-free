{{--
    ============================================================================
    Copyright (c) 2026 redfluz. All Rights Reserved.
    PROPRIETARY AND CONFIDENTIAL
    ============================================================================
    --}}

{{-- Product Picker Dialog --}}
<dialog class="dialog" id="invoiceProductPickerDialog"
        style="width: 90vw; max-width: 1200px; height: 85vh; max-height: 800px; padding: 0;">
    <div class="dialog-header">
        <h5 class="mb-0">{{ __('invoices.form.select_product') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="dialog"></button>
    </div>
    <div class="dialog-body d-flex flex-column" style="min-height: 0;">
        <div class="row g-3 mb-3 flex-shrink-0">
            <div class="md:col-6">
                <label class="visually-hidden" for="invoice-product-search">Search Products</label><input
                    type="text" class="form-control form-control-lg" id="invoice-product-search"
                    placeholder="{{ __('invoices.form.search_products') }}" style="font-size: 1.1rem;">
            </div>
            <div class="md:col-3">
                <label class="visually-hidden" for="invoice-product-category-filter">Product Category</label><select
                    class="form-control form-control-lg" id="invoice-product-category-filter"
                    style="font-size: 1.1rem;">
                    <option value="">{{ __('invoices.form.all_categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-3">
                <label class="visually-hidden" for="invoice-product-type-filter">Product Type</label>
                <select
                    class="form-control form-control-lg" id="invoice-product-type-filter"
                    style="font-size: 1.1rem;">
                    <option value="">{{ __('invoices.form.all_types') }}</option>
                    @foreach ($categories as $category)
                        @foreach ($category->types as $type)
                            <option value="{{ $type->id }}" data-category-id="{{ $category->id }}">
                                {{ $type->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive flex-fill" style="overflow-y: auto; min-height: 0;">
            <table class="table table-hover" id="invoice-product-search-results">
                <thead class="sticky-top bg-body">
                <tr>
                    <th scope="col" style="font-size: 1.1rem; padding: 1rem;">{{ __('invoices.form.sku') }}
                    </th>
                    <th scope="col" style="font-size: 1.1rem; padding: 1rem;">
                        {{ __('invoices.form.product_name') }}</th>
                    <th scope="col" style="font-size: 1.1rem; padding: 1rem;">
                        {{ __('invoices.form.category') }}</th>
                    <th scope="col" style="font-size: 1.1rem; padding: 1rem;">{{ __('invoices.form.price') }}
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td colspan="4" class="text-center text-muted" style="font-size: 1.05rem; padding: 1rem;">
                        {{ __('invoices.form.type_to_search') }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="dialog-footer">
        <button type="button" class="btn btn-solid theme-secondary"
                data-bs-dismiss="dialog">{{ __('common.actions.close') }}</button>
    </div>
</dialog>
