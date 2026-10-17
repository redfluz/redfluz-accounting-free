{{--
    ============================================================================
    Copyright (c) 2026 redfluz. All Rights Reserved.
    PROPRIETARY AND CONFIDENTIAL
    ============================================================================
--}}

@php
    use App\Enums\LedgerSubType;use App\Enums\UploadType;
@endphp

@props([
    'companies',
    'products',
    'contacts',
    'charts',
    'taxes',
    'discounts',
    'subtype',
    'indexRoute',
    'documentNumber',
    'deliveryOrder' => null,
        'canCreateCompany' => false,
])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('invoices.form.edit_title') }}: {{ $documentNumber }}</h4>
                <span class="fg-secondary small"> {{ __('ledgers.workflow.' . $subtype->value . '.subtitle') }} </span>
            </div>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('sales.invoices.update', $documentNumber) }}" method="POST"
                  enctype="multipart/form-data"
                  novalidate>
                @csrf
                @method('PUT')

                <ul class="sales-document-tabs nav nav-tabs" id="invoice-form-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-form-section="general" type="button">
                            {{ __('sales_orders.tabs.general') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-form-section="attachment" type="button">
                            {{ __('sales_orders.tabs.attachment') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-form-section="payment" type="button">
                            {{ __('sales_orders.tabs.payment') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-form-section="shipping" type="button">
                            {{ __('sales_orders.tabs.shipping') }}
                        </button>
                    </li>
                </ul>

                <div class="sales-tab-workspace">
                    <div class="sales-tab-panel sales-tab-panel-active" id="invoice-common-section">
                        <div class="mb-4">
                            <label class="form-label fw-bold"
                                   for="invoice_notes">{{ __('ledgers.table.column_notes') }}</label>
                            <textarea class="form-control @error('invoice_notes') is-invalid @enderror"
                                      id="invoice_notes" name="invoice_notes"
                                      rows="2">{{ old('invoice_notes') }}</textarea>
                        </div>
                    </div>

                    <div class="sales-tab-panel" id="attachment-section">
                        <label class="form-label fw-bold"
                               for="invoice_attachment">{{ __('ledgers.table.column_attachment_path') }}</label>
                        <input class="form-control @error('invoice_attachment') is-invalid @enderror"
                               id="invoice_attachment" name="invoice_attachment" type="file"
                               accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}"/>
                    </div>

                    <div class="sales-tab-panel bg-2 rounded border p-3" id="payment-details-section">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold"
                                       for="amount">{{ __('ledgers.table.column_amount') }}</label>
                                <label class="form-control form-adorn">
                                    <input class="form-ghost @error('amount') is-invalid @enderror" id="amount"
                                           name="amount" type="number" value="{{ old('amount') }}" min="0.01"
                                           step="0.01"/>
                                </label>
                                <div class="form-text">{{ __('ledgers.optional_payment_hint') }}</div>
                                @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold"
                                       for="payment_notes">{{ __('ledgers.table.column_notes') }}</label>
                                <textarea class="form-control @error('payment_notes') is-invalid @enderror"
                                          id="payment_notes" name="payment_notes"
                                          rows="2">{{ old('payment_notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="sales-tab-panel" id="shipping-section">
                        @if ($deliveryOrder !== null)
                            <div class="row g-3 rounded border p-3">
                                <div class="md:col-4">
                                    <span
                                        class="fg-secondary small d-block">{{ __('delivery_orders.table.number') }}</span><strong>{{ $deliveryOrder->document_number }}</strong>
                                </div>
                                <div class="md:col-4">
                                    <span
                                        class="fg-secondary small d-block">{{ __('delivery_orders.form.recipient_name') }}</span><strong>{{ $deliveryOrder->recipient_name }}</strong>
                                </div>
                                <div class="md:col-4">
                                    <span
                                        class="fg-secondary small d-block">{{ __('delivery_orders.form.shipping_status') }}</span><strong>{{ __('delivery_orders.shipping_status.' . $deliveryOrder->shipping_status->value) }}</strong>
                                </div>
                                <div class="md:col-8">
                                    <span
                                        class="fg-secondary small d-block">{{ __('delivery_orders.form.delivery_address') }}</span><strong>{{ $deliveryOrder->delivery_address }}</strong>
                                </div>
                                <div class="md:col-4">
                                    <span
                                        class="fg-secondary small d-block">{{ __('delivery_orders.form.tracking_number') }}</span><strong>{{ $deliveryOrder->tracking_number ?? '-' }}</strong>
                                </div>
                            </div>
                        @else
                            <p class="fg-secondary mb-0">{{ __('sales_orders.messages.no_shipping') }}</p>
                        @endif
                    </div>

                </div>

                <div class="border-top pt-4" id="invoice-lines-section">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0">{{ __('ledgers.invoice_lines') }}</h6>
                        <span class="fg-secondary small">{{ __('ledgers.complete_rows_needed') }}</span>
                    </div>
                    <div class="table-responsive mb-4 rounded border">
                        <table class="table-form table-striped mb-0 table align-middle" id="invoice-lines-table">
                            <thead class="bg-1">
                            <tr>
                                <th style="min-width: 220px">{{ __('ledgers.table.column_product') }}</th>
                                <th style="min-width: 220px">{{ __('ledgers.table.column_description') }}</th>
                                <th style="min-width: 110px">{{ __('ledgers.table.column_quantity') }}</th>
                                <th style="min-width: 140px">{{ __('ledgers.table.column_unit_price') }}</th>
                                <th style="min-width: 230px">{{ __('products.form.default_discount') }}</th>
                                <th style="min-width: 230px">{{ __('products.form.default_tax') }}</th>
                                <th class="text-end" style="min-width: 130px">
                                    {{ __('ledgers.table.column_total_amount') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            @for ($lineIndex = 0; $lineIndex < 10; $lineIndex++)
                                <tr>
                                    <td>
                                        <select
                                            class="form-control form-control-sm js-product-select @error("lines.$lineIndex.product_id") is-invalid @enderror"
                                            name="lines[{{ $lineIndex }}][product_id]">
                                            <option value="">{{ __('common.actions.select_option') }}</option>
                                            @foreach ($products as $product)
                                                <option data-base-price="{{ $product->base_price }}"
                                                        data-selling-price="{{ $product->selling_price }}"
                                                        data-default-discount-id="{{ $product->default_discount_id }}"
                                                        data-default-tax-id="{{ $product->default_tax_id }}"
                                                        value="{{ $product->id }}"
                                                    {{ old("lines.$lineIndex.product_id") == $product->id ? 'selected' : '' }}>
                                                    {{ $product->sku ? $product->sku . ' - ' : '' }}{{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("lines.$lineIndex.product_id")
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <label class="form-control form-control-sm form-adorn">
                                            <input
                                                class="form-ghost @error("lines.$lineIndex.description") is-invalid @enderror"
                                                name="lines[{{ $lineIndex }}][description]" type="text"
                                                value="{{ old("lines.$lineIndex.description") }}"
                                                placeholder="Description if no product is selected"/>
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
                                                value="{{ old("lines.$lineIndex.quantity") }}" min="1"/>
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
                                                step="0.01"/>
                                        </label>
                                        @error("lines.$lineIndex.unit_price")
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm line-discount-option"
                                                name="lines[{{ $lineIndex }}][discount_option]">
                                            <option data-type="percent" data-value="0" value="none"
                                                @selected(old("lines.$lineIndex.discount_option", 'none') === 'none')>
                                                {{ __('discounts.form.none') }}
                                            </option>
                                            <option data-type="{{ old("lines.$lineIndex.discount_type", 'percent') }}"
                                                    data-value="{{ old("lines.$lineIndex.discount_value", 0) }}"
                                                    value="manual" @selected(old("lines.$lineIndex.discount_option") === 'manual')>
                                                {{ __('discounts.form.manual') }}
                                            </option>
                                            @foreach ($discounts as $discount)
                                                <option data-type="{{ $discount->type->value }}"
                                                        data-value="{{ $discount->value }}" value="{{ $discount->id }}"
                                                    @selected(old("lines.$lineIndex.discount_option") === $discount->id)>
                                                    {{ $discount->code }} - {{ $discount->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="input-group input-group-sm mt-1">
                                            <select class="form-control line-discount-type"
                                                    name="lines[{{ $lineIndex }}][discount_type]">
                                                <option
                                                    value="percent" @selected(old("lines.$lineIndex.discount_type", 'percent') === 'percent')>
                                                    %
                                                </option>
                                                <option
                                                    value="amount" @selected(old("lines.$lineIndex.discount_type") === 'amount')>
                                                    RM
                                                </option>
                                            </select>
                                            <label class="form-control form-adorn">
                                                <input class="form-ghost line-discount-value"
                                                       name="lines[{{ $lineIndex }}][discount_value]" type="number"
                                                       value="{{ old("lines.$lineIndex.discount_value", 0) }}"
                                                       min="0" step="0.0001"/>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm line-tax-option"
                                                name="lines[{{ $lineIndex }}][tax_option]">
                                            <option data-mode="exclusive" data-rate="0" value="none"
                                                @selected(old("lines.$lineIndex.tax_option", 'none') === 'none')>
                                                {{ __('taxes.form.none') }}
                                            </option>
                                            <option data-mode="{{ old("lines.$lineIndex.tax_mode", 'exclusive') }}"
                                                    data-rate="{{ old("lines.$lineIndex.tax_rate", 0) }}" value="manual"
                                                @selected(old("lines.$lineIndex.tax_option") === 'manual')>
                                                {{ __('taxes.form.manual') }}
                                            </option>
                                            @foreach ($taxes as $tax)
                                                <option data-mode="{{ $tax->default_mode->value }}"
                                                        data-rate="{{ $tax->rate }}" value="{{ $tax->id }}"
                                                    @selected(old("lines.$lineIndex.tax_option") === $tax->id)>
                                                    {{ $tax->code }} - {{ $tax->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="input-group input-group-sm mt-1">
                                            <label class="form-control form-adorn">
                                                <input class="form-ghost line-tax-rate"
                                                       name="lines[{{ $lineIndex }}][tax_rate]" type="number"
                                                       value="{{ old("lines.$lineIndex.tax_rate", 0) }}" min="0"
                                                       max="100" step="0.0001"/>
                                            </label>
                                            <select class="form-control line-tax-mode"
                                                    name="lines[{{ $lineIndex }}][tax_mode]">
                                                <option
                                                    value="exclusive" @selected(old("lines.$lineIndex.tax_mode", 'exclusive') === 'exclusive')>
                                                    {{ __('ledgers.tax_mode.exclusive') }}
                                                </option>
                                                <option
                                                    value="inclusive" @selected(old("lines.$lineIndex.tax_mode") === 'inclusive')>
                                                    {{ __('ledgers.tax_mode.inclusive') }}
                                                </option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="line-total fw-bold text-end">0.00</td>
                                </tr>
                            @endfor
                            </tbody>
                            <tfoot class="bg-1">
                            <tr>
                                <th class="text-end" colspan="6">
                                    {{ __('ledgers.table.column_subtotal_amount') }}
                                </th>
                                <th class="text-end" id="invoice-subtotal">0.00</th>
                            </tr>
                            <tr>
                                <th class="text-end" colspan="6">
                                    {{ __('ledgers.table.column_discount_amount') }}
                                </th>
                                <th class="fg-danger text-end" id="invoice-discount-total">0.00</th>
                            </tr>
                            <tr>
                                <th class="text-end" colspan="6">{{ __('ledgers.table.column_tax_amount') }}</th>
                                <th class="text-end" id="invoice-tax-total">0.00</th>
                            </tr>
                            <tr>
                                <th class="text-end" colspan="6">{{ __('common.labels.total') }}</th>
                                <th class="fg-primary text-end" id="invoice-grand-total">0.00</th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-between border-top pt-3">
                    <a class="btn btn-solid theme-secondary" href="{{ route($indexRoute) }}">
                        <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                    </a>
                    <button class="btn btn-solid theme-accent" id="save-entry-button" type="submit">
                        <i class="me-1" data-lucide="save"></i>{{ __('common.actions.update') }}
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
        const invoiceSaveText = @json(__('common.actions.update'));

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
                const canDebit = type.isAsset === true && chart.isDebtor === true;
                const canCredit = type.isIncome === true;

                if (canDebit)
                    debitSelect.add(new Option(label, chart.id, false, String(chart.id) === String(
                        selectedDebitAcc)));
                if (canCredit)
                    creditSelect.add(new Option(label, chart.id, false, String(chart.id) === String(
                        selectedCreditAcc)));
                if (chart.isBank === true) paymentDebitSelect.add(new Option(label, chart.id));
                if (type.isAsset === true && chart.isDebtor === true) paymentCreditSelect.add(new Option(label,
                    chart.id));
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

        function refreshProductSelects() {
            if (typeof window.initCombobox !== 'function') {
                return;
            }

            document.querySelectorAll('.js-product-select').forEach((element) => {
                window.initCombobox(element, {
                    placeholder: selectText,
                });
            });
        }

        function setFormSection(section) {
            document.querySelectorAll('#invoice-form-tabs [data-form-section]').forEach((tab) => {
                tab.classList.toggle('active', tab.dataset.formSection === section);
            });
            document.getElementById('invoice-common-section').classList.toggle('sales-tab-panel-active', section ===
                'general');
            document.getElementById('attachment-section').classList.toggle('sales-tab-panel-active', section ===
                'attachment');
            document
                .getElementById('payment-details-section')
                .classList.toggle('sales-tab-panel-active', section === 'payment');
            document.getElementById('shipping-section').classList.toggle('sales-tab-panel-active', section === 'shipping');
            document.getElementById('accounting-section').classList.toggle('sales-tab-panel-active', section ===
                'accounting');
        }

        function refreshEntryForm() {
            filterChartOfAccounts();
        }

        function applySelectedProductPrice(select, force = false) {
            const row = select.closest('tr');

            if (!row) {
                return;
            }

            const unitPriceInput = row.querySelector('.line-unit-price');
            const selectedValue = String(select.value ?? '');
            const selectedOption = Array.from(select.options ?? []).find((option) => String(option.value) ===
                selectedValue);

            if (selectedValue === '' || !selectedOption) {
                if (force && unitPriceInput) {
                    unitPriceInput.value = '';
                    refreshLineTotals();
                }

                return;
            }

            if (!unitPriceInput || (!force && unitPriceInput.value !== '')) {
                return;
            }

            const priceAttribute = 'data-selling-price';
            const selectedPrice = selectedOption.getAttribute(priceAttribute);

            if (selectedPrice === null) {
                return;
            }

            unitPriceInput.value = selectedPrice;
            refreshLineTotals();
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
                const discountType = row.querySelector('.line-discount-type').value;
                const discountValue = Number(row.querySelector('.line-discount-value').value) || 0;
                const taxRate = Number(row.querySelector('.line-tax-rate').value) || 0;
                const taxMode = row.querySelector('.line-tax-mode').value;
                const lineDiscount =
                    discountType === 'percent' ?
                        roundMoney((lineSubtotal * discountValue) / 100) :
                        Math.min(lineSubtotal, discountValue);
                const discountedAmount = roundMoney(lineSubtotal - lineDiscount);
                const lineTax =
                    taxRate > 0 ?
                        roundMoney(
                            taxMode === 'inclusive' ?
                                (discountedAmount * taxRate) / (100 + taxRate) :
                                (discountedAmount * taxRate) / 100,
                        ) :
                        0;
                const lineTotal = taxMode === 'inclusive' ? discountedAmount : roundMoney(discountedAmount +
                    lineTax);

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

        function applySelectedDiscount(row) {
            const discountOption = row.querySelector('.line-discount-option');
            const discountType = row.querySelector('.line-discount-type');
            const discountValue = row.querySelector('.line-discount-value');
            const selected = discountOption.options[discountOption.selectedIndex];
            const isManual = discountOption.value === 'manual';

            discountType.disabled = !isManual;
            discountValue.readOnly = !isManual;
            if (!isManual) {
                discountType.value = selected.dataset.type || 'percent';
                discountValue.value = selected.dataset.value || '0';
            }
            discountValue.max = discountType.value === 'percent' ? '100' : '';
            refreshLineTotals();
        }

        function applySelectedTax(row) {
            const taxOption = row.querySelector('.line-tax-option');
            const taxRate = row.querySelector('.line-tax-rate');
            const taxMode = row.querySelector('.line-tax-mode');
            const selected = taxOption.options[taxOption.selectedIndex];
            const isManual = taxOption.value === 'manual';

            taxRate.readOnly = !isManual;
            if (!isManual) {
                taxRate.value = selected.dataset.rate || '0';
                taxMode.value = selected.dataset.mode || 'exclusive';
            }
            refreshLineTotals();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const invoiceForm = document.querySelector('#invoice-form-tabs')?.closest('form');
            let invoiceInvalidTabActivated = false;

            invoiceForm?.addEventListener(
                'invalid',
                (event) => {
                    if (invoiceInvalidTabActivated) return;

                    const sections = {
                        'invoice-common-section': 'general',
                        'invoice-lines-section': 'general',
                        'attachment-section': 'attachment',
                        'payment-details-section': 'payment',
                        'shipping-section': 'shipping',
                        'accounting-section': 'accounting',
                    };
                    const section = Object.entries(sections).find(([id]) => event.target.closest(`#${id}`));
                    if (section) {
                        invoiceInvalidTabActivated = true;
                        setFormSection(section[1]);
                        setTimeout(() => (invoiceInvalidTabActivated = false), 0);
                    }
                },
                true,
            );
            filterContactsByCompany();
            setFormSection('general');
            refreshProductSelects();
            document.querySelectorAll('.js-product-select').forEach((select) => {
                select.addEventListener('change', () => {
                    applySelectedProductPrice(select, true);
                    const row = select.closest('tr');
                    const option = select.selectedOptions[0];
                    if (!row || !option) return;
                    row.querySelector('.line-discount-option').value = option.dataset
                        .defaultDiscountId || 'none';
                    row.querySelector('.line-tax-option').value = option.dataset.defaultTaxId ||
                        'none';
                    applySelectedDiscount(row);
                    applySelectedTax(row);
                });

                if (select.value !== '') {
                    applySelectedProductPrice(select);
                }
            });
            document
                .querySelectorAll('#invoice-form-tabs [data-form-section]')
                .forEach((tab) => tab.addEventListener('click', () => setFormSection(tab.dataset.formSection)));
            document
                .querySelectorAll('.line-quantity, .line-unit-price')
                .forEach((input) => input.addEventListener('input', refreshLineTotals));
            document
                .querySelectorAll('.line-discount-value, .line-tax-rate, .line-tax-mode')
                .forEach((input) => input.addEventListener('input', refreshLineTotals));
            document.querySelectorAll('#invoice-lines-table tbody tr').forEach((row) => {
                row.querySelector('.line-discount-option').addEventListener('change', () =>
                    applySelectedDiscount(row));
                row.querySelector('.line-discount-type').addEventListener('change', refreshLineTotals);
                row.querySelector('.line-tax-option').addEventListener('change', () => applySelectedTax(
                    row));
                applySelectedDiscount(row);
                applySelectedTax(row);
            });
        });
    </script>
@endsection
