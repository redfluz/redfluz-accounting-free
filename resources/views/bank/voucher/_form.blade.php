@php
    use App\Enums\Bank\BankAccountKind;
    use App\Enums\Finance\PaymentTransferType;
    use App\Models\Bank\PaymentVoucher;

    // Which transfer types each account picker serves. Read from the enum rather
    // than repeated in JavaScript, so a fifth transfer type only has to say what
    // kind of account it moves and both the page and the server follow.
    $transferTypesFor = static function (BankAccountKind $kind): string {
        return collect(PaymentTransferType::cases())
            ->filter(static fn (PaymentTransferType $type): bool => $type->accountKind() === $kind)
            ->pluck('value')
            ->implode(' ');
    };

    $bankTransferTypes = $transferTypesFor(BankAccountKind::BANK);
    $cashTransferTypes = $transferTypesFor(BankAccountKind::CASH);
    $eWalletTransferTypes = $transferTypesFor(BankAccountKind::E_WALLET);

    $activeTransferType = PaymentTransferType::tryFrom((string) old(
        'payment_transfer_type',
        $voucher?->payment_transfer_type?->value ?? PaymentTransferType::CHEQUE->value,
    ));
    $activeAccountColumn = $activeTransferType?->accountColumn();
@endphp
@props([
    /** @var PaymentVoucher|null */
    'voucher' => null,
    'companies',
    'charts',
    'banks' => collect(),
    'cashAccounts' => collect(),
    'eWalletAccounts' => collect(),
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
                        <option value="{{ $company->id }}" @selected(old('company_id', $voucher?->company_id) === $company->id)>
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
                    @if (old('reference_number', $voucher?->reference_number))
                        <option value="{{ old('reference_number', $voucher?->reference_number) }}" selected>
                            {{ old('reference_number', $voucher?->reference_number) }}
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
                           value="{{ old('date', $voucher?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"/>
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
                           step="0.01" type="number" value="{{ old('amount', $voucher?->total_amount) }}"/>
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
                        <option value="{{ $transferType->value }}" @selected(old('payment_transfer_type', $voucher?->payment_transfer_type?->value) === $transferType->value)>
                            {{ $transferType->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Which account the money leaves, and it is a different question for
                 every transfer type: a cheque is drawn on a bank, a cash payment
                 comes out of a register and a wallet payment out of a wallet. Only
                 the picker the transfer type allows is shown, and the others are
                 disabled so they cannot be sent along with it. --}}
            <div class="md:col-6 mb-3 {{ $activeAccountColumn === 'bank_account_id' ? '' : 'd-none' }}"
                 data-account-field="bank_account_id"
                 data-transfer-types="{{ $bankTransferTypes }}">
                <label class="form-label fw-bold" for="bank_account_id">
                    {{ __('payments.form.bank_account') }} <span class="fg-danger">*</span>
                </label>
                <select class="form-control @error('bank_account_id') is-invalid @enderror" id="bank_account_id"
                        name="bank_account_id"
                        @required($activeAccountColumn === 'bank_account_id')
                        @disabled($activeAccountColumn !== 'bank_account_id')>
                    <option value="" disabled selected>{{ __('common.actions.select_option') }}</option>
                    @foreach ($banks as $account)
                        <option value="{{ $account->id }}" @selected(old('bank_account_id', $voucher?->bank_account_id) === $account->id)>
                            {{ $account->name }}@if ($account->chart) ({{ $account->chart->code }} - {{ $account->chart->name }})@endif
                        </option>
                    @endforeach
                </select>
                @error('bank_account_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="md:col-6 mb-3 {{ $activeAccountColumn === 'cash_account_id' ? '' : 'd-none' }}"
                 data-account-field="cash_account_id"
                 data-transfer-types="{{ $cashTransferTypes }}">
                <label class="form-label fw-bold" for="cash_account_id">
                    {{ __('payments.form.cash_account') }} <span class="fg-danger">*</span>
                </label>
                <select class="form-control @error('cash_account_id') is-invalid @enderror" id="cash_account_id"
                        name="cash_account_id"
                        @required($activeAccountColumn === 'cash_account_id')
                        @disabled($activeAccountColumn !== 'cash_account_id')>
                    <option value="" disabled selected>{{ __('common.actions.select_option') }}</option>
                    @foreach ($cashAccounts as $account)
                        <option value="{{ $account->id }}" @selected(old('cash_account_id', $voucher?->cash_account_id) === $account->id)>
                            {{ $account->name }}@if ($account->chart) ({{ $account->chart->code }} - {{ $account->chart->name }})@endif
                        </option>
                    @endforeach
                </select>
                @error('cash_account_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="md:col-6 mb-3 {{ $activeAccountColumn === 'e_wallet_account_id' ? '' : 'd-none' }}"
                 data-account-field="e_wallet_account_id"
                 data-transfer-types="{{ $eWalletTransferTypes }}">
                <label class="form-label fw-bold" for="e_wallet_account_id">
                    {{ __('payments.form.e_wallet_account') }} <span class="fg-danger">*</span>
                </label>
                <select class="form-control @error('e_wallet_account_id') is-invalid @enderror" id="e_wallet_account_id"
                        name="e_wallet_account_id"
                        @required($activeAccountColumn === 'e_wallet_account_id')
                        @disabled($activeAccountColumn !== 'e_wallet_account_id')>
                    <option value="" disabled selected>{{ __('common.actions.select_option') }}</option>
                    @foreach ($eWalletAccounts as $account)
                        <option value="{{ $account->id }}" @selected(old('e_wallet_account_id', $voucher?->e_wallet_account_id) === $account->id)>
                            {{ $account->name }}@if ($account->chart) ({{ $account->chart->code }} - {{ $account->chart->name }})@endif
                        </option>
                    @endforeach
                </select>
                @error('e_wallet_account_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 mb-3">
                <label class="form-label fw-bold" for="notes">
                    {{ __('ledgers.table.column_notes') }}
                </label>
                <textarea class="form-control" id="notes" name="notes"
                          rows="3">{{ old('notes', $voucher?->notes) }}</textarea>
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
        const invoiceOptionsUrl = @json(route('bank.payment-vouchers.invoices'));
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

        const accountFields = Array.from(document.querySelectorAll('[data-account-field]'));

        // Show only the account picker the transfer type allows. Each field lists
        // the types it serves on itself, which the page took from the enum, so
        // nothing here has to know that a cheque means a bank.
        function syncAccountFields() {
            const transferType = document.getElementById('payment_transfer_type').value;

            accountFields.forEach((field) => {
                const types = (field.dataset.transferTypes || '').split(' ');
                const matches = types.indexOf(transferType) !== -1;
                const select = field.querySelector('select');

                field.classList.toggle('d-none', !matches);
                select.disabled = !matches;
                select.required = matches;
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            initializeInvoiceSelect();
            syncAccountFields();

            document.getElementById('company_id').addEventListener('change', handleCompanyChange);
            document.getElementById('payment_transfer_type').addEventListener('change', syncAccountFields);
        });
    </script>
@endsection
