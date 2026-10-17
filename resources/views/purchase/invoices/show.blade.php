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

$canApprove = auth()->user()->hasFilePermission(FileResources::PURCHASE_INVOICES, FileAction::APPROVE);
$canRemove = auth()->user()->hasFilePermission(FileResources::PURCHASE_INVOICES, FileAction::REMOVE);
$canUpdate = auth()->user()->hasFilePermission(FileResources::PURCHASE_INVOICES, FileAction::UPDATE);
$canReverse = $canApprove && $isPosted && ! $invoice->undone_at;
?>

@props(['documentNumber', 'invoice', 'lines', 'payments', 'isPosted', 'indexRoute'])

@extends('layouts.app')

@section('content')
    @php
        $firstLine = $lines->first();
        $subtotal = $lines->sum(
            fn($line) => (float) $line->subtotal_amount !== 0.0
                ? (float) $line->subtotal_amount
                : (float) $line->quantity * (float) $line->unit_price,
        );
        $discountTotal = $lines->sum('discount_amount');
        $discountType = $firstLine?->discount_type?->value;
        $taxTotal = $lines->sum('tax_amount');
        $grandTotal = $lines->sum(
            fn($line) => (float) $line->line_total_amount !== 0.0
                ? (float) $line->line_total_amount
                : (float) $line->total_amount,
        );
        $taxMode = $firstLine?->tax_mode?->value ?? $firstLine?->tax_mode;
    @endphp
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">{{ $invoice->company?->name ?? '-' }} | {{ $invoice->date }}</span>
            </div>
            <div class="d-flex gap-2">
                @if ($isPosted)
                    <span class="badge theme-success d-flex align-items-center">
                        {{ __('journal_entries.status_approved') }}
                    </span>
                    @if ($canReverse)
                        <button class="btn btn-solid theme-warning btn-sm text-nowrap" data-bs-toggle="dialog"
                            data-bs-target="#reverseJournalModal" type="button">
                            <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                        </button>
                    @endif
                @elseif ($canApprove)
                    <form action="{{ route('purchase.invoices.approve', $documentNumber) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm text-nowrap" type="submit">
                            <i data-lucide="circle-check"></i>
                            {{ __('common.actions.post') }}
                        </button>
                    </form>
                @endif

                @if (!$isPosted && $canUpdate)
                    <a class="btn btn-solid theme-warning btn-sm text-nowrap"
                        href="{{ route('purchase.invoices.edit', $documentNumber) }}">
                        <i data-lucide="square-pen"></i>
                        {{ __('common.actions.edit') }}
                    </a>
                @endif

                @if (!$isPosted && $canRemove)
                    <form action="{{ route('purchase.invoices.destroy', $documentNumber) }}" method="POST"
                        data-confirm-message="{{ __('ledgers.delete_invoice_confirmation') }}" data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-solid theme-danger btn-sm text-nowrap" type="submit">
                            <i data-lucide="trash-2"></i>
                            {{ __('common.actions.delete') }}
                        </button>
                    </form>
                @endif

                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('purchase.invoices.pdf', $documentNumber) }}" target="_blank">
                    <i class="me-1" data-lucide="printer"></i>
                    {{ __('common.actions.print_pdf') }}
                </a>

                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route($indexRoute) }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <div class="row g-3 mb-4">
                <div class="md:col-4">
                    <div class="rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('companies.table.column_name') }}</span>
                        <strong>{{ $invoice->company_name ?? $invoice->company?->name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-4">
                    <div class="rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('contacts.table.column_name') }}</span>
                        <strong>{{ $invoice->contact_name ?? $invoice->contact?->name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-4">
                    <div class="rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_date') }}</span>
                        <strong>{{ $invoice->date }}</strong>
                    </div>
                </div>
            </div>
            <div class="table-responsive mb-4 rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('ledgers.table.column_product') }}</th>
                            <th>{{ __('ledgers.table.column_description') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_quantity') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_unit_price') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_discount_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_tax_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            <tr>
                                <td>{{ $line->product?->name ?? '-' }}</td>
                                <td>{{ $line->description ?? '-' }}</td>
                                <td class="text-end">{{ $line->quantity }}</td>
                                <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->discount_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->tax_amount, 2) }}</td>
                                <td class="fw-bold text-end">
                                    {{ number_format(
                                        (float) $line->line_total_amount !== 0.0 ? (float) $line->line_total_amount : (float) $line->total_amount,
                                        2,
                                    ) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-1">
                        <tr>
                            <th class="text-end" colspan="6">{{ __('ledgers.table.column_subtotal_amount') }}</th>
                            <th class="text-end">{{ number_format($subtotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">
                                {{ __('ledgers.table.column_discount_amount') }}
                                @if ($firstLine?->discount_name)
                                    <span class="fg-secondary fw-normal">({{ $firstLine->discount_name }} -
                                        {{ $discountType === 'percent' ? number_format((float) $firstLine->discount_value, 4) . '%' : $currencySymbol . ' ' . number_format((float) $firstLine->discount_value, 2) }})</span>
                                @endif
                            </th>
                            <th class="text-end">{{ number_format($discountTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">
                                {{ __('ledgers.table.column_tax_amount') }}
                                @if ($firstLine?->tax_name)
                                    <span class="fg-secondary fw-normal">({{ $firstLine->tax_name }})</span>
                                @endif
                                @if ($taxMode)
                                    <span class="fg-secondary fw-normal">({{ __('ledgers.tax_mode.' . $taxMode) }})</span>
                                @endif
                            </th>
                            <th class="text-end">{{ number_format($taxTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">{{ __('common.labels.total') }}</th>
                            <th class="fg-primary text-end">{{ number_format($grandTotal, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if ($invoice->notes)
                <div class="mt-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('ledgers.table.column_notes') }}</span>
                    {{ $invoice->notes }}
                </div>
            @endif
            <h6 class="fw-bold">{{ __('ledgers.payments_title') }}</h6>
            @forelse ($payments as $payment)
                <div class="align-items-center d-flex justify-content-between mb-2 rounded border p-3">
                    <div>
                        <span>
                            {{ __('ledgers.sub_type.' . ($payment->subtype->value ?? $payment->subtype)) }} -
                            {{ $payment->document_number }}
                        </span>
                        <strong class="d-block">{{ number_format((float) $payment->total_amount, 2) }}</strong>
                    </div>

                    <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                        href="{{ route('purchase.invoices.payments.pdf', $payment->document_number) }}" target="_blank">
                        <i class="me-1" data-lucide="printer"></i>
                        {{ __('common.actions.print_pdf') }}
                    </a>
                </div>
            @empty
                <p class="fg-secondary mb-0">-</p>
            @endforelse
        </div>
    </div>
    @if ($canReverse)
        <dialog class="dialog" id="reverseJournalModal" aria-labelledby="reverseJournalModalLabel">
            <form method="POST" action="{{ route('purchase.invoices.reverse', $documentNumber) }}">
                @csrf
                @method('PATCH')
                <div class="dialog-header">
                    <h5 class="dialog-title" id="reverseJournalModalLabel">
                        {{ __('journal_entries.reverse_title') }}
                    </h5>
                    <button class="btn-close" data-bs-dismiss="dialog" type="button"
                        aria-label="{{ __('common.actions.close') }}"></button>
                </div>
                <div class="dialog-body">
                    <div class="alert alert theme-warning">{{ __('journal_entries.reverse_explanation') }}</div>
                    <div class="alert alert theme-danger">{{ __('journal_entries.reverse_product_ledger_warning') }}</div>
                    <div class="mb-3">
                        <label class="form-label"
                            for="reversal_date">{{ __('journal_entries.reversal_date') }}</label>
                        <label class="form-control form-adorn">
                            <div class="form-adorn-icon" aria-hidden="true">
                                <i data-lucide="calendar"></i>
                            </div>
                            <input
                                class="form-ghost @error('reversal_date') is-invalid @enderror" id="reversal_date"
                                name="reversal_date" type="text" data-bs-toggle="datepicker"
                                value="{{ old('reversal_date', now()->toDateString()) }}" min="{{ $postingDateMin ?? '' }}"
                                max="{{ $postingDateMax ?? '' }}" required />
                        </label>
                        @error('reversal_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label" for="reason">{{ __('journal_entries.reversal_reason') }}</label>
                        <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="4"
                            maxlength="1000" placeholder="{{ __('journal_entries.reversal_reason_placeholder') }}" required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="dialog-footer">
                    <button class="btn btn-solid theme-secondary" data-bs-dismiss="dialog" type="button">
                        {{ __('common.actions.cancel') }}</button><button class="btn btn-solid theme-warning"
                        type="submit" data-confirm-message="{{ __('journal_entries.confirm_reverse') }}">
                        <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                    </button>
                </div>
            </form>
        </dialog>
        @if ($errors->has('reversal_date') || $errors->has('reason'))
            <script>
                document.addEventListener('DOMContentLoaded', () =>
                    bootstrap.Dialog.getOrCreateInstance(document.getElementById('reverseJournalModal')).show(),
                );
            </script>
        @endif
    @endif
@endsection
