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
use App\Enums\LedgerStatus;
use App\Models\Finance\JournalEntry;

$canApprove = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);
$canRemove = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::REMOVE);

?>

@props([
    /** @var JournalEntry */
    'journalEntry',
    'charts',
])

@extends('layouts.app')

@section('content')
    <div class="card md:col-11 mx-auto shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('journal_entries.edit_journal_title') }} - {{ $journalEntry->document_number }}
            </h4>
            <span class="badge {{ $journalEntry->status === LedgerStatus::POSTED ? 'theme-success' : 'theme-warning' }}">
                {{ $journalEntry->status === LedgerStatus::POSTED ? __('journal_entries.status_approved') : __('journal_entries.status_pending') }}
            </span>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('finance.journal-entries.update', $journalEntry) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-4">
                    <div class="md:col-4">
                        <label class="form-label fw-bold" for="date">
                            {{ __('journal_entries.table.column_date') }}
                        </label>
                        <label class="form-control form-adorn">
                            <div class="form-adorn-icon" aria-hidden="true">
                                <i data-lucide="calendar"></i>
                            </div>
                            <input class="form-ghost @error('date') is-invalid @enderror" id="date" name="date"
                                type="text" data-bs-toggle="datepicker" autocomplete="off"
                                value="{{ old('date', $journalEntry->date->format('Y-m-d')) }}"
                                min="{{ $postingDateMin ?? '' }}" max="{{ $postingDateMax ?? '' }}" required />
                        </label>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="md:col-4">
                        <label class="form-label fw-bold" for="reference_number">
                            {{ __('journal_entries.table.column_reference_number') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input class="form-ghost @error('reference_number') is-invalid @enderror" id="reference_number"
                                name="reference_number" type="text"
                                value="{{ old('reference_number', $journalEntry->reference_number) }}" />
                        </label>
                        @error('reference_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="md:col-4">
                        <label class="form-label fw-bold" for="notes">
                            {{ __('journal_entries.table.column_notes') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input class="form-ghost @error('notes') is-invalid @enderror" id="notes" name="notes"
                                type="text" value="{{ old('notes', $journalEntry->notes) }}" />
                        </label>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="table-responsive rounded-5 mb-3 border">
                    <table class="table-striped table-sm mb-0 table align-middle">
                        <thead class="bg-1">
                            <tr>
                                <th style="width: 30%">{{ __('journal_entries.table.column_account') }}</th>
                                <th style="width: 35%">{{ __('journal_entries.table.column_description') }}</th>
                                <th class="text-end" style="width: 15%">
                                    {{ __('journal_entries.table.column_debit_amount') }}
                                </th>
                                <th class="text-end" style="width: 15%">
                                    {{ __('journal_entries.table.column_credit_amount') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 10; $i++)
                                @php
                                    $item = $journalEntry->charts->get($i);
                                @endphp
                                <tr>
                                    <td>
                                        @php($selectedChartId = old('items.' . $i . '.chart_id', $item?->debit_id ?? $item?->credit_id))

                                        <select
                                            class="form-control form-control-sm js-chart-select @error('items.' . $i . '.chart_id') is-invalid @enderror"
                                            name="items[{{ $i }}][chart_id]">
                                            <option value="">{{ __('common.actions.select_option') }}</option>
                                            @if (filled($selectedChartId) && !$charts->contains(fn($chart) => (string) $chart->id === (string) $selectedChartId))
                                                <option value="{{ $selectedChartId }}" selected>
                                                    {{ $selectedChartId }}
                                                </option>
                                            @endif
                                            @foreach ($charts as $chart)
                                                <option value="{{ $chart->id }}" @selected((string) $selectedChartId === (string) $chart->id)>
                                                    {{ $chart->code }}{{ $chart->name ? ' - ' . $chart->name : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('items.' . $i . '.chart_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <label class="form-control form-adorn">
                                            <input class="form-ghost form-control-sm"
                                                name="items[{{ $i }}][description]" type="text"
                                                value="{{ old('items.' . $i . '.description', $item?->description) }}" />
                                        </label>
                                    </td>
                                    <td>
                                        <label class="form-control form-adorn">
                                            <input class="form-ghost form-control-sm debit-input text-end"
                                                name="items[{{ $i }}][debit_amount]" type="number"
                                                value="{{ old('items.' . $i . '.debit_amount', number_format((float) ($item?->debit_amount ?? 0), 2, '.', '')) }}"
                                                step="0.01" min="0" oninput="calculateTotals()" />
                                        </label>
                                    </td>
                                    <td>
                                        <label class="form-control form-adorn">
                                            <input class="form-ghost form-control-sm credit-input text-end"
                                                name="items[{{ $i }}][credit_amount]" type="number"
                                                value="{{ old('items.' . $i . '.credit_amount', number_format((float) ($item?->credit_amount ?? 0), 2, '.', '')) }}"
                                                step="0.01" min="0" oninput="calculateTotals()" />
                                        </label>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                        <tfoot class="bg-1 fw-bold">
                            <tr>
                                <td class="text-end" colspan="2">{{ __('common.labels.total') ?? 'Total' }}:</td>
                                <td class="text-end" id="totalDebitDisplay">0.00</td>
                                <td class="text-end" id="totalCreditDisplay">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <a class="btn btn-solid theme-secondary" href="{{ route('finance.journal-entries.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') ?? 'Back' }}
                    </a>

                    <div class="d-flex gap-2">
                        @if ($canRemove)
                            <button class="btn btn-sm btn-solid theme-danger btn-delete" data-id="{{ $journalEntry->id }}"
                                type="button" title="{{ __('common.actions.delete') }}">
                                <i data-lucide="trash-2"></i>
                            </button>
                        @endif

                        @if ($journalEntry->status !== LedgerStatus::POSTED && $canApprove)
                            <button class="btn btn-solid theme-success" type="button"
                                onclick="document.getElementById('approve-form').submit()">
                                <i class="me-1" data-lucide="check"></i>
                                {{ __('journal_entries.approve_button') }}
                            </button>
                        @endif

                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>
                            {{ __('common.actions.save') }}
                        </button>
                    </div>
                </div>
            </form>

            @if ($journalEntry->status !== LedgerStatus::POSTED)
                <form autocomplete="off" class="d-none" id="approve-form"
                    action="{{ route('finance.journal-entries.approve', $journalEntry) }}" method="POST">
                    @csrf
                    @method('PATCH')
                </form>
            @endif

            <form autocomplete="off" class="d-none" id="delete-form" action="{{ route('finance.journal-entries.destroy', $journalEntry) }}"
                method="POST">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <script>
        function calculateTotals() {
            let debitTotal = 0;
            let creditTotal = 0;

            document.querySelectorAll('.debit-input').forEach((input) => {
                debitTotal += parseFloat(input.value) || 0;
            });

            document.querySelectorAll('.credit-input').forEach((input) => {
                creditTotal += parseFloat(input.value) || 0;
            });

            document.getElementById('totalDebitDisplay').textContent = debitTotal.toFixed(2);
            document.getElementById('totalCreditDisplay').textContent = creditTotal.toFixed(2);
        }

        function initializeCoaSelects() {
            if (typeof window.initCombobox !== 'function') {
                return;
            }

            document.querySelectorAll('.js-chart-select').forEach((element) => {
                if (element.dataset.comboboxBound) {
                    return;
                }

                window.initCombobox(element, {
                    placeholder: @json(__('common.actions.select_option')),
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeCoaSelects();
            calculateTotals();
        });
    </script>
@endsection

@section('script')
    <script type="module">
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete');
            if (deleteBtn) {
                const dataId = deleteBtn.getAttribute('data-id');
                confirmDialog({
                    title: "{{ __('common.alerts.confirm_delete') }}",
                    text: "{{ __('common.alerts.confirm_delete_message') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "{{ __('common.alerts.confirm_delete_yes') }}",
                    confirmTone: 'danger',
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + dataId).submit();
                    }
                });
            }
        });
    </script>
@endsection
