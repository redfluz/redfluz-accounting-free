{{--
/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */
--}}
@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::SETTINGS, FileAction::UPDATE);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card md:col-10 mx-auto border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('settings.title') }}</h4>
            <p class="fg-secondary small mb-0">{{ __('settings.subtitle') }}</p>
        </div>

        <div class="card-body">
            @include('partials.alerts')
            <form autocomplete="off" action="{{ route('system.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <h6 class="text-uppercase fg-secondary small fw-bold">{{ __('settings.section.company') }}</h6>
                <hr />

                <div class="row">
                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="company_name">
                            {{ __('settings.table.column_company_name') }}
                        </label>
                        <input
                            class="form-control @error('company_name') is-invalid @enderror"
                            id="company_name"
                            name="company_name"
                            type="text"
                            value="{{ old('company_name', $setting->company_name) }}"
                            @disabled(! $canUpdate)
                            required
                        />
                        @error('company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="company_email">
                            {{ __('settings.table.column_company_email') }}
                        </label>
                        <input
                            class="form-control @error('company_email') is-invalid @enderror"
                            id="company_email"
                            name="company_email"
                            type="email"
                            value="{{ old('company_email', $setting->company_email) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="company_phone">
                            {{ __('settings.table.column_company_phone') }}
                        </label>
                        <input
                            class="form-control @error('company_phone') is-invalid @enderror"
                            id="company_phone"
                            name="company_phone"
                            type="text"
                            value="{{ old('company_phone', $setting->company_phone) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="company_mobile">
                            {{ __('settings.fields.mobile') }}
                        </label>
                        <input
                            class="form-control @error('company_mobile') is-invalid @enderror"
                            id="company_mobile"
                            name="company_mobile"
                            type="text"
                            value="{{ old('company_mobile', $setting->company_mobile) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="company_fax">
                            {{ __('settings.table.column_company_fax') }}
                        </label>
                        <input
                            class="form-control @error('company_fax') is-invalid @enderror"
                            id="company_fax"
                            name="company_fax"
                            type="text"
                            value="{{ old('company_fax', $setting->company_fax) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_fax')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="company_website">
                            {{ __('settings.table.column_company_website') }}
                        </label>
                        <input
                            class="form-control @error('company_website') is-invalid @enderror"
                            id="company_website"
                            name="company_website"
                            type="url"
                            value="{{ old('company_website', $setting->company_website) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_website')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="company_registration_no">
                            {{ __('settings.table.column_company_registration_no') }}
                        </label>
                        <input
                            class="form-control @error('company_registration_no') is-invalid @enderror"
                            id="company_registration_no"
                            name="company_registration_no"
                            type="text"
                            value="{{ old('company_registration_no', $setting->company_registration_no) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_registration_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="company_tax_no">
                            {{ __('settings.table.column_company_tax_no') }}
                        </label>
                        <input
                            class="form-control @error('company_tax_no') is-invalid @enderror"
                            id="company_tax_no"
                            name="company_tax_no"
                            type="text"
                            value="{{ old('company_tax_no', $setting->company_tax_no) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_tax_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h6 class="text-uppercase fg-secondary small fw-bold mt-4">{{ __('settings.section.address') }}</h6>
                <hr />

                <div class="mb-3">
                    <label class="form-label fw-bold" for="address">
                        {{ __('settings.table.column_address') }}
                    </label>
                    <textarea
                        class="form-control @error('address') is-invalid @enderror"
                        id="address"
                        name="address"
                        rows="3"
                        @disabled(! $canUpdate)
                    >{{ old('address', $setting->address) }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="md:col-3 mb-3">
                        <label class="form-label fw-bold" for="company_postcode">
                            {{ __('settings.fields.postcode') }}
                        </label>
                        <input
                            class="form-control @error('company_postcode') is-invalid @enderror"
                            id="company_postcode"
                            name="company_postcode"
                            type="text"
                            value="{{ old('company_postcode', $setting->company_postcode) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_postcode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-3 mb-3">
                        <label class="form-label fw-bold" for="company_city">
                            {{ __('settings.fields.city') }}
                        </label>
                        <input
                            class="form-control @error('company_city') is-invalid @enderror"
                            id="company_city"
                            name="company_city"
                            type="text"
                            value="{{ old('company_city', $setting->company_city) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-3 mb-3">
                        <label class="form-label fw-bold" for="company_state">
                            {{ __('settings.fields.state') }}
                        </label>
                        <input
                            class="form-control @error('company_state') is-invalid @enderror"
                            id="company_state"
                            name="company_state"
                            type="text"
                            value="{{ old('company_state', $setting->company_state) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_state')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-3 mb-3">
                        <label class="form-label fw-bold" for="company_country">
                            {{ __('settings.fields.country') }}
                        </label>
                        <input
                            class="form-control @error('company_country') is-invalid @enderror"
                            id="company_country"
                            name="company_country"
                            type="text"
                            value="{{ old('company_country', $setting->company_country) }}"
                            @disabled(! $canUpdate)
                        />
                        @error('company_country')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h6 class="text-uppercase fg-secondary small fw-bold mt-4">{{ __('settings.section.document') }}</h6>
                <hr />

                <div class="row">
                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="currency_id">
                            {{ __('settings.table.column_currency') }}
                        </label>
                        <select
                            class="form-control @error('currency_id') is-invalid @enderror"
                            id="currency_id"
                            name="currency_id"
                            @disabled(! $canUpdate)
                        >
                            <option value="">{{ __('common.actions.select_option') }}</option>
                            @foreach ($currencies as $currency)
                                <option
                                    value="{{ $currency->id }}"
                                    @selected(old('currency_id', $setting->currency_id) === $currency->id)
                                >
                                    {{ $currency->code }} - {{ $currency->name }} {{ $currency->symbol ? '('.$currency->symbol.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('currency_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="logo">
                            {{ __('settings.table.column_logo_path') }}
                        </label>
                        @if ($setting->logo_path)
                            <div class="mb-2">
                                <a
                                    data-fancybox="gallery"
                                    data-caption="{{ $setting->company_name }}"
                                    href="{{ asset('storage/' . $setting->logo_path) }}"
                                >
                                    <img
                                        class="img-thumbnail rounded border"
                                        src="{{ asset('storage/' . $setting->logo_path) }}"
                                        alt="{{ $setting->company_name }}"
                                        style="max-height: 100px; cursor: pointer"
                                    />
                                </a>
                            </div>
                        @endif
                        <input
                            class="form-control @error('logo') is-invalid @enderror"
                            id="logo"
                            name="logo"
                            type="file"
                            accept="{{ \App\Enums\UploadType::IMAGES->accept() }}"
                            @disabled(! $canUpdate)
                        />
                        @error('logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="short_date_format">
                            {{ __('settings.table.column_short_date_format') }}
                        </label>
                        <input
                            class="form-control @error('short_date_format') is-invalid @enderror"
                            id="short_date_format"
                            name="short_date_format"
                            type="text"
                            value="{{ old('short_date_format', $setting->short_date_format ?? 'd/m/Y') }}"
                            @disabled(! $canUpdate)
                        />
                        <div class="form-text">{{ __('settings.help.short_date_format') }}</div>
                        @error('short_date_format')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6 mb-3">
                        <label class="form-label fw-bold" for="long_date_format">
                            {{ __('settings.table.column_long_date_format') }}
                        </label>
                        <input
                            class="form-control @error('long_date_format') is-invalid @enderror"
                            id="long_date_format"
                            name="long_date_format"
                            type="text"
                            value="{{ old('long_date_format', $setting->long_date_format ?? 'd/m/Y H:i:s') }}"
                            @disabled(! $canUpdate)
                        />
                        <div class="form-text">{{ __('settings.help.long_date_format') }}</div>
                        @error('long_date_format')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if ($canUpdate)
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-solid theme-accent">
                            <i class="me-1" data-lucide="save"></i>{{ __('common.actions.save') }}
                        </button>
                    </div>
                @else
                    <div class="alert alert theme-secondary mt-4 mb-0">{{ __('settings.read_only') }}</div>
                @endif
            </form>
        </div>
    </div>
@endsection