<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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
@php use App\Models\System\Setting; @endphp

@props(['refund', 'documentNumber'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">
                    {{ __('refunds.title') }} | {{ $refund->company_name ?: '-' }}
                </span>
            </div>
            <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
                <span class="badge theme-danger d-flex align-items-center">
                    {{ __('ledgers.status_cancelled') }}
                </span>
                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('bank.customer-refunds.cancelled.pdf', $documentNumber) }}" target="_blank">
                    <i class="me-1" data-lucide="file-type-2"></i>
                    {{ __('payments.actions.print_pdf') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.customer-refunds.cancelled.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('ledgers.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_reference_number') }}</span>
                        <strong>{{ $refund->reference_number ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_date') }}</span>
                        <strong>{{ $refund->date->format(Setting::getDefaultDate()) }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_total_amount') }}</span>
                        <strong class="fg-primary">{{ number_format((float) $refund->total_amount, 2) }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('payments.form.payment_transfer') }}</span>
                        <strong>{{ $refund->payment_transfer_type?->label() ?? '-' }}</strong>
                    </div>
                </div>
            </div>

            <div class="table-responsive rounded border mb-4">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('payments.table.column_entry') }}</th>
                            <th>{{ __('ledgers.table.column_debit_acc') }}</th>
                            <th>{{ __('ledgers.table.column_credit_acc') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($refund->charts as $line)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $line->debit_acc ?? '-' }}</td>
                                <td>{{ $line->credit_acc ?? '-' }}</td>
                                <td class="text-end">
                                    {{ $line->debit_id !== null ? number_format((float) $line->debit_amount, 2) : '' }}
                                </td>
                                <td class="text-end">
                                    {{ $line->credit_id !== null ? number_format((float) $line->credit_amount, 2) : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-1 fw-bold border-top">
                        <tr>
                            <td colspan="3" class="text-end">{{ __('common.labels.total') }}:</td>
                            <td class="text-end">
                                {{ number_format((float) $refund->charts->whereNotNull('debit_id')->sum('debit_amount'), 2) }}
                            </td>
                            <td class="text-end">
                                {{ number_format((float) $refund->charts->whereNotNull('credit_id')->sum('credit_amount'), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($refund->notes)
                <div class="mb-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('ledgers.table.column_notes') }}</span>
                    {{ $refund->notes }}
                </div>
            @endif

            {{-- Who prepared it, who approved it, who $cancelled it, each with a
                 box to sign. --}}
            @include('bank.customer-refund.partials.audit', ['document' => $refund])
        </div>
    </div>
@endsection
