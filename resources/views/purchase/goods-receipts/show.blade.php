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

use App\Enums\ProductMovementStatus;

$status = $goodsReceipt->status->value ?? $goodsReceipt->status;

?>
@php use App\Models\System\Setting; @endphp

@props(['attachments', 'documentNumber', 'lines', 'goodsReceipt'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">
                    {{ $goodsReceipt->company_name ?? '-' }} | {{ __('product_ledgers.status.' . $status) }}
                </span>
            </div>
            <div class="d-flex gap-2">
                @if ($hasPending)
                    @if ($allPending)
                        <a class="btn btn-solid theme-warning btn-sm text-nowrap"
                            href="{{ route('purchase.goods-receipts.edit', $documentNumber) }}">
                            <i data-lucide="square-pen"></i>
                            {{ __('common.actions.edit') }}
                        </a>
                    @endif
                    <form autocomplete="off" action="{{ route('purchase.goods-receipts.approve', $documentNumber) }}"
                        method="POST">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm" type="submit"
                            data-confirm-message="{{ __('goods_receipts.messages.approve_confirmation') }}">
                            <i class=me-1" data-lucide="circle-check"></i>
                            {{ __('common.actions.post') }}
                        </button>
                    </form>
                @else
                    <span class="badge theme-success d-flex align-items-center">
                        {{ __('product_ledgers.status.active') }}
                    </span>
                    <a class="btn btn-sm btn-solid theme-primary"
                        href="{{ route('purchase.goods-receipts.adjust', $documentNumber) }}"
                        title="{{ __('goods_receipts.adjustment.title') }}">
                        <i class="me-1" data-lucide="sliders-horizontal"></i>
                        {{ __('goods_receipts.adjustment.title') }}
                    </a>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('purchase.goods-receipts.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="row g-3 mb-4">
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('goods_receipts.form.purchase_invoice') }}</span>
                        <strong>{{ $goodsReceipt->reference_number }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('goods_receipts.form.received_date') }}</span>
                        <strong>{{ $goodsReceipt->date->format(Setting::getDefaultDate()) }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('goods_receipts.table.column_company') }}</span>
                        <strong>{{ $goodsReceipt->company_name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('goods_receipts.form.stock_location') }}</span>
                        <strong>{{ $goodsReceipt->stock_location_name }}</strong>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                @foreach ($lines->groupBy(fn(array $line): string => $line['state']->value) as $rows)
                    @php($state = $rows->first()['state'])
                    <div class="md:col-4">
                        <div class="rounded border p-3 {{ $state->rowClass() }}">
                            <span class="fg-secondary small d-block">{{ $state->label() }}</span>
                            <strong>
                                {{ $rows->sum(fn(array $line): float => (float) $line['received_quantity']) }}
                                /
                                {{ $rows->sum(fn(array $line): float => (float) $line['ordered_quantity']) }}
                            </strong>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="table-responsive rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('goods_receipts.table.column_sku') }}</th>
                            <th>{{ __('goods_receipts.table.column_product') }}</th>
                            <th class="text-end">{{ __('goods_receipts.line_state.column_ordered') }}</th>
                            <th class="text-end">{{ __('goods_receipts.line_state.column_received') }}</th>
                            <th class="text-end">{{ __('goods_receipts.line_state.column_shortfall') }}</th>
                            <th class="text-end">{{ __('goods_receipts.table.column_unit_cost') }}</th>
                            <th class="text-end">{{ __('goods_receipts.table.column_total_cost') }}</th>
                            <th>{{ __('goods_receipts.line_state.column_state') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            <tr class="{{ $line['state']->rowClass() }}">
                                <td><code>{{ $line['product_sku'] }}</code></td>
                                <td>{{ $line['product_name'] }}</td>
                                <td class="text-end">{{ number_format((float) $line['ordered_quantity'], 4) }}</td>
                                <td class="text-end fw-bold">{{ number_format((float) $line['received_quantity'], 4) }}
                                </td>
                                <td class="text-end">
                                    {{ (float) $line['shortfall_quantity'] > 0 ? number_format((float) $line['shortfall_quantity'], 4) : '-' }}
                                </td>
                                <td class="text-end">{{ number_format((float) $line['unit_cost'], 2) }}</td>
                                <td class="fw-bold text-end">{{ number_format((float) $line['total_cost'], 2) }}</td>
                                <td>
                                    <span class="badge {{ $line['state']->badgeClass() }}">
                                        {{ $line['state']->label() }}
                                    </span>
                                    @if ($line['has_pending_correction'])
                                        <span class="badge theme-secondary">
                                            {{ __('goods_receipts.line_state.pending_correction') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-1 fw-bold">
                        <tr>
                            <td class="text-end" colspan="2">{{ __('common.labels.total') }}</td>
                            <td class="text-end">{{ number_format((float) $lines->sum('ordered_quantity'), 4) }}</td>
                            <td class="text-end">{{ number_format((float) $lines->sum('received_quantity'), 4) }}</td>
                            <td class="text-end">{{ number_format((float) $lines->sum('shortfall_quantity'), 4) }}</td>
                            <td></td>
                            <td class="text-end">{{ number_format((float) $lines->sum('total_cost'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($attachments->isNotEmpty())
                <h6 class="fw-bold mt-4">{{ __('goods_receipts.form.attachments') }}</h6>
                <div class="list-group">
                    @foreach ($attachments as $attachment)
                        <div class="list-group-item d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <div class="fw-bold">{{ $attachment->original_name }}</div>
                                <small class="badge theme-secondary">
                                    {{ number_format($attachment->file_size / 1024, 1) }} KB
                                    @if ($attachment->uploaded_by_name)
                                        | {{ $attachment->uploaded_by_name }}
                                    @endif
                                    | {{ $attachment->created_at->format(Setting::getDefaultDate('long_date_format')) }}
                                </small>
                            </div>
                            <a class="btn btn-solid theme-primary btn-sm"
                                href="{{ route('purchase.goods-receipts.attachments.download', [$documentNumber, $attachment]) }}">
                                <i class="me-1" data-lucide="download"></i>
                                {{ __('common.actions.download') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($goodsReceipt->notes)
                <div class="mt-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('goods_receipts.form.notes') }}</span>
                    {{ $goodsReceipt->notes }}
                </div>
            @endif
        </div>
    </div>
@endsection
