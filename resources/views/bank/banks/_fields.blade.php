@php
    use App\Models\Bank\Bank;
    use Illuminate\Database\Eloquent\Collection;
@endphp
@props([
    /** @var Bank|null */
    'bank' => null,
    /** @var Collection<int, \App\Models\ChartOfAccount\Chart> */
    'charts',
])

<style>
    .bank-tabs .nav-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        font-weight: 500;
        color: var(--body-color);
        border: none;
        border-bottom: 2px solid transparent;
    }
    .bank-tabs .nav-link:hover {
        border-bottom-color: color-mix(in oklch, var(--primary-base) 30%, transparent);
    }
    .bank-tabs .nav-link.active {
        color: var(--primary-base);
        border-bottom-color: var(--primary-base);
        background: transparent;
    }
    .bank-tabs .nav-link .tab-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in oklch, var(--primary-base) 10%, transparent);
        color: var(--primary-base);
        flex-shrink: 0;
    }
    .bank-tabs .nav-link.active .tab-icon {
        background: var(--primary-base);
        color: white;
    }
</style>

<ul class="nav nav-tabs bank-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <button
            class="nav-link active"
            id="account-tab"
            data-bs-toggle="tab"
            data-bs-target="#account-pane"
            type="button"
            role="tab"
            aria-controls="account-pane"
            aria-selected="true"
        >
            <span class="tab-icon"><i class="fs-9" data-lucide="credit-card"></i></span>
            {{ __('banks.sections.account') }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button
            class="nav-link"
            id="banking-tab"
            data-bs-toggle="tab"
            data-bs-target="#banking-pane"
            type="button"
            role="tab"
            aria-controls="banking-pane"
            aria-selected="false"
        >
            <span class="tab-icon"><i class="fs-9" data-lucide="landmark"></i></span>
            {{ __('banks.sections.banking') }}
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button
            class="nav-link"
            id="contact-tab"
            data-bs-toggle="tab"
            data-bs-target="#contact-pane"
            type="button"
            role="tab"
            aria-controls="contact-pane"
            aria-selected="false"
        >
            <span class="tab-icon"><i class="fs-9" data-lucide="contact"></i></span>
            {{ __('banks.sections.contact') }}
        </button>
    </li>
</ul>

<div class="tab-content">
    {{-- Maklumat Akaun --}}
    <div class="tab-pane fade show active" id="account-pane" role="tabpanel" aria-labelledby="account-tab">
        <div class="row g-3">
            <div class="md:col-6">
                <label class="form-label fw-bold" for="chart_id"
                    >{{ __('banks.fields.chart') }} <span class="fg-danger">*</span></label>
                <select class="form-control @error('chart_id') is-invalid @enderror" id="chart_id" name="chart_id" required>
                    <option value="">{{ __('banks.placeholders.chart') }}</option>
                    @foreach ($charts as $chart)
                        <option value="{{ $chart->id }}" @selected(old('chart_id', $bank?->chart_id) === $chart->id)>
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
                        value="{{ old('name', $bank?->name) }}"
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
                        value="{{ old('account_name', $bank?->account_name) }}"
                        required
                    />
                </label>
                @error('account_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold" for="account_number"
                    >{{ __('banks.fields.account_number') }} <span class="fg-danger">*</span></label>
                <label class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="hash"></i>
                    </div>
                    <input
                        class="form-ghost @error('account_number') is-invalid @enderror"
                        id="account_number"
                        name="account_number"
                        type="text"
                        value="{{ old('account_number', $bank?->account_number) }}"
                        required
                    />
                </label>
                @error('account_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold" for="account_type">{{ __('banks.fields.account_type') }}</label>
                <label class="form-control form-adorn">
                    <input
                        class="form-ghost"
                        id="account_type"
                        name="account_type"
                        type="text"
                        value="{{ old('account_type', $bank?->account_type) }}"
                        placeholder="{{ __('banks.placeholders.account_type') }}"
                    />
                </label>
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold" for="branch_name">{{ __('banks.fields.branch_name') }}</label>
                <label class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="map-pin"></i>
                    </div>
                    <input
                        class="form-ghost"
                        id="branch_name"
                        name="branch_name"
                        type="text"
                        value="{{ old('branch_name', $bank?->branch_name) }}"
                    />
                </label>
            </div>
        </div>
    </div>

    {{-- Kod Perbankan --}}
    <div class="tab-pane fade" id="banking-pane" role="tabpanel" aria-labelledby="banking-tab">
        <div class="row g-3">
            <div class="md:col-6">
                <label class="form-label fw-bold" for="bank_code">{{ __('banks.fields.bank_code') }}</label>
                <label class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="code"></i>
                    </div>
                    <input
                        class="form-ghost @error('bank_code') is-invalid @enderror"
                        id="bank_code"
                        name="bank_code"
                        type="text"
                        value="{{ old('bank_code', $bank?->bank_code) }}"
                    />
                </label>
                @error('bank_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold" for="swift_code">{{ __('banks.fields.swift_code') }}</label>
                <label class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="globe"></i>
                    </div>
                    <input
                        class="form-ghost @error('swift_code') is-invalid @enderror"
                        id="swift_code"
                        name="swift_code"
                        type="text"
                        value="{{ old('swift_code', $bank?->swift_code) }}"
                    />
                </label>
                @error('swift_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold" for="iban">{{ __('banks.fields.iban') }}</label>
                <label class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="file-text"></i>
                    </div>
                    <input
                        class="form-ghost @error('iban') is-invalid @enderror"
                        id="iban"
                        name="iban"
                        type="text"
                        value="{{ old('iban', $bank?->iban) }}"
                    />
                </label>
                @error('iban')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold" for="routing_number">{{ __('banks.fields.routing_number') }}</label>
                <label class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="route"></i>
                    </div>
                    <input
                        class="form-ghost @error('routing_number') is-invalid @enderror"
                        id="routing_number"
                        name="routing_number"
                        type="text"
                        value="{{ old('routing_number', $bank?->routing_number) }}"
                    />
                </label>
                @error('routing_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-3">
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
                        value="{{ old('country_code', $bank?->country_code ?? 'MY') }}"
                        required
                    />
                </label>
            </div>
        </div>
    </div>

    {{-- Maklumat Perhubungan --}}
    <div class="tab-pane fade" id="contact-pane" role="tabpanel" aria-labelledby="contact-tab">
        <div class="row g-3">
            <div class="md:col-4">
                <label class="form-label fw-bold" for="email">{{ __('banks.fields.email') }}</label>
                <label class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="mail"></i>
                    </div>
                    <input
                        class="form-ghost @error('email') is-invalid @enderror"
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $bank?->email) }}"
                    />
                </label>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-4">
                <label class="form-label fw-bold" for="phone">{{ __('banks.fields.phone') }}</label>
                <label class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="phone"></i>
                    </div>
                    <input
                        class="form-ghost @error('phone') is-invalid @enderror"
                        id="phone"
                        name="phone"
                        type="text"
                        value="{{ old('phone', $bank?->phone) }}"
                    />
                </label>
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-4">
                <label class="form-label fw-bold" for="website">{{ __('banks.fields.website') }}</label>
                <label class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="globe"></i>
                    </div>
                    <input
                        class="form-ghost @error('website') is-invalid @enderror"
                        id="website"
                        name="website"
                        type="url"
                        value="{{ old('website', $bank?->website) }}"
                    />
                </label>
                @error('website')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold" for="address">{{ __('banks.fields.address') }}</label>
                <label class="form-control form-adorn" style="align-items:flex-start">
                    <div class="form-adorn-icon" aria-hidden="true" style="margin-top:.5rem">
                        <i data-lucide="map-pin"></i>
                    </div>
                    <textarea
                        class="form-ghost"
                        id="address"
                        name="address"
                        rows="3"
                    >{{ old('address', $bank?->address) }}</textarea>
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
                    >{{ old('notes', $bank?->notes) }}</textarea>
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
                            @checked(old('is_active', $bank?->is_active ?? true))
                            role="switch" switch />
                    </div>
                    <label class="fw-bold mb-0" for="is_active">{{ __('banks.fields.is_active') }}</label>
                </div>
            </div>

            {{-- The one account of its kind that money leaves from when nobody says. --}}
            <div class="col-12">
                <div class="d-flex align-items-center gap-2 mt-2">
                    <div class="switch">
                        <input
                            id="is_default"
                            name="is_default"
                            type="checkbox"
                            value="1"
                            @checked(old('is_default', $bank?->is_default ?? false))
                            role="switch" switch />
                    </div>
                    <label class="fw-bold mb-0" for="is_default">{{ __('banks.fields.is_default') }}</label>
                </div>
                <div class="form-text">{{ __('banks.hints.is_default') }}</div>
            </div>
        </div>
    </div>
</div>
