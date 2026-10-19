<div class="row">
    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="bank_account_id">
            {{ __('bank_transfers.form.bank_account') }} <span class="fg-danger">*</span>
        </label>
        <select class="form-control @error('bank_account_id') is-invalid @enderror" id="bank_account_id"
            name="bank_account_id" required>
            <option value="">{{ __('bank_transfers.form.bank_account_placeholder') }}</option>
            @foreach ($banks as $bank)
                <option value="{{ $bank->id }}"
                    {{ old('bank_account_id', $transfer?->bank_account_id) === $bank->id ? 'selected' : '' }}>
                    {{ $bank->name }}
                    @if ($bank->chart)
                        ({{ $bank->chart->code }})
                    @endif
                </option>
            @endforeach
        </select>
        @error('bank_account_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="amount">
            {{ __('bank_transfers.form.amount') }} <span class="fg-danger">*</span>
        </label>
        <label class="form-control form-adorn">
            <input class="form-ghost @error('amount') is-invalid @enderror" id="amount" min="0.01" name="amount"
                step="0.01" type="number"
                value="{{ old('amount', $transfer?->amount !== null ? number_format((float) $transfer->amount, 2, '.', '') : '') }}" />
        </label>
        <div class="form-text">
            {{ __('bank_transfers.form.available') }}
            {{ number_format((float) $transitBalance, 2) }}
        </div>
        @error('amount')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="document_date">
            {{ __('bank_transfers.form.date') }} <span class="fg-danger">*</span>
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
            {{ __('bank_transfers.form.reference_number') }}
        </label>
        <label class="form-control form-adorn">
            <input class="form-ghost @error('reference_number') is-invalid @enderror" id="reference_number"
                name="reference_number" type="text" value="{{ old('reference_number', $transfer?->reference_number) }}" />
        </label>
        <div class="form-text">{{ __('bank_transfers.form.reference_hint') }}</div>
        @error('reference_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="description">
            {{ __('bank_transfers.form.description') }}
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
            {{ __('bank_transfers.form.from') }}
        </label>
        <div class="form-control-plaintext fw-semibold">
            {{ $transit->code }} ~ {{ $transit->name }}
        </div>
    </div>

    <div class="col-12 mb-3">
        <label class="form-label fw-bold" for="attachment">
            {{ __('bank_transfers.form.attachment') }}
        </label>
        <input class="form-control @error('attachment') is-invalid @enderror" id="attachment" name="attachment"
            type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" />
        <div class="form-text">{{ __('bank_transfers.form.attachment_hint') }}</div>

        @if ($transfer?->attachment_path)
            <div class="form-text">
                {{ __('bank_transfers.form.attachment_current') }}
                <span class="fw-semibold">{{ $transfer->attachment_name }}</span>
            </div>
        @endif

        @error('attachment')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mb-3">
        <label class="form-label fw-bold" for="notes">
            {{ __('bank_transfers.form.notes') }}
        </label>
        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $transfer?->notes) }}</textarea>
    </div>
</div>
