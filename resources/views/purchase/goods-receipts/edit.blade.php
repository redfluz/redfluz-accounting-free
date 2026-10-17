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

                <div class="row g-3 mb-4">
                    <div class="md:col-5">
                        <label class="form-label fw-bold" for="purchase_order_display">
                            {{ __('goods_receipts.form.purchase_invoice') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input class="form-ghost" id="purchase_order_display" type="text"
                                value="{{ $selectedInvoiceNumber }} - {{ $goodsReceipt->company_name }}" readonly />
                        </label>
                        <input name="purchase_order_number" type="hidden" value="{{ $selectedInvoiceNumber }}" />
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
                                type="text" data-bs-toggle="datepicker" autocomplete="off"
                                value="{{ old('date', $goodsReceipt->date->format('Y-m-d')) }}"
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

                @if ($invoiceLines->isEmpty())
                    <div class="alert alert theme-warning mb-3">
                        <i class="me-1" data-lucide="triangle-alert"></i>
                        {{ __('goods_receipts.messages.manual_product_required') }}
                    </div>

                    <div class="table-responsive rounded border">
                        <table class="table-form table-hover table-striped mb-0 table align-middle">
                            <thead class="bg-1">
                                <tr>
                                    <th>{{ __('goods_receipts.form.product') }}</th>
                                    <th style="width: 22%">{{ __('goods_receipts.form.quantity') }}</th>
                                    <th style="width: 22%">{{ __('goods_receipts.form.unit_cost') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($lineNumber = 0; $lineNumber < 5; $lineNumber++)
                                    @php
                                        $entry = $entries->get($lineNumber);
                                    @endphp
                                    <tr>
                                        <td>
                                            <select
                                                class="form-control @error('lines.' . $lineNumber . '.product_id') is-invalid @enderror"
                                                name="lines[{{ $lineNumber }}][product_id]" data-combobox>
                                                <option value="">{{ __('common.actions.select_option') }}</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" @selected(old("lines.$lineNumber.product_id", $entry?->product_id) === $product->id)>
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
                                                    value="{{ old("lines.{$lineNumber}.quantity", $entry?->quantity) }}"
                                                    min="0" max="{{ $invoiceSummary['remaining_quantity'] }}"
                                                    step="0.0001" />
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
                                                    value="{{ old("lines.{$lineNumber}.unit_cost", $entry->unit_cost ?? $invoiceSummary['unit_cost']) }}"
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
                        <table class="table-form table-hover table-striped mb-0 table align-middle">
                            <thead class="bg-1">
                                <tr>
                                    <th>{{ __('goods_receipts.form.product') }}</th>
                                    <th class="text-end">{{ __('goods_receipts.form.ordered_quantity') }}</th>
                                    <th class="text-end">{{ __('goods_receipts.form.previously_received') }}</th>
                                    <th class="text-end">{{ __('goods_receipts.form.available_quantity') }}</th>
                                    <th style="width: 18%">{{ __('goods_receipts.form.quantity') }}</th>
                                    <th class="text-end">{{ __('goods_receipts.form.unit_cost') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoiceLines as $lineNumber => $invoiceLine)
                                    @php
                                        $currentQuantity = $entries
                                            ->where('product_id', $invoiceLine['product_id'])
                                            ->sum('quantity');
                                    @endphp
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
                                                    value="{{ old("lines.{$lineNumber}.quantity", $currentQuantity) }}"
                                                    min="0" max="{{ $invoiceLine['remaining_quantity'] }}"
                                                    step="0.0001" />
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

                @if ($attachments->isNotEmpty())
                    <div class="mt-3">
                        <h6 class="fw-bold">{{ __('goods_receipts.form.existing_attachments') }}</h6>
                        <div class="list-group">
                            @foreach ($attachments as $attachment)
                                <label class="list-group-item d-flex align-items-center justify-content-between gap-3">
                                    <span>
                                        <span class="fw-bold d-block">{{ $attachment->original_name }}</span>
                                        <small class="badge theme-secondary">
                                            {{ number_format($attachment->file_size / 1024, 1) }} KB
                                            @if ($attachment->uploaded_by_name)
                                                | {{ $attachment->uploaded_by_name }}
                                            @endif
                                        </small>
                                    </span>
                                    <span class="mb-0">
                                        <input class="check" id="delete-goods-receipt-attachment-{{ $attachment->id }}"
                                            name="delete_attachments[]" type="checkbox" value="{{ $attachment->id }}"
                                            @checked(in_array($attachment->id, old('delete_attachments', []), true)) />
                                        <span class="fg-danger">
                                            {{ __('goods_receipts.actions.remove_attachment') }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-3">
                    <label class="form-label fw-bold" for="notes">
                        {{ __('goods_receipts.form.notes') }}
                    </label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $goodsReceipt->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a class="btn btn-solid theme-secondary"
                        href="{{ route('purchase.goods-receipts.show', $documentNumber) }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>
                    <button class="btn btn-solid theme-accent" type="submit">
                        <i class="me-1" data-lucide="save"></i>
                        {{ __('common.actions.update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
