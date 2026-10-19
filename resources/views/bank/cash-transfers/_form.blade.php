<div class="row">
    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="cash_account_id">
            {{ __('cash_transfers.form.cash_account') }} <span class="fg-danger">*</span>
        </label>
        <select class="form-control @error('cash_account_id') is-invalid @enderror" id="cash_account_id"
            name="cash_account_id" required>
            <option value="">{{ __('cash_transfers.form.cash_account_placeholder') }}</option>
            @foreach ($accounts as $account)
                @php
                    $figures = $balances[$account->id] ?? null;
                @endphp
                <option value="{{ $account->id }}"
                    data-surplus="{{ $figures['surplus'] ?? '0.00' }}"
                    {{ old('cash_account_id', $transfer?->cash_account_id) === $account->id ? 'selected' : '' }}>
                    {{ $account->name }}
                    @if ($account->chart)
                        ({{ $account->chart->code }})
                    @endif
                    @if ($figures)
                        — {{ __('cash_transfers.form.holding') }}
                        {{ number_format((float) $figures['balance'], 2) }}
                    @endif
                </option>
            @endforeach
        </select>
        @error('cash_account_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="amount">
            {{ __('cash_transfers.form.amount') }} <span class="fg-danger">*</span>
        </label>
        <label class="form-control form-adorn">
            <input class="form-ghost @error('amount') is-invalid @enderror" id="amount" min="0.01" name="amount"
                step="0.01" type="number"
                value="{{ old('amount', $transfer?->amount !== null ? number_format((float) $transfer->amount, 2, '.', '') : '') }}" />
        </label>
        <div class="form-text" id="js-surplus-hint">
            {{ __('cash_transfers.form.surplus_hint') }}
        </div>
        @error('amount')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="document_date">
            {{ __('cash_transfers.form.date') }} <span class="fg-danger">*</span>
        </label>
        <label class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="calendar"></i>
            </div>
            <input class="form-ghost @error('document_date') is-invalid @enderror" id="document_date"
                name="document_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                value="{{ old('document_date', $transfer?->document_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" />
        </label>
        @error('document_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="reference_number">
            {{ __('cash_transfers.form.reference_number') }}
        </label>
        <label class="form-control form-adorn">
            <input class="form-ghost @error('reference_number') is-invalid @enderror" id="reference_number"
                name="reference_number" type="text" value="{{ old('reference_number', $transfer?->reference_number) }}" />
        </label>
        @error('reference_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="description">
            {{ __('cash_transfers.form.description') }}
        </label>
        <label class="form-control form-adorn">
            <input class="form-ghost @error('description') is-invalid @enderror" id="description"
                name="description" type="text" value="{{ old('description', $transfer?->description) }}" />
        </label>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold">
            {{ __('cash_transfers.form.to') }}
        </label>
        <div class="form-control-plaintext fw-semibold">
            {{ $transit->code }} ~ {{ $transit->name }}
        </div>
        <div class="form-text">
            {{ __('cash_transfers.form.transit_balance') }}
            {{ number_format((float) $transitBalance, 2) }}
        </div>
    </div>

    <div class="col-12 mb-3">
        <label class="form-label fw-bold" for="notes">
            {{ __('cash_transfers.form.notes') }}
        </label>
        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $transfer?->notes) }}</textarea>
    </div>
</div>

@section('scripts')
    <script>
        // The amount a drawer can hand over is what it holds above its float, and
        // that changes as the day goes, so it is read off the selected option
        // rather than fixed in the template. Filling it in saves typing a figure
        // that is nearly always the same one; clearing it for a drawer with no
        // surplus says so rather than offering a transfer of nothing.
        (function () {
            var accountSelect = document.getElementById('cash_account_id');
            var amountInput = document.getElementById('amount');
            var hint = document.getElementById('js-surplus-hint');

            if (!accountSelect || !amountInput || !hint) {
                return;
            }

            var template = @json(__('cash_transfers.form.surplus_available'));
            var none = @json(__('cash_transfers.form.no_surplus'));

            function refresh() {
                var option = accountSelect.options[accountSelect.selectedIndex];

                if (!option || !option.value) {
                    hint.textContent = '';
                    return;
                }

                var surplus = parseFloat(option.getAttribute('data-surplus')) || 0;

                if (surplus <= 0) {
                    hint.textContent = none;
                    return;
                }

                hint.textContent = template.replace(':amount', surplus.toFixed(2));

                if (amountInput.value === '' || parseFloat(amountInput.value) > surplus) {
                    amountInput.value = surplus.toFixed(2);
                }
            }

            accountSelect.addEventListener('change', refresh);
            refresh();
        })();
    </script>
@endsection
