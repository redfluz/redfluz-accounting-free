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
    use App\Enums\ChartReportGroupEnum;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\ChartOfAccount\ChartType;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::CHARTS, FileAction::UPDATE);
    $currentReportGroup =
        $chart->report_group instanceof \BackedEnum ? $chart->report_group->value : $chart->report_group;
@endphp
@props([
    /** @var ChartType */
    'type',
])
@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">
                <i class="me-1" data-lucide="pencil"></i>
                {{ __('common.actions.edit') }}
            </h4>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" action="{{ route('chart-of-account.charts.update', $chart) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-bold" for="chart_type_id">
                        {{ __('charts.table.column_type') }}
                    </label>

                    <select
                        class="form-control @error('chart_type_id') is-invalid @enderror"
                        id="chart_type_id"
                        name="chart_type_id"
                        required
                    >
                        <option value="">{{ __('charts.form.report_group_select_type') }}</option>

                        @foreach ($chartTypes as $type)
                            <option
                                value="{{ $type->id }}"
                                {{ old('chart_type_id', $chart->chart_type_id) == $type->id ? 'selected' : '' }}
                            >
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('chart_type_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="report_group">
                        {{ __('charts.form.report_group') }}
                    </label>
                    <select
                        class="form-control @error('report_group') is-invalid @enderror"
                        id="report_group"
                        name="report_group"
                    >
                        <option value="">{{ __('common.actions.select_option') }}</option>
                        @foreach (ChartReportGroupEnum::cases() as $reportGroup)
                            <option
                                value="{{ $reportGroup->value }}"
                                @selected(old('report_group', $currentReportGroup) === $reportGroup->value)
                            >
                                {{ __('charts.report_group.' . $reportGroup->value) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('charts.form.report_group_help') }}</div>
                    @error('report_group')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="code"> {{ __('charts.table.column_code') }} </label>

                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('code') is-invalid @enderror"
                            id="code"
                            name="code"
                            type="text"
                            value="{{ old('code', $chart->code) }}"
                            required
                        />
                    </label>

                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="name"> {{ __('charts.table.column_name') }} </label>

                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $chart->name) }}"
                            required
                        />
                    </label>

                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex mb-3 flex-wrap gap-4">
                    @foreach (['isBank' => 'column_bank', 'isDebtor' => 'column_debtor', 'isCreditor' => 'column_creditor'] as $field => $label)
                        <div class="switch">
                        <input
                            id="{{ $field }}"
                            name="{{ $field }}"
                            type="checkbox"
                            value="1"
                            @checked(old($field, $chart->{$field}))
                            role="switch" switch />
                        </div>
                        <label
                            for="{{ $field }}"
                        >{{ __('charts.table.' . $label) }}</label>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('chart-of-account.charts.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>

                    @if ($canUpdate)
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

@section('script')
    <script type="module">
        const chartTypeSelect = document.getElementById('chart_type_id');
        const reportGroupSelect = document.getElementById('report_group');
        const reportGroupsByChartType = @json($reportGroupsByChartType);
        const selectOptionText = @json(__('common.actions.select_option'));
        const selectTypeText = @json(__('charts.form.report_group_select_type'));
        const emptyGroupText = @json(__('charts.form.report_group_empty'));

        function updateReportGroupOptions() {
            const chartTypeId = chartTypeSelect.value;
            const allowedGroups = reportGroupsByChartType[chartTypeId] ?? [];
            const currentGroup = reportGroupSelect.value;

            for (const option of reportGroupSelect.options) {
                if (option.value === '') {
                    continue;
                }

                const isAllowed = allowedGroups.includes(option.value);
                option.disabled = !isAllowed;
                option.hidden = !isAllowed;
            }

            if (currentGroup !== '' && !allowedGroups.includes(currentGroup)) {
                reportGroupSelect.value = '';
            }

            reportGroupSelect.options[0].textContent = !chartTypeId
                ? selectTypeText
                : allowedGroups.length === 0
                  ? emptyGroupText
                  : selectOptionText;
            reportGroupSelect.disabled = !chartTypeId || allowedGroups.length === 0;
        }

        chartTypeSelect.addEventListener('change', updateReportGroupOptions);
        updateReportGroupOptions();
    </script>
@endsection
