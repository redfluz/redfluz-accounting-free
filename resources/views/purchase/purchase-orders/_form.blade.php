@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $existingLines = $purchaseOrder !== null ? $purchaseOrder->lines->values() : collect();
    $poContacts = $contacts
        ->map(
            fn($contact): array => [
                'id' => $contact->id,
                'company_id' => $contact->company_id,
                'name' => $contact->name,
            ],
        )
        ->values();
    $canQuickCreateCompany = auth()->user()->hasFilePermission(FileResources::PURCHASE_ORDERS, FileAction::ADD);
@endphp

<ul class="purchase-document-tabs nav nav-tabs" style="order: 1" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-purchase-tab="general" type="button">{{ __('ledgers.general') }}</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-purchase-tab="attachment" type="button">{{ __('ledgers.attachment') }}</button>
    </li>
    <li class="nav-item">
        <button class="nav-link disabled" type="button" disabled>{{ __('ledgers.type.payment') }}</button>
    </li>
</ul>

<div data-purchase-panel="general" style="order: 2">
    <div class="row g-3 mb-4">
        <div class="md:col-6">
            <label class="form-label fw-bold" for="company_id">{{ __('purchase_orders.form.supplier') }}</label>
            <select class="form-control @error('company_id') is-invalid @enderror" id="company_id" name="company_id"
                onchange="filterPoContacts()" required>
                <option value="">
                    {{ __('common.actions.select_option') }}
                </option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(old('company_id', $purchaseOrder?->company_id) === $company->id)>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            @error('company_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            @if ($canQuickCreateCompany)
                @include('partials.quick-company', [
                    'quickCompanyRoute' => route('purchase.purchase-orders.companies.store'),
                    'quickCompanyLabel' => __('purchase_orders.actions.quick_company'),
                    'quickCompanyNamePlaceholder' => __('purchase_orders.form.quick_company_name'),
                    'quickCompanySaveLabel' => __('purchase_orders.actions.add_company'),
                    'quickCompanyRequiredMessage' => __('purchase_orders.validation.company_name_required'),
                    'quickCompanyFailedMessage' => __('purchase_orders.validation.company_create_failed'),
                ])
            @endif
        </div>
        <div class="md:col-6">
            <label class="form-label fw-bold" for="contact_id">{{ __('purchase_orders.form.contact') }}</label>
            <select class="form-control @error('contact_id') is-invalid @enderror" id="contact_id"
                name="contact_id"></select>
            @error('contact_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="md:col-3">
            <label class="form-label fw-bold" for="purchase_type">{{ __('purchase_orders.form.type') }}</label>
            <label class="form-control form-adorn">
                <input class="form-ghost" id="purchase_type_display" type="text"
                    value="{{ __('purchase_orders.type.purchase') }}" readonly />
            </label>
            <input type="hidden" name="purchase_type" value="purchase" />
        </div>
        <div class="md:col-3">
            <label class="form-label fw-bold" for="date">{{ __('purchase_orders.form.date') }}</label>
            <label class="form-control form-adorn">
                <div class="form-adorn-icon" aria-hidden="true">
                    <i data-lucide="calendar"></i>
                </div>
                <input class="form-ghost @error('date') is-invalid @enderror" id="date" name="date"
                    type="text" data-bs-toggle="datepicker" autocomplete="off"
                    value="{{ old('date', $purchaseOrder?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                    required />
            </label>
        </div>
        <div class="md:col-3">
            <label class="form-label fw-bold"
                for="expected_date">{{ __('purchase_orders.form.expected_date') }}</label>
            <label class="form-control form-adorn">
                <div class="form-adorn-icon" aria-hidden="true">
                    <i data-lucide="calendar"></i>
                </div>
                <input class="form-ghost @error('expected_date') is-invalid @enderror" id="expected_date"
                    name="expected_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                    value="{{ old('expected_date', $purchaseOrder?->expected_date?->format('Y-m-d')) }}" />
            </label>
        </div>
        <div class="md:col-3">
            <label class="form-label fw-bold"
                for="supplier_reference_number">{{ __('purchase_orders.form.supplier_reference') }}</label>
            <label class="form-control form-adorn">
                <input class="form-ghost" id="supplier_reference_number" name="supplier_reference_number"
                    value="{{ old('supplier_reference_number', $purchaseOrder?->supplier_reference_number) }}" />
            </label>
        </div>
        <div class="md:col-3">
            <label class="form-label fw-bold"
                for="credit_term_days">{{ __('purchase_orders.form.credit_term_days') }}</label>
            <div class="input-group">
                <label class="form-control form-adorn">
                    <input class="form-ghost" id="credit_term_days" name="credit_term_days" type="number"
                        value="{{ old('credit_term_days', $purchaseOrder?->credit_term_days ?? $defaultCreditTermDays) }}"
                        min="0" max="365" required />
                </label><span class="input-group-text">{{ __('purchase_order_settings.days') }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="md:col-6">
            <label class="form-label fw-bold"
                for="supplier_sales_order_number">{{ __('purchase_orders.form.supplier_sales_order_number') }}</label>
            <label class="form-control form-adorn">
                <input class="form-ghost" id="supplier_sales_order_number" name="supplier_sales_order_number"
                    value="{{ old('supplier_sales_order_number', $purchaseOrder?->supplier_sales_order_number) }}" />
            </label>
        </div>
    </div>
</div>

<div class="purchase-lines-heading d-flex align-items-center justify-content-between mb-2" style="order: 3">
    <h6 class="fw-bold mb-0">{{ __('purchase_orders.form.lines') }}</h6>
    <span class="fg-secondary small">{{ __('purchase_orders.messages.complete_rows') }}</span>
</div>
<div class="table-responsive mb-4 rounded border" style="order: 3">
    <table class="table-form table-striped mb-0 table align-middle" id="purchase-order-lines-table">
        <thead class="bg-1">
            <tr>
                <th style="min-width: 220px">
                    {{ __('purchase_orders.form.description') }}
                    <span class="fg-danger">*</span>
                </th>
                <th style="min-width: 110px">
                    {{ __('purchase_orders.form.quantity') }}
                </th>
                <th style="min-width: 140px">
                    {{ __('purchase_orders.form.unit_price') }}
                </th>
                <th class="text-end" style="min-width: 130px">
                    {{ __('purchase_orders.table.total') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @for ($lineIndex = 0; $lineIndex < 10; $lineIndex++)
                @php($existingLine = $existingLines->get($lineIndex))
                <tr>
                    <td>
                        <label class="form-control form-control-sm form-adorn">
                            <input class="form-ghost @error("lines.$lineIndex.description") is-invalid @enderror"
                                name="lines[{{ $lineIndex }}][description]"
                                value="{{ old("lines.$lineIndex.description", $existingLine?->description) }}" />
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
                                value="{{ old("lines.$lineIndex.quantity", $existingLine?->quantity) }}"
                                min="1" />
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
                                value="{{ old("lines.$lineIndex.unit_price", $existingLine?->unit_price) }}"
                                min="0" step="0.01" />
                        </label>
                        @error("lines.$lineIndex.unit_price")
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </td>
                    <td class="line-total fw-bold text-end">0.00</td>
                </tr>
            @endfor
        </tbody>
        <tfoot class="bg-1">
            <tr>
                <th class="text-end" colspan="3">
                    {{ __('common.labels.total') }}
                </th>
                <th class="fg-primary text-end" id="po-grand-total">0.00</th>
            </tr>
        </tfoot>
    </table>
</div>

<div class="mb-4" data-purchase-panel="general" style="order: 2">
    <label class="form-label fw-bold" for="notes">{{ __('purchase_orders.form.notes') }}</label>
    <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $purchaseOrder?->notes) }}</textarea>
</div>
<div class="mb-4" data-purchase-panel="attachment" style="order: 2">
    <label class="form-label fw-bold" for="purchase-order-attachments">
        {{ __('purchase_orders.form.attachments') }}
    </label>
    <input accept="{{ \App\Enums\UploadType::DOCUMENT_AND_IMAGES->accept() }}"
        class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
        id="purchase-order-attachments" name="attachments[]" type="file" multiple />
    <div class="form-text">{{ __('purchase_orders.form.attachments_hint') }}</div>
    @error('attachments')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('attachments.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
@if ($purchaseOrder?->attachments?->isNotEmpty())
    <div class="mb-4" data-purchase-panel="attachment" style="order: 2">
        <h6 class="fw-bold">{{ __('purchase_orders.form.existing_attachments') }}</h6>
        <div class="list-group">
            @foreach ($purchaseOrder->attachments as $attachment)
                <div class="list-group-item d-flex align-items-center justify-content-between gap-3">
                    <span>
                        <a class="fw-bold d-block"
                            href="{{ route('purchase.purchase-orders.attachments.download', [$purchaseOrder, $attachment]) }}">{{ $attachment->original_name }}</a>
                        <small class="badge theme-secondary">
                            {{ number_format($attachment->file_size / 1024, 1) }} KB
                            @if ($attachment->uploaded_by_name)
                                | {{ $attachment->uploaded_by_name }}
                            @endif
                        </small>
                    </span>
                    <label class="mb-0">
                        <input class="check" id="delete-purchase-order-attachment-{{ $attachment->id }}"
                            name="delete_attachments[]" type="checkbox" value="{{ $attachment->id }}"
                            @checked(in_array($attachment->id, old('delete_attachments', []), true)) />
                        <span class="fg-danger">
                            {{ __('purchase_orders.actions.remove_attachment') }}
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
@endif
<div class="d-flex justify-content-between" style="order: 4">
    <a class="btn btn-solid theme-secondary" href="{{ route('purchase.purchase-orders.index') }}"><i class="me-1"
            data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a><button
        class="btn btn-solid theme-accent" type="submit">
        <i class="me-1"
            data-lucide="save"></i>{{ __($purchaseOrder === null ? 'common.actions.save' : 'common.actions.update') }}
    </button>
</div>

@push('scripts')
    <script>
        const poContacts = @json($poContacts);
        const selectedPoContact = @json(old('contact_id', $purchaseOrder?->contact_id));

        function filterPoContacts() {
            const companyId = document.getElementById('company_id').value;
            const select = document.getElementById('contact_id');
            select.innerHTML =
                `<option value="">{{ __('common.actions.select_option') }}</option>`;
            poContacts
                .filter((item) => item.company_id === companyId)
                .forEach((item) => select.add(new Option(item.name, item.id, false, item.id === selectedPoContact)));

        }

        function updatePoTotals() {
            let total = 0;
            document.querySelectorAll('#purchase-order-lines-table tbody tr').forEach((row) => {
                const lineTotal =
                    Number(row.querySelector('.line-quantity').value || 0) *
                    Number(row.querySelector('.line-unit-price').value || 0);
                row.querySelector('.line-total').textContent = lineTotal.toFixed(2);
                total += lineTotal;
            });
            document.getElementById('po-grand-total').textContent = total.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', () => {
            filterPoContacts();
            document
                .querySelectorAll('.line-quantity, .line-unit-price')
                .forEach((input) => input.addEventListener('input', updatePoTotals));
            updatePoTotals();
        });
    </script>
@endpush
