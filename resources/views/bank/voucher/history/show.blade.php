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

@props([
    'voucher',
    'documentNumber',
    'bankLedgers' => collect(),
    'cashLedgers' => collect(),
    'eWalletLedgers' => collect(),
    'apLedgers' => collect(),
    'glLedgers' => collect(),
])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">
                    {{ __('payment_vouchers.title') }} | {{ $voucher->company_name ?: '-' }}
                </span>
            </div>
            <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
                <span class="badge theme-success d-flex align-items-center">
                    {{ __('journal_entries.status_approved') }}
                </span>
                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('bank.payment-vouchers.history.pdf', $documentNumber) }}" target="_blank">
                    <i class="me-1" data-lucide="file-type-2"></i>
                    {{ __('payments.actions.print_pdf') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.payment-vouchers.history.index') }}">
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

<div class="accordion mb-4 accordion-gap">
                <details class="accordion-item" open>
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="truck"></i>
                        <span class="fw-bold">{{ __('ledgers.sections.ap_ledgers') }}</span>
                        <span class="badge theme-secondary ms-2">{{ $apLedgers->count() }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                        <div class="table-responsive rounded border">
                            <table class="table-hover table-striped mb-0 table align-middle">
                                <thead class="bg-1">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                                        <th scope="col">{{ __('ledgers.sections.account') }}</th>
                                        <th scope="col">{{ __('ledgers.sections.reference') }}</th>
                                        <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                                        <th scope="col" class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                                        <th scope="col" class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($apLedgers as $line)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-nowrap">
                                                {{ $line->date?->format(Setting::getDefaultDate()) ?? '-' }}</td>
                                            <td>{{ trim(($line->chart_code ?? '') . ' ' . ($line->chart_name ?? '')) ?: '-' }}</td>
                                            <td>{{ $line->reference_number ?? '-' }}</td>
                                            <td><small>{{ $line->description ?? '-' }}</small></td>
                                            <td class="fw-bold fg-success text-end">
                                                {{ (float) $line->debit_amount > 0 ? number_format((float) $line->debit_amount, 2) : '-' }}
                                            </td>
                                            <td class="fw-bold fg-danger text-end">
                                                {{ (float) $line->credit_amount > 0 ? number_format((float) $line->credit_amount, 2) : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="fg-secondary py-3 text-center" colspan="7">
                                                {{ __('ledgers.no_records') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if ($apLedgers->isNotEmpty())
                                    <tfoot class="bg-1 fw-bold border-top">
                                        <tr>
                                            <td class="text-end" colspan="5">{{ __('common.labels.total') }}:</td>
                                            <td class="text-end">
                                                {{ number_format((float) $apLedgers->sum('debit_amount'), 2) }}</td>
                                            <td class="text-end">
                                                {{ number_format((float) $apLedgers->sum('credit_amount'), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </details>

                @if ($bankLedgers->isNotEmpty())
                    <details class="accordion-item" open>
                        <summary class="accordion-header">
                            <i class="me-2" data-lucide="landmark"></i>
                            <span class="fw-bold">{{ __('ledgers.sections.bank_ledgers') }}</span>
                            <span class="badge theme-secondary ms-2">{{ $bankLedgers->count() }}</span>
                            <i class="accordion-icon" data-lucide="chevron-down"></i>
                        </summary>
                        <div class="accordion-body">
                            <div class="table-responsive rounded border">
                                <table class="table-hover table-striped mb-0 table align-middle">
                                    <thead class="bg-1">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                                            <th scope="col">{{ __('ledgers.sections.account') }}</th>
                                            <th scope="col">{{ __('ledgers.sections.reference') }}</th>
                                            <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                                            <th scope="col" class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                                            <th scope="col" class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                                            <th scope="col" class="text-end">{{ __('ledgers.sections.balance') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($bankLedgers as $line)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="text-nowrap">
                                                    {{ $line->date?->format(Setting::getDefaultDate()) ?? '-' }}</td>
                                                <td>{{ $line->bankAccount?->chart?->name ?? ($line->bankAccount?->name ?? '-') }}</td>
                                                <td>{{ $line->reference_number ?? '-' }}</td>
                                                <td><small>{{ $line->description ?? '-' }}</small></td>
                                                <td class="fw-bold fg-success text-end">
                                                    {{ (float) $line->debit_amount > 0 ? number_format((float) $line->debit_amount, 2) : '-' }}
                                                </td>
                                                <td class="fw-bold fg-danger text-end">
                                                    {{ (float) $line->credit_amount > 0 ? number_format((float) $line->credit_amount, 2) : '-' }}
                                                </td>
                                                <td class="text-end">{{ number_format((float) $line->balance, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-1 fw-bold border-top">
                                        <tr>
                                            <td class="text-end" colspan="5">{{ __('common.labels.total') }}:</td>
                                            <td class="text-end">
                                                {{ number_format((float) $bankLedgers->sum('debit_amount'), 2) }}</td>
                                            <td class="text-end">
                                                {{ number_format((float) $bankLedgers->sum('credit_amount'), 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </details>
                @endif

                @if ($cashLedgers->isNotEmpty())
                    <details class="accordion-item" open>
                        <summary class="accordion-header">
                            <i class="me-2" data-lucide="banknote"></i>
                            <span class="fw-bold">{{ __('ledgers.sections.cash_ledgers') }}</span>
                            <span class="badge theme-secondary ms-2">{{ $cashLedgers->count() }}</span>
                            <i class="accordion-icon" data-lucide="chevron-down"></i>
                        </summary>
                        <div class="accordion-body">
                            <div class="table-responsive rounded border">
                                <table class="table-hover table-striped mb-0 table align-middle">
                                    <thead class="bg-1">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                                            <th scope="col">{{ __('ledgers.sections.account') }}</th>
                                            <th scope="col">{{ __('ledgers.sections.reference') }}</th>
                                            <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                                            <th scope="col" class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                                            <th scope="col" class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                                            <th scope="col" class="text-end">{{ __('ledgers.sections.balance') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cashLedgers as $line)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="text-nowrap">
                                                    {{ $line->date?->format(Setting::getDefaultDate()) ?? '-' }}</td>
                                                <td>{{ $line->cashAccount?->chart?->name ?? ($line->cashAccount?->name ?? '-') }}</td>
                                                <td>{{ $line->reference_number ?? '-' }}</td>
                                                <td><small>{{ $line->description ?? '-' }}</small></td>
                                                <td class="fw-bold fg-success text-end">
                                                    {{ (float) $line->debit_amount > 0 ? number_format((float) $line->debit_amount, 2) : '-' }}
                                                </td>
                                                <td class="fw-bold fg-danger text-end">
                                                    {{ (float) $line->credit_amount > 0 ? number_format((float) $line->credit_amount, 2) : '-' }}
                                                </td>
                                                <td class="text-end">{{ number_format((float) $line->balance, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-1 fw-bold border-top">
                                        <tr>
                                            <td class="text-end" colspan="5">{{ __('common.labels.total') }}:</td>
                                            <td class="text-end">
                                                {{ number_format((float) $cashLedgers->sum('debit_amount'), 2) }}</td>
                                            <td class="text-end">
                                                {{ number_format((float) $cashLedgers->sum('credit_amount'), 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </details>
                @endif

                @if ($eWalletLedgers->isNotEmpty())
                    <details class="accordion-item" open>
                        <summary class="accordion-header">
                            <i class="me-2" data-lucide="wallet"></i>
                            <span class="fw-bold">{{ __('ledgers.sections.e_wallet_ledgers') }}</span>
                            <span class="badge theme-secondary ms-2">{{ $eWalletLedgers->count() }}</span>
                            <i class="accordion-icon" data-lucide="chevron-down"></i>
                        </summary>
                        <div class="accordion-body">
                            <div class="table-responsive rounded border">
                                <table class="table-hover table-striped mb-0 table align-middle">
                                    <thead class="bg-1">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                                            <th scope="col">{{ __('ledgers.sections.account') }}</th>
                                            <th scope="col">{{ __('ledgers.sections.reference') }}</th>
                                            <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                                            <th scope="col" class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                                            <th scope="col" class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                                            <th scope="col" class="text-end">{{ __('ledgers.sections.balance') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($eWalletLedgers as $line)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="text-nowrap">
                                                    {{ $line->date?->format(Setting::getDefaultDate()) ?? '-' }}</td>
                                                <td>{{ $line->eWalletAccount?->chart?->name ?? ($line->eWalletAccount?->name ?? '-') }}</td>
                                                <td>{{ $line->reference_number ?? '-' }}</td>
                                                <td><small>{{ $line->description ?? '-' }}</small></td>
                                                <td class="fw-bold fg-success text-end">
                                                    {{ (float) $line->debit_amount > 0 ? number_format((float) $line->debit_amount, 2) : '-' }}
                                                </td>
                                                <td class="fw-bold fg-danger text-end">
                                                    {{ (float) $line->credit_amount > 0 ? number_format((float) $line->credit_amount, 2) : '-' }}
                                                </td>
                                                <td class="text-end">{{ number_format((float) $line->balance, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-1 fw-bold border-top">
                                        <tr>
                                            <td class="text-end" colspan="5">{{ __('common.labels.total') }}:</td>
                                            <td class="text-end">
                                                {{ number_format((float) $eWalletLedgers->sum('debit_amount'), 2) }}</td>
                                            <td class="text-end">
                                                {{ number_format((float) $eWalletLedgers->sum('credit_amount'), 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </details>
                @endif

                <details class="accordion-item" open>
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="book-open"></i>
                        <span class="fw-bold">{{ __('ledgers.sections.ledgers') }}</span>
                        <span class="badge theme-secondary ms-2">{{ $glLedgers->count() }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                        <div class="table-responsive rounded border">
                            <table class="table-hover table-striped mb-0 table align-middle">
                                <thead class="bg-1">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                                        <th scope="col">{{ __('ledgers.sections.account') }}</th>
                                        <th scope="col">{{ __('ledgers.sections.reference') }}</th>
                                        <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                                        <th scope="col" class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                                        <th scope="col" class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($glLedgers as $line)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-nowrap">
                                                {{ $line->date?->format(Setting::getDefaultDate()) ?? '-' }}</td>
                                            <td>{{ trim(($line->chart_code ?? '') . ' ' . ($line->chart_name ?? '')) ?: '-' }}</td>
                                            <td>{{ $line->reference_number ?? '-' }}</td>
                                            <td><small>{{ $line->description ?? ($line->line_description ?? '-') }}</small>
                                            </td>
                                            <td class="fw-bold fg-success text-end">
                                                {{ (float) $line->debit_amount > 0 ? number_format((float) $line->debit_amount, 2) : '-' }}
                                            </td>
                                            <td class="fw-bold fg-danger text-end">
                                                {{ (float) $line->credit_amount > 0 ? number_format((float) $line->credit_amount, 2) : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="fg-secondary py-3 text-center" colspan="7">
                                                {{ __('ledgers.no_records') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if ($glLedgers->isNotEmpty())
                                    <tfoot class="bg-1 fw-bold border-top">
                                        <tr>
                                            <td class="text-end" colspan="5">{{ __('common.labels.total') }}:</td>
                                            <td class="text-end">
                                                {{ number_format((float) $glLedgers->sum('debit_amount'), 2) }}</td>
                                            <td class="text-end">
                                                {{ number_format((float) $glLedgers->sum('credit_amount'), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </details>
            </div>

            @if ($voucher->notes)
                <div class="mb-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('ledgers.table.column_notes') }}</span>
                    {{ $voucher->notes }}
                </div>
            @endif

            {{-- Who prepared it, who approved it, who cancelled it, each with a
                 box to sign. --}}
            @include('bank.voucher.partials.audit', ['document' => $voucher])
        </div>
    </div>
@endsection
