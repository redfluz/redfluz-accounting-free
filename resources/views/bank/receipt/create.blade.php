<?php

use App\Enums\FileAction;
use App\Enums\FileResources;

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

$canCreate = auth()->user()->hasFilePermission(FileResources::PAYMENTS, FileAction::ADD);

?>

@props(['companies', 'charts', 'documentNumber'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold m-0">{{ __('payments.workflow.receipt.create_title') }}</h5>
            <span class="fg-secondary small">{{ $documentNumber }}</span>
        </div>

        <form autocomplete="off" action="{{ route('bank.receipts.store') }}" enctype="multipart/form-data" method="POST">
            @csrf
            <div class="card-body">
                @include('partials.alerts')

                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button aria-controls="general-pane" aria-selected="true" class="nav-link active"
                            data-bs-toggle="tab" data-bs-target="#general-pane" role="tab" type="button">
                            <i class="me-1" data-lucide="list"></i>
                            {{ __('ledgers.general') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button aria-controls="attachment-pane" aria-selected="false" class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#attachment-pane" role="tab" type="button">
                            <i class="me-1" data-lucide="paperclip"></i>
                            {{ __('ledgers.attachment') }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="general-pane" role="tabpanel">
                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="company_id">
                                    {{ __('ledgers.table.column_company') }}
                                </label>
                                <select class="form-control @error('company_id') is-invalid @enderror" id="company_id"
                                    name="company_id" required>
                                    <option value="">{{ __('common.actions.select_option') }}</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}" @selected(old('company_id') === $company->id)>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="reference_number">
                                    {{ __('payments.form.linked_sales_invoice') }}
                                </label>
                                <select class="form-control @error('reference_number') is-invalid @enderror"
                                    id="reference_number" name="reference_number">
                                    <option value="">{{ __('common.actions.select_option') }}</option>
                                    @if (old('reference_number'))
                                        <option value="{{ old('reference_number') }}" selected>
                                            {{ old('reference_number') }}
                                        </option>
                                    @endif
                                </select>
                                @error('reference_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="date">
                                    {{ __('ledgers.table.column_date') }}
                                </label>
                                <label class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true">
                                        <i data-lucide="calendar"></i>
                                    </div>
                                    <input class="form-ghost @error('document_date') is-invalid @enderror" id="document_date"
                                        name="document_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                                        value="{{ old('document_date', now()->format('Y-m-d')) }}" />
                                </label>
                                @error('document_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="amount">
                                    {{ __('ledgers.table.column_total_amount') }}
                                </label>
                                <label class="form-control form-adorn">
                                    <input class="form-ghost @error('amount') is-invalid @enderror" id="amount"
                                        min="0.01" name="amount" step="0.01" type="number"
                                        value="{{ old('amount') }}" />
                                </label>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="payment_transfer_type">
                                    {{ __('payments.form.payment_transfer') }}
                                </label>
                                <select class="form-control" id="payment_transfer_type" name="payment_transfer_type">
                                    @foreach (\App\Enums\PaymentTransferType::cases() as $transferType)
                                        <option value="{{ $transferType->value }}">{{ $transferType->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold" for="notes">
                                    {{ __('ledgers.table.column_notes') }}
                                </label>
                                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="fade tab-pane" id="attachment-pane" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="attachment">
                                {{ __('ledgers.attachment') }}
                            </label>
                            <input class="form-control @error('attachment') is-invalid @enderror" id="attachment"
                                name="attachment" type="file" />
                            @error('attachment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a class="btn btn-solid theme-secondary" href="{{ route('bank.receipts.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('ledgers.back') }}
                </a>
                @if ($canCreate)
                    <button class="btn btn-solid theme-primary" type="submit">
                        <i class="me-1" data-lucide="save"></i>
                        {{ __('payments.actions.save') }}
                    </button>
                @endif
            </div>
        </form>
    </div>

    @section('script')
        <script>
            const invoiceOptionsUrl = @json(route('bank.receipts.invoices'));
            const selectText = @json(__('common.actions.select_option'));

            function initializeInvoiceSelect() {
                if (typeof window.initCombobox !== 'function') {
                    return;
                }

                const referenceSelect = document.getElementById('reference_number');

                if (referenceSelect.autoComplete) {
                    referenceSelect.autoComplete.destroy();
                }

                window.initCombobox(referenceSelect, {
                    placeholder: selectText,
                    load(query, callback) {
                        const companyId = document.getElementById('company_id').value;

                        if (!companyId) {
                            callback();

                            return;
                        }

                        const url = new URL(invoiceOptionsUrl, window.location.origin);
                        url.searchParams.set('company_id', companyId);
                        url.searchParams.set('search', query);

                        fetch(url, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        })
                            .then((response) => {
                                if (!response.ok) {
                                    throw new Error('Unable to load invoice options.');
                                }

                                return response.json();
                            })
                            .then((payload) => callback(payload.results ?? []))
                            .catch(() => callback());
                    },
                });
            }

            function handleCompanyChange() {
                const referenceSelect = document.getElementById('reference_number');

                if (!referenceSelect.autoComplete) {
                    return;
                }

                referenceSelect.autoComplete.clear();

                if (document.getElementById('company_id').value) {
                    referenceSelect.autoComplete.load('');
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                initializeInvoiceSelect();

                document.getElementById('company_id').addEventListener('change', handleCompanyChange);
            });
        </script>
    @endsection
@endsection
