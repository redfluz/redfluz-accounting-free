@php
    use App\Enums\Sales\QuotationStatus;
    use App\Enums\UploadType;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $quotation = $quotation ?? null;
    $quotationLines = $lines ?? collect();
    $canQuickCreateCompany = auth()->user()->hasFilePermission(FileResources::QUOTATIONS, FileAction::ADD);
@endphp

<ul class="sales-document-tabs nav nav-tabs" id="quotation-tabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-quotation-tab="general" type="button">
            {{ __('quotations.tabs.general') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-quotation-tab="attachment" type="button">
            {{ __('quotations.tabs.attachment') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link disabled" type="button" disabled>{{ __('quotations.tabs.payment') }}</button>
    </li>
    <li class="nav-item">
        <button class="nav-link disabled" type="button" disabled>{{ __('quotations.tabs.shipping') }}</button>
    </li>
</ul>

<div class="sales-tab-workspace">
    <section class="sales-tab-panel sales-tab-panel-active" data-quotation-section="general">
        <div class="row g-3 mb-4">
            <div class="md:col-6">
                <label class="form-label fw-bold" for="company_id">{{ __('quotations.form.company') }}</label
                ><select
                    class="form-control @error('company_id') is-invalid @enderror"
                    id="company_id"
                    name="company_id"
                    required
                >
                    <option value="">{{ __('common.actions.select_option') }}</option>
                    @foreach ($companies as $company)
                        <option
                            value="{{ $company->id }}"
                            @selected(old('company_id', $quotation?->company_id) === $company->id)
                        >
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
                @error('company_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if ($canQuickCreateCompany)
                    @include('partials.quick-company', [
                        'quickCompanyRoute' => route('sales.quotations.companies.store'),
                        'quickCompanyLabel' => __('quotations.actions.quick_company'),
                        'quickCompanyNamePlaceholder' => __('quotations.form.quick_company_name'),
                        'quickCompanySaveLabel' => __('quotations.actions.add_company'),
                        'quickCompanyRequiredMessage' => __('quotations.validation.company_name_required'),
                        'quickCompanyFailedMessage' => __('quotations.validation.company_create_failed'),
                    ])
                @endif
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold" for="contact_id">{{ __('quotations.form.contact') }}</label
                ><select
                    class="form-control @error('contact_id') is-invalid @enderror"
                    id="contact_id"
                    name="contact_id"
                >
                    <option value="">{{ __('common.actions.select_option') }}</option>
                    @foreach ($contacts as $contact)
                        <option
                            data-company-id="{{ $contact->company_id }}"
                            value="{{ $contact->id }}"
                            @selected(old('contact_id', $quotation?->contact_id) === $contact->id)
                        >
                            {{ $contact->name }}
                        </option>
                    @endforeach
                </select>
                @error('contact_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-4">
                <label
                    class="form-label fw-bold"
                    for="reference_number"
                    >{{ __('quotations.form.reference_number') }}</label
                ><div class="form-control form-adorn">
                    <input
                        class="form-ghost @error('reference_number') is-invalid @enderror"
                        id="reference_number"
                        name="reference_number"
                        value="{{ old('reference_number', $quotation?->reference_number) }}"
                        maxlength="255"
                    />
                </div>
                @error('reference_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-3">
                <label class="form-label fw-bold" for="date">{{ __('quotations.form.date') }}</label
                ><div class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="calendar"></i>
                    </div>
                    <input
                        class="form-ghost @error('date') is-invalid @enderror"
                        id="date"
                        name="date"
                        type="text" data-bs-toggle="datepicker" autocomplete="off"
                        value="{{ old('date', $quotation?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        required
                    />
                </div>
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-3">
                <label
                    class="form-label fw-bold"
                    for="expiry_date"
                    >{{ __('quotations.form.expiry_date') }}</label
                ><div class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="calendar"></i>
                    </div>
                    <input
                        class="form-ghost @error('expiry_date') is-invalid @enderror"
                        id="expiry_date"
                        name="expiry_date"
                        type="text" data-bs-toggle="datepicker" autocomplete="off"
                        value="{{ old('expiry_date', $quotation?->expiry_date?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}"
                    />
                </div>
                @error('expiry_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-2">
                <label class="form-label fw-bold" for="status">{{ __('quotations.form.status') }}</label
                ><select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach ($statuses as $status)
                        @if ($status !== QuotationStatus::CONVERTED)
                            <option
                                value="{{ $status->value }}"
                                @selected(old('status', $quotation?->status?->value ?? 'draft') === $status->value)
                            >
                                {{ __('quotations.status.'.$status->value) }}
                            </option>
                        @endif
                    @endforeach
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-bold" for="notes">{{ __('quotations.form.notes') }}</label
                ><textarea
                    class="form-control @error('notes') is-invalid @enderror"
                    id="notes"
                    name="notes"
                    rows="3"
                    >{{ old('notes', $quotation?->notes) }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>

    <section class="sales-tab-panel" data-quotation-section="attachment">
        <label class="form-label fw-bold" for="attachment">{{ __('quotations.form.attachment') }}</label
        ><input
            class="form-control @error('attachment') is-invalid @enderror"
            id="attachment"
            name="attachment"
            type="file"
            accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}"
        />
        @if ($quotation?->attachment_path)
            <div class="form-text">{{ basename($quotation->attachment_path) }}</div>
        @endif
        @error('attachment')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </section>
</div>

<div class="table-responsive mb-4 rounded border" id="quotation-lines-section">
    <table class="table-striped mb-0 table align-middle" id="quotation-lines">
        <thead class="bg-1">
            <tr>
                <th scope="col">{{ __('quotations.form.product') }}</th>
                <th scope="col">{{ __('quotations.form.description') }}</th>
                <th scope="col">{{ __('quotations.form.quantity') }}</th>
                <th scope="col">{{ __('quotations.form.unit_price') }}</th>
                <th scope="col" class="text-end">{{ __('quotations.form.total_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @for ($index = 0; $index < $perPageGridDetail; $index++)
                @php($line = $quotationLines->get($index))
                <tr data-line-index="{{ $index }}">
                    <td style="min-width: 220px">
                        <div class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="lines[{{ $index }}][product_id]"
                                class="quotation-product-id"
                                value="{{ old("lines.$index.product_id", $line?->product_id) }}">
                            <div
                                class="quotation-product-display form-control d-flex align-items-center justify-content-between">
                                <span
                                    class="quotation-product-label text-truncate">{{ old("lines.$index.product_id", $line?->product_id) ? $products->firstWhere('id', old("lines.$index.product_id", $line?->product_id))?->sku . ' - ' . $products->firstWhere('id', old("lines.$index.product_id", $line?->product_id))?->name : __('quotations.form.no_product_selected') }}</span>
                            </div>
                            <button class="btn btn-solid theme-primary quotation-product-choose-btn flex-shrink-0"
                                type="button" data-line-index="{{ $index }}">
                                <i data-lucide="search"></i>
                            </button>
                        </div>
                    </td>
                    <td style="min-width: 220px">
                        <div class="form-control form-adorn">
                            <label class="visually-hidden"
                                for="line-{{ $index }}-description">{{ __('quotations.form.description') }}</label>
                            <input
                                id="line-{{ $index }}-description"
                                class="form-ghost quotation-description"
                                name="lines[{{ $index }}][description]"
                                value="{{ old("lines.$index.description", $line?->description) }}"
                            />
                        </div>
                    </td>
                    <td style="min-width: 120px">
                        <div class="form-control form-adorn">
                            <label class="visually-hidden"
                                for="line-{{ $index }}-quantity">{{ __('quotations.form.quantity') }}</label>
                            <input
                                id="line-{{ $index }}-quantity"
                                class="form-ghost quotation-quantity"
                                name="lines[{{ $index }}][quantity]"
                                type="number"
                                min="0.0001"
                                step="0.0001"
                                value="{{ old("lines.$index.quantity", $line?->quantity) }}"
                            />
                        </div>
                    </td>
                    <td style="min-width: 140px">
                        <div class="form-control form-adorn">
                            <label class="visually-hidden"
                                for="line-{{ $index }}-unit_price">{{ __('quotations.form.unit_price') }}</label>
                            <input
                                id="line-{{ $index }}-unit_price"
                                class="form-ghost quotation-price"
                                name="lines[{{ $index }}][unit_price]"
                                type="number"
                                min="0"
                                step="0.01"
                                value="{{ old("lines.$index.unit_price", $line?->unit_price) }}"
                            />
                        </div>
                    </td>
                    <td class="quotation-line-total fw-bold text-end">
                        {{ number_format((float) ($line?->total_amount ?? 0), 2) }}
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-between">
    <a class="btn btn-solid theme-secondary" href="{{ route('sales.quotations.index') }}">
        <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
    </a>
    <button class="btn btn-solid theme-accent" type="submit">
        <i class="me-1" data-lucide="save"></i>
        {{ $quotation === null ? __('common.actions.save') : __('common.actions.update') }}
    </button>
</div>

{{-- Product Selection Dialog --}}
<dialog class="dialog" id="quotationProductPickerModal" aria-labelledby="quotationProductPickerModalLabel"
    style="width: 90vw; max-width: 1200px; height: 85vh; max-height: 800px; padding: 0;">
    <div class="dialog-header" style="padding: 1.5rem 2rem;">
        <h4 class="dialog-title mb-0" id="quotationProductPickerModalLabel">
            {{ __('quotations.form.select_product') }}</h4>
        <button class="btn-close" data-bs-dismiss="dialog" type="button"></button>
    </div>
    <div class="dialog-body d-flex flex-column" style="padding: 1.5rem 2rem; min-height: 0;">
        <div class="row g-3 mb-4 flex-shrink-0">
            <div class="md:col-6">
                <label class="visually-hidden" for="quotationProductSearchInput">Search Products</label><input
                    type="text" class="form-control form-control-lg" id="quotationProductSearchInput"
                    placeholder="{{ __('quotations.form.search_products') }}" style="font-size: 1.1rem;"
                >
            </div>
            <div class="md:col-3">
                <label class="visually-hidden" for="quotationProductCategoryFilter">Product Category</label><select
                    class="form-control form-control-lg" id="quotationProductCategoryFilter"
                    style="font-size: 1.1rem;">
                    <option value="">{{ __('quotations.form.all_categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-3">
                <label class="visually-hidden" for="quotationProductTypeFilter">Product Type</label><select
                    class="form-control form-control-lg" id="quotationProductTypeFilter" style="font-size: 1.1rem;">
                    <option value="">{{ __('quotations.form.all_types') }}</option>
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
                        <th scope="col" style="padding: 1rem;">{{ __('quotations.form.sku') }}</th>
                        <th scope="col" style="padding: 1rem;">{{ __('quotations.form.product_name') }}</th>
                        <th scope="col" style="padding: 1rem;">{{ __('quotations.form.category') }}</th>
                        <th scope="col" class="text-end" style="padding: 1rem;">{{ __('quotations.form.price') }}
                        </th>
                    </tr>
                </thead>
                <tbody id="quotationProductPickerResults">
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5" style="font-size: 1.1rem;">
                            {{ __('quotations.form.type_to_search') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="dialog-footer" style="padding: 1.5rem 2rem;">
        <button class="btn btn-solid theme-primary btn-lg" data-bs-dismiss="dialog" type="button"
            style="font-size: 1.1rem; padding: 0.75rem 2rem;">
            {{ __('quotations.form.close_information') }}
        </button>
    </div>
</dialog>

@push('scripts')
    <script>
        let quotationInvalidTabActivated = false;
        const quotationForm = document.querySelector('[data-quotation-section]')?.closest('form');
        quotationForm?.addEventListener(
            'invalid',
            (event) => {
                if (quotationInvalidTabActivated) return;

                const section = event.target.closest('[data-quotation-section]');
                const sectionName =
                    section?.dataset.quotationSection ?? (event.target.closest('#quotation-lines-section') ? 'general' : null);
                const tab = sectionName ? document.querySelector(`[data-quotation-tab="${sectionName}"]`) : null;
                if (tab) {
                    quotationInvalidTabActivated = true;
                    tab.click();
                    setTimeout(() => (quotationInvalidTabActivated = false), 0);
                }
            },
            true,
        );

        document.querySelectorAll('[data-quotation-tab]').forEach((tab) =>
            tab.addEventListener('click', () => {
                document
                    .querySelectorAll('[data-quotation-tab]')
                    .forEach((item) => item.classList.toggle('active', item === tab));
                document
                    .querySelectorAll('[data-quotation-section]')
                    .forEach((section) =>
                        section.classList.toggle(
                            'sales-tab-panel-active',
                            section.dataset.quotationSection === tab.dataset.quotationTab,
                        ),
                    );
            }),
        );

        const quotationCompanySelect = document.getElementById('company_id');
        const quotationContactSelect = document.getElementById('contact_id');

        function filterQuotationContacts() {
            const companyId = quotationCompanySelect.value;
            let hasContacts = false;

            Array.from(quotationContactSelect.options).forEach((option) => {
                if (!option.value) return;
                const belongsToCompany = option.dataset.companyId === companyId;
                option.hidden = !belongsToCompany;
                option.disabled = !belongsToCompany;
                hasContacts ||= belongsToCompany;
            });

            if (quotationContactSelect.selectedOptions[0]?.disabled) {
                quotationContactSelect.value = '';
            }
            quotationContactSelect.disabled = !companyId || !hasContacts;
        }

        quotationCompanySelect.addEventListener('change', filterQuotationContacts);
        filterQuotationContacts();

        // Product Picker Modal - wait for bootstrap to be available
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.bootstrap) {
                console.error('Bootstrap is not loaded');
                return;
            }

            const quotationProductPickerModal = window.bootstrap.Dialog.getOrCreateInstance(document.getElementById(
                'quotationProductPickerModal'));
            const quotationProductSearchInput = document.getElementById('quotationProductSearchInput');
            const quotationProductCategoryFilter = document.getElementById('quotationProductCategoryFilter');
            const quotationProductTypeFilter = document.getElementById('quotationProductTypeFilter');
            const quotationProductPickerResults = document.getElementById('quotationProductPickerResults');
            let quotationCurrentLineIndex = null;
            let quotationSearchTimeout = null;

            function filterQuotationProductTypes() {
                const categoryId = quotationProductCategoryFilter.value;

                Array.from(quotationProductTypeFilter.options).forEach((option) => {
                    if (!option.dataset.categoryId) return;
                    const belongsToCategory = option.dataset.categoryId === categoryId;
                    option.hidden = !belongsToCategory;
                    option.disabled = !belongsToCategory;
                });

                if (quotationProductTypeFilter.selectedOptions[0]?.hidden) {
                    quotationProductTypeFilter.value = '';
                }

                quotationProductTypeFilter.disabled = categoryId === '';
            }

            function searchQuotationProducts() {
                const query = quotationProductSearchInput.value;
                const categoryId = quotationProductCategoryFilter.value;
                const typeId = quotationProductTypeFilter.value;
                const noProductsText = @json(__('quotations.form.no_products_found'));
                const searchErrorText = @json(__('quotations.form.search_error'));

                if (quotationSearchTimeout) {
                    clearTimeout(quotationSearchTimeout);
                }

                quotationSearchTimeout = setTimeout(() => {
                    fetch(
                            `{{ route('sales.quotations.products.search') }}?search=${encodeURIComponent(query)}&category_id=${encodeURIComponent(categoryId)}&type_id=${encodeURIComponent(typeId)}`
                            )
                        .then(response => response.json())
                        .then(products => {
                            if (products.length === 0) {
                                quotationProductPickerResults.innerHTML =
                                    `<tr><td colspan="4" class="text-center text-muted py-5" style="font-size: 1.1rem;">${noProductsText}</td></tr>`;
                            } else {
                                quotationProductPickerResults.innerHTML = products.map(product => `
                            <tr class="quotation-product-picker-row" data-product-id="${product.id}" data-product-sku="${escapeHtml(product.sku)}" data-product-name="${escapeHtml(product.name)}" data-product-price="${product.price}" data-product-description="${escapeHtml(product.description)}" style="cursor: pointer; font-size: 1.05rem;">
                                <td style="padding: 1rem;">${escapeHtml(product.sku)}</td>
                                <td style="padding: 1rem;">${escapeHtml(product.name)}</td>
                                <td style="padding: 1rem;">${escapeHtml(product.category)}</td>
                                <td class="text-end" style="padding: 1rem;">${quantity(product.price)}</td>
                            </tr>
                        `).join('');

                                quotationProductPickerResults.querySelectorAll(
                                    '.quotation-product-picker-row').forEach(row => {
                                    row.addEventListener('click', () => {
                                        const productId = row.dataset.productId;
                                        const productSku = row.dataset.productSku;
                                        const productName = row.dataset.productName;
                                        const productPrice = row.dataset.productPrice;
                                        const productDescription = row.dataset
                                            .productDescription;

                                        const targetRow = document.querySelector(
                                            `tr[data-line-index="${quotationCurrentLineIndex}"]`
                                        );
                                        const productIdInput = targetRow.querySelector(
                                            '.quotation-product-id');
                                        const productLabel = targetRow.querySelector(
                                            '.quotation-product-label');
                                        const descriptionInput = targetRow
                                            .querySelector('.quotation-description');
                                        const priceInput = targetRow.querySelector(
                                            '.quotation-price');
                                        const quantityInput = targetRow.querySelector(
                                            '.quotation-quantity');
                                        const totalCell = targetRow.querySelector(
                                            '.quotation-line-total');

                                        productIdInput.value = productId;
                                        productLabel.textContent =
                                            `${productSku} - ${productName}`;
                                        descriptionInput.value ||= productDescription;
                                        quantityInput.value ||= '1';
                                        priceInput.value ||= productPrice;

                                        const newTotal = ((Number(quantityInput
                                            .value) || 0) * (Number(priceInput
                                            .value) || 0)).toFixed(2);
                                        totalCell.textContent = newTotal;

                                        quotationProductPickerModal.hide();
                                    });
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error searching products:', error);
                            quotationProductPickerResults.innerHTML =
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

            document.querySelectorAll('#quotation-lines tbody tr').forEach((row) => {
                const chooseBtn = row.querySelector('.quotation-product-choose-btn');
                const quantity = row.querySelector('.quotation-quantity');
                const price = row.querySelector('.quotation-price');
                const total = row.querySelector('.quotation-line-total');

                const updateTotal = () =>
                    (total.textContent = ((Number(quantity.value) || 0) * (Number(price.value) || 0))
                        .toFixed(2));

                chooseBtn?.addEventListener('click', (e) => {
                    quotationCurrentLineIndex = e.currentTarget.dataset.lineIndex;
                    quotationProductSearchInput.value = '';
                    quotationProductCategoryFilter.value = '';
                    quotationProductTypeFilter.value = '';
                    filterQuotationProductTypes();
                    const typeToSearchText = @json(__('quotations.form.type_to_search'));
                    quotationProductPickerResults.innerHTML =
                        `<tr><td colspan="4" class="text-center text-muted py-5" style="font-size: 1.1rem;">${typeToSearchText}</td></tr>`;
                    quotationProductPickerModal.show();
                    setTimeout(() => quotationProductSearchInput.focus(), 500);
                });

                quantity.addEventListener('input', updateTotal);
                price.addEventListener('input', updateTotal);
            });

            quotationProductSearchInput.addEventListener('input', searchQuotationProducts);
            quotationProductCategoryFilter.addEventListener('change', () => {
                filterQuotationProductTypes();
                searchQuotationProducts();
            });
            quotationProductTypeFilter.addEventListener('change', searchQuotationProducts);
            filterQuotationProductTypes();
        });

    </script>
@endpush
