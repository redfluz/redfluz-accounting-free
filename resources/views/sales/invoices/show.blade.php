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

$canApprove = auth()->user()->hasFilePermission(FileResources::SALES_INVOICES, FileAction::APPROVE);
$canRemove = auth()->user()->hasFilePermission(FileResources::SALES_INVOICES, FileAction::REMOVE);
$canUpdate = auth()->user()->hasFilePermission(FileResources::SALES_INVOICES, FileAction::UPDATE);
$canReverse = $canApprove && $isPosted && ! $invoice->reversal_journal_id && ! $invoice->undone_at;
$canReturn = $isPosted && $canUpdate && ! $invoice->reversal_journal_id && ! $invoice->undone_at;
$canUndo = $canReverse || ($canReturn && $returnableLines->isNotEmpty());
?>

@props([
    'documentNumber',
    'invoice',
    'lines',
    'payments',
    'isPosted',
    'indexRoute',
    'lineReturns',
    'returnableLines',
    'canDeductInventory' => false,
    'inventoryDeducted' => false,
])

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
        $discountType = $firstLine?->discount_type;
        $taxTotal = $lines->sum('tax_amount');
        $grandTotal = $lines->sum(
            fn($line) => (float) $line->line_total_amount !== 0.0
                ? (float) $line->line_total_amount
                : (float) $line->total_amount,
        );
        $taxMode = $firstLine?->tax_mode;
    @endphp
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">{{ $invoice->company->name ?? '-' }} | {{ $invoice->date }}</span>
                @if ($invoice->quotation)
                    <span class="fg-secondary small d-block">
                        {{ __('quotations.table.column_quotation_number') }}:
                        <a href="{{ route('sales.quotations.show', $invoice->quotation->document_number) }}">
                            {{ $invoice->quotation->document_number }}
                        </a>
                    </span>
                @endif
                @if ($invoice->salesOrder)
                    <span class="fg-secondary small d-block">
                        {{ __('sales_orders.table.number') }}:
                        <a href="{{ route('sales.sales-orders.show', $invoice->salesOrder) }}">
                            {{ $invoice->salesOrder->document_number }}
                        </a>
                    </span>
                @endif
                @if ($invoice->reference_number && $invoice->reference_number !== $documentNumber && (! $invoice->salesOrder || $invoice->reference_number !== $invoice->salesOrder->document_number))
                    <span class="fg-secondary small d-block">
                        {{ __('delivery_orders.table.number') }}:
                        <a href="{{ route('sales.delivery-orders.show', $invoice->reference_number) }}">
                            {{ $invoice->reference_number }}
                        </a>
                    </span>
                @endif
            </div>
            <div class="d-flex gap-2">
                @if ($isPosted)
                    <span class="badge theme-success d-flex align-items-center">
                        {{ __('journal_entries.status_approved') }}
                    </span>
                    @if ($canUndo)
                        <button class="btn btn-solid theme-warning btn-sm text-nowrap" data-bs-toggle="dialog"
                            data-bs-target="#undoWizardModal" type="button">
                            <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                        </button>
                    @endif
                @elseif ($canApprove)
                    <form action="{{ route('sales.invoices.approve', $documentNumber) }}" method="POST">
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
                        href="{{ route('sales.invoices.edit', $documentNumber) }}">
                        <i data-lucide="square-pen"></i>
                        {{ __('common.actions.edit') }}
                    </a>
                @endif

                @if ($canDeductInventory && $canUpdate)
                    <form action="{{ route('sales.invoices.deduct-inventory', $documentNumber) }}" method="POST"
                        data-confirm-message="{{ __('invoices.deduct_inventory.confirm') }}" data-confirm-tone="danger">
                        @csrf
                        <button class="btn btn-sm btn-solid theme-warning me-1" type="submit"
                            title="{{ __('invoices.deduct_inventory.label') }}">
                            <i data-lucide="package-minus"></i>
                        </button>
                    </form>
                @endif

                @if (!$isPosted && $canRemove)
                    <form action="{{ route('sales.invoices.destroy', $documentNumber) }}" method="POST"
                        data-confirm-message="{{ __('journal_entries.confirm_reverse') }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-solid theme-danger btn-sm text-nowrap" type="submit">
                            <i data-lucide="trash-2"></i>
                            {{ __('common.actions.delete') }}
                        </button>
                    </form>
                @endif

                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('sales.invoices.pdf', $documentNumber) }}" target="_blank">
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
            <div class="table-responsive mb-4 rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
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
                                <td>{{ $line->product->name ?? '-' }}</td>
                                <td>{{ $line->description ?? '-' }}</td>
                                <td class="text-end">
                                    {{ $line->quantity }}
                                    @if (bccomp($lineReturns[$line->id]['returned'] ?? '0', '0', 4) === 1)
                                        <small class="fg-secondary d-block">
                                            {{ __('sales_returns.returned_indicator', ['qty' => $lineReturns[$line->id]['returned']]) }}
                                        </small>
                                    @endif
                                </td>
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
                                    <span class="fg-secondary fw-normal">({{ $firstLine?->discount_name }} -
                                        {{ $discountType === 'percent' ? number_format((float) $firstLine?->discount_value, 4) . '%' : $currencySymbol . ' ' . number_format((float) $firstLine?->discount_value, 2) }})</span>
                                @endif
                            </th>
                            <th class="text-end">{{ number_format($discountTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">
                                {{ __('ledgers.table.column_tax_amount') }}
                                @if ($firstLine?->tax_name)
                                    <span class="fg-secondary fw-normal">({{ $firstLine?->tax_name }})</span>
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
                        href="{{ route('sales.invoices.payments.pdf', $payment->document_number) }}" target="_blank">
                        <i class="me-1" data-lucide="printer"></i>
                        {{ __('common.actions.print_pdf') }}
                    </a>
                </div>
            @empty
                <p class="fg-secondary mb-0">-</p>
            @endforelse
        </div>
    </div>
    @if ($canUndo)
        <dialog class="dialog dialog-lg" id="undoWizardModal" aria-labelledby="undoWizardModalLabel">
            <div class="dialog-header">
                <h5 class="dialog-title" id="undoWizardModalLabel">
                    {{ __('sales_returns.wizard_title', ['document' => $documentNumber]) }}
                </h5>
                <button class="btn-close" data-bs-dismiss="dialog" type="button"
                    aria-label="{{ __('common.actions.close') }}"></button>
            </div>
            <div class="dialog-body">
                @if ($canReverse)
                    <button
                        class="btn btn-solid theme-warning w-100 mb-2 flex-column align-items-start text-start text-wrap"
                        type="button" data-bs-toggle="dialog" data-bs-target="#reverseJournalModal">
                        <strong>
                            <i data-lucide="undo-2"></i> {{ __('sales_returns.wizard_full_title') }}
                        </strong>
                        <span class="small fw-normal">{{ __('journal_entries.reverse_explanation') }}</span>
                    </button>
                @endif
                @if ($canReturn && $returnableLines->isNotEmpty())
                    <button
                        class="btn btn-solid theme-primary w-100 flex-column align-items-start text-start text-wrap"
                        type="button" data-bs-toggle="dialog" data-bs-target="#partialReturnModal">
                        <strong>
                            <i data-lucide="package-check"></i> {{ __('sales_returns.wizard_partial_title') }}
                        </strong>
                        <span class="small fw-normal">{{ __('sales_returns.wizard_partial_description') }}</span>
                    </button>
                @endif
            </div>
            <div class="dialog-footer">
                <button class="btn btn-solid theme-secondary" data-bs-dismiss="dialog" type="button">
                    {{ __('common.actions.cancel') }}
                </button>
            </div>
        </dialog>
    @endif
    @if ($canReverse)
        <dialog class="dialog dialog-lg" id="reverseJournalModal" aria-labelledby="reverseJournalModalLabel">
            <form method="POST" action="{{ route('sales.invoices.reverse', $documentNumber) }}">
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
                    @if ($canReturn && $returnableLines->isNotEmpty())
                        <button class="btn btn-solid theme-secondary me-auto" type="button"
                            data-bs-toggle="dialog" data-bs-target="#undoWizardModal">
                            <i class="me-1" data-lucide="arrow-left"></i> {{ __('common.actions.back') }}
                        </button>
                    @endif
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
    @if ($canReturn && $returnableLines->isNotEmpty())
        <dialog class="dialog dialog-lg" id="partialReturnModal" aria-labelledby="partialReturnModalLabel">
            <form method="POST" action="{{ route('sales.invoices.receive-return', $documentNumber) }}"
                data-confirm-message="{{ __('sales_returns.confirm') }}" data-confirm-tone="danger">
                @csrf
                <div class="dialog-header">
                    <h5 class="dialog-title" id="partialReturnModalLabel">
                        {{ __('sales_returns.wizard_partial_title') }}
                    </h5>
                    <button class="btn-close" data-bs-dismiss="dialog" type="button"
                        aria-label="{{ __('common.actions.close') }}"></button>
                </div>
                <div class="dialog-body">
                    @if ($errors->has('returns'))
                        <div class="alert alert theme-danger mb-3">{{ $errors->first('returns') }}</div>
                    @endif
                    <div class="table-responsive mb-3 rounded border">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="bg-1">
                                <tr>
                                    <th>{{ __('ledgers.table.column_product') }}</th>
                                    <th class="text-end">{{ __('sales_returns.column_invoiced') }}</th>
                                    <th class="text-end">{{ __('sales_returns.column_returned') }}</th>
                                    <th class="text-end">{{ __('sales_returns.column_remaining') }}</th>
                                    <th class="text-end" style="min-width: 10rem;">{{ __('sales_returns.quantity') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($returnableLines as $returnableLine)
                                    <tr>
                                        <td>{{ $returnableLine['product_name'] }}</td>
                                        <td class="text-end">{{ $returnableLine['quantity'] }}</td>
                                        <td class="text-end">{{ $returnableLine['returned'] }}</td>
                                        <td class="text-end">{{ $returnableLine['remaining'] }}</td>
                                        <td class="text-end">
                                            <input type="hidden" name="returns[{{ $returnableLine['id'] }}][line_id]"
                                                value="{{ $returnableLine['id'] }}" />
                                            <input type="number" step="0.0001" min="0"
                                                max="{{ $returnableLine['remaining'] }}"
                                                name="returns[{{ $returnableLine['id'] }}][quantity]"
                                                value="{{ old('returns.' . $returnableLine['id'] . '.quantity') }}"
                                                data-unit-price="{{ $returnableLine['unit_price'] }}"
                                                class="form-control form-control-sm text-end return-quantity"
                                                placeholder="0.0000" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-1">
                                <tr>
                                    <th colspan="4" class="text-end">{{ __('sales_returns.credit_note_total') }}</th>
                                    <th class="text-end" id="returnTotalAmount">{{ $currencySymbol }} 0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="return_notes">{{ __('sales_returns.notes') }}</label>
                        <input name="return_notes" id="return_notes" type="text" maxlength="1000" class="form-control"
                            value="{{ old('return_notes') }}" />
                    </div>
                </div>
                <div class="dialog-footer">
                    @if ($canReverse)
                        <button class="btn btn-solid theme-secondary me-auto" type="button"
                            data-bs-toggle="dialog" data-bs-target="#undoWizardModal">
                            <i class="me-1" data-lucide="arrow-left"></i> {{ __('common.actions.back') }}
                        </button>
                    @endif
                    <button class="btn btn-solid theme-secondary" data-bs-dismiss="dialog" type="button">
                        {{ __('common.actions.cancel') }}</button><button class="btn btn-solid theme-warning"
                        type="submit">
                        <i data-lucide="package-check"></i> {{ __('sales_returns.submit') }}
                    </button>
                </div>
            </form>
        </dialog>
        @if ($errors->has('returns'))
            <script>
                document.addEventListener('DOMContentLoaded', () =>
                    bootstrap.Dialog.getOrCreateInstance(document.getElementById('partialReturnModal')).show(),
                );
            </script>
        @endif
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const inputs = Array.from(document.querySelectorAll('#partialReturnModal .return-quantity'));
                const totalCell = document.getElementById('returnTotalAmount');

                if (inputs.length === 0) {
                    return;
                }

                const currencySymbol = @json($currencySymbol);
                const formatAmount = (value) =>
                    currencySymbol + ' ' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                // Every filled line lands on the same credit note, so show what
                // that note will be worth while the quantities are being typed.
                const refreshTotal = () => {
                    let total = 0;

                    inputs.forEach((input) => {
                        const remaining = parseFloat(input.max);
                        let quantity = parseFloat(input.value);

                        if (!isNaN(remaining) && quantity > remaining) {
                            quantity = remaining;
                            input.value = remaining;
                        }

                        if (isNaN(quantity) || quantity <= 0) {
                            return;
                        }

                        total += quantity * parseFloat(input.dataset.unitPrice || '0');
                    });

                    if (totalCell) {
                        totalCell.textContent = formatAmount(total);
                    }
                };

                inputs.forEach((input) => input.addEventListener('input', refreshTotal));
                refreshTotal();
            });
        </script>
    @endif
@endsection
