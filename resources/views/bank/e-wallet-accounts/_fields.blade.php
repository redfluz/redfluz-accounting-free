@php
    use App\Models\Bank\EWalletAccount;
    use Illuminate\Database\Eloquent\Collection;
@endphp
@props([
    /** @var EWalletAccount|null */
    'eWalletAccount' => null,
    /** @var Collection<int, \App\Models\ChartOfAccount\Chart> */
    'charts',
    /** @var Collection<int, \App\Models\Bank\EWalletProvider> */
    'eWalletProviders',
])

{{--
    Wallets are issued by a provider and hold electronic money: no banking
    codes, no account type. Everything a wallet needs fits on one screen.
--}}

<div class="row g-3">
    <div class="md:col-6">
        <label class="form-label fw-bold" for="e_wallet_provider_id"
            >{{ __('banks.fields.e_wallet_provider') }} <span class="fg-danger">*</span></label>
        <select class="form-control @error('e_wallet_provider_id') is-invalid @enderror"
            id="e_wallet_provider_id" name="e_wallet_provider_id" required>
            <option value="">{{ __('common.actions.select_option') }}</option>
            @foreach ($eWalletProviders as $provider)
                <option value="{{ $provider->id }}"
                    @selected(old('e_wallet_provider_id', $eWalletAccount?->e_wallet_provider_id) === $provider->id)>
                    {{ $provider->name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('banks.hints.e_wallet_provider') }}</div>
        @error('e_wallet_provider_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="chart_id"
            >{{ __('banks.fields.chart') }} <span class="fg-danger">*</span></label>
        <select class="form-control @error('chart_id') is-invalid @enderror" id="chart_id" name="chart_id" required>
            <option value="">{{ __('banks.placeholders.chart') }}</option>
            @foreach ($charts as $chart)
                <option value="{{ $chart->id }}" @selected(old('chart_id', $eWalletAccount?->chart_id) === $chart->id)>
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
                value="{{ old('name', $eWalletAccount?->name) }}"
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
                value="{{ old('account_name', $eWalletAccount?->account_name) }}"
                required
            />
        </label>
        @error('account_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="account_number"
            >{{ __('banks.fields.account_number') }}</label>
        <label class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="hash"></i>
            </div>
            <input
                class="form-ghost @error('account_number') is-invalid @enderror"
                id="account_number"
                name="account_number"
                type="text"
                value="{{ old('account_number', $eWalletAccount?->account_number) }}"
            />
        </label>
        @error('account_number')
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
                value="{{ old('country_code', $eWalletAccount?->country_code ?? 'MY') }}"
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
            >{{ old('notes', $eWalletAccount?->notes) }}</textarea>
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
                    @checked(old('is_active', $eWalletAccount?->is_active ?? true))
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
                    @checked(old('is_default', $eWalletAccount?->is_default ?? false))
                    role="switch" switch />
            </div>
            <label class="fw-bold mb-0" for="is_default">{{ __('banks.fields.is_default') }}</label>
        </div>
        <div class="form-text">{{ __('banks.hints.is_default') }}</div>
    </div>
</div>
