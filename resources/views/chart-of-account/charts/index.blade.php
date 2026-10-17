@php use App\Models\System\Setting; @endphp
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
    use App\Enums\ChartTypeEnum;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\ChartOfAccount\Chart;

    $canCreate = auth()->user()->hasFilePermission(FileResources::CHARTS, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::CHARTS, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::CHARTS, FileAction::REMOVE);
@endphp
@props([
    /** @var Chart */
    'chart',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('charts.title') }}
                <p class="fg-secondary small mb-0">{{ __('charts.subtitle') }}</p>
            </h4>
            <div class="d-flex gap-2">
                <a class="btn btn-solid theme-success btn-sm"
                    href="{{ route('chart-of-account.charts.export-excel', request()->query()) }}">
                    <i class="me-1" data-lucide="file-spreadsheet"></i>
                    {{ __('charts.export_excel') }}
                </a>
                @if ($canCreate)
                    <a class="btn btn-solid theme-success btn-sm" href="{{ route('chart-of-account.charts.create') }}">
                        <i class="me-1" data-lucide="plus"></i>
                        {{ __('common.actions.add') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <form autocomplete="off" class="row g-2 mb-3" method="GET"
                action="{{ route('chart-of-account.charts.index') }}">
                <div class="md:col-3">
                    <div class="input-group">
                        <input class="form-control" name="search" type="text" value="{{ $search }}"
                            placeholder="{{ __('common.actions.search') }}" />
                        <button class="btn btn-solid theme-secondary" type="submit">
                            <i class="" data-lucide="search"></i>
                        </button>
                        @if ($search)
                            <a class="btn btn-solid theme-danger" href="{{ route('chart-of-account.charts.index') }}"
                                title="{{ __('common.actions.reset') }}">
                                <i class="" data-lucide="x"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="md:col-2">
                    <select class="form-control" name="chart_type" id="chart_type_filter">
                        <option value="">{{ __('charts.all_types') }}</option>
                        @foreach (ChartTypeEnum::cases() as $chartType)
                            @continue($chartType === ChartTypeEnum::NONE)
                            <option value="{{ $chartType->value }}" @selected(($chartTypeFilter ?? '') === $chartType->value)>
                                {{ __('charts.chart_type.' . $chartType->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-3">
                    <select class="form-control" name="report_group" id="report_group_filter">
                        <option value="">{{ __('charts.all_report_groups') }}</option>
                        @foreach (ChartReportGroupEnum::cases() as $reportGroup)
                            <option value="{{ $reportGroup->value }}" @selected(($reportGroupFilter ?? '') === $reportGroup->value)>
                                {{ __('charts.report_group.' . $reportGroup->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-2">
                    <button class="btn btn-solid theme-secondary w-100" type="submit">
                        <i class="me-1" data-lucide="list-filter"></i>
                        {{ __('common.actions.filter') }}
                    </button>
                </div>

                @if ($chartTypeFilter || $reportGroupFilter)
                    <div class="md:col-2">
                        <a class="btn btn-solid theme-danger w-100" href="{{ route('chart-of-account.charts.index') }}"
                            title="{{ __('common.actions.reset') }}">
                            <i class="me-1" data-lucide="x"></i>
                            {{ __('common.actions.reset') }}
                        </a>
                    </div>
                @endif
            </form>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('charts.table.column_code') }}</th>
                            <th>{{ __('charts.table.column_name') }}</th>
                            <th>{{ __('charts.table.column_type') }}</th>
                            <th>{{ __('charts.table.column_report_group') }}</th>
                            <th>{{ __('charts.table.column_bank') }}</th>
                            <th>{{ __('charts.table.column_debtor') }}</th>
                            <th>{{ __('charts.table.column_creditor') }}</th>
                            <th class="col-date">{{ __('common.labels.created_at') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($charts as $chart)
                            @php
                                $reportGroupValue =
                                    $chart->report_group instanceof BackedEnum
                                        ? $chart->report_group->value
                                        : $chart->report_group;
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $chart->code }}</td>
                                <td>{{ $chart->name }}</td>
                                <td>
                                    <span class="badge theme-accent">
                                        {{ $chart->chartType->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($reportGroupValue)
                                        <span class="badge theme-secondary">
                                            {{ __('charts.report_group.' . $reportGroupValue) }}
                                        </span>
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($chart->isBank)
                                        <span class="badge theme-secondary">{{ __('charts.table.column_bank') }}</span>
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($chart->isDebtor)
                                        <span class="badge theme-success">{{ __('charts.table.column_debtor') }}</span>
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($chart->isCreditor)
                                        <span class="badge theme-warning">{{ __('charts.table.column_creditor') }}</span>
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td>{{ $chart->created_at->format(Setting::getDefaultDate()) ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('chart-of-account.charts.edit', $chart) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $chart->id }}" type="button"
                                            title="{{ __('common.actions.delete') }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off" class="d-none" id="delete-form-{{ $chart->id }}"
                                            action="{{ route('chart-of-account.charts.destroy', $chart) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="9">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $charts->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script type="module">
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete');

            if (deleteBtn) {
                const dataId = deleteBtn.getAttribute('data-id');

                confirmDialog({
                    title: "{{ __('common.alerts.confirm_delete') }}",
                    text: "{{ __('common.alerts.confirm_delete_message') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "{{ __('common.alerts.confirm_delete_yes') }}",
                    confirmTone: 'danger',
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + dataId).submit();
                    }
                });
            }
        });
    </script>
@endsection
