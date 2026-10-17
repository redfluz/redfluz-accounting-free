{{--
    ============================================================================

    @author      redfluz

    @github      https://github.com/redfluz

    @youtube     https://youtube.com/@redfluz

    @contact     corex2020@icloud.com

    @customer

    @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
    ============================================================================

    PROPRIETARY AND CONFIDENTIAL

    All code, concepts, and intellectual property contained in this file are the
    exclusive property of redfluz. Unauthorized copying, distribution, modification,
    reverse engineering, or public display of this software via any medium is
    strictly prohibited without explicit written permission from the copyright owner.
--}}

@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::CHART_REPORTS, FileAction::ADD);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card xl:col-9 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('chart_reports.create_title') }}</h4>
            <p class="fg-secondary small mb-0">{{ __('chart_reports.form.report_help') }}</p>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('chart-of-account.chart-reports.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="md:col-4">
                        <label class="form-label fw-bold" for="code">
                            {{ __('chart_reports.form.code') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input
                                class="form-ghost @error('code') is-invalid @enderror"
                                id="code"
                                name="code"
                                type="text"
                                value="{{ old('code') }}"
                            />
                        </label>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-8">
                        <label class="form-label fw-bold" for="name">
                            {{ __('chart_reports.form.name') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input
                                class="form-ghost @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                required
                            />
                        </label>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-8">
                        <label class="form-label fw-bold" for="slug">
                            {{ __('chart_reports.form.slug') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input
                                class="form-ghost @error('slug') is-invalid @enderror"
                                id="slug"
                                name="slug"
                                type="text"
                                value="{{ old('slug') }}"
                                placeholder="balance_sheet"
                                required
                            />
                        </label>
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
                            @checked(old('is_default'))
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
                                <option value="{{ $basis }}" @selected(old('calculation_basis', 'period') === $basis)>
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

                <div class="d-flex justify-content-between border-top mt-4 pt-3">
                    <a class="btn btn-solid theme-secondary" href="{{ route('chart-of-account.chart-reports.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>

                    @if ($canCreate)
                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>
                            {{ __('common.actions.save') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
