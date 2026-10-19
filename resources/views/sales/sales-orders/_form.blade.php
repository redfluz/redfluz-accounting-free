@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $existingLines = $salesOrder?->lines ?? collect();
    $canQuickCreateCompany = auth()->user()->hasFilePermission(FileResources::SALES_ORDERS, FileAction::ADD);
@endphp

<ul class="sales-document-tabs nav nav-tabs" id="sales-order-tabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-sales-order-tab="general" type="button">
            {{ __('sales_orders.tabs.general') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-sales-order-tab="attachment" type="button">
            {{ __('sales_orders.tabs.attachment') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link disabled" type="button" disabled>{{ __('sales_orders.tabs.payment') }}</button>
    </li>
    <li class="nav-item">
        <button class="nav-link disabled" type="button" disabled>{{ __('sales_orders.tabs.shipping') }}</button>
    </li>
</ul>

<div class="sales-tab-workspace">
    <section class="sales-tab-panel sales-tab-panel-active" data-sales-order-section="general">
        <div class="row g-3 mb-4">
            <div class="md:col-6">
                <label class="form-label" for="company_id">
                    {{ __('sales_orders.form.company') }} <span class="fg-danger">*</span>
                </label>
                <div class="input-group">
                    <select class="form-control" id="company_id" name="company_id" required>
                        <option value="">{{ __('common.actions.select_option') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected(old('company_id', $salesOrder?->company_id) === $company->id)>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($canQuickCreateCompany)
                        <button class="btn btn-solid theme-success" id="show-quick-company" type="button">
                            <i class="me-1" data-lucide="plus"></i>{{ __('sales_orders.actions.quick_company') }}
                        </button>
                    @endif
                </div>
                @if ($canQuickCreateCompany)
                    <div class="d-none mt-2" id="quick-company-panel">
                        <div class="input-group">
                            <label class="form-control form-adorn">
                                <input class="form-ghost" id="quick-company-name" maxlength="255"
                                    placeholder="{{ __('sales_orders.form.quick_company_name') }}" type="text" />
                            </label>
                            <button class="btn btn-solid theme-success" id="save-quick-company" type="button">
                                <i class="me-1" data-lucide="plus"></i>{{ __('sales_orders.actions.add_company') }}
                            </button>
                        </div>
                        <div class="invalid-feedback d-block" id="quick-company-error"></div>
                    </div>
                @endif
            </div>
            <div class="md:col-6">
                <label class="form-label" for="contact_id">{{ __('sales_orders.form.contact') }}</label><select
                    class="form-control" id="contact_id" name="contact_id">
                    <option value="">{{ __('common.actions.select_option') }}</option>
                    @foreach ($contacts as $contact)
                        <option data-company-id="{{ $contact->company_id }}" value="{{ $contact->id }}"
                            @selected(old('contact_id', $salesOrder?->contact_id) === $contact->id)>
                            {{ $contact->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-4">
                <label class="form-label"
                    for="customer_reference_number">{{ __('sales_orders.form.customer_reference') }}</label><label
                    class="form-control form-adorn">
                    <input
                        class="form-ghost" id="customer_reference_number" name="customer_reference_number"
                        value="{{ old('customer_reference_number', $salesOrder?->customer_reference_number) }}" />
                </label>
            </div>
            <div class="md:col-4">
                <label class="form-label" for="order_date">{{ __('sales_orders.form.order_date') }}</label><label
                    class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="calendar"></i>
                    </div>
                    <input
                        class="form-ghost" id="order_date" name="order_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                        value="{{ old('order_date', $salesOrder?->order_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        required />
                </label>
            </div>
            <div class="md:col-4">
                <label class="form-label"
                    for="expected_delivery_date">{{ __('sales_orders.form.expected_delivery_date') }}</label><label
                    class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="calendar"></i>
                    </div>
                    <input
                        class="form-ghost" id="expected_delivery_date" name="expected_delivery_date" type="text"
                        data-bs-toggle="datepicker" autocomplete="off"
                        value="{{ old('expected_delivery_date', $salesOrder?->expected_delivery_date?->format('Y-m-d')) }}" />
                </label>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">{{ __('sales_orders.form.notes') }}</label>
                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $salesOrder?->notes) }}</textarea>
            </div>
        </div>
    </section>

    <section class="sales-tab-panel" data-sales-order-section="attachment">
        <div class="mb-4">
            <label class="form-label" for="attachment">{{ __('sales_orders.form.attachment') }}</label><input
                class="form-control" id="attachment" name="attachment" type="file"
                accept="{{ \App\Enums\UploadType::DOCUMENT_AND_IMAGES->accept() }}" />
            @if ($salesOrder?->attachment_path)
                <div class="form-text">{{ basename($salesOrder->attachment_path) }}</div>
            @endif
        </div>
    </section>
</div>

<div id="sales-order-lines-section">
    <div class="table-responsive mb-4 rounded border">
        <table class="table-striped mb-0 table align-middle" id="sales-order-lines">
            <thead class="bg-1">
                <tr>
                    <th>{{ __('sales_orders.form.product') }}</th>
                    <th>{{ __('sales_orders.form.description') }}</th>
                    <th>{{ __('sales_orders.form.quantity') }}</th>
                    <th>{{ __('sales_orders.form.unit_price') }}</th>
                    <th>{{ __('sales_orders.form.warranty') }}</th>
                    <th>{{ __('products.form.default_discount') }}</th>
                    <th>{{ __('products.form.default_tax') }}</th>
                    <th>{{ __('sales_orders.form.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @for ($index = 0; $index < 20; $index++)
                    @php($line = $existingLines->get($index))
                    <tr>
                        <td style="min-width: 220px">
                            <select class="form-control so-product" name="lines[{{ $index }}][product_id]">
                                <option value="">{{ __('common.actions.select_option') }}</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}"
                                        data-description="{{ $product->description }}"
                                        data-default-discount-id="{{ $product->default_discount_id }}"
                                        data-default-tax-id="{{ $product->default_tax_id }}"
                                        data-warranty-months="{{ $product->warranty_months ?? -1 }}"
                                        @selected(old("lines.$index.product_id", $line?->product_id) === $product->id)>
                                        {{ $product->sku }} - {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td style="min-width: 180px">
                            <label class="form-control form-adorn">
                                <input class="form-ghost so-description" name="lines[{{ $index }}][description]"
                                    value="{{ old("lines.$index.description", $line?->description) }}" />
                            </label>
                        </td>
                        <td style="min-width: 115px">
                            <label class="form-control form-adorn">
                                <input class="form-ghost so-quantity" name="lines[{{ $index }}][quantity]"
                                    type="number" min="0.0001" step="0.0001"
                                    value="{{ old("lines.$index.quantity", $line?->ordered_quantity) }}" />
                            </label>
                        </td>
                        <td style="min-width: 125px">
                            <label class="form-control form-adorn">
                                <input class="form-ghost so-price" name="lines[{{ $index }}][unit_price]"
                                    type="number" min="0" step="0.01"
                                    value="{{ old("lines.$index.unit_price", $line?->unit_price) }}" />
                            </label>
                        </td>
                        <td style="min-width: 150px">
                            <select class="form-control so-warranty"
                                name="lines[{{ $index }}][warranty_months]">
                                @foreach ([-1, 0, 3, 6, 12, 24, 36] as $months)
                                    <option value="{{ $months }}" @selected((int) old("lines.$index.warranty_months", $line?->warranty_months ?? -1) === $months)>
                                        {{ $months === -1 ? __('products.warranty.unlimited') : ($months === 0 ? __('products.warranty.none') : __('products.warranty.months', ['count' => $months])) }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td style="min-width: 210px">
                            <select class="form-control so-discount"
                                name="lines[{{ $index }}][discount_option]">
                                <option value="none" data-type="percent" data-value="0">
                                    {{ __('discounts.form.none') }}
                                </option>
                                <option value="manual" data-type="percent" data-value="0">
                                    {{ __('discounts.form.manual') }}
                                </option>
                                @foreach ($discounts as $discount)
                                    <option value="{{ $discount->id }}" data-type="{{ $discount->type->value }}"
                                        data-value="{{ $discount->value }}" @selected(old("lines.$index.discount_option", $line?->discount_id) === $discount->id)>
                                        {{ $discount->code }} - {{ $discount->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group input-group-sm mt-1">
                                <select class="form-control so-discount-type"
                                    name="lines[{{ $index }}][discount_type]">
                                    <option value="percent">%</option>
                                    <option value="amount">RM</option>
                                </select><label class="form-control form-adorn">
                                    <input class="form-ghost so-discount-value"
                                        name="lines[{{ $index }}][discount_value]" type="number" min="0"
                                        step="0.0001"
                                        value="{{ old("lines.$index.discount_value", $line?->discount_value ?? 0) }}" />
                                </label>
                            </div>
                        </td>
                        <td style="min-width: 210px">
                            <select class="form-control so-tax" name="lines[{{ $index }}][tax_option]">
                                <option value="none" data-rate="0" data-mode="exclusive">
                                    {{ __('taxes.form.none') }}
                                </option>
                                <option value="manual" data-rate="0" data-mode="exclusive">
                                    {{ __('taxes.form.manual') }}
                                </option>
                                @foreach ($taxes as $tax)
                                    <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}"
                                        data-mode="{{ $tax->default_mode->value }}" @selected(old("lines.$index.tax_option", $line?->tax_id) === $tax->id)>
                                        {{ $tax->code }} - {{ $tax->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group input-group-sm mt-1">
                                <label class="form-control form-adorn">
                                    <input class="form-ghost so-tax-rate" name="lines[{{ $index }}][tax_rate]"
                                        type="number" min="0" max="100" step="0.0001"
                                        value="{{ old("lines.$index.tax_rate", $line?->tax_rate ?? 0) }}" /></label><select
                                    class="form-control so-tax-mode" name="lines[{{ $index }}][tax_mode]">
                                    <option value="exclusive">{{ __('ledgers.tax_mode.exclusive') }}</option>
                                    <option value="inclusive">{{ __('ledgers.tax_mode.inclusive') }}</option>
                                </select>
                            </div>
                        </td>
                        <td class="so-line-total fw-bold text-end">
                            {{ number_format((float) ($line?->total_amount ?? 0), 2) }}
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between">
    <a class="btn btn-solid theme-secondary" href="{{ route('sales.sales-orders.index') }}">
        <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
    </a>
    <button class="btn btn-solid theme-accent" type="submit">
        <i class="me-1"
            data-lucide="save"></i>{{ $salesOrder === null ? __('common.actions.save') : __('common.actions.update') }}
    </button>
</div>

@push('scripts')
    <script>
        let salesOrderInvalidTabActivated = false;
        const salesOrderForm = document.querySelector('[data-sales-order-section]')?.closest('form');
        const companySelect = document.getElementById('company_id');
        const contactSelect = document.getElementById('contact_id');
        const selectedContactId = @json(old('contact_id', $salesOrder?->contact_id));

        function filterSalesOrderContacts() {
            const companyId = companySelect.value;
            Array.from(contactSelect.options).forEach((option) => {
                if (!option.value) return;
                option.hidden = option.dataset.companyId !== companyId;
            });
            if (contactSelect.selectedOptions[0]?.hidden) {
                contactSelect.value = '';
            }
        }

        companySelect.addEventListener('change', () => {
            filterSalesOrderContacts();
        });
        filterSalesOrderContacts();
        if (selectedContactId) {
            contactSelect.value = selectedContactId;
        }

        const quickCompanyButton = document.getElementById('show-quick-company');
        const quickCompanyPanel = document.getElementById('quick-company-panel');
        const quickCompanyName = document.getElementById('quick-company-name');
        const quickCompanySave = document.getElementById('save-quick-company');
        const quickCompanyError = document.getElementById('quick-company-error');

        quickCompanyButton?.addEventListener('click', () => {
            quickCompanyPanel.classList.toggle('d-none');
            if (!quickCompanyPanel.classList.contains('d-none')) quickCompanyName.focus();
        });
        quickCompanySave?.addEventListener('click', async () => {
            const name = quickCompanyName.value.trim();
            quickCompanyError.textContent = '';
            if (!name) {
                quickCompanyError.textContent = @json(__('sales_orders.validation.company_name_required'));
                return;
            }

            quickCompanySave.disabled = true;
            try {
                const response = await fetch(@json(route('sales.sales-orders.companies.store')), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        name
                    }),
                });
                const result = await response.json();
                if (!response.ok) {
                    quickCompanyError.textContent = result.errors?.name?.[0] || result.message;
                    return;
                }

                companySelect.add(new Option(result.name, result.id, true, true));
                contactSelect.value = '';
                filterSalesOrderContacts();
                quickCompanyName.value = '';
                quickCompanyPanel.classList.add('d-none');
            } catch (error) {
                quickCompanyError.textContent = @json(__('sales_orders.validation.company_create_failed'));
            } finally {
                quickCompanySave.disabled = false;
            }
        });
        salesOrderForm?.addEventListener(
            'invalid',
            (event) => {
                if (salesOrderInvalidTabActivated) return;

                const section = event.target.closest('[data-sales-order-section]');
                const sectionName =
                    section?.dataset.salesOrderSection ??
                    (event.target.closest('#sales-order-lines-section') ? 'general' : null);
                const tab = sectionName ? document.querySelector(`[data-sales-order-tab="${sectionName}"]`) : null;
                if (tab) {
                    salesOrderInvalidTabActivated = true;
                    tab.click();
                    setTimeout(() => (salesOrderInvalidTabActivated = false), 0);
                }
            },
            true,
        );

        document.querySelectorAll('[data-sales-order-tab]').forEach((tab) =>
            tab.addEventListener('click', () => {
                document
                    .querySelectorAll('[data-sales-order-tab]')
                    .forEach((item) => item.classList.toggle('active', item === tab));
                document
                    .querySelectorAll('[data-sales-order-section]')
                    .forEach((section) =>
                        section.classList.toggle(
                            'sales-tab-panel-active',
                            section.dataset.salesOrderSection === tab.dataset.salesOrderTab,
                        ),
                    );
            }),
        );

        function refreshSalesOrderProductSelects() {
            if (typeof window.initCombobox !== 'function') {
                return;
            }

            document.querySelectorAll('.so-product').forEach((element) => {
                window.initCombobox(element, {
                    placeholder: @json(__('common.actions.select_option')),
                });
            });
        }

        function updateSalesOrderLine(row) {
            const subtotal =
                (Number(row.querySelector('.so-quantity').value) || 0) * (Number(row.querySelector('.so-price').value) ||
                    0);
            const discountType = row.querySelector('.so-discount-type').value;
            const discountValue = Number(row.querySelector('.so-discount-value').value) || 0;
            const discount = discountType === 'percent' ? (subtotal * discountValue) / 100 : Math.min(subtotal,
                discountValue);
            const net = subtotal - discount;
            const taxRate = Number(row.querySelector('.so-tax-rate').value) || 0;
            const taxMode = row.querySelector('.so-tax-mode').value;
            const tax = taxMode === 'inclusive' ? (net * taxRate) / (100 + taxRate) : (net * taxRate) / 100;
            row.querySelector('.so-line-total').textContent = (taxMode === 'inclusive' ? net : net + tax).toFixed(2);
        }
        const initializeSalesOrderProducts = () => {
            refreshSalesOrderProductSelects();

            document.querySelectorAll('#sales-order-lines tbody tr').forEach((row) => {
                const product = row.querySelector('.so-product');
                const discount = row.querySelector('.so-discount');
                const tax = row.querySelector('.so-tax');

                function applyDiscount() {
                    const option = discount.selectedOptions[0];
                    const manual = discount.value === 'manual';
                    row.querySelector('.so-discount-type').disabled = !manual;
                    row.querySelector('.so-discount-value').readOnly = !manual;
                    if (!manual) {
                        row.querySelector('.so-discount-type').value = option.dataset.type || 'percent';
                        row.querySelector('.so-discount-value').value = option.dataset.value || '0';
                    }
                    updateSalesOrderLine(row);
                }

                function applyTax() {
                    const option = tax.selectedOptions[0];
                    const manual = tax.value === 'manual';
                    row.querySelector('.so-tax-rate').readOnly = !manual;
                    if (!manual) {
                        row.querySelector('.so-tax-rate').value = option.dataset.rate || '0';
                        row.querySelector('.so-tax-mode').value = option.dataset.mode || 'exclusive';
                    }
                    updateSalesOrderLine(row);
                }
                product.addEventListener('change', () => {
                    const option = product.selectedOptions[0];
                    if (!option?.value) return;
                    row.querySelector('.so-price').value = option.dataset.price || '0';
                    row.querySelector('.so-description').value ||= option.dataset.description || '';
                    row.querySelector('.so-quantity').value ||= '1';
                    row.querySelector('.so-warranty').value = option.dataset.warrantyMonths || '-1';
                    discount.value = option.dataset.defaultDiscountId || 'none';
                    tax.value = option.dataset.defaultTaxId || 'none';
                    applyDiscount();
                    applyTax();
                });
                discount.addEventListener('change', applyDiscount);
                tax.addEventListener('change', applyTax);
                row.querySelectorAll('input, select').forEach((input) =>
                    input.addEventListener('input', () => updateSalesOrderLine(row)),
                );
                applyDiscount();
                applyTax();
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeSalesOrderProducts);
        } else {
            initializeSalesOrderProducts();
        }
    </script>
@endpush

```
