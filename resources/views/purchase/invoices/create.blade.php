{{--
    ============================================================================
    Copyright (c) 2026 redfluz. All Rights Reserved.
    PROPRIETARY AND CONFIDENTIAL
    ============================================================================
--}}

@props([
    'companies',
    'contacts',
    'charts',
    'subtype',
    'indexRoute',
    'sourcePurchaseOrder' => null,
    'canQuickCreateCompany',
])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('ledgers.workflow.' . $subtype->value . '.create_title') }}</h4>
                <span class="fg-secondary small"> {{ __('ledgers.workflow.' . $subtype->value . '.subtitle') }} </span>
            </div>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" class="d-flex flex-column" action="{{ route('purchase.invoices.store') }}" method="POST"
                enctype="multipart/form-data" novalidate>
                @csrf
                <ul class="purchase-document-tabs nav nav-tabs" id="entry-mode-tabs" role="tablist" style="order: 1">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-entry-mode="general" data-purchase-tab="general"
                            type="button">
                            {{ __('ledgers.general') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-entry-mode="attachment" data-purchase-tab="attachment" type="button">
                            {{ __('ledgers.attachment') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-entry-mode="payment" data-purchase-tab="payment" type="button">
                            {{ __('ledgers.type.payment') }}
                        </button>
                    </li>
                </ul>

                <div id="invoice-common-section" data-purchase-panel="general" style="order: 2">
                    <div class="row g-3 mb-4">
                        <div class="md:col-3">
                            <label class="form-label fw-bold"
                                for="purchase_type">{{ __('purchase_orders.form.type') }}</label>
                            <select class="form-control @error('purchase_type') is-invalid @enderror" id="purchase_type"
                                name="purchase_type" required>
                                @foreach (\App\Enums\PurchaseOrderType::cases() as $purchaseType)
                                    <option value="{{ $purchaseType->value }}" @selected(old('purchase_type', $sourcePurchaseOrder?->purchase_type?->value ?? \App\Enums\PurchaseOrderType::BILL->value) === $purchaseType->value)>
                                        {{ $purchaseType->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('purchase_type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="md:col-3">
                            <label class="form-label fw-bold"
                                for="date">{{ __('ledgers.table.column_date') }}</label>
                            <label class="form-control form-adorn">
                                <div class="form-adorn-icon" aria-hidden="true">
                                    <i data-lucide="calendar"></i>
                                </div>
                                <input class="form-ghost @error('date') is-invalid @enderror" id="date" name="date"
                                    type="text" data-bs-toggle="datepicker" autocomplete="off"
                                    value="{{ old('date', now()->format('Y-m-d')) }}" min="{{ $postingDateMin ?? '' }}"
                                    max="{{ $postingDateMax ?? '' }}" required />
                            </label>
                        </div>
                        <div class="md:col-3">
                            <label class="form-label fw-bold" for="credit_term_days">
                                {{ __('purchase_orders.form.credit_term_days') }}
                            </label>
                            <div class="input-group">
                                <label class="form-control form-adorn">
                                    <input class="form-ghost" id="credit_term_days" name="credit_term_days" type="number"
                                        value="{{ old('credit_term_days', $sourcePurchaseOrder?->credit_term_days ?? 30) }}"
                                        min="0" max="365" required />
                                </label>
                                <span class="input-group-text">{{ __('purchase_order_settings.days') }}</span>
                            </div>
                        </div>
                        <div class="md:col-3">
                            <label class="form-label fw-bold">{{ __('ledgers.table.column_due_date') }}</label>
                            <div class="form-control bg-2" id="invoice-due-date" aria-live="polite">-</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="md:col-6">
                            <label class="form-label fw-bold"
                                for="company_id">{{ __('ledgers.table.column_company') }}</label>
                            <select class="form-control @error('company_id') is-invalid @enderror" id="company_id"
                                name="company_id" onchange="filterContactsByCompany()">
                                <option value="">{{ __('common.actions.select_option') }}</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($canQuickCreateCompany)
                                @include('partials.quick-company', [
                                    'quickCompanyRoute' => route('purchase.invoices.companies.store'),
                                    'quickCompanyLabel' => __('invoices.actions.quick_supplier'),
                                    'quickCompanyNamePlaceholder' => __('invoices.form.quick_supplier_name'),
                                    'quickCompanySaveLabel' => __('invoices.actions.add_supplier'),
                                    'quickCompanyRequiredMessage' => __(
                                        'invoices.validation.company_name_required'),
                                    'quickCompanyFailedMessage' => __('invoices.validation.company_create_failed'),
                                ])
                            @endif
                        </div>
                        <div class="md:col-6">
                            <label class="form-label fw-bold"
                                for="contact_id">{{ __('ledgers.table.column_contact') }}</label>
                            <select class="form-control @error('contact_id') is-invalid @enderror" id="contact_id"
                                name="contact_id"></select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold"
                            for="invoice_notes">{{ __('ledgers.table.column_notes') }}</label>
                        <textarea class="form-control @error('invoice_notes') is-invalid @enderror" id="invoice_notes" name="invoice_notes"
                            rows="2">{{ old('invoice_notes') }}</textarea>
                    </div>
                </div>

                <div class="mb-4" id="invoice-attachment-section" data-purchase-panel="attachment" style="order: 2">
                    <label class="form-label fw-bold" for="invoice_attachment">
                        {{ __('ledgers.table.column_attachment_path') }}
                    </label>
                    <input class="form-control @error('invoice_attachment') is-invalid @enderror" id="invoice_attachment"
                        name="invoice_attachment" type="file"
                        accept="{{ \App\Enums\UploadType::DOCUMENT_AND_IMAGES->accept() }}" />
                </div>

                <div class="bg-2 mb-4 rounded border p-3" id="payment-details-section" data-purchase-panel="payment"
                    style="order: 2">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold"
                                for="amount">{{ __('ledgers.table.column_amount') }}</label>
                            <label class="form-control form-adorn">
                                <input class="form-ghost @error('amount') is-invalid @enderror" id="amount"
                                    name="amount" type="number" value="{{ old('amount') }}" min="0.01"
                                    step="0.01" />
                            </label>
                            <div class="form-text">{{ __('ledgers.optional_payment_hint') }}</div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold"
                                for="payment_notes">{{ __('ledgers.table.column_notes') }}</label>
                            <textarea class="form-control @error('payment_notes') is-invalid @enderror" id="payment_notes" name="payment_notes"
                                rows="2">{{ old('payment_notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="purchase-lines-heading" id="invoice-lines-section" style="order: 3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0">{{ __('ledgers.invoice_lines') }}</h6>
                        <span class="fg-secondary small">{{ __('ledgers.complete_rows_needed') }}</span>
                    </div>
                    <div class="table-responsive mb-4 rounded border">
                        <table class="table-striped mb-0 table align-middle" id="invoice-lines-table">
                            <thead class="bg-1">
                                <tr>
                                    <th style="min-width: 220px">
                                        {{ __('ledgers.table.column_description') }} <span class="fg-danger">*</span>
                                    </th>
                                    <th style="min-width: 110px">{{ __('ledgers.table.column_quantity') }}</th>
                                    <th style="min-width: 140px">{{ __('ledgers.table.column_unit_price') }}</th>
                                    <th style="min-width: 150px">{{ __('ledgers.table.column_discount_amount') }}</th>
                                    <th style="min-width: 150px">{{ __('ledgers.table.column_tax_amount') }}</th>
                                    <th class="text-end" style="min-width: 130px">
                                        {{ __('ledgers.table.column_total_amount') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($lineIndex = 0; $lineIndex < 10; $lineIndex++)
                                    <tr>
                                        <td>
                                            <label class="form-control form-control-sm form-adorn">
                                                <input
                                                    class="form-ghost @error("lines.$lineIndex.description") is-invalid @enderror"
                                                    name="lines[{{ $lineIndex }}][description]" type="text"
                                                    value="{{ old("lines.$lineIndex.description") }}" />
                                            </label>
                                            @error("lines.$lineIndex.description")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <label class="form-control form-control-sm form-adorn">
                                                <input
                                                    class="form-ghost line-quantity @error("lines.$lineIndex.quantity") is-invalid @enderror"
                                                    name="lines[{{ $lineIndex }}][quantity]" type="number"
                                                    value="{{ old("lines.$lineIndex.quantity") }}" min="1" />
                                            </label>
                                            @error("lines.$lineIndex.quantity")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <label class="form-control form-control-sm form-adorn">
                                                <input
                                                    class="form-ghost line-unit-price @error("lines.$lineIndex.unit_price") is-invalid @enderror"
                                                    name="lines[{{ $lineIndex }}][unit_price]" type="number"
                                                    value="{{ old("lines.$lineIndex.unit_price") }}" min="0"
                                                    step="0.01" />
                                            </label>
                                            @error("lines.$lineIndex.unit_price")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <label class="form-control form-control-sm form-adorn">
                                                <input
                                                    class="form-ghost line-discount-amount @error("lines.$lineIndex.discount_amount") is-invalid @enderror"
                                                    name="lines[{{ $lineIndex }}][discount_amount]" type="number"
                                                    value="{{ old("lines.$lineIndex.discount_amount", 0) }}" min="0"
                                                    step="0.01" />
                                            </label>
                                        </td>
                                        <td>
                                            <label class="form-control form-control-sm form-adorn">
                                                <input
                                                    class="form-ghost line-tax-amount @error("lines.$lineIndex.tax_amount") is-invalid @enderror"
                                                    name="lines[{{ $lineIndex }}][tax_amount]" type="number"
                                                    value="{{ old("lines.$lineIndex.tax_amount", 0) }}" min="0"
                                                    step="0.01" />
                                            </label>
                                        </td>
                                        <td class="line-total fw-bold text-end">0.00</td>
                                    </tr>
                                @endfor
                            </tbody>
                            <tfoot class="bg-1">
                                <tr>
                                    <th class="text-end" colspan="5">
                                        {{ __('ledgers.table.column_subtotal_amount') }}
                                    </th>
                                    <th class="text-end" id="invoice-subtotal">0.00</th>
                                </tr>
                                <tr>
                                    <th class="text-end" colspan="5">
                                        {{ __('ledgers.table.column_discount_amount') }}
                                    </th>
                                    <th class="fg-danger text-end" id="invoice-discount-total">0.00</th>
                                </tr>
                                <tr>
                                    <th class="text-end" colspan="5">{{ __('ledgers.table.column_tax_amount') }}</th>
                                    <th class="text-end" id="invoice-tax-total">0.00</th>
                                </tr>
                                <tr>
                                    <th class="text-end" colspan="5">{{ __('common.labels.total') }}</th>
                                    <th class="fg-primary text-end" id="invoice-grand-total">0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>


                <div class="d-flex justify-content-between border-top pt-3" style="order: 4">
                    <a class="btn btn-solid theme-secondary" href="{{ route($indexRoute) }}">
                        <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                    </a>
                    <button class="btn btn-solid theme-accent" id="save-entry-button" type="submit">
                        <i class="me-1" data-lucide="save"></i>{{ __('common.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const allContacts = @json($contacts);
        const allCharts = @json($charts);
        const selectText = @json(__('common.actions.select_option'));
        const selectedContactId = @json(old('contact_id'));
        const selectedDebitAcc = @json(old('debit_id'));
        const selectedCreditAcc = @json(old('credit_id'));

        function filterContactsByCompany() {
            const companyId = document.getElementById('company_id').value;
            const contactSelect = document.getElementById('contact_id');
            contactSelect.innerHTML = `<option value="">${selectText}</option>`;

            allContacts
                .filter((contact) => String(contact.company_id) === String(companyId))
                .forEach((contact) => {
                    const option = new Option(
                        contact.name,
                        contact.id,
                        false,
                        String(contact.id) === String(selectedContactId),
                    );
                    contactSelect.add(option);
                });
        }

        function filterChartOfAccounts() {
            const debitSelect = document.getElementById('debit_id');
            const creditSelect = document.getElementById('credit_id');
            const paymentDebitSelect = document.getElementById('payment_debit_id');
            const paymentCreditSelect = document.getElementById('payment_credit_id');
            debitSelect.innerHTML = `<option value="">${selectText}</option>`;
            creditSelect.innerHTML = `<option value="">${selectText}</option>`;
            paymentDebitSelect.innerHTML = `<option value="">${selectText}</option>`;
            paymentCreditSelect.innerHTML = `<option value="">${selectText}</option>`;

            allCharts.forEach((chart) => {
                const type = chart.chart_type;
                if (!type) return;

                const label = `${chart.code ? `${chart.code} - ` : ''}${chart.name}`;
                const canDebit = type.isExpenses === true || (type.isAsset === true && chart.isBank === false);
                const canCredit = type.isLiability === true && chart.isCreditor === true;

                if (canDebit)
                    debitSelect.add(new Option(label, chart.id, false, String(chart.id) === String(
                        selectedDebitAcc)));
                if (canCredit)
                    creditSelect.add(new Option(label, chart.id, false, String(chart.id) === String(
                        selectedCreditAcc)));
                if (type.isLiability === true && chart.isCreditor === true) paymentDebitSelect.add(new Option(label,
                    chart.id));
                if (chart.isBank === true) paymentCreditSelect.add(new Option(label, chart.id));
            });

            refreshAccountSelects();
        }

        function refreshAccountSelects() {
            if (typeof window.initCombobox !== 'function') {
                return;
            }

            document.querySelectorAll('.js-account-select').forEach((element) => {
                window.initCombobox(element, {
                    placeholder: selectText,
                });
            });
        }

        function refreshEntryForm() {
            filterChartOfAccounts();
        }

        function refreshLineTotals() {
            const roundMoney = (value) => Math.round((value + Number.EPSILON) * 100) / 100;
            const rows = Array.from(document.querySelectorAll('#invoice-lines-table tbody tr'));
            const lineSubtotals = rows.map((row) => {
                const quantity = Number(row.querySelector('.line-quantity').value) || 0;
                const unitPrice = Number(row.querySelector('.line-unit-price').value) || 0;
                return roundMoney(quantity * unitPrice);
            });
            let subtotal = 0;
            let discountTotal = 0;
            let taxTotal = 0;
            let grandTotal = 0;

            rows.forEach((row, index) => {
                const lineSubtotal = lineSubtotals[index];
                const lineDiscount = Math.min(lineSubtotal, Number(row.querySelector('.line-discount-amount')
                    .value) || 0);
                const lineTax = Number(row.querySelector('.line-tax-amount').value) || 0;
                const lineTotal = roundMoney(lineSubtotal - lineDiscount + lineTax);

                row.querySelector('.line-total').textContent = lineTotal.toFixed(2);
                subtotal += lineSubtotal;
                discountTotal += lineDiscount;
                taxTotal += lineTax;
                grandTotal += lineTotal;
            });

            document.getElementById('invoice-subtotal').textContent = roundMoney(subtotal).toFixed(2);
            document.getElementById('invoice-discount-total').textContent = roundMoney(discountTotal).toFixed(2);
            document.getElementById('invoice-tax-total').textContent = roundMoney(taxTotal).toFixed(2);
            document.getElementById('invoice-grand-total').textContent = roundMoney(grandTotal).toFixed(2);
        }

        function refreshInvoiceDueDate() {
            const date = document.getElementById('date').value;
            const days = Number(document.getElementById('credit_term_days').value || 0);
            const output = document.getElementById('invoice-due-date');
            if (!date) {
                output.textContent = '-';
                return;
            }
            const dueDate = new Date(`${date}T00:00:00`);
            dueDate.setDate(dueDate.getDate() + days);
            output.textContent = dueDate.toLocaleDateString();
        }

        document.addEventListener('DOMContentLoaded', () => {
            filterContactsByCompany();
            document
                .querySelectorAll('#entry-mode-tabs [data-entry-mode]')
                .forEach((tab) => tab.addEventListener('click', refreshEntryForm));
            document
                .querySelectorAll('.line-quantity, .line-unit-price')
                .forEach((input) => input.addEventListener('input', refreshLineTotals));
            document
                .querySelectorAll('.line-discount-amount, .line-tax-amount')
                .forEach((input) => input.addEventListener('input', refreshLineTotals));
            refreshLineTotals();
            document.getElementById('date').addEventListener('change', refreshInvoiceDueDate);
            document.getElementById('credit_term_days').addEventListener('input', refreshInvoiceDueDate);
            refreshInvoiceDueDate();

            @if (isset($cloneData) && $cloneData)
                // Populate form with clone data
                const cloneData = @json($cloneData);

                if (cloneData.company_id) {
                    document.getElementById('company_id').value = cloneData.company_id;
                    filterContactsByCompany();
                }

                if (cloneData.contact_id) {
                    setTimeout(() => {
                        document.getElementById('contact_id').value = cloneData.contact_id;
                    }, 100);
                }

                if (cloneData.lines && cloneData.lines.length > 0) {
                    const rows = document.querySelectorAll('#invoice-lines-table tbody tr');
                    cloneData.lines.forEach((line, index) => {
                        if (index >= rows.length) return;
                        const row = rows[index];
                        if (line.description) {
                            row.querySelector('[name*="[description]"]').value = line.description;
                        }
                        if (line.quantity) {
                            row.querySelector('.line-quantity').value = line.quantity;
                        }
                        if (line.unit_price) {
                            row.querySelector('.line-unit-price').value = line.unit_price;
                        }
                        if (line.discount_amount) {
                            row.querySelector('.line-discount-amount').value = line.discount_amount;
                        }
                        if (line.tax_amount) {
                            row.querySelector('.line-tax-amount').value = line.tax_amount;
                        }
                    });
                    refreshLineTotals();
                }
            @endif
        });
    </script>
@endsection
