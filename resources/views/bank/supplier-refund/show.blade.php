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
                    {{ __('payments.sub_type.supplier_refund') }} | {{ $refund->company_name ?: '-' }}
                </span>
            </div>
            <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
                @if ($refund->isPosted())
                    <span class="badge theme-success d-flex align-items-center">
                        {{ __('journal_entries.status_approved') }}
                    </span>
                @elseif ($refund->isCancelled())
                    <span class="badge theme-danger d-flex align-items-center">
                        {{ __('ledgers.status_cancelled') }}
                    </span>
                @else
                    <form autocomplete="off" action="{{ route('bank.supplier-refunds.approve', $documentNumber) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm text-nowrap" type="submit">
                            <i class="me-1" data-lucide="book-check"></i>
                            {{ __('journal_entries.approve_button') }}
                        </button>
                    </form>
                @endif

                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('bank.supplier-refunds.pdf', $documentNumber) }}" target="_blank">
                    <i class="me-1" data-lucide="file-type-2"></i>
                    {{ __('payments.actions.print_pdf') }}
                </a>

                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.supplier-refunds.index') }}">
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
                {{-- The account the money came back into, named on the document rather
                     than inferred: the transfer type only says how it travelled. --}}
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('payments.form.received_into') }}</span>
                        <strong>{{ $refund->moneyAccount()?->name ?? '-' }}</strong>
                    </div>
                </div>
            </div>

            @if ($refund->notes)
                <div class="mt-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('ledgers.table.column_notes') }}</span>
                    {{ $refund->notes }}
                </div>
            @endif
        </div>
    </div>
@endsection
