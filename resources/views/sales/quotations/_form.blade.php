@php
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
                ><label class="form-control form-adorn">
                    <input
                        class="form-ghost @error('reference_number') is-invalid @enderror"
                        id="reference_number"
                        name="reference_number"
                        value="{{ old('reference_number', $quotation?->reference_number) }}"
                        maxlength="255"
                    />
                </label>
                @error('reference_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-3">
                <label class="form-label fw-bold" for="date">{{ __('quotations.form.date') }}</label
                ><label class="form-control form-adorn">
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
                </label>
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-3">
                <label
                    class="form-label fw-bold"
                    for="expiry_date"
                    >{{ __('quotations.form.expiry_date') }}</label
                ><label class="form-control form-adorn">
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
                </label>
                @error('expiry_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-2">
                <label class="form-label fw-bold" for="status">{{ __('quotations.form.status') }}</label
                ><select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach ($statuses as $status)
                        @if ($status !== \App\Enums\QuotationStatus::CONVERTED)
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
            accept="{{ \App\Enums\UploadType::DOCUMENT_AND_IMAGES->accept() }}"
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
                <th>{{ __('quotations.form.product') }}</th>
                <th>{{ __('quotations.form.description') }}</th>
                <th>{{ __('quotations.form.quantity') }}</th>
                <th>{{ __('quotations.form.unit_price') }}</th>
                <th class="text-end">{{ __('quotations.form.total_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @for ($index = 0; $index < 20; $index++)
                @php($line = $quotationLines->get($index))
                <tr>
                    <td style="min-width: 220px">
                        <select class="form-control quotation-product" name="lines[{{ $index }}][product_id]">
                            <option value="">{{ __('common.actions.select_option') }}</option>
                            @foreach ($products as $product)
                                <option
                                    data-description="{{ $product->description }}"
                                    data-price="{{ $product->selling_price }}"
                                    value="{{ $product->id }}"
                                    @selected(old("lines.$index.product_id", $line?->product_id) === $product->id)
                                >
                                    {{ $product->sku }} - {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td style="min-width: 220px">
                        <label class="form-control form-adorn">
                            <input
                                class="form-ghost quotation-description"
                                name="lines[{{ $index }}][description]"
                                value="{{ old("lines.$index.description", $line?->description) }}"
                            />
                        </label>
                    </td>
                    <td style="min-width: 120px">
                        <label class="form-control form-adorn">
                            <input
                                class="form-ghost quotation-quantity"
                                name="lines[{{ $index }}][quantity]"
                                type="number"
                                min="0.0001"
                                step="0.0001"
                                value="{{ old("lines.$index.quantity", $line?->quantity) }}"
                            />
                        </label>
                    </td>
                    <td style="min-width: 140px">
                        <label class="form-control form-adorn">
                            <input
                                class="form-ghost quotation-price"
                                name="lines[{{ $index }}][unit_price]"
                                type="number"
                                min="0"
                                step="0.01"
                                value="{{ old("lines.$index.unit_price", $line?->unit_price) }}"
                            />
                        </label>
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

        function refreshQuotationProductSelects() {
            if (typeof window.initCombobox !== 'function') {
                return;
            }

            document.querySelectorAll('.quotation-product').forEach((element) => {
                window.initCombobox(element, {
                    placeholder: @json(__('common.actions.select_option')),
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', refreshQuotationProductSelects);
        } else {
            refreshQuotationProductSelects();
        }

        document.querySelectorAll('#quotation-lines tbody tr').forEach((row) => {
            const product = row.querySelector('.quotation-product');
            const quantity = row.querySelector('.quotation-quantity');
            const price = row.querySelector('.quotation-price');
            const total = row.querySelector('.quotation-line-total');
            const updateTotal = () =>
                (total.textContent = ((Number(quantity.value) || 0) * (Number(price.value) || 0)).toFixed(2));
            product.addEventListener('change', () => {
                const option = product.selectedOptions[0];
                if (!option?.value) return;
                row.querySelector('.quotation-description').value ||= option.dataset.description || '';
                quantity.value ||= '1';
                price.value ||= option.dataset.price || '0';
                updateTotal();
            });
            quantity.addEventListener('input', updateTotal);
            price.addEventListener('input', updateTotal);
        });

    </script>
@endpush
