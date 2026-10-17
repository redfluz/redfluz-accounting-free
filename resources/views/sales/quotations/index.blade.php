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

use App\Enums\QuotationStatus;
use Carbon\Carbon;

?>
@php use App\Models\System\Setting; @endphp

@props(['quotations', 'search', 'startDate', 'endDate'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('quotations.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('quotations.subtitle') }}</p>
            </div>
            <a class="btn btn-solid theme-success btn-sm" href="{{ route('sales.quotations.create') }}">
                <i class="me-1" data-lucide="plus"></i>
                {{ __('common.actions.add') }}
            </a>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            @include('partials.date-range-filter', [
                'action' => route('sales.quotations.index'),
                'search' => $search,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'placeholder' => __('quotations.form.search_placeholder'),
            ])

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('quotations.table.column_quotation_number') }}</th>
                            <th>{{ __('quotations.table.column_date') }}</th>
                            <th>{{ __('quotations.table.column_expiry_date') }}</th>
                            <th>{{ __('quotations.table.column_company') }}</th>
                            <th>{{ __('quotations.table.column_reference_number') }}</th>
                            <th>{{ __('quotations.table.column_status') }}</th>
                            <th class="text-end">{{ __('quotations.table.column_total_amount') }}</th>
                            <th class="text-center">{{ __('quotations.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            @php($status = $quotation->status->value ?? $quotation->status)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $quotation->document_number }}</div>
                                    @if ($quotation->convertedSalesOrder)
                                        <small class="fg-success d-block">
                                            {{ __('quotations.table.converted_to') }}:
                                            {{ $quotation->convertedSalesOrder->document_number }}
                                        </small>
                                    @elseif ($quotation->convertedInvoice)
                                        <small class="badge theme-secondary">
                                            {{ __('quotations.table.converted_to') }}:
                                            {{ $quotation->convertedInvoice->document_number }}
                                        </small>
                                    @endif
                                </td>
                                <td>{{ Carbon::parse($quotation->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>
                                    {{ $quotation->expiry_date ? Carbon::parse($quotation->expiry_date)->format(Setting::getDefaultDate()) : '-' }}
                                </td>
                                <td>{{ $quotation->company_name ?? '-' }}</td>
                                <td>{{ $quotation->reference_number ?? '-' }}</td>
                                <td>
                                    <span @class([
                                        'badge',
                                        'bg-secondary' => $status === QuotationStatus::DRAFT->value,
                                        'bg-info' => $status === QuotationStatus::SENT->value,
                                        'bg-success' => in_array(
                                            $status,
                                            [QuotationStatus::ACCEPTED->value, QuotationStatus::CONVERTED->value],
                                            true),
                                        'bg-danger' => $status === QuotationStatus::REJECTED->value,
                                        'bg-warning fg-black' => $status === QuotationStatus::EXPIRED->value,
                                    ])>
                                        {{ __('quotations.status.' . $status) }}
                                    </span>
                                </td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $quotation->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a class="btn btn-solid theme-primary btn-sm"
                                            href="{{ route('sales.quotations.show', $quotation->document_number) }}"
                                            title="{{ __('quotations.actions.view') }}">
                                            <i data-lucide="file-text"></i>
                                        </a>

                                        @if (!$quotation->is_converted)
                                            <a class="btn btn-sm btn-solid theme-warning me-1"
                                                href="{{ route('sales.quotations.edit', $quotation->document_number) }}"
                                                title="{{ __('common.actions.edit') }}">
                                                <i data-lucide="square-pen"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="8">
                                    {{ __('quotations.messages.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $quotations->links() }}</div>
        </div>
    </div>
@endsection
