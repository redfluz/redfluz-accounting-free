@php
    use App\Models\ChartOfAccount\ChartReport;
@endphp
@props([
    /** @var ChartReport|null */
    'chartReport' => null,
])

<div class="row g-3">
    <div class="md:col-4">
        <label class="form-label fw-bold" for="code">
            {{ __('chart_reports.form.code') }}
        </label>
        <div class="form-control form-adorn">
            <input
                class="form-ghost @error('code') is-invalid @enderror"
                id="code"
                name="code"
                type="text"
                value="{{ old('code', $chartReport?->code) }}"
            />
        </div>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-8">
        <label class="form-label fw-bold" for="name">
            {{ __('chart_reports.form.name') }}
        </label>
        <div class="form-control form-adorn">
            <input
                class="form-ghost @error('name') is-invalid @enderror"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $chartReport?->name) }}"
                required
            />
        </div>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-8">
        <label class="form-label fw-bold" for="slug">
            {{ __('chart_reports.form.slug') }}
        </label>
        <div class="form-control form-adorn">
            <input
                class="form-ghost @error('slug') is-invalid @enderror"
                id="slug"
                name="slug"
                type="text"
                value="{{ old('slug', $chartReport?->slug) }}"
                placeholder="balance_sheet"
                required
            />
        </div>
        <div class="form-text">{{ __('chart_reports.form.slug_help') }}</div>
        @error('slug')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-4 d-flex align-items-center md:pt-4">
        <div class="switch">
        <input
            id="is_default"
            name="is_default"
            type="checkbox"
            value="1"
            @checked(old('is_default', $chartReport?->is_default))
            role="switch" switch />
        </div>
        <label for="is_default">
            {{ __('chart_reports.form.is_default') }}
        </label>
    </div>

    <div class="md:col-6">
        <label class="form-label fw-bold" for="calculation_basis">
            {{ __('chart_reports.form.calculation_basis') }}
        </label>
        <select
            class="form-control @error('calculation_basis') is-invalid @enderror"
            id="calculation_basis"
            name="calculation_basis"
            required
        >
            @foreach (['period', 'as_of'] as $basis)
                <option value="{{ $basis }}" @selected(old('calculation_basis', $chartReport?->calculation_basis ?? 'period') === $basis)>
                    {{ __('chart_reports.calculation_basis.' . $basis) }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('chart_reports.form.calculation_basis_help') }}</div>
        @error('calculation_basis')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
