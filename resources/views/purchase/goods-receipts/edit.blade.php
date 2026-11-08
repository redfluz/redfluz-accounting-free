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
$goodsReceipt = $entries->first();
?>

@props([
    'purchaseInvoices',
    'products',
    'selectedInvoiceNumber',
    'invoiceLines',
    'invoiceSummary',
    'postingDateMin',
    'postingDateMax',
    'documentNumber',
    'entries',
    'stockLocations',
    'selectedStockLocationId',
    'attachments',
])

@extends('layouts.app')

@section('content')
    <div class="card xl:col-11 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('goods_receipts.form.edit_title') }}: {{ $documentNumber }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('purchase.goods-receipts.update', $documentNumber) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input name="id" type="hidden" value="{{ $goodsReceipt->id }}" />
                @include('purchase.goods-receipts._form', [
                    'goodsReceipt' => $goodsReceipt,
                    'attachments' => $attachments,
                ])
            </form>
        </div>
    </div>
@endsection
