<div class="mb-3">
    <label class="form-label fw-bold" for="name">
        {{ __('e_wallet_providers.form.name') }} <span class="fg-danger">*</span>
    </label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $provider?->name) }}"
            required
        />
    </label>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="country">{{ __('e_wallet_providers.form.country') }}</label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('country') is-invalid @enderror"
            id="country"
            name="country"
            type="text"
            value="{{ old('country', $provider?->country) }}"
        />
    </label>
    @error('country')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="identifier_label">{{ __('e_wallet_providers.form.identifier_label') }}</label>
    <label class="form-control form-adorn">
        <input
            class="form-ghost @error('identifier_label') is-invalid @enderror"
            id="identifier_label"
            name="identifier_label"
            type="text"
            value="{{ old('identifier_label', $provider?->identifier_label) }}"
        />
    </label>
    @error('identifier_label')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="chart_id">{{ __('e_wallet_providers.form.chart') }}</label>
    <select class="form-control @error('chart_id') is-invalid @enderror" id="chart_id" name="chart_id">
        <option value="">{{ __('e_wallet_providers.form.chart_placeholder') }}</option>
        @foreach ($charts as $chart)
            <option value="{{ $chart->id }}" @selected(old('chart_id', $provider?->chart_id) === $chart->id)>
                {{ $chart->code }} - {{ $chart->name }}
            </option>
        @endforeach
    </select>
    @error('chart_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-4">
<input name="is_active" type="hidden" value="0" />
    <div class="switch">
    <input
        id="is_active"
        name="is_active"
        type="checkbox"
        value="1"
        @checked(old('is_active', $provider?->is_active ?? true))
        role="switch" switch />
    </div>
    <label class="fw-bold" for="is_active">{{ __('e_wallet_providers.form.is_active') }}</label>
</div>
