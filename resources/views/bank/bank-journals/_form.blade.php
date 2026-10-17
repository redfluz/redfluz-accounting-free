<div class="row">
    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="bank_account_id">
            {{ __('bank_journals.form.account') }} <span class="fg-danger">*</span>
        </label>
        <select class="form-control @error('bank_account_id') is-invalid @enderror" id="bank_account_id"
            name="bank_account_id" required>
            <option value="">{{ __('bank_journals.form.account_placeholder') }}</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected(old('bank_account_id', $journal?->bank_account_id ?? $journal?->cash_account_id ?? $journal?->e_wallet_account_id) === $account->id)>
                    {{ $account->name }}
                    @if ($account->chart)
                        ({{ $account->chart->code }} - {{ $account->chart->name }})
                    @endif
                </option>
            @endforeach
        </select>
        @error('bank_account_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="direction">
            {{ __('bank_journals.form.direction') }} <span class="fg-danger">*</span>
        </label>
        <select class="form-control @error('direction') is-invalid @enderror" id="direction" name="direction"
            required>
            <option value="in" @selected(old('direction', $journal?->direction ?? 'in') === 'in')>
                {{ __('bank_journals.form.direction_in') }}
            </option>
            <option value="out" @selected(old('direction', $journal?->direction ?? 'in') === 'out')>
                {{ __('bank_journals.form.direction_out') }}
            </option>
        </select>
        @error('direction')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="document_date">
            {{ __('bank_journals.form.date') }} <span class="fg-danger">*</span>
        </label>
        <label class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="calendar"></i>
            </div>
            <input class="form-ghost @error('document_date') is-invalid @enderror" id="document_date"
                name="document_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                value="{{ old('document_date', $journal?->document_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" />
        </label>
        @error('document_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="amount">
            {{ __('bank_journals.form.amount') }} <span class="fg-danger">*</span>
        </label>
        <label class="form-control form-adorn">
            <input class="form-ghost @error('amount') is-invalid @enderror" id="amount" min="0.01"
                name="amount" step="0.01" type="number"
                value="{{ old('amount', $journal?->amount !== null ? number_format((float) $journal->amount, 2, '.', '') : '') }}" />
        </label>
        @error('amount')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="offset_chart_id">
            {{ __('bank_journals.form.offset_chart') }} <span class="fg-danger">*</span>
        </label>
        <select class="form-control js-account-select @error('offset_chart_id') is-invalid @enderror"
            id="offset_chart_id" name="offset_chart_id" required>
            <option value="">{{ __('bank_journals.form.offset_chart_placeholder') }}</option>
            @foreach ($charts as $chart)
                <option value="{{ $chart->id }}" @selected(old('offset_chart_id', $journal?->offset_chart_id) === $chart->id)>
                    {{ $chart->code }} - {{ $chart->name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('bank_journals.form.offset_chart_hint') }}</div>
        @error('offset_chart_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-6 mb-3">
        <label class="form-label fw-bold" for="description">
            {{ __('bank_journals.form.description') }}
        </label>
        <label class="form-control form-adorn">
            <input class="form-ghost @error('description') is-invalid @enderror" id="description"
                name="description" type="text" value="{{ old('description', $journal?->description) }}" />
        </label>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mb-3">
        <label class="form-label fw-bold" for="notes">
            {{ __('bank_journals.form.notes') }}
        </label>
        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $journal?->notes) }}</textarea>
    </div>
</div>
