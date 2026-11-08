@php
    use App\Enums\Finance\PaymentTransferType;
    use App\Models\Bank\Refund\SupplierRefund;
@endphp
@props([
    /** @var SupplierRefund|null */
    'refund' => null,
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
    <li class="nav-item">
        <button aria-controls="accounting-pane" aria-selected="false" class="nav-link" data-bs-toggle="tab"
                data-bs-target="#accounting-pane" role="tab" type="button">
            <i class="me-1" data-lucide="book-open"></i>
            {{ __('ledgers.accounting') }}
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
                        <option value="{{ $company->id }}" @selected(old('company_id', $refund?->company_id) === $company->id)>
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
                    {{ __('payments.form.linked_purchase_invoice') }}
                </label>
                <select class="form-control @error('reference_number') is-invalid @enderror" id="reference_number"
                        name="reference_number">
                    <option value="">{{ __('common.actions.select_option') }}</option>
                    @if (old('reference_number', $refund?->reference_number))
                        <option selected value="{{ old('reference_number', $refund?->reference_number) }}">
                            {{ old('reference_number', $refund?->reference_number) }}
                        </option>
                    @endif
                </select>
                <div class="alert alert-warning d-none mb-0 mt-2 py-2" id="no-paid-balance-message">
                    <i class="me-1" data-lucide="info"></i>
                    {{ __('refunds.messages.no_paid_balance') }}
                </div>
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
                           value="{{ old('date', $refund?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"/>
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
                           step="0.01" type="number" value="{{ old('amount', $refund?->total_amount) }}"/>
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
                        <option value="{{ $transferType->value }}" @selected(old('payment_transfer_type', $refund?->payment_transfer_type?->value) === $transferType->value)>
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
                          rows="3">{{ old('notes', $refund?->notes) }}</textarea>
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

    <div class="fade tab-pane" id="accounting-pane" role="tabpanel">
        <div class="alert fg-secondary small mb-3 border">
            <i class="me-1" data-lucide="info"></i>
            {{ __('payments.form.accounting_hint') }}
        </div>

        <div class="row">
            <div class="md:col-6 mb-3">
                <label class="form-label fw-bold" for="debit_id">
                    {{ __('ledgers.table.column_debit_acc') }}
                </label>
                <select class="form-control js-account-select @error('debit_id') is-invalid @enderror" id="debit_id"
                        name="debit_id" data-filter="bank">
                    <option value="">{{ __('common.actions.select_option') }}</option>
                    @foreach ($charts->where('isBank', true) as $chart)
                        <option value="{{ $chart->id }}" @selected(old('debit_id', $refund?->debit_id) === $chart->id)>
                            {{ $chart->code }} - {{ $chart->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">{{ __('ledgers.default_account_hint') }}</div>
                @error('debit_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="md:col-6 mb-3">
                <label class="form-label fw-bold" for="credit_id">
                    {{ __('ledgers.table.column_credit_acc') }}
                </label>
                <select class="form-control js-account-select @error('credit_id') is-invalid @enderror" id="credit_id"
                        name="credit_id" data-filter="debtor">
                    <option value="">{{ __('common.actions.select_option') }}</option>
                    @foreach ($charts->where('isDebtor', true) as $chart)
                        <option value="{{ $chart->id }}" @selected(old('credit_id', $refund?->credit_id) === $chart->id)>
                            {{ $chart->code }} - {{ $chart->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">{{ __('ledgers.default_account_hint') }}</div>
                @error('credit_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

@section('script')
    <script>
        const invoiceOptionsUrl = @json(route('bank.supplier-refunds.invoices'));
        const selectText = @json(__('common.actions.select_option'));

        // The available balance is carried on each option by the server, but the
        // combobox renders only its label, so the figures are kept here keyed by
        // invoice number to be read when a choice is made.
        let availableByInvoice = {};

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
                        .then((payload) => {
                            const results = payload.results ?? [];

                            results.forEach((row) => {
                                availableByInvoice[row.value] = row.available_amount;
                            });

                            callback(results);
                        })
                        .catch(() => callback());
                },
            });

            referenceSelect.addEventListener('change', updateRefundAvailability);

            updateRefundAvailability();
        }

        function updateRefundAvailability() {
            const referenceSelect = document.getElementById('reference_number');
            const amountInput = document.getElementById('amount');
            const submitButton = document.getElementById('refund-submit');
            const warning = document.getElementById('no-paid-balance-message');
            const selectedValue = referenceSelect.value;
            const availableAmount = Number.parseFloat(availableByInvoice[selectedValue]);

            // An invoice chosen but not yet loaded reports no balance, so the
            // form refuses it rather than guessing that money was received.
            const hasUnavailableSelection =
                selectedValue !== '' && (!Number.isFinite(availableAmount) || availableAmount <= 0);

            warning.classList.toggle('d-none', !hasUnavailableSelection);
            amountInput.disabled = hasUnavailableSelection;

            if (hasUnavailableSelection) {
                amountInput.value = '';
                amountInput.removeAttribute('max');
            } else if (Number.isFinite(availableAmount)) {
                amountInput.max = availableAmount.toFixed(2);
            } else {
                amountInput.removeAttribute('max');
            }

            if (submitButton) {
                submitButton.disabled = hasUnavailableSelection;
            }
        }

        function resetInvoiceSelect() {
            const referenceSelect = document.getElementById('reference_number');

            if (!referenceSelect.autoComplete) {
                return;
            }

            availableByInvoice = {};
            referenceSelect.autoComplete.clearOptions();

            if (document.getElementById('company_id').value) {
                referenceSelect.autoComplete.load('');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            initializeInvoiceSelect();

            document.getElementById('company_id').addEventListener('change', resetInvoiceSelect);
        });
    </script>
@endsection
