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

$canApprove = auth()->user()->hasFilePermission(FileResources::SALES_ADJUSTMENTS, FileAction::APPROVE);
$canReverse = $canApprove && $isPosted && ! $adjustment->reversal_journal_id && ! $adjustment->undone_at;
?>
@php use App\Models\System\Setting; @endphp

@props(['documentNumber', 'entries', 'adjustment', 'isPosted'])

@extends('layouts.app')

@section('content')
    @php($subtype = $adjustment->subtype->value ?? $adjustment->subtype)

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">
                    {{ __('adjustments.sub_type.' . $subtype) }} | {{ $adjustment->company_name ?? '-' }}
                </span>
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
                    <form action="{{ route('sales.adjustments.approve', $documentNumber) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm text-nowrap" type="submit">
                            <i data-lucide="circle-check"></i>
                            {{ __('common.actions.post') }}
                        </button>
                    </form>
                @endif

                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('sales.adjustments.pdf', $documentNumber) }}" target="_blank">
                    <i class="me-1" data-lucide="printer"></i>
                    {{ __('common.actions.print_pdf') }}
                </a>

                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('sales.adjustments.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="md:col-4">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('adjustments.form.linked_invoice') }}</span>
                        <strong>{{ $adjustment->reference_number }}</strong>
                    </div>
                </div>
                <div class="md:col-4">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_date') }}</span>
                        <strong>{{ $adjustment->date->format(Setting::getDefaultDate()) }}</strong>
                    </div>
                </div>
                <div class="md:col-4">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_total_amount') }}</span>
                        <strong class="fg-primary">{{ number_format((float) $adjustment->total_amount, 2) }}</strong>
                    </div>
                </div>
            </div>

            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('adjustments.table.column_entry') }}</th>
                            <th>{{ __('ledgers.table.column_debit_acc') }}</th>
                            <th>{{ __('ledgers.table.column_credit_acc') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $entry->debit_acc ?? '-' }}
                                    @if ($entry->debit_acc_desc)
                                        <span class="fg-secondary small d-block">{{ $entry->debit_acc_desc }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $entry->credit_acc ?? '-' }}
                                    @if ($entry->credit_acc_desc)
                                        <span class="fg-secondary small d-block">{{ $entry->credit_acc_desc }}</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $entry->debit_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $entry->credit_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($adjustment->notes)
                <div class="mt-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('ledgers.table.column_notes') }}</span>
                    {{ $adjustment->notes }}
                </div>
            @endif
        </div>
    </div>
    @if ($canReverse)
        <dialog class="dialog" id="reverseJournalModal" aria-labelledby="reverseJournalModalLabel">
            <form method="POST" action="{{ route('sales.adjustments.reverse', $documentNumber) }}">
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
