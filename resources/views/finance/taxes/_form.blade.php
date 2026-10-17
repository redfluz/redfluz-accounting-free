<div class="row g-3">
    <div class="md:col-4">
        <label class="form-label" for="code">{{ __('taxes.form.code') }}</label>
        <label class="form-control form-adorn">
            <input
                class="form-ghost @error('code') is-invalid @enderror"
                id="code"
                name="code"
                value="{{ old('code', $tax?->code) }}"
                maxlength="50"
                required
            />
        </label>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-8">
        <label class="form-label" for="name">{{ __('taxes.form.name') }}</label>
        <label class="form-control form-adorn">
            <input
                class="form-ghost @error('name') is-invalid @enderror"
                id="name"
                name="name"
                value="{{ old('name', $tax?->name) }}"
                required
            />
        </label>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label" for="rate">{{ __('taxes.form.rate') }}</label>
        <label class="form-control form-adorn">
            <div class="input-group">
                <input
                    class="form-ghost @error('rate') is-invalid @enderror"
                    id="rate"
                    name="rate"
                    type="number"
                    value="{{ old('rate', $tax?->rate) }}"
                    min="0"
                    max="100"
                    step="0.0001"
                    required
                />
                <span class="input-group-text">%</span>
            </div>
        </label>
        @error('rate')
            <div class="fg-danger small">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label" for="default_mode">{{ __('taxes.form.default_mode') }}</label>
        <select
            class="form-control @error('default_mode') is-invalid @enderror"
            id="default_mode"
            name="default_mode"
            required
        >
            @foreach ($modes as $mode)
                <option
                    value="{{ $mode->value }}"
                    @selected(old('default_mode', $tax?->default_mode?->value ?? 'exclusive') === $mode->value)
                >
                    {{ __('ledgers.tax_mode.' . $mode->value) }}
                </option>
            @endforeach
        </select>
        @error('default_mode')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12">
        <div class="switch">
        <input
            id="is_active"
            name="is_active"
            type="checkbox"
            value="1"
            @checked(old('is_active', $tax?->is_active ?? true))
            role="switch" switch />
        </div>
        <label for="is_active">{{ __('taxes.form.is_active') }}</label>
    </div>
</div>
<div class="d-flex justify-content-between mt-4">
    <a class="btn btn-solid theme-secondary" href="{{ route('finance.taxes.index') }}">
        <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
    </a>
    <button class="btn btn-solid theme-primary" type="submit">{{ __('taxes.form.save') }}</button>
</div>
