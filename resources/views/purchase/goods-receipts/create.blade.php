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
?>

@props([
    'purchaseInvoices',
    'products',
    'selectedInvoiceNumber',
    'invoiceLines',
    'invoiceSummary',
    'postingDateMin',
    'postingDateMax',
    'stockLocations',
    'selectedStockLocationId',
])

@php
    $entries = collect();
@endphp

@extends('layouts.app')

@section('content')
    <div class="card xl:col-11 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('goods_receipts.form.create_title') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('purchase.goods-receipts.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('purchase.goods-receipts._form', ['goodsReceipt' => null])
            </form>
        </div>
    </div>

    {{-- Product Selection Dialog --}}
    <dialog class="dialog" id="grnProductPickerModal" aria-labelledby="grnProductPickerModalLabel"
        style="width: 90vw; max-width: 1200px; height: 85vh; max-height: 800px; padding: 0;">
        <div class="dialog-header" style="padding: 1.5rem 2rem;">
            <h4 class="dialog-title mb-0" id="grnProductPickerModalLabel">{{ __('goods_receipts.form.select_product') }}
            </h4>
            <button class="btn-close" data-bs-dismiss="dialog" type="button"></button>
        </div>
        <div class="dialog-body d-flex flex-column" style="padding: 1.5rem 2rem; min-height: 0;">
            <div class="row g-3 mb-4 flex-shrink-0">
                <div class="md:col-6">
                    <label class="visually-hidden" for="grnProductSearchInput">Search products</label>
                    <input type="text" class="form-control form-control-lg" id="grnProductSearchInput"
                        placeholder="{{ __('goods_receipts.form.search_products') }}" style="font-size: 1.1rem;">
                </div>
                <div class="md:col-3">
                    <label class="visually-hidden" for="grnProductCategoryFilter">Category filter</label>
                    <select class="form-control form-control-lg" id="grnProductCategoryFilter"
                        style="font-size: 1.1rem;">
                        <option value="">{{ __('goods_receipts.form.all_categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-3">
                    <label class="visually-hidden" for="grnProductTypeFilter">Type filter</label>
                    <select class="form-control form-control-lg" id="grnProductTypeFilter" style="font-size: 1.1rem;">
                        <option value="">{{ __('goods_receipts.form.all_types') }}</option>
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
                <table class="table table-hover" style="font-size: 1.05rem;">
                    <thead class="sticky-top bg-body" style="font-size: 1.1rem;">
                        <tr>
                            <th scope="col" style="padding: 1rem;">{{ __('goods_receipts.form.sku') }}</th>
                            <th scope="col" style="padding: 1rem;">{{ __('goods_receipts.form.product_name') }}</th>
                            <th scope="col" style="padding: 1rem;">{{ __('goods_receipts.form.category') }}</th>
                            <th scope="col" class="text-end" style="padding: 1rem;">
                                {{ __('goods_receipts.form.price') }}</th>
                        </tr>
                    </thead>
                    <tbody id="grnProductPickerResults">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5" style="font-size: 1.1rem;">
                                {{ __('goods_receipts.form.type_to_search') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dialog-footer" style="padding: 1.5rem 2rem;">
            <button class="btn btn-solid theme-primary btn-lg" data-bs-dismiss="dialog" type="button"
                style="font-size: 1.1rem; padding: 0.75rem 2rem;">
                {{ __('goods_receipts.form.close_information') }}
            </button>
        </div>
    </dialog>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const grnProductPickerModal = window.bootstrap.Dialog.getOrCreateInstance(document.getElementById(
                    'grnProductPickerModal'));
                const grnProductSearchInput = document.getElementById('grnProductSearchInput');
                const grnProductCategoryFilter = document.getElementById('grnProductCategoryFilter');
                const grnProductTypeFilter = document.getElementById('grnProductTypeFilter');
                const grnProductPickerResults = document.getElementById('grnProductPickerResults');
                let grnCurrentLineIndex = null;
                let grnSearchTimeout = null;

                function filterGrnProductTypes() {
                    const categoryId = grnProductCategoryFilter.value;

                    Array.from(grnProductTypeFilter.options).forEach((option) => {
                        if (!option.dataset.categoryId) return;
                        const belongsToCategory = option.dataset.categoryId === categoryId;
                        option.hidden = !belongsToCategory;
                        option.disabled = !belongsToCategory;
                    });

                    if (grnProductTypeFilter.selectedOptions[0]?.hidden) {
                        grnProductTypeFilter.value = '';
                    }

                    grnProductTypeFilter.disabled = categoryId === '';
                }

                function searchGrnProducts() {
                    const query = grnProductSearchInput.value;
                    const categoryId = grnProductCategoryFilter.value;
                    const typeId = grnProductTypeFilter.value;
                    const noProductsText = @json(__('goods_receipts.form.no_products_found'));
                    const searchErrorText = @json(__('goods_receipts.form.search_error'));
                    const typeToSearchText = @json(__('goods_receipts.form.type_to_search'));

                    if (grnSearchTimeout) {
                        clearTimeout(grnSearchTimeout);
                    }

                    grnSearchTimeout = setTimeout(() => {
                        fetch(
                                `{{ route('purchase.goods-receipts.products.search') }}?search=${encodeURIComponent(query)}&category_id=${encodeURIComponent(categoryId)}&type_id=${encodeURIComponent(typeId)}`
                            )
                            .then(response => response.json())
                            .then(products => {
                                if (products.length === 0) {
                                    grnProductPickerResults.innerHTML =
                                        `<tr><td colspan="4" class="text-center text-muted py-5" style="font-size: 1.1rem;">${noProductsText}</td></tr>`;
                                } else {
                                    grnProductPickerResults.innerHTML = products.map(product => `
                            <tr class="grn-product-picker-row" data-product-id="${product.id}" data-product-sku="${escapeHtml(product.sku)}" data-product-name="${escapeHtml(product.name)}" data-product-price="${product.price}" style="cursor: pointer; font-size: 1.05rem;">
                                <td style="padding: 1rem;">${escapeHtml(product.sku)}</td>
                                <td style="padding: 1rem;">${escapeHtml(product.name)}</td>
                                <td style="padding: 1rem;">${escapeHtml(product.category)}</td>
                                <td class="text-end" style="padding: 1rem;">${quantity(product.price)}</td>
                            </tr>
                        `).join('');

                                    grnProductPickerResults.querySelectorAll('.grn-product-picker-row')
                                        .forEach(row => {
                                            row.addEventListener('click', () => {
                                                const productId = row.dataset.productId;
                                                const productSku = row.dataset.productSku;
                                                const productName = row.dataset.productName;
                                                const productPrice = row.dataset.productPrice;

                                                const targetRow = document.querySelector(
                                                    `tr[data-line-index="${grnCurrentLineIndex}"]`
                                                );
                                                const productIdInput = targetRow.querySelector(
                                                    '.grn-product-id');
                                                const productLabel = targetRow.querySelector(
                                                    '.grn-product-label');
                                                const unitCostInput = targetRow.querySelector(
                                                    'input[name*="unit_cost"]');

                                                productIdInput.value = productId;
                                                productLabel.textContent =
                                                    `${productSku} - ${productName}`;
                                                unitCostInput.value ||= productPrice;

                                                grnProductPickerModal.hide();
                                            });
                                        });
                                }
                            })
                            .catch(error => {
                                console.error('Error searching products:', error);
                                grnProductPickerResults.innerHTML =
                                    `<tr><td colspan="4" class="text-center text-danger py-5" style="font-size: 1.1rem;">${searchErrorText}</td></tr>`;
                            });
                    }, 300);
                }

                const quantity = (value) =>
                    Number(value || 0).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                const escapeHtml = (value) =>
                    String(value).replace(
                        /[&<>'"]/g,
                        (character) =>
                        ({
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            "'": '&#039;',
                            '"': '&quot;',
                        })[character],
                    );

                document.querySelectorAll('.grn-product-choose-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        grnCurrentLineIndex = e.currentTarget.dataset.lineIndex;
                        grnProductSearchInput.value = '';
                        grnProductCategoryFilter.value = '';
                        grnProductTypeFilter.value = '';
                        filterGrnProductTypes();
                        const typeToSearchText = @json(__('goods_receipts.form.type_to_search'));
                        grnProductPickerResults.innerHTML =
                            `<tr><td colspan="4" class="text-center text-muted py-5" style="font-size: 1.1rem;">${typeToSearchText}</td></tr>`;
                        grnProductPickerModal.show();
                        setTimeout(() => grnProductSearchInput.focus(), 500);
                    });
                });

                grnProductSearchInput.addEventListener('input', searchGrnProducts);
                grnProductCategoryFilter.addEventListener('change', () => {
                    filterGrnProductTypes();
                    searchGrnProducts();
                });
                grnProductTypeFilter.addEventListener('change', searchGrnProducts);
                filterGrnProductTypes();
            });
        </script>
    @endpush
@endsection

@section('script')
    <script>
        document.getElementById('purchase_order_number')?.addEventListener('change', function() {
            const createUrl = this.dataset.createUrl;

            if (this.value === '') {
                window.location.assign(createUrl);
                return;
            }

            const url = new URL(createUrl, window.location.origin);
            url.searchParams.set('purchase_order_number', this.value);
            window.location.assign(url.toString());
        });
    </script>
@endsection
