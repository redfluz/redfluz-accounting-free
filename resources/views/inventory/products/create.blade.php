<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

use App\Enums\FileAction;
use App\Enums\FileResources;

$canCreate = auth()->user()->hasFilePermission(FileResources::PRODUCTS, FileAction::ADD);

?>

@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('products.form.create_title') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off"
                id="product-form"
                action="{{ route('inventory.products.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-product-tab="details" type="button">
                            {{ __('products.tabs.details') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-product-tab="variants" type="button">
                            {{ __('products.tabs.variants') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-product-tab="attachments" type="button">
                            {{ __('products.tabs.attachments') }}
                        </button>
                    </li>
                </ul>

                <section data-product-section="details">
                    <div class="row">
                        <div class="md:col-6 mb-3">
                            <label class="form-label fw-bold" for="name">
                                {{ __('products.table.column_name') }} <span class="fg-danger">*</span>
                            </label>
                            <label class="form-control form-adorn">
                                <input
                                    class="form-ghost @error('name') is-invalid @enderror"
                                    id="name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    required
                                />
                            </label>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="md:col-6 mb-3">
                            <label class="form-label fw-bold" for="sku">
                                {{ __('products.table.column_sku') }} <span class="fg-danger">*</span>
                            </label>
                            <label class="form-control form-adorn">
                                <input
                                    class="form-ghost @error('sku') is-invalid @enderror"
                                    id="sku"
                                    name="sku"
                                    type="text"
                                    value="{{ old('sku') }}"
                                    required
                                />
                            </label>
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="md:col-6 mb-3">
                            <label class="form-label fw-bold" for="barcode">
                                {{ __('products.form.barcode') }}
                            </label>
                            <label class="form-control form-adorn">
                                <input
                                    class="form-ghost @error('barcode') is-invalid @enderror"
                                    id="barcode"
                                    name="barcode"
                                    type="text"
                                    value="{{ old('barcode') }}"
                                />
                            </label>
                            @error('barcode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="md:col-6 mb-3">
                            <label class="form-label fw-bold" for="mpn"> {{ __('products.form.mpn') }} </label>
                            <label class="form-control form-adorn">
                                <input
                                    class="form-ghost @error('mpn') is-invalid @enderror"
                                    id="mpn"
                                    name="mpn"
                                    type="text"
                                    value="{{ old('mpn') }}"
                                />
                            </label>
                            @error('mpn')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="brand_id">
                            {{ __('products.form.brand') }}
                        </label>
                        <select
                            class="form-control @error('brand_id') is-invalid @enderror"
                            id="brand_id"
                            name="brand_id"
                        >
                            <option value="">{{ __('brands.form.none') }}</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id') === $brand->id)>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('brand_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="fg-secondary small mt-1">{{ __('products.form.brand_hint') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="supplier_company_id">
                            {{ __('products.form.supplier') }}
                        </label>
                        <select
                            class="form-control @error('supplier_company_id') is-invalid @enderror"
                            id="supplier_company_id"
                            name="supplier_company_id"
                        >
                            <option value="">{{ __('products.form.no_supplier') }}</option>
                            @foreach ($suppliers as $supplier)
                                <option
                                    value="{{ $supplier->id }}"
                                    @selected(old('supplier_company_id') === $supplier->id)
                                >
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="fg-secondary small mt-1">{{ __('products.form.supplier_hint') }}</div>
                    </div>

                    @include('inventory.products._classification')

                    <div class="row">
                        <div class="md:col-6 mb-3">
                            <label
                                class="form-label fw-bold"
                                for="default_tax_id"
                            >{{ __('products.form.default_tax') }}</label>
                            <select
                                class="form-control @error('default_tax_id') is-invalid @enderror"
                                id="default_tax_id"
                                name="default_tax_id"
                            >
                                <option value="">{{ __('taxes.form.none') }}</option>
                                @foreach ($taxes as $tax)
                                    <option value="{{ $tax->id }}" @selected(old('default_tax_id') === $tax->id)>
                                        {{ $tax->code }} - {{ $tax->name }} ({{ number_format((float) $tax->rate, 4) }}%)
                                    </option>
                                @endforeach
                            </select>
                            @error('default_tax_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="md:col-6 mb-3">
                            <label
                                class="form-label fw-bold"
                                for="default_discount_id"
                            >{{ __('products.form.default_discount') }}</label>
                            <select
                                class="form-control @error('default_discount_id') is-invalid @enderror"
                                id="default_discount_id"
                                name="default_discount_id"
                            >
                                <option value="">{{ __('discounts.form.none') }}</option>
                                @foreach ($discounts as $discount)
                                    <option
                                        value="{{ $discount->id }}"
                                        @selected(old('default_discount_id') === $discount->id)
                                    >
                                        {{ $discount->code }} - {{ $discount->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('default_discount_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="fg-secondary small mb-3">{{ __('products.form.commercial_defaults_hint') }}</div>
                    </div>

                    <div class="mb-3">
                        <label
                            class="form-label fw-bold"
                            for="warranty_months"
                        >{{ __('products.form.warranty') }}</label>
                        <select class="form-control" id="warranty_months" name="warranty_months" required>
                            @foreach ([-1, 0, 3, 6, 12, 24, 36] as $months)
                                <option
                                    value="{{ $months }}"
                                    @selected((int) old('warranty_months', $defaultWarrantyMonths ?? -1) === $months)
                                >
                                    {{ $months === -1 ? __('products.warranty.unlimited') : ($months === 0 ? __('products.warranty.none') : __('products.warranty.months', ['count' => $months])) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('products.form.warranty_hint') }}</div>
                    </div>

                    <div class="row">
                        <div class="md:col-6 mb-3">
                            <label class="form-label fw-bold" for="base_price">
                                {{ __('products.table.column_base_price') }} (RM) <span class="fg-danger">*</span>
                            </label>
                            <label class="form-control form-adorn">
                                <input
                                    class="form-ghost @error('base_price') is-invalid @enderror"
                                    id="base_price"
                                    name="base_price"
                                    type="number"
                                    value="{{ old('base_price', '0.00') }}"
                                    min="0"
                                    step="0.01"
                                    required
                                />
                            </label>
                            @error('base_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="md:col-6 mb-3">
                            <label class="form-label fw-bold" for="selling_price">
                                {{ __('products.table.column_selling_price') }} (RM) <span class="fg-danger">*</span>
                            </label>
                            <label class="form-control form-adorn">
                                <input
                                    class="form-ghost @error('selling_price') is-invalid @enderror"
                                    id="selling_price"
                                    name="selling_price"
                                    type="number"
                                    value="{{ old('selling_price', '0.00') }}"
                                    min="0"
                                    step="0.01"
                                    required
                                />
                            </label>
                            @error('selling_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="md:col-4 mb-3">
                            <label class="form-label fw-bold" for="default_acquisition_type">
                                {{ __('products.form.default_acquisition_type') }} <span class="fg-danger">*</span>
                            </label>
                            <select
                                class="form-control"
                                id="default_acquisition_type"
                                name="default_acquisition_type"
                                required
                            >
                                @foreach (\App\Enums\AcquisitionType::cases() as $acquisitionType)
                                    <option
                                        value="{{ $acquisitionType->value }}"
                                        @selected(old('default_acquisition_type', 'stock') === $acquisitionType->value)
                                    >
                                        {{ __('acquisition_types.' . $acquisitionType->value) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-4 mb-3">
                            <label
                                class="form-label fw-bold"
                                for="reorder_level"
                            >{{ __('products.form.reorder_level') }}</label>
                            <label class="form-control form-adorn">
                                <input
                                    class="form-ghost"
                                    id="reorder_level"
                                    name="reorder_level"
                                    type="number"
                                    value="{{ old('reorder_level', 0) }}"
                                    min="0"
                                    step="0.0001"
                                />
                            </label>
                        </div>
                        <div class="md:col-4 mb-3">
                            <label
                                class="form-label fw-bold"
                                for="reorder_quantity"
                            >{{ __('products.form.reorder_quantity') }}</label>
                            <label class="form-control form-adorn">
                                <input
                                    class="form-ghost"
                                    id="reorder_quantity"
                                    name="reorder_quantity"
                                    type="number"
                                    value="{{ old('reorder_quantity', 0) }}"
                                    min="0"
                                    step="0.0001"
                                />
                            </label>
                        </div>
                        <div class="fg-secondary small mb-3">{{ __('products.form.reorder_hint') }}</div>
                    </div>

                    <div class="bg-2 mb-3 rounded border p-3">
                        <div class="switch">
                        <input
                            id="is_service"
                            name="is_service"
                            type="checkbox"
                            value="1"
                            @checked(old('is_service'))
                            role="switch" switch />
                        </div>
                        <label class="fw-bold" for="is_service">
                            {{ __('products.form.is_service') }}
                        </label>
                        <div class="fg-secondary small mt-1">{{ __('products.form.is_service_hint') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="description">
                            {{ __('products.table.column_description') }}
                        </label>
                        <textarea
                            class="form-control @error('description') is-invalid @enderror"
                            id="description"
                            name="description"
                            rows="3"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </section>

                <section class="d-none" data-product-section="variants">
                    @include('inventory.products._variants')
                </section>

                <section class="d-none" data-product-section="attachments">
                    @include('inventory.products._attachments')
                </section>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('inventory.products.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>
                    @if ($canCreate)
                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>
                            {{ __('common.actions.save') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function previewProductImages(event) {
            const preview = document.getElementById('attachment-preview');
            preview.replaceChildren();

            Array.from(event.target.files).forEach((file) => {
                const column = document.createElement('div');
                const image = document.createElement('img');
                const caption = document.createElement('div');
                const reader = new FileReader();

                column.className = 'col-6 md:col-4 xl:col-3';
                image.className = 'img-thumbnail object-fit-cover w-100';
                image.style.height = '140px';
                image.alt = file.name;
                caption.className = 'small text-truncate mt-1';
                caption.textContent = file.name;
                reader.addEventListener('load', (loadEvent) => {
                    image.src = loadEvent.target.result;
                });
                reader.readAsDataURL(file);
                column.append(image, caption);
                preview.append(column);
            });
        }

        function updateProductPurchaseFields() {
            const isService = document.getElementById('is_service').checked;
            const purpose = document.getElementById('default_acquisition_type');
            if (isService) purpose.value = 'expense';
            document.getElementById('reorder_level').disabled = isService || purpose.value !== 'stock';
            document.getElementById('reorder_quantity').disabled = isService || purpose.value !== 'stock';
        }

        function updateVariantSellingPrice(row) {
            const inheritsPrice = row.querySelector('[data-inherit-selling-price]').checked;
            const sellingPrice = row.querySelector('[data-variant-selling-price]');
            sellingPrice.readOnly = inheritsPrice;
            if (inheritsPrice) sellingPrice.value = document.getElementById('selling_price').value;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const productForm = document.getElementById('product-form');
            let invalidTabActivated = false;

            productForm.addEventListener(
                'invalid',
                (event) => {
                    if (invalidTabActivated) return;

                    const section = event.target.closest('[data-product-section]');
                    const tab = section
                        ? document.querySelector(`[data-product-tab="${section.dataset.productSection}"]`)
                        : null;
                    if (tab) {
                        invalidTabActivated = true;
                        tab.click();
                        setTimeout(() => (invalidTabActivated = false), 0);
                    }
                },
                true,
            );
            document.getElementById('is_service').addEventListener('change', updateProductPurchaseFields);
            document.getElementById('default_acquisition_type').addEventListener('change', updateProductPurchaseFields);
            document.querySelectorAll('[data-product-tab]').forEach((tab) => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('[data-product-tab]').forEach((item) => item.classList.remove('active'));
                    document
                        .querySelectorAll('[data-product-section]')
                        .forEach((section) => section.classList.add('d-none'));
                    tab.classList.add('active');
                    document
                        .querySelector(`[data-product-section="${tab.dataset.productTab}"]`)
                        .classList.remove('d-none');
                });
            });
            document.querySelectorAll('[data-variant-row]').forEach((row) => {
                row.querySelector('[data-inherit-selling-price]').addEventListener('change', () =>
                    updateVariantSellingPrice(row),
                );
                updateVariantSellingPrice(row);
            });
            document.getElementById('selling_price').addEventListener('input', () => {
                document.querySelectorAll('[data-variant-row]').forEach(updateVariantSellingPrice);
            });
            @if ($errors->has('images') || $errors->has('images.*'))
                document.querySelector('[data-product-tab="attachments"]').click();
            @elseif ($errors->has('variants') || $errors->has('variants.*'))
                document.querySelector('[data-product-tab="variants"]').click();
            @endif
            updateProductPurchaseFields();
        });
    </script>
@endsection
