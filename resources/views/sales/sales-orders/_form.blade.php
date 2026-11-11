@php
    use App\Enums\UploadType;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $existingLines = $salesOrder?->lines ?? collect();
    $canQuickCreateCompany = auth()->user()->hasFilePermission(FileResources::SALES_ORDERS, FileAction::ADD);
@endphp

<ul class="sales-document-tabs nav nav-tabs" id="sales-order-tabs">
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
                            <div class="form-control form-adorn">
                                <input class="form-ghost" id="quick-company-name" maxlength="255"
                                    placeholder="{{ __('sales_orders.form.quick_company_name') }}" type="text" />
                            </div>
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
                    for="customer_reference_number">{{ __('sales_orders.form.customer_reference') }}</label>
                <div
                    class="form-control form-adorn">
                    <input
                        class="form-ghost" id="customer_reference_number" name="customer_reference_number"
                        value="{{ old('customer_reference_number', $salesOrder?->customer_reference_number) }}" />
                </div>
            </div>
            <div class="md:col-4">
                <label class="form-label" for="order_date">{{ __('sales_orders.form.order_date') }}</label>
                <div
                    class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="calendar"></i>
                    </div>
                    <input
                        class="form-ghost" id="order_date" name="order_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                        value="{{ old('order_date', $salesOrder?->order_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        required />
                </div>
            </div>
            <div class="md:col-4">
                <label class="form-label"
                    for="expected_delivery_date">{{ __('sales_orders.form.expected_delivery_date') }}</label>
                <div
                    class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="calendar"></i>
                    </div>
                    <input
                        class="form-ghost" id="expected_delivery_date" name="expected_delivery_date" type="text"
                        data-bs-toggle="datepicker" autocomplete="off"
                        value="{{ old('expected_delivery_date', $salesOrder?->expected_delivery_date?->format('Y-m-d')) }}" />
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">{{ __('sales_orders.form.notes') }}</label>
                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $salesOrder?->notes) }}</textarea>
            </div>
        </div>
    </section>

    <section class="sales-tab-panel" data-sales-order-section="attachment">
        <div class="mb-4">
            <label class="form-label" for="attachment">{{ __('sales_orders.form.attachment') }}</label>
            @if ($salesOrder?->attachment_path)
                @php($attachmentExtension = strtolower(pathinfo($salesOrder->attachment_path, PATHINFO_EXTENSION)))
                <div class="mb-2">
                    <div class="form-text mb-1">{{ __('sales_orders.form.current_attachment') }}</div>
                    @if (in_array($attachmentExtension, ['jpg', 'jpeg', 'png', 'webp'], true))
                        <a href="{{ route('sales.sales-orders.attachment', $salesOrder) }}" target="_blank">
                            <img class="img-thumbnail rounded-3 border"
                                src="{{ route('sales.sales-orders.attachment', $salesOrder) }}"
                                alt="{{ basename($salesOrder->attachment_path) }}" style="max-height: 140px" />
                        </a>
                    @else
                        <a class="btn btn-sm btn-solid theme-secondary"
                            href="{{ route('sales.sales-orders.attachment', $salesOrder) }}" target="_blank">
                            <i class="me-1" data-lucide="paperclip"></i>{{ basename($salesOrder->attachment_path) }}
                        </a>
                    @endif
                </div>
            @endif
            <input class="form-control" id="attachment" name="attachment" type="file"
                accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}"
                onchange="previewSalesOrderAttachment(event)" />
            <div class="form-text">{{ __('sales_orders.form.attachment_size_hint') }}</div>
            <div class="invalid-feedback d-none" id="attachment-size-error"
                data-too-large="{{ __('sales_orders.form.attachment_too_large') }}"></div>
            <div class="d-none mt-2" id="attachment-preview-container">
                <div class="form-text mb-1">{{ __('sales_orders.form.new_attachment_preview') }}</div>
                <div id="attachment-preview-box"></div>
            </div>
        </div>
    </section>
</div>

