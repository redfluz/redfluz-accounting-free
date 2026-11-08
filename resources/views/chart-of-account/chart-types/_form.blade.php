@php
    use App\Models\ChartOfAccount\ChartType;
@endphp
@props([
    /** @var ChartType|null */
    'chartType' => null,
])

<div class="mb-3">
    <label class="form-label fw-bold" for="name">
        {{ __('chart_types.table.column_name') }}
    </label>

    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $chartType?->name) }}"
            required
        />
    </div>

    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@php
    $selectedType = old('account_type', match (true) {
        (bool) ($chartType?->isAsset ?? false) => 'asset',
        (bool) ($chartType?->isLiability ?? false) => 'liability',
        (bool) ($chartType?->isEquity ?? false) => 'equity',
        (bool) ($chartType?->isIncome ?? false) => 'income',
        (bool) ($chartType?->isExpenses ?? false) => 'expenses',
        (bool) ($chartType?->isBank ?? false) => 'bank',
        default => null,
    });
@endphp

<fieldset class="mb-3" style="border: none; padding: 0; margin: 0;">
    <legend class="form-label fw-bold"> Account Type </legend>

    <div class="row g-3">
        <div class="md:col-4">
            <input
                class="check"
                id="isAsset"
                name="account_type"
                type="radio"
                value="asset"
                {{ $selectedType === 'asset' ? 'checked' : '' }}
                required
            />
            <label for="isAsset">
                {{ __('chart_types.flags.asset') }}
            </label>
        </div>

        <div class="md:col-4">
            <input
                class="check"
                id="isLiability"
                name="account_type"
                type="radio"
                value="liability"
                {{ $selectedType === 'liability' ? 'checked' : '' }}
            />
            <label for="isLiability">
                {{ __('chart_types.flags.liability') }}
            </label>
        </div>

        <div class="md:col-4">
            <input
                class="check"
                id="isEquity"
                name="account_type"
                type="radio"
                value="equity"
                {{ $selectedType === 'equity' ? 'checked' : '' }}
            />
            <label for="isEquity">
                {{ __('chart_types.flags.equity') }}
            </label>
        </div>

        <div class="md:col-4">
            <input
                class="check"
                id="isIncome"
                name="account_type"
                type="radio"
                value="income"
                {{ $selectedType === 'income' ? 'checked' : '' }}
            />
            <label for="isIncome">
                {{ __('chart_types.flags.income') }}
            </label>
        </div>

        <div class="md:col-4">
            <input
                class="check"
                id="isExpenses"
                name="account_type"
                type="radio"
                value="expenses"
                {{ $selectedType === 'expenses' ? 'checked' : '' }}
            />
            <label for="isExpenses">
                {{ __('chart_types.flags.expenses') }}
            </label>
        </div>
    </div>

    @error('account_type')
        <div class="fg-danger small mt-2">{{ $message }}</div>
    @enderror
</fieldset>
