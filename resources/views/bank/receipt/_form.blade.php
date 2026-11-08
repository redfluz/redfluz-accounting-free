@php
    use App\Enums\Finance\PaymentTransferType;
    use App\Models\Bank\Receipt;
@endphp
@props([
    /** @var Receipt|null */
    'receipt' => null,
    'companies',
    'charts',
])

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <button aria-controls="general-pane" aria-selected="true" class="nav-link active" data-bs-toggle="tab"
                data-bs-target="#general-pane" role="tab" type="button">
            <i class="me-1" data-lucide="list"></i>
            {{ __('ledgers.general') }}
        </button>
    </li>
    <li class="nav-item">
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
                <select class="form-control @error('company_id') is-invalid @enderror" id="company_id" name="company_id"
                        required>
                    <option value="">{{ __('common.actions.select_option') }}</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id', $receipt?->company_id) === $company->id)>
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
                <select class="form-control @error('reference_number') is-invalid @enderror" id="reference_number"
                        name="reference_number">
                    <option value="">{{ __('common.actions.select_option') }}</option>
                    @if (old('reference_number', $receipt?->reference_number))
                        <option value="{{ old('reference_number', $receipt?->reference_number) }}" selected>
                            {{ old('reference_number', $receipt?->reference_number) }}
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
                <div class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="calendar"></i>
                    </div>
                    <input class="form-ghost @error('date') is-invalid @enderror" id="date"
                           name="date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                           value="{{ old('date', $receipt?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"/>
                </div>
                @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="md:col-6 mb-3">
                <label class="form-label fw-bold" for="amount">
                    {{ __('ledgers.table.column_total_amount') }}
                </label>
                <div class="form-control form-adorn">
                    <input class="form-ghost @error('amount') is-invalid @enderror" id="amount" min="0.01" name="amount"
                           step="0.01" type="number" value="{{ old('amount', $receipt?->total_amount) }}"/>
                </div>
                @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="md:col-6 mb-3">
                <label class="form-label fw-bold" for="payment_transfer_type">
                    {{ __('payments.form.payment_transfer') }}
                </label>
                <select class="form-control" id="payment_transfer_type" name="payment_transfer_type">
                    @foreach (PaymentTransferType::cases() as $transferType)
                        <option value="{{ $transferType->value }}" @selected(old('payment_transfer_type', $receipt?->payment_transfer_type?->value) === $transferType->value)>
                            {{ $transferType->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 mb-3">
                <label class="form-label fw-bold" for="notes">
                    {{ __('ledgers.table.column_notes') }}
                </label>
                <textarea class="form-control" id="notes" name="notes"
                          rows="3">{{ old('notes', $receipt?->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="fade tab-pane" id="attachment-pane" role="tabpanel">
        <div class="mb-3">
            <label class="form-label fw-bold" for="attachment">
                {{ __('ledgers.attachment') }}
            </label>
            <input class="form-control @error('attachment') is-invalid @enderror" id="attachment" name="attachment"
                   type="file"/>
            @error('attachment')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
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

        document.addEventListener('DOMContentLoaded', function () {
            initializeInvoiceSelect();

            document.getElementById('company_id').addEventListener('change', handleCompanyChange);
        });
    </script>
@endsection
