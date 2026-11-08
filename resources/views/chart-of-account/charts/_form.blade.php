@php
    use App\Enums\ChartOfAccount\ChartReportGroupEnum;
    use App\Models\ChartOfAccount\Chart;
@endphp
@props([
    /** @var Chart|null */
    'chart' => null,
])

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
            <option value="{{ $type->id }}" {{ old('chart_type_id', $chart?->chart_type_id) == $type->id ? 'selected' : '' }}>
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
    @php
        $currentReportGroup = $chart?->report_group instanceof \BackedEnum ? $chart->report_group->value : $chart?->report_group;
    @endphp
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
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('code') is-invalid @enderror"
            id="code"
            name="code"
            type="text"
            value="{{ old('code', $chart?->code) }}"
            required
        />
    </div>
    @error('code')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="name"> {{ __('charts.table.column_name') }} </label>
    <div class="form-control form-adorn">
        <input
            class="form-ghost @error('name') is-invalid @enderror"
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $chart?->name) }}"
            required
        />
    </div>
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
            @checked(old($field, $chart?->{$field}))
            role="switch" switch />
        </div>
        <label
            for="{{ $field }}"
        >{{ __('charts.table.' . $label) }}</label>
    @endforeach
</div>

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