<div id="sales-order-lines-section">
    <div class="table-responsive mb-4 rounded border">
        <table class="table-striped mb-0 table align-middle" id="sales-order-lines">
            <thead class="bg-1">
                <tr>
                    <th scope="col">{{ __('sales_orders.form.product') }}</th>
                    <th scope="col">{{ __('sales_orders.form.description') }}</th>
                    <th scope="col">{{ __('sales_orders.form.quantity') }}</th>
                    <th scope="col">{{ __('sales_orders.form.unit_price') }}</th>
                    <th scope="col">{{ __('sales_orders.form.warranty') }}</th>
                    <th scope="col">{{ __('products.form.default_discount') }}</th>
                    <th scope="col">{{ __('products.form.default_tax') }}</th>
                    <th scope="col">{{ __('sales_orders.form.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @for ($index = 0; $index < $perPageGridDetail; $index++)
                    @php($line = $existingLines->get($index))
                    <tr data-line-index="{{ $index }}">
                        <td style="min-width: 220px">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="lines[{{ $index }}][product_id]"
                                    class="so-product-id"
                                    value="{{ old("lines.$index.product_id", $line?->product_id) }}">
                                <div
                                    class="so-product-display form-control d-flex align-items-center justify-content-between">
                                    <span
                                        class="so-product-label text-truncate">{{ old("lines.$index.product_id", $line?->product_id) ? $products->firstWhere('id', old("lines.$index.product_id", $line?->product_id))?->sku . ' - ' . $products->firstWhere('id', old("lines.$index.product_id", $line?->product_id))?->name : __('sales_orders.form.no_product_selected') }}</span>
                                </div>
                                <button class="btn btn-solid theme-primary so-product-choose-btn flex-shrink-0"
                                    type="button" data-line-index="{{ $index }}">
                                    <i data-lucide="search"></i>
                                </button>
                            </div>
                        </td>
                        <td style="min-width: 180px">
                            <div class="form-control form-adorn">
                                <label class="visually-hidden"
                                    for="line-{{ $index }}-description">{{ __('sales_orders.form.description') }}</label>
                                <input id="line-{{ $index }}-description" class="form-ghost so-description"
                                    name="lines[{{ $index }}][description]"
                                    value="{{ old("lines.$index.description", $line?->description) }}" />
                            </div>
                        </td>
                        <td style="min-width: 115px">
                            <div class="form-control form-adorn">
                                <label class="visually-hidden"
                                    for="line-{{ $index }}-quantity">{{ __('sales_orders.form.quantity') }}</label>
                                <input id="line-{{ $index }}-quantity" class="form-ghost so-quantity"
                                    name="lines[{{ $index }}][quantity]" type="number" min="0.0001" step="0.0001"
                                    value="{{ old("lines.$index.quantity", $line?->ordered_quantity) }}" />
                            </div>
                        </td>
                        <td style="min-width: 125px">
                            <div class="form-control form-adorn">
                                <label class="visually-hidden"
                                    for="line-{{ $index }}-unit_price">{{ __('sales_orders.form.unit_price') }}</label>
                                <input id="line-{{ $index }}-unit_price" class="form-ghost so-price"
                                    name="lines[{{ $index }}][unit_price]" type="number" min="0" step="0.01"
                                    value="{{ old("lines.$index.unit_price", $line?->unit_price) }}" />
                            </div>
                        </td>
                        <td style="min-width: 150px">
                            <label class="visually-hidden"
                                for="line-{{ $index }}-warranty_months">{{ __('sales_orders.form.warranty') }}</label>
                            <select id="line-{{ $index }}-warranty_months" class="form-control so-warranty"
                                name="lines[{{ $index }}][warranty_months]">
                                @foreach ([-1, 0, 3, 6, 12, 24, 36] as $months)
                                    <option value="{{ $months }}" @selected((int) old("lines.$index.warranty_months", $line?->warranty_months ?? -1) === $months)>
                                        {{ $months === -1 ? __('products.warranty.unlimited') : ($months === 0 ? __('products.warranty.none') : __('products.warranty.months', ['count' => $months])) }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td style="min-width: 210px">
                            <label class="visually-hidden"
                                for="line-{{ $index }}-discount_option">{{ __('products.form.default_discount') }}</label>
                            <select id="line-{{ $index }}-discount_option" class="form-control so-discount"
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
                                <label class="visually-hidden"
                                    for="line-{{ $index }}-discount_type">{{ __('products.form.default_discount') }} {{ __('ledgers.table.column_type') }}</label>
                                <select id="line-{{ $index }}-discount_type" class="form-control so-discount-type"
                                    name="lines[{{ $index }}][discount_type]">
                                    <option value="percent">%</option>
                                    <option value="amount">RM</option>
                                </select>
                                <div class="form-control form-adorn">
                                    <label class="visually-hidden"
                                        for="line-{{ $index }}-discount_value">{{ __('products.form.default_discount') }} {{ __('ledgers.table.column_amount') }}</label>
                                    <input id="line-{{ $index }}-discount_value" class="form-ghost so-discount-value"
                                        name="lines[{{ $index }}][discount_value]" type="number" min="0"
                                        step="0.0001"
                                        value="{{ old("lines.$index.discount_value", $line?->discount_value ?? 0) }}" />
                                </div>
                            </div>
                        </td>
                        <td style="min-width: 210px">
                            <label class="visually-hidden"
                                for="line-{{ $index }}-tax_option">{{ __('products.form.default_tax') }}</label>
                            <select id="line-{{ $index }}-tax_option" class="form-control so-tax"
                                name="lines[{{ $index }}][tax_option]">
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
                                <div class="form-control form-adorn">
                                    <label class="visually-hidden"
                                        for="line-{{ $index }}-tax_rate">{{ __('ledgers.table.column_tax_rate') }}</label>
                                    <input id="line-{{ $index }}-tax_rate" class="form-ghost so-tax-rate"
                                        name="lines[{{ $index }}][tax_rate]" type="number" min="0" max="100"
                                        step="0.0001"
                                        value="{{ old("lines.$index.tax_rate", $line?->tax_rate ?? 0) }}" />
                                </div>
                                <label class="visually-hidden"
                                    for="line-{{ $index }}-tax_mode">{{ __('products.form.default_tax') }} {{ __('ledgers.table.column_type') }}</label>
                                <select
                                    id="line-{{ $index }}-tax_mode" class="form-control so-tax-mode"
                                    name="lines[{{ $index }}][tax_mode]">
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

{{-- Product Selection Dialog --}}
<dialog class="dialog" id="salesOrderProductPickerModal" aria-labelledby="salesOrderProductPickerModalLabel"
    style="width: 90vw; max-width: 1200px; height: 85vh; max-height: 800px; padding: 0;">
    <div class="dialog-header" style="padding: 1.5rem 2rem;">
        <h4 class="dialog-title mb-0" id="salesOrderProductPickerModalLabel">
            {{ __('sales_orders.form.select_product') }}</h4>
        <button class="btn-close" data-bs-dismiss="dialog" type="button"></button>
    </div>
    <div class="dialog-body d-flex flex-column" style="padding: 1.5rem 2rem; min-height: 0;">
        <div class="row g-3 mb-4 flex-shrink-0">
            <div class="md:col-6">
                <label class="visually-hidden" for="salesOrderProductSearchInput">Search Products</label><input
                    type="text" class="form-control form-control-lg" id="salesOrderProductSearchInput"
                    placeholder="{{ __('sales_orders.form.search_products') }}" style="font-size: 1.1rem;"
                >
            </div>
            <div class="md:col-3">
                <label class="visually-hidden" for="salesOrderProductCategoryFilter">Product Category</label><select
                    class="form-control form-control-lg" id="salesOrderProductCategoryFilter"
                    style="font-size: 1.1rem;">
                    <option value="">{{ __('sales_orders.form.all_categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-3">
                <label class="visually-hidden" for="salesOrderProductTypeFilter">Product Type</label><select
                    class="form-control form-control-lg" id="salesOrderProductTypeFilter" style="font-size: 1.1rem;">
                    <option value="">{{ __('sales_orders.form.all_types') }}</option>
                    @foreach ($categories as $category)
                        @foreach ($category->types as $type)
                            <option value="{{ $type->id }}" data-category-id="{{ $category->id }}">
                                {{ $type->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive flex-fill" style="overflow-y: auto; min-height: 0;">
            <table class="table table-hover" style="font-size: 1.05rem;">
                <thead class="sticky-top bg-body" style="font-size: 1.1rem;">
                    <tr>
                        <th scope="col" style="padding: 1rem;">{{ __('sales_orders.form.sku') }}</th>
                        <th scope="col" style="padding: 1rem;">{{ __('sales_orders.form.product_name') }}</th>
                        <th scope="col" style="padding: 1rem;">{{ __('sales_orders.form.category') }}</th>
                        <th scope="col" class="text-end" style="padding: 1rem;">
                            {{ __('sales_orders.form.price') }}</th>
                    </tr>
                </thead>
                <tbody id="salesOrderProductPickerResults">
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5" style="font-size: 1.1rem;">
                            {{ __('sales_orders.form.type_to_search') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="dialog-footer" style="padding: 1.5rem 2rem;">
        <button class="btn btn-solid theme-primary btn-lg" data-bs-dismiss="dialog" type="button"
            style="font-size: 1.1rem; padding: 0.75rem 2rem;">
            {{ __('sales_orders.form.close_information') }}
        </button>
    </div>
</dialog>

@push('scripts')
    <script>
        function previewSalesOrderAttachment(event) {
            const input = event.target;
            const errorBox = document.getElementById('attachment-size-error');
            const container = document.getElementById('attachment-preview-container');
            const previewBox = document.getElementById('attachment-preview-box');
            errorBox.classList.add('d-none');
            errorBox.textContent = '';

            if (!input.files || !input.files[0]) {
                container.classList.add('d-none');
                previewBox.replaceChildren();
                return;
            }

            const file = input.files[0];

            if (file.size > 5120 * 1024) {
                errorBox.textContent = errorBox.dataset.tooLarge;
                errorBox.classList.remove('d-none');
                input.value = '';
                container.classList.add('d-none');
                previewBox.replaceChildren();
                return;
            }

            container.classList.remove('d-none');

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.addEventListener('load', (loadEvent) => {
                    const image = document.createElement('img');
                    image.src = loadEvent.target.result;
                    image.className = 'img-thumbnail rounded-3 border';
                    image.style.maxHeight = '140px';
                    image.alt = file.name;
                    previewBox.replaceChildren(image);
                });
                reader.readAsDataURL(file);
            } else {
                const caption = document.createElement('div');
                caption.className = 'small';
                caption.textContent = file.name;
                previewBox.replaceChildren(caption);
            }
        }

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

        // Product Picker Modal - wait for bootstrap to be available
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.bootstrap) {
                console.error('Bootstrap is not loaded');
                return;
            }

            const salesOrderProductPickerModal = window.bootstrap.Dialog.getOrCreateInstance(document
                .getElementById('salesOrderProductPickerModal'));
            const salesOrderProductSearchInput = document.getElementById('salesOrderProductSearchInput');
            const salesOrderProductCategoryFilter = document.getElementById('salesOrderProductCategoryFilter');
            const salesOrderProductTypeFilter = document.getElementById('salesOrderProductTypeFilter');
            const salesOrderProductPickerResults = document.getElementById('salesOrderProductPickerResults');
            let salesOrderCurrentLineIndex = null;
            let salesOrderSearchTimeout = null;

            function filterSalesOrderProductTypes() {
                const categoryId = salesOrderProductCategoryFilter.value;

                Array.from(salesOrderProductTypeFilter.options).forEach((option) => {
                    if (!option.dataset.categoryId) return;
                    const belongsToCategory = option.dataset.categoryId === categoryId;
                    option.hidden = !belongsToCategory;
                    option.disabled = !belongsToCategory;
                });

                if (salesOrderProductTypeFilter.selectedOptions[0]?.hidden) {
                    salesOrderProductTypeFilter.value = '';
                }

                salesOrderProductTypeFilter.disabled = categoryId === '';
            }

            function searchSalesOrderProducts() {
                const query = salesOrderProductSearchInput.value;
                const categoryId = salesOrderProductCategoryFilter.value;
                const typeId = salesOrderProductTypeFilter.value;
                const noProductsText = @json(__('sales_orders.form.no_products_found'));
                const searchErrorText = @json(__('sales_orders.form.search_error'));

                if (salesOrderSearchTimeout) {
                    clearTimeout(salesOrderSearchTimeout);
                }

                salesOrderSearchTimeout = setTimeout(() => {
                    fetch(
                            `{{ route('sales.sales-orders.products.search') }}?search=${encodeURIComponent(query)}&category_id=${encodeURIComponent(categoryId)}&type_id=${encodeURIComponent(typeId)}`
                        )
                        .then(response => response.json())
                        .then(products => {
                            if (products.length === 0) {
                                salesOrderProductPickerResults.innerHTML =
                                    `<tr><td colspan="4" class="text-center text-muted py-5" style="font-size: 1.1rem;">${noProductsText}</td></tr>`;
                            } else {
                                salesOrderProductPickerResults.innerHTML = products.map(product => `
                            <tr class="sales-order-product-picker-row" data-product-id="${product.id}" data-product-sku="${escapeHtml(product.sku)}" data-product-name="${escapeHtml(product.name)}" data-product-price="${product.price}" data-product-description="${escapeHtml(product.description)}" style="cursor: pointer; font-size: 1.05rem;">
                                <td style="padding: 1rem;">${escapeHtml(product.sku)}</td>
                                <td style="padding: 1rem;">${escapeHtml(product.name)}</td>
                                <td style="padding: 1rem;">${escapeHtml(product.category)}</td>
                                <td class="text-end" style="padding: 1rem;">${quantity(product.price)}</td>
                            </tr>
                        `).join('');

                                salesOrderProductPickerResults.querySelectorAll(
                                    '.sales-order-product-picker-row').forEach(row => {
                                    row.addEventListener('click', () => {
                                        const productId = row.dataset.productId;
                                        const productSku = row.dataset.productSku;
                                        const productName = row.dataset.productName;
                                        const productPrice = row.dataset.productPrice;
                                        const productDescription = row.dataset
                                            .productDescription;

                                        const targetRow = document.querySelector(
                                            `tr[data-line-index="${salesOrderCurrentLineIndex}"]`
                                        );
                                        const productIdInput = targetRow.querySelector(
                                            '.so-product-id');
                                        const productLabel = targetRow.querySelector(
                                            '.so-product-label');
                                        const descriptionInput = targetRow
                                            .querySelector('.so-description');
                                        const priceInput = targetRow.querySelector(
                                            '.so-price');
                                        const quantityInput = targetRow.querySelector(
                                            '.so-quantity');

                                        productIdInput.value = productId;
                                        productLabel.textContent =
                                            `${productSku} - ${productName}`;
                                        descriptionInput.value ||= productDescription;
                                        quantityInput.value ||= '1';
                                        priceInput.value ||= productPrice;

                                        updateSalesOrderLine(targetRow);
                                        salesOrderProductPickerModal.hide();
                                    });
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error searching products:', error);
                            salesOrderProductPickerResults.innerHTML =
                                `<tr><td colspan="4" class="text-center text-danger py-5" style="font-size: 1.1rem;">${searchErrorText}</td></tr>`;
                        });
                }, 300);
            }

            const quantity = (value) =>
                Number(value || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            const escapeHtml = (value) =>
                String(value).replace(
                    /[&<>'"]/g,
                    (character) =>
                    ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        "'": '&#039;',
                        '"': '&quot;',
                    })[character],
                );

            function updateSalesOrderLine(row) {
                const subtotal =
                    (Number(row.querySelector('.so-quantity').value) || 0) * (Number(row.querySelector('.so-price')
                            .value) ||
                        0);
                const discountType = row.querySelector('.so-discount-type').value;
                const discountValue = Number(row.querySelector('.so-discount-value').value) || 0;
                const discount = discountType === 'percent' ? (subtotal * discountValue) / 100 : Math.min(subtotal,
                    discountValue);
                const net = subtotal - discount;
                const taxRate = Number(row.querySelector('.so-tax-rate').value) || 0;
                const taxMode = row.querySelector('.so-tax-mode').value;
                const tax = taxMode === 'inclusive' ? (net * taxRate) / (100 + taxRate) : (net * taxRate) / 100;
                row.querySelector('.so-line-total').textContent = (taxMode === 'inclusive' ? net : net + tax)
                    .toFixed(2);
            }

            const initializeSalesOrderProducts = () => {
                document.querySelectorAll('#sales-order-lines tbody tr').forEach((row) => {
                    const chooseBtn = row.querySelector('.so-product-choose-btn');
                    const discount = row.querySelector('.so-discount');
                    const tax = row.querySelector('.so-tax');

                    function applyDiscount() {
                        const option = discount.selectedOptions[0];
                        const manual = discount.value === 'manual';
                        row.querySelector('.so-discount-type').disabled = !manual;
                        row.querySelector('.so-discount-value').readOnly = !manual;
                        if (!manual) {
                            row.querySelector('.so-discount-type').value = option.dataset.type ||
                                'percent';
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
                            row.querySelector('.so-tax-mode').value = option.dataset.mode ||
                                'exclusive';
                        }
                        updateSalesOrderLine(row);
                    }

                    chooseBtn?.addEventListener('click', (e) => {
                        salesOrderCurrentLineIndex = e.currentTarget.dataset.lineIndex;
                        salesOrderProductSearchInput.value = '';
                        salesOrderProductCategoryFilter.value = '';
                        salesOrderProductTypeFilter.value = '';
                        filterSalesOrderProductTypes();
                        const typeToSearchText = @json(__('sales_orders.form.type_to_search'));
                        salesOrderProductPickerResults.innerHTML =
                            `<tr><td colspan="4" class="text-center text-muted py-5" style="font-size: 1.1rem;">${typeToSearchText}</td></tr>`;
                        salesOrderProductPickerModal.show();
                        setTimeout(() => salesOrderProductSearchInput.focus(), 500);
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

            salesOrderProductSearchInput.addEventListener('input', searchSalesOrderProducts);
            salesOrderProductCategoryFilter.addEventListener('change', () => {
                filterSalesOrderProductTypes();
                searchSalesOrderProducts();
            });
            salesOrderProductTypeFilter.addEventListener('change', searchSalesOrderProducts);
            filterSalesOrderProductTypes();

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeSalesOrderProducts);
            } else {
                initializeSalesOrderProducts();
            }
        });
    </script>
@endpush
