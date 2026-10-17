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

use App\Enums\GoodsReceiptCompletion;
use App\Enums\ProductMovementStatus;
use Carbon\Carbon;

?>
@php use App\Models\System\Setting; @endphp

@props(['goodsReceipts', 'search', 'startDate', 'endDate', 'completion', 'completionOptions'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('goods_receipts.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('goods_receipts.subtitle') }}</p>
            </div>
            <a class="btn btn-solid theme-success btn-sm" href="{{ route('purchase.goods-receipts.create') }}">
                <i class="me-1" data-lucide="plus"></i>
                {{ __('common.actions.add') }}
            </a>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            @include('partials.date-range-filter', [
                'action' => route('purchase.goods-receipts.index'),
                'search' => $search,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'placeholder' => __('goods_receipts.form.search_placeholder'),
                'selectName' => 'completion',
                'selectLabel' => __('goods_receipts.completion.label'),
                'selectCurrent' => $completion?->value ?? '',
                'selectOptions' => $completionOptions,
            ])

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('goods_receipts.table.column_document_number') }}</th>
                            <th>{{ __('goods_receipts.table.column_purchase_invoice') }}</th>
                            <th>{{ __('goods_receipts.table.column_date') }}</th>
                            <th>{{ __('goods_receipts.table.column_company') }}</th>
                            <th>{{ __('goods_receipts.table.column_stock_location') }}</th>
                            <th class="text-end">{{ __('goods_receipts.table.column_lines') }}</th>
                            <th class="text-end">{{ __('goods_receipts.table.column_quantity') }}</th>
                            <th>{{ __('goods_receipts.table.column_status') }}</th>
                            <th>{{ __('goods_receipts.table.column_completion') }}</th>
                            <th class="text-center">{{ __('goods_receipts.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($goodsReceipts as $goodsReceipt)
                            @php($status = $goodsReceipt->status->value ?? $goodsReceipt->status)
                            @php($bucket = GoodsReceiptCompletion::fromReceivingFlags((bool) $goodsReceipt->purchase_order_is_partial_received, (bool) $goodsReceipt->purchase_order_is_received))
                                        @php($canAdjust = $status === ProductMovementStatus::ACTIVE->value)
                                        @php($canDelete = $status === ProductMovementStatus::PENDING->value)
                            <tr>
                                <td class="fw-bold">{{ $goodsReceipt->document_number }}</td>
                                <td>{{ $goodsReceipt->reference_number }}</td>
                                <td>{{ Carbon::parse($goodsReceipt->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $goodsReceipt->company_name ?? '-' }}</td>
                                <td>{{ $goodsReceipt->stock_location_name }}</td>
                                <td class="text-end">{{ $goodsReceipt->line_count }}</td>
                                <td class="text-end">{{ number_format((float) $goodsReceipt->total_quantity, 4) }}</td>
                                <td>
                                    <span class="badge {{ $status === ProductMovementStatus::ACTIVE->value ? 'theme-success' : 'theme-warning fg-black' }}">
                                        {{ __('product_ledgers.status.' . $status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $bucket->badgeClass() }}">{{ $bucket->label() }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a
                                            class="btn btn-solid theme-primary btn-sm"
                                            href="{{ route('purchase.goods-receipts.show', $goodsReceipt->document_number) }}"
                                            title="{{ __('common.actions.view') }}"
                                        >
                                            <i data-lucide="file-text"></i>
                                        </a>
                                        @if ($canDelete)
                                            <a
                                                class="btn btn-sm btn-solid theme-warning me-1"
                                                href="{{ route('purchase.goods-receipts.edit', $goodsReceipt->document_number) }}"
                                                title="{{ __('common.actions.edit') }}"
                                            >
                                                <i data-lucide="square-pen"></i>
                                            </a>
                                            <form autocomplete="off" action="{{ route('purchase.goods-receipts.destroy', $goodsReceipt->document_number) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="btn btn-sm btn-outline theme-danger"
                                                    type="submit"
                                                    title="{{ __('common.actions.delete') }}"
                                                    data-confirm-message="{{ __('goods_receipts.messages.delete_confirmation') }}"
                                                >
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @elseif ($canAdjust)
                                            <a
                                                class="btn btn-sm btn-solid theme-primary me-1"
                                                href="{{ route('purchase.goods-receipts.adjust', $goodsReceipt->document_number) }}"
                                                title="{{ __('goods_receipts.adjustment.title') }}"
                                            >
                                                <i data-lucide="sliders-horizontal"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="10">
                                    {{ __('goods_receipts.messages.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $goodsReceipts->links() }}</div>
        </div>
    </div>
@endsection
