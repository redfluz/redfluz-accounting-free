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
    $canUpdate = auth()->user()->hasFilePermission(FileResources::CHART_REPORTS, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::CHART_REPORTS, FileAction::REMOVE);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('chart_reports.edit_title') }}</h4>
            <p class="fg-secondary small mb-0">{{ $chartReport->name }}</p>
        </div>

        <div class="card-body">
            @include('partials.alerts')
            <form autocomplete="off" action="{{ route('chart-of-account.chart-reports.update', $chartReport) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="md:col-4">
                        <label class="form-label fw-bold" for="code">
                            {{ __('chart_reports.form.code') }}
                        </label>
                        <label class="form-control form-adorn">
                            <input class="form-ghost @error('code') is-invalid @enderror" id="code" name="code"
                                type="text" value="{{ old('code', $chartReport->code) }}" />
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
                            <input class="form-ghost @error('name') is-invalid @enderror" id="name" name="name"
                                type="text" value="{{ old('name', $chartReport->name) }}" required />
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
                            <input class="form-ghost @error('slug') is-invalid @enderror" id="slug" name="slug"
                                type="text" value="{{ old('slug', $chartReport->slug) }}" required />
                        </label>
                        <div class="form-text">{{ __('chart_reports.form.slug_help') }}</div>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-4 d-flex align-items-center md:pt-4">
                        <div class="switch">
                            <input id="is_default" name="is_default" type="checkbox" value="1"
                                @checked(old('is_default', $chartReport->is_default)) role="switch" switch />
                        </div>
                        <label for="is_default">
                            {{ __('chart_reports.form.is_default') }}
                        </label>
                    </div>

                    <div class="md:col-6">
                        <label class="form-label fw-bold" for="calculation_basis">
                            {{ __('chart_reports.form.calculation_basis') }}
                        </label>
                        <select class="form-control @error('calculation_basis') is-invalid @enderror" id="calculation_basis"
                            name="calculation_basis" required>
                            @foreach (['period', 'as_of'] as $basis)
                                <option value="{{ $basis }}" @selected(old('calculation_basis', $chartReport->calculation_basis) === $basis)>
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

    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('chart_reports.rows_title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('chart_reports.rows_subtitle') }}</p>
            </div>

            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm"
                    href="{{ route('chart-of-account.chart-reports.details.create', $chartReport) }}">
                    <i class="me-1" data-lucide="list-plus"></i>
                    {{ __('chart_reports.actions.add_row') }}
                </a>
            @endif
        </div>

        <div class="card-body">
            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th class="text-center">{{ __('chart_reports.table.column_line') }}</th>
                            <th>{{ __('chart_reports.table.column_code') }}</th>
                            <th>{{ __('chart_reports.table.column_name') }}</th>
                            <th>{{ __('chart_reports.table.column_parent') }}</th>
                            <th>{{ __('chart_reports.table.column_row_type') }}</th>
                            <th>{{ __('chart_reports.table.column_source') }}</th>
                            <th>{{ __('chart_reports.table.column_formula') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($chartReport->details as $detail)
                            @php
                                $reportType = $detail->chart_report_type->value ?? $detail->chart_report_type;
                                $chartType = $detail->chart_type->value ?? $detail->chart_type;
                                $reportGroup =
                                    $detail->chart_report_group instanceof \BackedEnum
                                        ? $detail->chart_report_group->value
                                        : $detail->chart_report_group;
                            @endphp
                            <tr>
                                <td class="fw-bold text-center">{{ $detail->line }}</td>
                                <td><code>{{ $detail->code }}</code></td>
                                <td>{{ $detail->name }}</td>
                                <td>{{ $detail->parent->code ?? '-' }}</td>
                                <td>
                                    <span class="badge theme-secondary">
                                        {{ __('chart_reports.row_type.' . $reportType) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($detail->chart)
                                        {{ $detail->chart->code }} - {{ $detail->chart->name }}
                                    @elseif ($reportGroup)
                                        {{ __('chart_reports.report_group.' . $reportGroup) }}
                                    @elseif ($chartType)
                                        {{ __('chart_reports.chart_type.' . $chartType) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="small"><code>{{ $detail->formula ?? '-' }}</code></td>
                                <td class="text-nowrap text-center">
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('chart-of-account.chart-reports.details.edit', [$chartReport, $detail]) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif

                                    @if ($canRemove)
                                        <button class="btn btn-sm btn-solid theme-danger btn-delete-row"
                                            data-form-id="delete-row-{{ $detail->id }}" type="button"
                                            title="{{ __('common.actions.delete') }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off" class="d-none" id="delete-row-{{ $detail->id }}"
                                            action="{{ route('chart-of-account.chart-reports.details.destroy', [$chartReport, $detail]) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="8">
                                    {{ __('chart_reports.rows_empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="module">
        document.addEventListener('click', function(event) {
            const deleteButton = event.target.closest('.btn-delete-row');

            if (!deleteButton) {
                return;
            }

            confirmDialog({
                title: @json(__('common.alerts.confirm_delete')),
                text: @json(__('chart_reports.alerts.delete_row')),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: @json(__('common.alerts.confirm_delete_yes')),
                confirmTone: 'danger',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(deleteButton.dataset.formId)?.submit();
                }
            });
        });
    </script>
@endsection
