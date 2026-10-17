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
    use App\Enums\ChartReportTypeEnum;
    use App\Enums\ChartTotalReportTypeEnum;
    use App\Enums\ChartTypeEnum;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::CHART_REPORTS, FileAction::UPDATE);
    $currentReportType = $chartReportDetail->chart_report_type->value ?? $chartReportDetail->chart_report_type;
    $currentTotalType =
        $chartReportDetail->chart_total_report_type->value ?? $chartReportDetail->chart_total_report_type;
    $currentChartType = $chartReportDetail->chart_type->value ?? $chartReportDetail->chart_type;
    $selectedReportType = old('chart_report_type', $currentReportType);
    $currentReportGroup =
        $chartReportDetail->chart_report_group instanceof \BackedEnum
            ? $chartReportDetail->chart_report_group->value
            : $chartReportDetail->chart_report_group;
@endphp

@extends('layouts.app')

@section('content')
    <div class="card xl:col-10 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('chart_reports.edit_row_title') }}</h4>
            <p class="fg-secondary small mb-0">{{ $chartReport->name }} | {{ $chartReportDetail->code }}</p>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off"
                action="{{ route('chart-of-account.chart-reports.details.update', [$chartReport, $chartReportDetail]) }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="md:col-3">
                        <label class="form-label fw-bold" for="line">
                            {{ __('chart_reports.form.line') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input
                                class="form-ghost @error('line') is-invalid @enderror"
                                id="line"
                                name="line"
                                type="number"
                                value="{{ old('line', $chartReportDetail->line) }}"
                                min="1"
                                required
                            />
                        </label>
                        @error('line')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-3">
                        <label class="form-label fw-bold" for="code">
                            {{ __('chart_reports.form.row_code') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input
                                class="form-ghost @error('code') is-invalid @enderror"
                                id="code"
                                name="code"
                                type="text"
                                value="{{ old('code', $chartReportDetail->code) }}"
                                required
                            />
                        </label>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6">
                        <label class="form-label fw-bold" for="name">
                            {{ __('chart_reports.form.row_name') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input
                                class="form-ghost @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $chartReportDetail->name) }}"
                                required
                            />
                        </label>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6">
                        <label class="form-label fw-bold" for="parent_id">
                            {{ __('chart_reports.form.parent') }}
                        </label>
                        <select
                            class="form-control @error('parent_id') is-invalid @enderror"
                            id="parent_id"
                            name="parent_id"
                        >
                            <option value="">{{ __('chart_reports.form.no_parent') }}</option>
                            @foreach ($parents as $parent)
                                <option
                                    value="{{ $parent->id }}"
                                    @selected(old('parent_id', $chartReportDetail->parent_id) === $parent->id)
                                >
                                    {{ $parent->line }} - {{ $parent->code }} - {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-3">
                        <label class="form-label fw-bold" for="chart_report_type">
                            {{ __('chart_reports.form.row_type') }}
                        </label>
                        <select
                            class="form-control @error('chart_report_type') is-invalid @enderror"
                            id="chart_report_type"
                            name="chart_report_type"
                            required
                        >
                            @foreach (ChartReportTypeEnum::cases() as $reportType)
                                @continue($reportType === ChartReportTypeEnum::CATEGORIES)
                                <option
                                    value="{{ $reportType->value }}"
                                    @selected($selectedReportType === $reportType->value)
                                >
                                    {{ __('chart_reports.row_type.' . $reportType->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('chart_report_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-3">
                        <label class="form-label fw-bold" for="chart_total_report_type">
                            {{ __('chart_reports.form.total_type') }}
                        </label>
                        <select
                            class="form-control @error('chart_total_report_type') is-invalid @enderror"
                            id="chart_total_report_type"
                            name="chart_total_report_type"
                            required
                        >
                            @foreach (ChartTotalReportTypeEnum::cases() as $totalType)
                                <option
                                    value="{{ $totalType->value }}"
                                    @selected(old('chart_total_report_type', $currentTotalType) === $totalType->value)
                                >
                                    {{ __('chart_reports.total_type.' . $totalType->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('chart_total_report_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 report-source" id="chart_source_group">
                        <label class="form-label fw-bold" for="chart_id">
                            {{ __('chart_reports.form.chart') }}
                        </label>
                        <select
                            class="form-control @error('chart_id') is-invalid @enderror"
                            id="chart_id"
                            name="chart_id"
                        >
                            <option value="">{{ __('chart_reports.form.select') }}</option>
                            @foreach ($charts as $chart)
                                <option
                                    value="{{ $chart->id }}"
                                    @selected(old('chart_id', $chartReportDetail->chart_id) === $chart->id)
                                >
                                    {{ $chart->code }} - {{ $chart->name }} - {{ $chart->chartType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('chart_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6 report-source" id="chart_type_source_group">
                        <label class="form-label fw-bold" for="chart_type">
                            {{ __('chart_reports.form.chart_type') }}
                        </label>
                        <select
                            class="form-control @error('chart_type') is-invalid @enderror"
                            id="chart_type"
                            name="chart_type"
                        >
                            <option value="">{{ __('chart_reports.form.select') }}</option>
                            @foreach (ChartTypeEnum::cases() as $chartType)
                                <option
                                    value="{{ $chartType->value }}"
                                    @selected(old('chart_type', $currentChartType) === $chartType->value)
                                >
                                    {{ __('chart_reports.chart_type.' . $chartType->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('chart_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-6 report-source" id="report_group_source_group">
                        <label class="form-label fw-bold" for="chart_report_group">
                            {{ __('chart_reports.form.report_group') }}
                        </label>
                        <select
                            class="form-control @error('chart_report_group') is-invalid @enderror"
                            id="chart_report_group"
                            name="chart_report_group"
                        >
                            <option value="">{{ __('chart_reports.form.select') }}</option>
                            @foreach (ChartReportGroupEnum::cases() as $reportGroup)
                                <option
                                    value="{{ $reportGroup->value }}"
                                    @selected(old('chart_report_group', $currentReportGroup) === $reportGroup->value)
                                >
                                    {{ __('chart_reports.report_group.' . $reportGroup->value) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('chart_reports.form.report_group_help') }}</div>
                        @error('chart_report_group')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 report-source" id="formula_source_group">
                        <label class="form-label fw-bold" for="formula">
                            {{ __('chart_reports.form.formula') }}
                        </label>
                        <textarea
                            class="form-control font-monospace @error('formula') is-invalid @enderror"
                            id="formula"
                            name="formula"
                            rows="3"
                            placeholder="{SUM_TYPE:INCOME} - {SUM_TYPE:EXPENSES}"
                        >{{ old('formula', $chartReportDetail->formula) }}</textarea>
                        <div class="form-text">{{ __('chart_reports.form.formula_help') }}</div>
                        <div class="alert small mb-0 mt-2 border py-2">
                            <div class="fw-semibold mb-1">{{ __('chart_reports.form.formula_help') }}</div>
                            <ul class="mb-2 ps-3">
                                <li>
                                    {{ __('chart_reports.form.formula_examples.account') }}:
                                    <code>{CHART:1100}</code>
                                </li>
                                <li>
                                    {{ __('chart_reports.form.formula_examples.type') }}:
                                    <code>{SUM_TYPE:ASSET}</code>
                                </li>
                                <li>
                                    {{ __('chart_reports.form.formula_examples.group') }}:
                                    <code>{SUM_GROUP:cash_and_cash_equivalents}</code>
                                </li>
                                <li>
                                    {{ __('chart_reports.form.formula_examples.row') }}:
                                    <code>{ROW:NET_OPERATING_CASH_FLOW}</code>
                                </li>
                                <li>
                                    {{ __('chart_reports.form.formula_examples.calculation') }}:
                                    <code>{SUM_TYPE:INCOME} - {SUM_TYPE:EXPENSES}</code>
                                </li>
                            </ul>
                            <div>{{ __('chart_reports.form.formula_examples.aliases') }}</div>
                            <div>{{ __('chart_reports.form.formula_examples.operators') }}</div>

                            <details class="mt-2">
                                <summary class="fw-semibold">
                                    {{ __('chart_reports.form.formula_examples.available_types') }}
                                </summary>
                                <div class="d-flex mt-2 flex-wrap gap-2">
                                    @foreach (ChartTypeEnum::cases() as $chartType)
                                        @continue($chartType === ChartTypeEnum::NONE)
                                        <span class="rounded border bg-2 px-2 py-1">
                                            <code>{{ mb_strtoupper($chartType->value) }}</code>
                                            - {{ __('chart_reports.chart_type.' . $chartType->value) }}
                                        </span>
                                    @endforeach
                                </div>
                            </details>

                            <details class="mt-2">
                                <summary class="fw-semibold">
                                    {{ __('chart_reports.form.formula_examples.available_groups') }}
                                </summary>
                                <div class="d-flex mt-2 flex-wrap gap-2">
                                    @foreach (ChartReportGroupEnum::cases() as $reportGroup)
                                        <span class="rounded border bg-2 px-2 py-1">
                                            <code>{{ $reportGroup->value }}</code>
                                            - {{ __('chart_reports.report_group.' . $reportGroup->value) }}
                                        </span>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                        @error('formula')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold" for="notes">
                            {{ __('chart_reports.form.notes') }}
                        </label>
                        <textarea
                            class="form-control @error('notes') is-invalid @enderror"
                            id="notes"
                            name="notes"
                            rows="3"
                        >{{ old('notes', $chartReportDetail->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between border-top mt-4 pt-3">
                    <a
                        class="btn btn-solid theme-secondary"
                        href="{{ route('chart-of-account.chart-reports.edit', $chartReport) }}"
                    >
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>

                    @if ($canUpdate)
                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>
                            {{ __('common.actions.update') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script type="module">
        const reportType = document.getElementById('chart_report_type');
        const chartSelect = document.getElementById('chart_id');
        const chartTypeSelect = document.getElementById('chart_type');
        const reportGroupSelect = document.getElementById('chart_report_group');
        const formulaInput = document.getElementById('formula');

        if (chartSelect && typeof window.initCombobox === 'function') {
            chartSelect.dataset.comboboxBound = 'true';
            window.initCombobox(chartSelect);
        }

        function updateSourceFields() {
            const selectedType = reportType.value;
            const showChart = selectedType === @json(ChartReportTypeEnum::CHART->value);
            const showChartType =
                selectedType === @json(ChartReportTypeEnum::TYPES->value) || selectedType === @json(ChartReportTypeEnum::FORMULA->value);
            const showFormula = selectedType === @json(ChartReportTypeEnum::FORMULA->value);
            const showReportGroup = selectedType === @json(ChartReportTypeEnum::GROUPS->value);

            document.getElementById('chart_source_group').classList.toggle('d-none', !showChart);
            document.getElementById('chart_type_source_group').classList.toggle('d-none', !showChartType);
            document.getElementById('formula_source_group').classList.toggle('d-none', !showFormula);
            document.getElementById('report_group_source_group').classList.toggle('d-none', !showReportGroup);

            chartSelect.required = showChart;
            chartTypeSelect.required = selectedType === @json(ChartReportTypeEnum::TYPES->value);
            formulaInput.required = showFormula;
            reportGroupSelect.required = showReportGroup;
        }

        reportType.addEventListener('change', updateSourceFields);
        updateSourceFields();
    </script>
@endsection
