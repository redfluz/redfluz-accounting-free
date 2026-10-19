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

@props(['voucher', 'documentNumber'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">
                    {{ __('payments.sub_type.payment_voucher') }} | {{ $voucher->company_name ?: '-' }}
                </span>
            </div>
            <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
                @if ($voucher->isPosted())
                    <span class="badge theme-success d-flex align-items-center">
                        {{ __('journal_entries.status_approved') }}
                    </span>
                @elseif ($voucher->isCancelled())
                    <span class="badge theme-danger d-flex align-items-center">
                        {{ __('ledgers.status_cancelled') }}
                    </span>
                @else
                    <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.payment-vouchers.edit', $documentNumber) }}">
                        <i class="me-1" data-lucide="pencil"></i>
                        {{ __('common.actions.edit') }}
                    </a>
                    <form autocomplete="off" action="{{ route('bank.payment-vouchers.destroy', $documentNumber) }}" method="POST"
                        onsubmit="return confirm('{{ __('common.messages.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-solid theme-danger btn-sm" type="submit">
                            <i class="me-1" data-lucide="trash-2"></i>
                            {{ __('common.actions.delete') }}
                        </button>
                    </form>
                    <form autocomplete="off" action="{{ route('bank.payment-vouchers.approve', $documentNumber) }}"
                        method="POST">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm text-nowrap" type="submit">
                            <i class="me-1" data-lucide="book-check"></i>
                            {{ __('journal_entries.approve_button') }}
                        </button>
                    </form>
                @endif

                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.payment-vouchers.index') }}">
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
                        <strong>{{ $voucher->reference_number ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_date') }}</span>
                        <strong>{{ $voucher->date->format(Setting::getDefaultDate()) }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_total_amount') }}</span>
                        <strong class="fg-primary">{{ number_format((float) $voucher->total_amount, 2) }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('payments.form.payment_transfer') }}</span>
                        <strong>{{ $voucher->payment_transfer_type?->label() ?? '-' }}</strong>
                    </div>
                </div>
            </div>

            {{-- The double entry. Each line carries one side only, which is why
                 the debit and credit columns are never both filled on a row. --}}
            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
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
                        @foreach ($voucher->charts as $line)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                {{-- Debit Account: Code ~ Name --}}
                                <td>
                                    @if ($line->debitChart)
                                        {{ $line->debitChart->code }} ~ {{ $line->debitChart->name }}
                                    @else
                                        {{ $line->debit_acc ?? '-' }}
                                    @endif
                                </td>

                                {{-- Credit Account: Code ~ Name --}}
                                <td>
                                    @if ($line->creditChart)
                                        {{ $line->creditChart->code }} ~ {{ $line->creditChart->name }}
                                    @else
                                        {{ $line->credit_acc ?? '-' }}
                                    @endif
                                </td>
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
                                {{ number_format((float) $voucher->charts->whereNotNull('debit_id')->sum('debit_amount'), 2) }}
                            </td>
                            <td class="text-end">
                                {{ number_format((float) $voucher->charts->whereNotNull('credit_id')->sum('credit_amount'), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($voucher->notes)
                <div class="mt-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('ledgers.table.column_notes') }}</span>
                    {{ $voucher->notes }}
                </div>
            @endif
        </div>
    </div>
@endsection
