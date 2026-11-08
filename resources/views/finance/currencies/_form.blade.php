@php
    use App\Models\Finance\Currency;
@endphp

@props([
    /** @var Currency|null */
    'currency' => null,
])

<div class="mb-3">
    <label class="form-label fw-bold" for="code">
        {{ __('currencies.table.column_code') }}
    </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('code') is-invalid @enderror"
            id="code"
            name="code"
            type="text"
            value="{{ old('code', $currency?->code) }}"
            placeholder="e.g. USD"
            required
        />
    </div>
    @error('code')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="name">
        {{ __('currencies.table.column_name') }}
    </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $currency?->name) }}"
            placeholder="e.g. US Dollar"
            required
        />
    </div>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="symbol">
        {{ __('currencies.table.column_symbol') }}
    </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('symbol') is-invalid @enderror"
            id="symbol"
            name="symbol"
            type="text"
            value="{{ old('symbol', $currency?->symbol) }}"
            placeholder="e.g. $"
        />
    </div>
    @error('symbol')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <input
        class="check"
        id="is_default"
        name="is_default"
        type="checkbox"
        value="1"
        {{ old('is_default', $currency?->is_default) ? 'checked' : '' }}
    />
    <label for="is_default">
        {{ __('currencies.table.column_default') }}
    </label>
</div>

<div class="mb-3">
    <input
        class="check"
        id="is_active"
        name="is_active"
        type="checkbox"
        value="1"
        {{ old('is_active', $currency?->is_active ?? true) ? 'checked' : '' }}
    />
    <label for="is_active"> {{ __('currencies.table.column_active') }} </label>
</div>
