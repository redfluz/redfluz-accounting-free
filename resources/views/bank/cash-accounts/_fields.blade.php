@php
    use App\Models\Bank\CashAccount;
    use Illuminate\Database\Eloquent\Collection;
@endphp
@props([
    /** @var CashAccount|null */
    'cashAccount' => null,
    /** @var Collection<int, \App\Models\ChartOfAccount\Chart> */
    'charts',
    /** @var Collection<int, \App\Models\Inventory\StockLocation> */
    'stockLocations',
])

{{--
    Cash drawers hold physical money: no provider, no banking codes, no
    account number. Everything a drawer needs fits on one screen.
--}}

<div class="row g-3">
    <div class="md:col-6">
        <label class="form-label fw-bold" for="chart_id"
            >{{ __('banks.fields.chart') }} <span class="fg-danger">*</span></label>
        <select class="form-control @error('chart_id') is-invalid @enderror" id="chart_id" name="chart_id" required>
            <option value="">{{ __('banks.placeholders.chart') }}</option>
            @foreach ($charts as $chart)
                <option value="{{ $chart->id }}" @selected(old('chart_id', $cashAccount?->chart_id) === $chart->id)>
                    {{ $chart->code }} - {{ $chart->name }}
                </option>
            @endforeach
        </select>
        @error('chart_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="name"
            >{{ __('banks.fields.name') }} <span class="fg-danger">*</span></label>
        <label class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="building"></i>
            </div>
            <input
                class="form-ghost @error('name') is-invalid @enderror"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $cashAccount?->name) }}"
                required
            />
        </label>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="account_name"
            >{{ __('banks.fields.account_name') }} <span class="fg-danger">*</span></label>
        <label class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="user"></i>
            </div>
            <input
                class="form-ghost @error('account_name') is-invalid @enderror"
                id="account_name"
                name="account_name"
                type="text"
                value="{{ old('account_name', $cashAccount?->account_name) }}"
                required
            />
        </label>
        @error('account_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="asset_location_id"
            >{{ __('banks.fields.asset_location') }}</label>
        <select class="form-control @error('asset_location_id') is-invalid @enderror"
            id="asset_location_id" name="asset_location_id">
            <option value="">{{ __('common.actions.select_option') }}</option>
            @foreach ($stockLocations as $location)
                <option value="{{ $location->id }}"
                    @selected(old('asset_location_id', $cashAccount?->asset_location_id) === $location->id)>
                    {{ $location->name }}
                </option>
            @endforeach
        </select>
        @error('asset_location_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="float_amount"
            >{{ __('banks.fields.float_amount') }}</label>
        <label class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="coins"></i>
            </div>
            <input
                class="form-ghost @error('float_amount') is-invalid @enderror"
                id="float_amount"
                name="float_amount"
                type="number"
                step="0.01"
                min="0"
                value="{{ old('float_amount', $cashAccount?->float_amount) }}"
            />
        </label>
        @error('float_amount')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="country_code"
            >{{ __('banks.fields.country_code') }} <span class="fg-danger">*</span></label>
        <label class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="flag"></i>
            </div>
            <input
                class="form-ghost"
                id="country_code"
                name="country_code"
                type="text"
                maxlength="2"
                value="{{ old('country_code', $cashAccount?->country_code ?? 'MY') }}"
                required
            />
        </label>
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="notes">{{ __('banks.fields.notes') }}</label>
        <label class="form-control form-adorn" style="align-items:flex-start">
            <div class="form-adorn-icon" aria-hidden="true" style="margin-top:.5rem">
                <i data-lucide="sticky-note"></i>
            </div>
            <textarea
                class="form-ghost"
                id="notes"
                name="notes"
                rows="3"
            >{{ old('notes', $cashAccount?->notes) }}</textarea>
        </label>
    </div>
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mt-2">
            <div class="switch">
                <input
                    id="is_active"
                    name="is_active"
                    type="checkbox"
                    value="1"
                    @checked(old('is_active', $cashAccount?->is_active ?? true))
                    role="switch" switch />
            </div>
            <label class="fw-bold mb-0" for="is_active">{{ __('banks.fields.is_active') }}</label>
        </div>
    </div>
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mt-2">
            <div class="switch">
                <input
                    id="is_default"
                    name="is_default"
                    type="checkbox"
                    value="1"
                    @checked(old('is_default', $cashAccount?->is_default ?? false))
                    role="switch" switch />
            </div>
            <label class="fw-bold mb-0" for="is_default">{{ __('banks.fields.is_default') }}</label>
        </div>
        <div class="form-text">{{ __('banks.hints.is_default') }}</div>
    </div>
</div>
