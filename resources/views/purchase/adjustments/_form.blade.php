@php
    use App\Enums\UploadType;
@endphp

@props([
    'companies',
    'purchaseAdjustment' => null,
])

@php
    use App\Enums\Finance\LedgerSubType;
@endphp

<input name="type" type="hidden" value="invoice" />

{{-- Navigation Tabs --}}
<ul class="nav nav-tabs mb-4" id="adjustmentFormTabs">
    <li class="nav-item">
        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-pane"
            type="button" role="tab" aria-controls="general-pane" aria-selected="true">
            <i class="me-1" data-lucide="list"></i>
            {{ __('ledgers.general') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="attachments-tab" data-bs-toggle="tab"
            data-bs-target="#attachments-pane" type="button" role="tab"
            aria-controls="attachments-pane" aria-selected="false">
            <i class="me-1" data-lucide="paperclip"></i>
            {{ __('ledgers.attachment') }}
        </button>
    </li>
</ul>

<div class="tab-content" id="adjustmentTabContent">
    {{-- TAB 1: General --}}
    <div class="tab-pane fade show active" id="general-pane" role="tabpanel" aria-labelledby="general-tab">
        <div class="mb-3">
            <label class="form-label fw-bold" for="subtype">
                {{ __('ledgers.table.column_subtype') }}
            </label>
            <select class="form-control @error('subtype') is-invalid @enderror" id="subtype"
                name="subtype" required>
                <option value="{{ LedgerSubType::PURCHASE_DEBIT_NOTE->value }}"
                    {{ old('subtype', $purchaseAdjustment?->subtype?->value) === LedgerSubType::PURCHASE_DEBIT_NOTE->value ? 'selected' : '' }}>
                    {{ __('ledgers.sub_type.purchase_debit_note') }}
                </option>
                <option value="{{ LedgerSubType::PURCHASE_CREDIT_NOTE->value }}"
                    {{ old('subtype', $purchaseAdjustment?->subtype?->value) === LedgerSubType::PURCHASE_CREDIT_NOTE->value ? 'selected' : '' }}>
                    {{ __('ledgers.sub_type.purchase_credit_note') }}
                </option>
            </select>
            @error('subtype')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold" for="company_id">
                {{ __('ledgers.table.column_company') }}
            </label>
            <select class="form-control @error('company_id') is-invalid @enderror" id="company_id"
                name="company_id" required>
                <option value="">{{ __('common.actions.select_option') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}"
                        {{ old('company_id', $purchaseAdjustment?->company_id) === $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            @error('company_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold" for="reference_number">
                {{ __('purchase_adjustments.form.linked_invoice') }}
            </label>
            <select class="form-control @error('reference_number') is-invalid @enderror"
                id="reference_number" name="reference_number" required>
                <option value="">{{ __('common.actions.select_option') }}</option>
                @if (old('reference_number', $purchaseAdjustment?->reference_number))
                    <option value="{{ old('reference_number', $purchaseAdjustment?->reference_number) }}" selected>
                        {{ old('reference_number', $purchaseAdjustment?->reference_number) }}
                    </option>
                @endif
            </select>
            @error('reference_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold" for="date">
                {{ __('ledgers.table.column_date') }}
            </label>
            <div class="form-control form-adorn">
                <div class="form-adorn-icon" aria-hidden="true">
                    <i data-lucide="calendar"></i>
                </div>
                <input class="form-ghost @error('date') is-invalid @enderror" id="date" name="date"
                    type="text" data-bs-toggle="datepicker" autocomplete="off"
                    value="{{ old('date', $purchaseAdjustment?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                    min="{{ $postingDateMin ?? '' }}" max="{{ $postingDateMax ?? '' }}" required />
            </div>
            @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="md:col-6 mb-3">
                <label class="form-label fw-bold" for="quantity">
                    {{ __('ledgers.table.column_quantity') }}
                </label>
                <div class="form-control form-adorn">
                    <input class="form-ghost @error('quantity') is-invalid @enderror" id="quantity"
                        name="quantity" type="number" value="{{ old('quantity', 1) }}" min="1"
                        required />
                </div>
                @error('quantity')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6 mb-3">
                <label class="form-label fw-bold" for="unit_price">
                    {{ __('ledgers.table.column_unit_price') }}
                </label>
                <div class="form-control form-adorn">
                    <input class="form-ghost @error('unit_price') is-invalid @enderror" id="unit_price"
                        name="unit_price" type="number" value="{{ old('unit_price') }}" step="0.01"
                        required />
                </div>
                @error('unit_price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold" for="notes">
                {{ __('ledgers.table.column_description') }}
            </label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3"
                placeholder="{{ __('ledgers.table.column_notes') }}">{{ old('notes', $purchaseAdjustment?->notes) }}</textarea>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- TAB 2: Attachment --}}
    <div class="tab-pane fade" id="attachments-pane" role="tabpanel" aria-labelledby="attachments-tab">
        <div class="mb-3">
            <label class="form-label fw-bold" for="attachment">
                {{ __('ledgers.table.column_attachment_path') }}
            </label>

            <div class="alert alert theme-info small mb-2 px-3 py-2">
                <i class="me-1" data-lucide="info"></i>
                {{ __('ledgers.attachment_info') }}
            </div>

            <div class="input-group">
                <label class="btn btn-solid theme-secondary d-flex align-items-center mb-0"
                    for="attachment">
                    <i class="me-1" data-lucide="arrow-up-from-line"></i>
                    {{ __('common.labels.choose_file') }}
                </label>
                <span
                    class="form-control fg-secondary d-flex align-items-center @error('attachment') is-invalid @enderror bg-white"
                    id="file-chosen">
                    {{ __('common.labels.no_file_chosen') }}
                </span>
                <input class="d-none" id="attachment" name="attachment" type="file"
                    accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}"
                    onchange="previewFiles(this)" />
            </div>

            <div class="fg-danger small d-none mt-1" id="attachment-error"></div>

            @error('attachment')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror

            <div class="row g-2 mt-3" id="file-preview-container"></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between border-top mt-4 pt-3">
    <a class="btn btn-solid theme-secondary" href="{{ route('purchase.adjustments.index') }}">
        <i class="me-1" data-lucide="arrow-left"></i>
        {{ __('common.actions.back') }}
    </a>
    @if ($canCreate)
        <button class="btn btn-solid theme-accent" type="submit">
            <i class="me-1" data-lucide="save"></i>
            {{ __('common.actions.save') }}
        </button>
    @endif
</div>

<script>
    const selectText = "{{ __('common.actions.select_option') }}";
    const invoiceOptionsUrl = @json(route('purchase.adjustments.invoices'));

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

    function previewFiles(input) {
        const container = document.getElementById('file-preview-container');
        const errorDiv = document.getElementById('attachment-error');
        const fileChosenSpan = document.getElementById('file-chosen');
        const defaultPlaceholder = "{{ __('common.labels.no_file_chosen') }}";

        container.innerHTML = '';
        errorDiv.classList.add('d-none');
        errorDiv.textContent = '';

        const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        const maxFiles = 1;
        const files = Array.from(input.files);

        if (files.length > maxFiles) {
            errorDiv.textContent = `You can only select up to ${maxFiles} attachment.`;
            errorDiv.classList.remove('d-none');
            input.value = '';
            fileChosenSpan.textContent = defaultPlaceholder;
            return;
        }

        if (files.length === 0) {
            fileChosenSpan.textContent = defaultPlaceholder;
            return;
        }

        const file = files[0];
        const ext = file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(ext)) {
            errorDiv.textContent = `"${file.name}" is not an allowed file type.`;
            errorDiv.classList.remove('d-none');
            input.value = '';
            fileChosenSpan.textContent = defaultPlaceholder;
            return;
        }

        fileChosenSpan.textContent = file.name;
        fileChosenSpan.classList.remove('fg-secondary');

        const col = document.createElement('div');
        col.className = 'col-6';

        const card = document.createElement('div');
        card.className = 'card h-100 border p-2 text-center bg-2';

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'img-fluid rounded mb-2';
            img.style.maxHeight = '100px';
            img.style.objectFit = 'contain';
            card.appendChild(img);
        } else {
            const iconBox = document.createElement('div');
            iconBox.className = 'py-3 fg-secondary';
            iconBox.innerHTML =
                ext === 'pdf' ?
                '<i class="fs-4xl fg-danger" data-lucide="file-text"></i>' :
                '<i class="fs-4xl fg-primary" data-lucide="file-type"></i>';
            card.appendChild(iconBox);
        }

        const fileName = document.createElement('div');
        fileName.className = 'small fw-bold text-truncate';
        fileName.textContent = file.name;
        card.appendChild(fileName);

        const fileSize = document.createElement('div');
        fileSize.className = 'fg-secondary extra-small';
        fileSize.style.fontSize = '0.75rem';
        fileSize.textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        card.appendChild(fileSize);

        col.appendChild(card);
        container.appendChild(col);
    }

    document.addEventListener('DOMContentLoaded', function() {
        initializeInvoiceSelect();
        document.getElementById('company_id').addEventListener('change', handleCompanyChange);

        const errors = @json($errors->keys());
        if (errors.length > 0) {
            if (
                errors.includes('subtype') ||
                errors.includes('company_id') ||
                errors.includes('reference_number') ||
                errors.includes('date') ||
                errors.includes('quantity') ||
                errors.includes('unit_price') ||
                errors.includes('notes')
            ) {
                const tab = new bootstrap.Tab(document.getElementById('general-tab'));
                tab.show();
            } else if (errors.includes('attachment')) {
                const tab = new bootstrap.Tab(document.getElementById('attachments-tab'));
                tab.show();
            }
        }
    });
</script>
