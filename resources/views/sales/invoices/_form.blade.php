@php
    use App\Enums\Finance\LedgerSubType;
    use App\Enums\Finance\PaymentTransferType;
    use App\Enums\UploadType;

    $existingLines = $invoice?->lines ?? collect();
@endphp

@props([
    'invoice' => null,
    'companies',
    'products',
    'contacts',
    'charts',
    'taxes',
    'discounts',
    'subtype',
    'indexRoute',
    'deliveryOrder' => null,
    'canCreateCompany' => false,
])

@if ($invoice !== null)
    <input name="id" type="hidden" value="{{ $invoice->id }}" />
@endif

<ul class="sales-document-tabs nav nav-tabs" id="invoice-form-tabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-form-section="general" type="button">
            {{ __('sales_orders.tabs.general') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-form-section="attachment" type="button">
            {{ __('sales_orders.tabs.attachment') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-form-section="payment" type="button">
            {{ __('sales_orders.tabs.payment') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-form-section="shipping" type="button">
            {{ __('sales_orders.tabs.shipping') }}
        </button>
    </li>
</ul>

<div class="sales-tab-workspace">
    <div class="sales-tab-panel sales-tab-panel-active" id="invoice-common-section">
        @if ($invoice === null)
            <div class="row g-3 mb-4">
                <div class="md:col-6">
                    <label class="form-label fw-bold"
                        for="date">{{ __('ledgers.table.column_date') }}</label>
                    <div class="form-control form-adorn">
                        <div class="form-adorn-icon" aria-hidden="true">
                            <i data-lucide="calendar"></i>
                        </div>
                        <input class="form-ghost @error('date') is-invalid @enderror" id="date"
                            name="date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                            value="{{ old('date', now()->format('Y-m-d')) }}" min="{{ $postingDateMin ?? '' }}"
                            max="{{ $postingDateMax ?? '' }}" required />
                    </div>
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
                    @if ($canCreateCompany)
                        @include('partials.quick-company', [
                            'quickCompanyRoute' => route('sales.invoices.companies.store'),
                            'quickCompanyLabel' => __('invoices.actions.quick_customer'),
                            'quickCompanyNamePlaceholder' => __('invoices.form.quick_customer_name'),
                            'quickCompanySaveLabel' => __('invoices.actions.add_customer'),
                            'quickCompanyRequiredMessage' => __(
                                'invoices.validation.company_name_required'),
                            'quickCompanyFailedMessage' => __(
                                'invoices.validation.company_create_failed'),
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
        @endif

        <div class="mb-4">
            <label class="form-label fw-bold"
                for="invoice_notes">{{ __('ledgers.table.column_notes') }}</label>
            <textarea class="form-control @error('invoice_notes') is-invalid @enderror" id="invoice_notes" name="invoice_notes"
                rows="2">{{ old('invoice_notes', $invoice?->notes) }}</textarea>
        </div>
    </div>

    <div class="sales-tab-panel" id="attachment-section">
        <label class="form-label fw-bold"
            for="invoice_attachment">{{ __('ledgers.table.column_attachment_path') }}</label>
        <input class="form-control @error('invoice_attachment') is-invalid @enderror"
            id="invoice_attachment" name="invoice_attachment" type="file"
            accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}" />
    </div>

    <div class="sales-tab-panel bg-2 rounded border p-3" id="payment-details-section">
        <div class="row g-3">
            <div class="md:col-6">
                <label class="form-label fw-bold"
                    for="amount">{{ __('ledgers.table.column_amount') }}</label>
                <div class="form-control form-adorn">
                    <input class="form-ghost @error('amount') is-invalid @enderror" id="amount"
                        name="amount" type="number" value="{{ old('amount', $invoice?->amount) }}" min="0.01"
                        step="0.01" />
                </div>
                <div class="form-text">{{ __('ledgers.optional_payment_hint') }}</div>
                @error('amount')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold" for="payment_transfer_type">
                    {{ __('payments.form.payment_transfer') }}
                </label>
                <select class="form-control @error('payment_transfer_type') is-invalid @enderror" id="payment_transfer_type" name="payment_transfer_type">
                    <option value="" disabled hidden>{{ __('common.actions.select_option') }}</option>
                    @foreach (PaymentTransferType::cases() as $transferType)
                        <option value="{{ $transferType->value }}" @selected(old('payment_transfer_type', $invoice?->payment_transfer_type?->value) === $transferType->value)>
                            {{ $transferType->label() }}
                        </option>
                    @endforeach
                </select>
                @error('payment_transfer_type')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-bold" for="payment_attachment">
                    {{ __('ledgers.form.payment_attachment') }}
                </label>
                <input class="form-control @error('payment_attachment') is-invalid @enderror"
                    id="payment_attachment" name="payment_attachment" type="file"
                    accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}" />
                @if ($invoice?->payment_attachment_path)
                    <div class="mt-2">
                        <a href="{{ asset('storage/' . $invoice->payment_attachment_path) }}" target="_blank" class="fg-primary small">
                            {{ __('common.actions.view_attachment') }}
                        </a>
                    </div>
                @endif
                @error('payment_attachment')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-bold"
                    for="payment_notes">{{ __('ledgers.table.column_notes') }}</label>
                <textarea class="form-control @error('payment_notes') is-invalid @enderror" id="payment_notes" name="payment_notes"
                    rows="2">{{ old('payment_notes', $invoice?->payment_notes) }}</textarea>
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
        <table class="table-striped mb-0 table align-middle" id="invoice-lines-table">
            <thead class="bg-1">
                <tr>
                    <th scope="col" style="min-width: 220px">{{ __('ledgers.table.column_product') }}</th>
                    <th scope="col" style="min-width: 220px">{{ __('ledgers.table.column_description') }}</th>
                    <th scope="col" style="min-width: 110px">{{ __('ledgers.table.column_quantity') }}</th>
                    <th scope="col" style="min-width: 140px">{{ __('ledgers.table.column_unit_price') }}</th>
                    <th scope="col" style="min-width: 230px">{{ __('products.form.default_discount') }}</th>
                    <th scope="col" style="min-width: 230px">{{ __('products.form.default_tax') }}</th>
                    <th scope="col" class="text-end" style="min-width: 130px">
                        {{ __('ledgers.table.column_total_amount') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                @for ($lineIndex = 0; $lineIndex < $perPageGridDetail; $lineIndex++)
                    @php
                        $line = $existingLines->get($lineIndex);
                    @endphp
                    <tr data-line-index="{{ $lineIndex }}">
                        <td style="min-width: 220px">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="lines[{{ $lineIndex }}][product_id]"
                                    class="invoice-product-id"
                                    value="{{ old("lines.$lineIndex.product_id") }}">
                                <div
                                    class="invoice-product-display form-control form-control-sm d-flex align-items-center justify-content-between">
                                    <span class="invoice-product-label text-truncate">
                                        @php
                                            $oldProductId = old("lines.$lineIndex.product_id", $line?->product_id);
                                            $selectedProduct = $oldProductId
                                                ? $products->firstWhere('id', $oldProductId)
                                                : null;
                                        @endphp
                                        {{ $selectedProduct ? ($selectedProduct->sku ? $selectedProduct->sku . ' - ' : '') . $selectedProduct->name : __('invoices.form.select_product') }}
                                    </span>
                                </div>
                                <button
                                    class="btn btn-solid theme-primary invoice-product-choose-btn flex-shrink-0"
                                    type="button" data-line-index="{{ $lineIndex }}">
                                    <i data-lucide="search"></i>
                                </button>
                            </div>
                            @error("lines.$lineIndex.product_id")
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </td>
                        <td>
                            <div class="form-control form-control-sm form-adorn">
                                <label class="visually-hidden"
                                    for="line-{{ $lineIndex }}-description">{{ __('ledgers.table.column_description') }}</label>
                                <input
                                    id="line-{{ $lineIndex }}-description"
                                    class="form-ghost @error("lines.$lineIndex.description") is-invalid @enderror"
                                    name="lines[{{ $lineIndex }}][description]" type="text"
                                    value="{{ old("lines.$lineIndex.description", $line?->description) }}"
                                    placeholder="Description if no product is selected" />
                            </div>
                            @error("lines.$lineIndex.description")
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </td>
                        <td>
                            <div class="form-control form-control-sm form-adorn">
                                <label class="visually-hidden"
                                    for="line-{{ $lineIndex }}-quantity">{{ __('ledgers.table.column_quantity') }}</label>
                                <input
                                    id="line-{{ $lineIndex }}-quantity"
                                    class="form-ghost line-quantity @error("lines.$lineIndex.quantity") is-invalid @enderror"
                                    name="lines[{{ $lineIndex }}][quantity]" type="number"
                                    value="{{ old("lines.$lineIndex.quantity", $line?->quantity) }}" min="1" />
                            </div>
                            @error("lines.$lineIndex.quantity")
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </td>
                        <td>
                            <div class="form-control form-control-sm form-adorn">
                                <label class="visually-hidden"
                                    for="line-{{ $lineIndex }}-unit_price">{{ __('ledgers.table.column_unit_price') }}</label>
                                <input
                                    id="line-{{ $lineIndex }}-unit_price"
                                    class="form-ghost line-unit-price @error("lines.$lineIndex.unit_price") is-invalid @enderror"
                                    name="lines[{{ $lineIndex }}][unit_price]" type="number"
                                    value="{{ old("lines.$lineIndex.unit_price", $line?->unit_price) }}" min="0"
                                    step="0.01" />
                            </div>
                            @error("lines.$lineIndex.unit_price")
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </td>
                        <td>
                            <label class="visually-hidden"
                                for="line-{{ $lineIndex }}-discount_option">{{ __('products.form.default_discount') }}</label>
                            <select class="form-control form-control-sm line-discount-option"
                                id="line-{{ $lineIndex }}-discount_option"
                                name="lines[{{ $lineIndex }}][discount_option]">
                                <option data-type="percent" data-value="0" value="none"
                                    @selected(old("lines.$lineIndex.discount_option", $line?->discount_id ?? 'none') === 'none')>
                                    {{ __('discounts.form.none') }}
                                </option>
                                <option data-type="{{ old("lines.$lineIndex.discount_type", $line?->discount_type ?? 'percent') }}"
                                    data-value="{{ old("lines.$lineIndex.discount_value", $line?->discount_value ?? 0) }}"
                                    value="manual" @selected(old("lines.$lineIndex.discount_option", $line?->discount_id) === 'manual')>
                                    {{ __('discounts.form.manual') }}
                                </option>
                                @foreach ($discounts as $discount)
                                    <option data-type="{{ $discount->type->value }}"
                                        data-value="{{ $discount->value }}" value="{{ $discount->id }}"
                                        @selected(old("lines.$lineIndex.discount_option", $line?->discount_id) === $discount->id)>
                                        {{ $discount->code }} - {{ $discount->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group input-group-sm mt-1">
                                <label class="visually-hidden"
                                    for="line-{{ $lineIndex }}-discount_type">{{ __('products.form.default_discount') }} {{ __('ledgers.table.column_type') }}</label>
                                <select class="form-control line-discount-type"
                                    id="line-{{ $lineIndex }}-discount_type"
                                    name="lines[{{ $lineIndex }}][discount_type]">
                                    <option value="percent" @selected(old("lines.$lineIndex.discount_type", $line?->discount_type ?? 'percent') === 'percent')>
                                        %
                                    </option>
                                    <option value="amount" @selected(old("lines.$lineIndex.discount_type", $line?->discount_type) === 'amount')>
                                        RM
                                    </option>
                                </select>
                                <div class="form-control form-adorn">
                                    <label class="visually-hidden"
                                        for="line-{{ $lineIndex }}-discount_value">{{ __('products.form.default_discount') }} {{ __('ledgers.table.column_amount') }}</label>
                                    <input id="line-{{ $lineIndex }}-discount_value" class="form-ghost line-discount-value"
                                        name="lines[{{ $lineIndex }}][discount_value]" type="number"
                                        value="{{ old("lines.$lineIndex.discount_value", $line?->discount_value ?? 0) }}"
                                        min="0" step="0.0001" />
                                </div>
                            </div>
                        </td>
                        <td>
                            <label class="visually-hidden"
                                for="line-{{ $lineIndex }}-tax_option">{{ __('products.form.default_tax') }}</label>
                            <select class="form-control form-control-sm line-tax-option"
                                id="line-{{ $lineIndex }}-tax_option"
                                name="lines[{{ $lineIndex }}][tax_option]">
                                <option data-mode="exclusive" data-rate="0" value="none"
                                    @selected(old("lines.$lineIndex.tax_option", $line?->tax_id ?? 'none') === 'none')>
                                    {{ __('taxes.form.none') }}
                                </option>
                                <option data-mode="{{ old("lines.$lineIndex.tax_mode", $line?->tax_mode ?? 'exclusive') }}"
                                    data-rate="{{ old("lines.$lineIndex.tax_rate", $line?->tax_rate ?? 0) }}" value="manual"
                                    @selected(old("lines.$lineIndex.tax_option", $line?->tax_id) === 'manual')>
                                    {{ __('taxes.form.manual') }}
                                </option>
                                @foreach ($taxes as $tax)
                                    <option data-mode="{{ $tax->default_mode->value }}"
                                        data-rate="{{ $tax->rate }}" value="{{ $tax->id }}"
                                        @selected(old("lines.$lineIndex.tax_option", $line?->tax_id) === $tax->id)>
                                        {{ $tax->code }} - {{ $tax->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group input-group-sm mt-1">
                                <div class="form-control form-adorn">
                                    <label class="visually-hidden"
                                        for="line-{{ $lineIndex }}-tax_rate">{{ __('ledgers.table.column_tax_rate') }}</label>
                                    <input id="line-{{ $lineIndex }}-tax_rate" class="form-ghost line-tax-rate"
                                        name="lines[{{ $lineIndex }}][tax_rate]" type="number"
                                        value="{{ old("lines.$lineIndex.tax_rate", $line?->tax_rate ?? 0) }}" min="0"
                                        max="100" step="0.0001" />
                                </div>
                                <label class="visually-hidden"
                                    for="line-{{ $lineIndex }}-tax_mode">{{ __('products.form.default_tax') }} {{ __('ledgers.table.column_type') }}</label>
                                <select class="form-control line-tax-mode"
                                    id="line-{{ $lineIndex }}-tax_mode"
                                    name="lines[{{ $lineIndex }}][tax_mode]">
                                    <option value="exclusive" @selected(old("lines.$lineIndex.tax_mode", $line?->tax_mode ?? 'exclusive') === 'exclusive')>
                                        {{ __('ledgers.tax_mode.exclusive') }}
                                    </option>
                                    <option value="inclusive" @selected(old("lines.$lineIndex.tax_mode", $line?->tax_mode) === 'inclusive')>
                                        {{ __('ledgers.tax_mode.inclusive') }}
                                    </option>
                                </select>
                            </div>
                        </td>
                        <td class="line-total fw-bold text-end">
                            {{ $invoice ? number_format((float) ($line?->total_amount ?? 0), 2) : '0.00' }}
                        </td>
                    </tr>
                @endfor
            </tbody>
            <tfoot class="bg-1">
                <tr>
                    <th scope="row" class="text-end" colspan="6">
                        {{ __('ledgers.table.column_subtotal_amount') }}
                    </th>
                    <th scope="row" class="text-end" id="invoice-subtotal">0.00</th>
                </tr>
                <tr>
                    <th scope="row" class="text-end" colspan="6">
                        {{ __('ledgers.table.column_discount_amount') }}
                    </th>
                    <th scope="row" class="fg-danger text-end" id="invoice-discount-total">0.00</th>
                </tr>
                <tr>
                    <th scope="row" class="text-end" colspan="6">{{ __('ledgers.table.column_tax_amount') }}</th>
                    <th scope="row" class="text-end" id="invoice-tax-total">0.00</th>
                </tr>
                <tr>
                    <th scope="row" class="text-end" colspan="6">{{ __('common.labels.total') }}</th>
                    <th scope="row" class="fg-primary text-end" id="invoice-grand-total">0.00</th>
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
        <i class="me-1" data-lucide="save"></i>{{ $invoice === null ? __('common.actions.save') : __('common.actions.update') }}
    </button>
</div>

@push('scripts')
    <script>
        const allContacts = @json($contacts);
        const selectText = @json(__('common.actions.select_option'));
        const selectedContactId = @json(old('contact_id', $invoice?->contact_id));
        const invoiceSaveText = @json($invoice === null ? __('common.actions.save') : __('common.actions.update'));

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

        filterContactsByCompany();

        function setFormSection(section) {
            document.querySelectorAll('#invoice-form-tabs [data-form-section]').forEach((tab) => {
                tab.classList.toggle('active', tab.dataset.formSection === section);
            });
            const panels = {
                'invoice-common-section': 'general',
                'attachment-section': 'attachment',
                'payment-details-section': 'payment',
                'shipping-section': 'shipping',
                'accounting-section': 'accounting',
            };
            Object.entries(panels).forEach(([id, name]) => {
                document.getElementById(id)?.classList.toggle('sales-tab-panel-active', section === name);
            });
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

            // Product picker modal functionality
            const productPickerDialog = document.getElementById('invoiceProductPickerDialog');
            const productSearchInput = document.getElementById('invoice-product-search');
            const productCategoryFilter = document.getElementById('invoice-product-category-filter');
            const productTypeFilter = document.getElementById('invoice-product-type-filter');
            const productSearchResults = document.getElementById('invoice-product-search-results');
            let currentProductLineIndex = null;
            let productSearchTimeout = null;

            function filterInvoiceProductTypes() {
                const categoryId = productCategoryFilter.value;

                Array.from(productTypeFilter.options).forEach((option) => {
                    if (!option.dataset.categoryId) return;
                    const belongsToCategory = option.dataset.categoryId === categoryId;
                    option.hidden = !belongsToCategory;
                    option.disabled = !belongsToCategory;
                });

                if (productTypeFilter.selectedOptions[0]?.hidden) {
                    productTypeFilter.value = '';
                }

                productTypeFilter.disabled = categoryId === '';
            }

            function searchInvoiceProducts() {
                const search = productSearchInput.value;
                const categoryId = productCategoryFilter.value;
                const typeId = productTypeFilter.value;
                const typeToSearchText = @json(__('invoices.form.type_to_search'));
                const noProductsText = @json(__('invoices.form.no_products_found'));
                const searchErrorText = @json(__('invoices.form.search_error'));

                if (search.length < 2 && !categoryId && !typeId) {
                    productSearchResults.querySelector('tbody').innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-muted" style="font-size: 1.05rem; padding: 1rem;">${typeToSearchText}</td>
                        </tr>
                    `;
                    return;
                }

                fetch(
                    `{{ route('sales.invoices.products.search') }}?search=${encodeURIComponent(search)}&category_id=${encodeURIComponent(categoryId)}&type_id=${encodeURIComponent(typeId)}`)
                    .then(response => response.json())
                    .then(products => {
                        if (products.length === 0) {
                            productSearchResults.querySelector('tbody').innerHTML = `
                                <tr>
                                    <td colspan="4" class="text-center text-muted" style="font-size: 1.05rem; padding: 1rem;">${noProductsText}</td>
                                </tr>
                            `;
                            return;
                        }

                        productSearchResults.querySelector('tbody').innerHTML = products.map(product => `
                            <tr style="cursor: pointer; font-size: 1.05rem;"
                                data-product-id="${product.id}"
                                data-product-name="${product.name.replace(/"/g, '&quot;')}"
                                data-product-sku="${(product.sku || '').replace(/"/g, '&quot;')}"
                                data-product-price="${product.price}"
                                data-discount-id="${product.default_discount_id || 'none'}"
                                data-tax-id="${product.default_tax_id || 'none'}">
                                <td style="padding: 1rem;">${product.sku || '-'}</td>
                                <td style="padding: 1rem;">${product.name}</td>
                                <td style="padding: 1rem;">${product.category || '-'}</td>
                                <td style="padding: 1rem;">${parseFloat(product.price).toFixed(2)}</td>
                            </tr>
                        `).join('');

                        // Add click handlers
                        productSearchResults.querySelectorAll('tbody tr[data-product-id]').forEach(row => {
                            row.addEventListener('click', function () {
                                selectInvoiceProduct(
                                    this.dataset.productId,
                                    this.dataset.productName,
                                    this.dataset.productSku,
                                    this.dataset.productPrice,
                                    this.dataset.discountId,
                                    this.dataset.taxId
                                );
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Error searching products:', error);
                        productSearchResults.querySelector('tbody').innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center text-danger" style="font-size: 1.05rem; padding: 1rem;">${searchErrorText}</td>
                            </tr>
                        `;
                    });
            }

            window.selectInvoiceProduct = function (productId, productName, productSku, productPrice,
                                                    defaultDiscountId, defaultTaxId) {
                if (currentProductLineIndex === null) return;

                const row = document.querySelector(
                    `#invoice-lines-table tbody tr[data-line-index="${currentProductLineIndex}"]`);
                if (!row) return;

                const productInput = row.querySelector('.invoice-product-id');
                const productLabel = row.querySelector('.invoice-product-label');

                if (productInput) productInput.value = productId;
                if (productLabel) productLabel.textContent = productSku ? `${productSku} - ${productName}` :
                    productName;

                // Set price
                const unitPriceInput = row.querySelector('.line-unit-price');
                if (unitPriceInput) {
                    unitPriceInput.value = parseFloat(productPrice).toFixed(2);
                }

                // Set discount
                const discountOption = row.querySelector('.line-discount-option');
                if (discountOption) {
                    discountOption.value = defaultDiscountId || 'none';
                    applySelectedDiscount(row);
                }

                // Set tax
                const taxOption = row.querySelector('.line-tax-option');
                if (taxOption) {
                    taxOption.value = defaultTaxId || 'none';
                    applySelectedTax(row);
                }

                refreshLineTotals();
                window.bootstrap.Dialog.getOrCreateInstance(productPickerDialog).hide();
            };

            document.querySelectorAll('.invoice-product-choose-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    currentProductLineIndex = this.dataset.lineIndex;
                    productSearchInput.value = '';
                    productCategoryFilter.value = '';
                    productTypeFilter.value = '';
                    filterInvoiceProductTypes();
                    const typeToSearchText = @json(__('invoices.form.type_to_search'));
                    productSearchResults.querySelector('tbody').innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-muted" style="font-size: 1.05rem; padding: 1rem;">${typeToSearchText}</td>
                        </tr>
                    `;
                    window.bootstrap.Dialog.getOrCreateInstance(productPickerDialog).show();
                    setTimeout(() => productSearchInput.focus(), 100);
                });
            });

            productSearchInput.addEventListener('input', () => {
                clearTimeout(productSearchTimeout);
                productSearchTimeout = setTimeout(searchInvoiceProducts, 300);
            });

            productCategoryFilter.addEventListener('change', () => {
                filterInvoiceProductTypes();
                searchInvoiceProducts();
            });
            productTypeFilter.addEventListener('change', searchInvoiceProducts);
            filterInvoiceProductTypes();

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

            @if ($invoice !== null)
                refreshLineTotals();
            @endif

            @if (isset($cloneData) && $cloneData)
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


                const cloneLines = (cloneData.lines || []).filter(
                    (l) => !l.line_type || l.line_type !== 'account',
                );

                if (cloneLines.length > 0) {
                    const rows = document.querySelectorAll('#invoice-lines-table tbody tr');

                    cloneLines.forEach((line, index) => {
                        if (index >= rows.length) return;
                        const row = rows[index];

                        if (line.product_id) {
                            const productInput = row.querySelector('.invoice-product-id');
                            const productLabel = row.querySelector('.invoice-product-label');

                            if (productInput) productInput.value = line.product_id;

                            // Find product name from the products collection
                            const allProducts = @json($products);
                            const product = allProducts.find(p => p.id === line.product_id);
                            if (productLabel && product) {
                                productLabel.textContent = product.sku ?
                                    `${product.sku} - ${product.name}` : product.name;
                            }

                            // Set price
                            if (line?.unit_price) {
                                const unitPriceInput = row.querySelector('.line-unit-price');
                                if (unitPriceInput) unitPriceInput.value = line.unit_price;
                            } else if (product) {
                                const unitPriceInput = row.querySelector('.line-unit-price');
                                if (unitPriceInput) unitPriceInput.value = parseFloat(product.selling_price)
                                    .toFixed(2);
                            }
                        }

                        if (line.description) {
                            row.querySelector('[name*="[description]"]').value = line.description;
                        }
                        if (line.quantity) {
                            row.querySelector('.line-quantity').value = line.quantity;
                        }
                        if (line.unit_price) {
                            row.querySelector('.line-unit-price').value = line.unit_price;
                        }

                        // Discount and tax are copied exactly: prefer the catalog
                        // entry by id (carries the exact type/rate/mode), fall
                        // back to the stored manual values, never reverse-
                        // computed from amounts (that produced 5.9989 for 6%).
                        const discountOption = row.querySelector('.line-discount-option');
                        const discountOptionValues = Array.from(discountOption.options).map((o) => o.value);
                        if (line.discount_id && discountOptionValues.includes(String(line.discount_id))) {
                            discountOption.value = String(line.discount_id);
                        } else if (line.discount_amount && parseFloat(line.discount_amount) > 0) {
                            discountOption.value = 'manual';
                            row.querySelector('.line-discount-type').value = line.discount_type || 'amount';
                            row.querySelector('.line-discount-value').value = line.discount_value ?? 0;
                        } else {
                            discountOption.value = 'none';
                        }
                        applySelectedDiscount(row);

                        const taxOption = row.querySelector('.line-tax-option');
                        const taxOptionValues = Array.from(taxOption.options).map((o) => o.value);
                        if (line.tax_id && taxOptionValues.includes(String(line.tax_id))) {
                            taxOption.value = String(line.tax_id);
                        } else if ((line.tax_amount && parseFloat(line.tax_amount) > 0) ||
                            (line.tax_rate && parseFloat(line.tax_rate) > 0)) {
                            taxOption.value = 'manual';
                            row.querySelector('.line-tax-rate').value = line.tax_rate ?? 0;
                            row.querySelector('.line-tax-mode').value = line.tax_mode || 'exclusive';
                        } else {
                            taxOption.value = 'none';
                        }
                        applySelectedTax(row);
                    });

                    refreshLineTotals();
                }
            @endif
        });
    </script>
@endpush
