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
