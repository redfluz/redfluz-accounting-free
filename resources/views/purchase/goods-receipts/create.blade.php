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

                <div class="row g-3 mb-4">
                    <div class="md:col-5">
                        <label class="form-label fw-bold" for="purchase_order_number">
                            {{ __('goods_receipts.form.purchase_invoice') }}
                        </label>
                        <select class="form-control @error('purchase_order_number') is-invalid @enderror"
                            id="purchase_order_number" name="purchase_order_number" data-combobox
                            data-create-url="{{ route('purchase.goods-receipts.create') }}" required>
                            <option value="">{{ __('common.actions.select_option') }}</option>
                            @foreach ($purchaseInvoices as $purchaseInvoice)
                                <option value="{{ $purchaseInvoice->document_number }}" @selected(old('purchase_order_number', $selectedInvoiceNumber) === $purchaseInvoice->document_number)>
                                    {{ $purchaseInvoice->document_number }} - {{ $purchaseInvoice->company_name }} ({{ $currencySymbol }}
                                    {{ number_format((float) $purchaseInvoice->total_amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('goods_receipts.form.purchase_invoice_hint') }}</div>
                        @error('purchase_order_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-3">
                        <label class="form-label fw-bold" for="date">
                            {{ __('goods_receipts.form.received_date') }}
                        </label>
                        <label class="form-control form-adorn">
                            <div class="form-adorn-icon" aria-hidden="true">
                                <i data-lucide="calendar"></i>
                            </div>
                            <input class="form-ghost @error('date') is-invalid @enderror" id="date" name="date"
                                type="text" data-bs-toggle="datepicker" autocomplete="off" value="{{ old('date', now()->format('Y-m-d')) }}"
                                min="{{ $postingDateMin ?? '' }}" max="{{ $postingDateMax ?? '' }}" required />
                        </label>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-4">
                        <label class="form-label fw-bold" for="stock_location_id">
                            {{ __('goods_receipts.form.stock_location') }}
                        </label>
                        <select class="form-control @error('stock_location_id') is-invalid @enderror" id="stock_location_id"
                            name="stock_location_id" required>
                            <option value="">{{ __('common.actions.select_option') }}</option>
                            @foreach ($stockLocations as $stockLocation)
                                <option value="{{ $stockLocation->id }}" @selected(old('stock_location_id', $selectedStockLocationId) === $stockLocation->id)>
                                    {{ $stockLocation->code }} - {{ $stockLocation->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('stock_location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if ($stockLocations->isEmpty())
                    <div class="alert alert theme-danger">{{ __('goods_receipts.messages.no_stock_location') }}</div>
                @endif

                @if ($selectedInvoiceNumber === '')
                    <div class="alert alert theme-info mb-4">
                        <i class="me-1" data-lucide="info"></i>
                        {{ __('goods_receipts.messages.select_invoice') }}
                    </div>
                @elseif ($invoiceLines->isEmpty())
                    <div class="alert alert theme-warning mb-4">
                        <i class="me-1" data-lucide="triangle-alert"></i>
                        {{ __('goods_receipts.messages.manual_product_required') }}
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="md:col-4">
                            <div class="bg-2 rounded border p-3">
                                <div class="fg-secondary small">{{ __('goods_receipts.form.ordered_quantity') }}</div>
                                <div class="fs-xl fw-bold">
                                    {{ number_format((float) $invoiceSummary['ordered_quantity'], 4) }}
                                </div>
                            </div>
                        </div>
                        <div class="md:col-4">
                            <div class="bg-2 rounded border p-3">
                                <div class="fg-secondary small">{{ __('goods_receipts.form.previously_received') }}</div>
                                <div class="fs-xl fw-bold">
                                    {{ number_format((float) $invoiceSummary['received_quantity'], 4) }}
                                </div>
                            </div>
                        </div>
                        <div class="md:col-4">
                            <div class="bg-2 rounded border p-3">
                                <div class="fg-secondary small">{{ __('goods_receipts.form.remaining_quantity') }}</div>
                                <div class="fs-xl fw-bold">
                                    {{ number_format((float) $invoiceSummary['remaining_quantity'], 4) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive rounded border">
                        <table class="table-hover table-striped mb-0 table align-middle">
                            <thead class="bg-1">
                                <tr>
                                    <th>{{ __('goods_receipts.form.product') }}</th>
                                    <th style="width: 22%">{{ __('goods_receipts.form.quantity') }}</th>
                                    <th style="width: 22%">{{ __('goods_receipts.form.unit_cost') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($lineNumber = 0; $lineNumber < 5; $lineNumber++)
                                    <tr>
                                        <td>
                                            <select
                                                class="form-control @error('lines.' . $lineNumber . '.product_id') is-invalid @enderror"
                                                name="lines[{{ $lineNumber }}][product_id]" data-combobox>
                                                <option value="">{{ __('common.actions.select_option') }}</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" @selected(old("lines.{$lineNumber}.product_id") === $product->id)>
                                                        {{ $product->sku }} - {{ $product->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('lines.' . $lineNumber . '.product_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <label class="form-control form-adorn">
                                                <input
                                                    class="form-ghost @error('lines.' . $lineNumber . '.quantity') is-invalid @enderror"
                                                    name="lines[{{ $lineNumber }}][quantity]" type="number"
                                                    value="{{ old("lines.{$lineNumber}.quantity") }}" min="0"
                                                    max="{{ $invoiceSummary['remaining_quantity'] }}" step="0.0001" />
                                            </label>
                                            @error('lines.' . $lineNumber . '.quantity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <label class="form-control form-adorn">
                                                <input
                                                    class="form-ghost @error('lines.' . $lineNumber . '.unit_cost') is-invalid @enderror"
                                                    name="lines[{{ $lineNumber }}][unit_cost]" type="number"
                                                    value="{{ old("lines.{$lineNumber}.unit_cost", $invoiceSummary['unit_cost']) }}"
                                                    min="0" step="0.01" />
                                            </label>
                                            @error('lines.' . $lineNumber . '.unit_cost')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="table-responsive rounded border">
                        <table class="table-hover table-striped mb-0 table align-middle">
                            <thead class="bg-1">
                                <tr>
                                    <th>{{ __('goods_receipts.form.product') }}</th>
                                    <th class="text-end">{{ __('goods_receipts.form.ordered_quantity') }}</th>
                                    <th class="text-end">{{ __('goods_receipts.form.previously_received') }}</th>
                                    <th class="text-end">{{ __('goods_receipts.form.remaining_quantity') }}</th>
                                    <th style="width: 18%">{{ __('goods_receipts.form.quantity') }}</th>
                                    <th class="text-end">{{ __('goods_receipts.form.unit_cost') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoiceLines as $lineNumber => $invoiceLine)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $invoiceLine['product_name'] }}</div>
                                            <div class="fg-secondary small">{{ $invoiceLine['product_sku'] }}</div>
                                            <input name="lines[{{ $lineNumber }}][product_id]" type="hidden"
                                                value="{{ $invoiceLine['product_id'] }}" />
                                        </td>
                                        <td class="text-end">
                                            {{ number_format((float) $invoiceLine['ordered_quantity'], 4) }}
                                        </td>
                                        <td class="text-end">
                                            {{ number_format((float) $invoiceLine['received_quantity'], 4) }}
                                        </td>
                                        <td class="fw-bold text-end">
                                            {{ number_format((float) $invoiceLine['remaining_quantity'], 4) }}
                                        </td>
                                        <td>
                                            <label class="form-control form-adorn">
                                                <input
                                                    class="form-ghost @error('lines.' . $lineNumber . '.quantity') is-invalid @enderror"
                                                    name="lines[{{ $lineNumber }}][quantity]" type="number"
                                                    value="{{ old("lines.{$lineNumber}.quantity", $invoiceLine['remaining_quantity']) }}"
                                                    min="0" max="{{ $invoiceLine['remaining_quantity'] }}"
                                                    step="0.0001" @disabled((float) $invoiceLine['remaining_quantity'] <= 0) />
                                            </label>
                                            @error('lines.' . $lineNumber . '.quantity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="text-end">
                                            {{ $currencySymbol }} {{ number_format((float) $invoiceLine['unit_cost'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-3">
                    <label class="form-label fw-bold" for="goods-receipt-attachments">
                        {{ __('goods_receipts.form.attachments') }}
                    </label>
                    <input accept="{{ \App\Enums\UploadType::DOCUMENT_AND_IMAGES->accept() }}"
                        class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                        id="goods-receipt-attachments" name="attachments[]" type="file" multiple />
                    <div class="form-text">{{ __('goods_receipts.form.attachments_hint') }}</div>
                    @error('attachments')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('attachments.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-3">
                    <label class="form-label fw-bold" for="notes">
                        {{ __('goods_receipts.form.notes') }}
                    </label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a class="btn btn-solid theme-secondary" href="{{ route('purchase.goods-receipts.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>
                    <button class="btn btn-solid theme-accent" type="submit" @disabled(
                        $stockLocations->isEmpty() ||
                            $selectedInvoiceNumber === '' ||
                            $invoiceSummary === null ||
                            (float) $invoiceSummary['remaining_quantity'] <= 0)>
                        <i class="me-1" data-lucide="save"></i>
                        {{ __('common.actions.save') }}
                    </button>
                </div>
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
