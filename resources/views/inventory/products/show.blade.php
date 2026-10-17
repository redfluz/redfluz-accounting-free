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
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementStatus;

$canUpdate = auth()->user()->hasFilePermission(FileResources::PRODUCTS, FileAction::UPDATE);
$primaryImagePath = $product->attachments->first()?->file_path ?? $product->image_path;
?>
@php use App\Models\System\Setting; @endphp

@props(['product', 'summary', 'currentBalance', 'stockByLocation', 'movements', 'variantStock', 'variantTotals'])

@extends('layouts.app')

@section('content')
    <div class="card mb-4 shadow-sm">
        <div class="card-body py-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    @if ($primaryImagePath)
                        <img class="object-fit-cover rounded border" src="{{ asset('storage/' . $primaryImagePath) }}"
                            alt="{{ $product->name }}" width="72" height="72" />
                    @else
                        <div class="bg-2 d-flex align-items-center justify-content-center rounded border"
                            style="width: 72px; height: 72px">
                            <i data-lucide="package"></i>
                        </div>
                    @endif

                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="fw-bold mb-1">{{ $product->name }}</h3>
                            <span class="badge {{ $product->is_service ? 'theme-info' : 'theme-success' }}">
                                {{ $product->is_service ? __('products.types.service') : __('products.types.physical') }}
                            </span>
                        </div>
                        <div class="badge theme-secondary">
                            <code>{{ $product->sku }}</code>
                            @if ($product->barcode)
                                <span class="mx-2">|</span>
                                {{ __('products.form.barcode') }}:
                                <code>{{ $product->barcode }}</code>
                            @endif
                            @if ($product->mpn)
                                <span class="mx-2">|</span>
                                {{ __('products.form.mpn') }}:
                                <code>{{ $product->mpn }}</code>
                            @endif
                            <span class="mx-2">|</span>
                            {{ __('products.table.column_brand') }}: {{ $product->brand?->name ?? '-' }}
                            <span class="mx-2">|</span>
                            {{ __('products.table.column_category') }}: {{ $product->category?->name ?? '-' }}
                            <span class="mx-2">|</span>
                            {{ __('products.table.column_product_type') }}: {{ $product->type?->name ?? '-' }}
                            <span class="mx-2">|</span>
                            {{ __('products.table.column_base_price') }}: {{ $currencySymbol }}
                            {{ number_format((float) $product->base_price, 2) }}
                            <span class="mx-2">|</span>
                            {{ __('products.form.warranty') }}:
                            {{ $product->warranty_months === null ? __('products.warranty.unlimited') : ($product->warranty_months === 0 ? __('products.warranty.none') : __('products.warranty.months', ['count' => $product->warranty_months])) }}
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    @if ($canUpdate)
                        <a class="btn btn-solid theme-warning" href="{{ route('inventory.products.edit', $product) }}">
                            <i class="me-1" data-lucide="square-pen"></i>
                            {{ __('common.actions.edit') }}
                        </a>
                    @endif
                    <a class="btn btn-solid theme-secondary" href="{{ route('inventory.products.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('products.attachments.title') }}</h4>
            <p class="fg-secondary small mb-0">{{ __('products.attachments.shared_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @forelse ($product->attachments as $attachment)
                    <div class="col-6 md:col-4 xl:col-2">
                        <a href="{{ route('inventory.products.attachments.download', [$product, $attachment]) }}"
                            target="_blank">
                            <img class="img-thumbnail object-fit-cover w-100"
                                src="{{ route('inventory.products.attachments.download', [$product, $attachment]) }}"
                                alt="{{ $attachment->original_name }}" style="height: 150px" />
                        </a>
                        <div class="small text-truncate mt-1" title="{{ $attachment->original_name }}">
                            {{ $attachment->original_name }}
                        </div>
                    </div>
                @empty
                    <div class="fg-secondary py-3 text-center">{{ __('products.attachments.empty') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('products.variants.title') }}</h4>
            <p class="fg-secondary small mb-0">{{ __('products.variants.hint') }}</p>
        </div>
        <div class="card-body">
            <div class="table-responsive rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('products.variants.name') }}</th>
                            <th>{{ __('products.table.column_sku') }}</th>
                            <th>{{ __('products.variants.details') }}</th>
                            <th class="text-end">{{ __('products.table.column_base_price') }}</th>
                            <th class="text-end">{{ __('products.table.column_selling_price') }}</th>
                            <th class="text-end">{{ __('products.variants.total_in') }}</th>
                            <th class="text-end">{{ __('products.variants.total_out') }}</th>
                            <th class="text-end">{{ __('products.movement.current_balance') }}</th>
                            <th>{{ __('products.form.warranty') }}</th>
                            <th>{{ __('products.variants.use_main_selling_price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($product->variants as $variant)
                            <tr>
                                <td class="fw-bold">{{ $variant->variant_name }}</td>
                                <td><code>{{ $variant->sku }}</code></td>
                                <td>{{ $variant->variant_details ?? '-' }}</td>
                                <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $variant->base_price, 2) }}</td>
                                <td class="fg-success fw-bold text-end">
                                    {{ $currencySymbol }} {{ number_format((float) $variant->selling_price, 2) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format((float) ($variantStock[$variant->id]['in'] ?? 0), 4) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format((float) ($variantStock[$variant->id]['out'] ?? 0), 4) }}
                                </td>
                                <td class="fw-bold text-end">
                                    {{ number_format((float) ($variantStock[$variant->id]['balance'] ?? 0), 4) }}
                                </td>
                                <td>
                                    {{ $variant->warranty_months === null ? __('products.warranty.unlimited') : ($variant->warranty_months === 0 ? __('products.warranty.none') : __('products.warranty.months', ['count' => $variant->warranty_months])) }}
                                </td>
                                <td>
                                    {{ $variant->inherits_selling_price ? __('products.variants.same_price') : __('products.variants.own_price') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="10">
                                    {{ __('products.variants.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($product->variants->isNotEmpty())
                        <tfoot class="bg-1">
                            <tr>
                                <th colspan="5">{{ __('products.variants.total') }}</th>
                                <th class="text-end">{{ number_format((float) $variantTotals['in'], 4) }}</th>
                                <th class="text-end">{{ number_format((float) $variantTotals['out'], 4) }}</th>
                                <th class="text-end">{{ number_format((float) $variantTotals['balance'], 4) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="md:col-6 xl:col-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="fg-secondary small text-uppercase">{{ __('products.movement.current_balance') }}</div>
                    <div class="fg-primary fs-md fw-bold">{{ number_format((float) $currentBalance, 4) }}</div>
                </div>
            </div>
        </div>
        <div class="md:col-6 xl:col-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="fg-secondary small text-uppercase">{{ __('products.movement.total_received') }}</div>
                    <div class="fg-success fs-md fw-bold">
                        {{ number_format((float) $summary->received_quantity, 4) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="md:col-6 xl:col-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="fg-secondary small text-uppercase">{{ __('products.movement.total_issued') }}</div>
                    <div class="fg-danger fs-md fw-bold">
                        {{ number_format((float) $summary->issued_quantity, 4) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="md:col-6 xl:col-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="fg-secondary small text-uppercase">{{ __('products.movement.pending') }}</div>
                    <div class="fw-bold">
                        <span class="fg-success">
                            +{{ number_format((float) $summary->pending_in_quantity, 4) }}
                        </span>
                        <span class="fg-secondary mx-2">/</span>
                        <span class="fg-danger">
                            -{{ number_format((float) $summary->pending_out_quantity, 4) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('products.movement.location_title') }}</h4>
            <p class="fg-secondary small mb-0">{{ __('products.movement.location_subtitle') }}</p>
        </div>
        <div class="card-body">
            <div class="table-responsive rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('products.movement.location_code') }}</th>
                            <th>{{ __('products.movement.location_name') }}</th>
                            <th>{{ __('products.movement.location_type') }}</th>
                            <th class="text-end">{{ __('products.movement.available') }}</th>
                            <th class="text-end">{{ __('products.movement.pending_in') }}</th>
                            <th class="text-end">{{ __('products.movement.pending_out') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stockByLocation as $stockLocation)
                            <tr>
                                <td><code>{{ $stockLocation->code }}</code></td>
                                <td class="fw-bold">{{ $stockLocation->name }}</td>
                                <td>{{ __('stock_locations.type.' . $stockLocation->type) }}</td>
                                <td class="fg-primary fs-xl fw-bold text-end">
                                    {{ number_format((float) $stockLocation->available_quantity, 4) }}
                                </td>
                                <td class="fg-success fw-bold text-end">
                                    {{ number_format((float) $stockLocation->pending_in_quantity, 4) }}
                                </td>
                                <td class="fg-danger fw-bold text-end">
                                    {{ number_format((float) $stockLocation->pending_out_quantity, 4) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="6">
                                    {{ __('products.movement.location_empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('products.movement.title') }}</h4>
            <p class="fg-secondary small mb-0">{{ __('products.movement.subtitle') }}</p>
        </div>

        <div class="card-body">
            <div class="table-responsive rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('products.movement.date') }}</th>
                            <th>{{ __('products.movement.document') }}</th>
                            <th>{{ __('products.movement.item') }}</th>
                            <th>{{ __('products.movement.source') }}</th>
                            <th>{{ __('products.movement.location') }}</th>
                            <th>{{ __('products.movement.company') }}</th>
                            <th>{{ __('products.movement.status') }}</th>
                            <th>{{ __('products.movement.bucket') }}</th>
                            <th class="text-end">{{ __('products.movement.quantity_in') }}</th>
                            <th class="text-end">{{ __('products.movement.quantity_out') }}</th>
                            <th class="text-end">{{ __('products.movement.unit_cost') }}</th>
                            <th class="text-end">{{ __('products.movement.running_balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->date->format(Setting::getDefaultDate()) }}</td>
                                <td>
                                    @if ($movement->document_url)
                                        <a class="fw-bold text-decoration-none" href="{{ $movement->document_url }}">
                                            {{ $movement->document_number }}
                                        </a>
                                    @else
                                        <span class="fw-bold">{{ $movement->document_number }}</span>
                                    @endif
                                    @if ($movement->reference_number)
                                        <div class="fg-secondary small">{{ $movement->reference_number }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $movement->product?->variant_name ?? $product->name }}</div>
                                    <code class="small">{{ $movement->product?->sku ?? $product->sku }}</code>
                                </td>
                                <td>{{ __('products.movement.sources.' . $movement->source_type->value) }}</td>
                                <td>{{ $movement->stock_location_name }}</td>
                                <td>{{ $movement->company_name ?? '-' }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($movement->status) {
                                            ProductMovementStatus::ACTIVE => 'bg-success',
                                            ProductMovementStatus::PENDING => 'bg-warning fg-black',
                                            ProductMovementStatus::CANCELLED => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">
                                        {{ __('products.movement.statuses.' . $movement->status->value) }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="badge theme-secondary">{{ __('products.movement.buckets.' . $movement->stock_bucket->value) }}</span>
                                </td>
                                <td class="fg-success fw-bold text-end">
                                    @if ($movement->direction === ProductMovementDirection::IN)
                                        {{ number_format((float) $movement->quantity, 4) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="fg-danger fw-bold text-end">
                                    @if ($movement->direction === ProductMovementDirection::OUT)
                                        {{ number_format((float) $movement->quantity, 4) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $movement->unit_cost, 2) }}</td>
                                <td class="fw-bold text-end">
                                    {{ number_format((float) $movement->running_balance, 4) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="12">
                                    {{ __('products.movement.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $movements->links() }}</div>
        </div>
    </div>
@endsection
