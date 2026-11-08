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
    <div class="card xl:col-10 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('chart_reports.create_row_title') }}</h4>
            <p class="fg-secondary small mb-0">{{ $chartReport->name }}</p>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('chart-of-account.chart-reports.details.store', $chartReport) }}" method="POST">
                @csrf
                @include('chart-of-account.chart-reports.details._form', ['chartReportDetail' => null])

                <div class="d-flex justify-content-between border-top mt-4 pt-3">
                    <a
                        class="btn btn-solid theme-secondary"
                        href="{{ route('chart-of-account.chart-reports.edit', $chartReport) }}"
                    >
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
