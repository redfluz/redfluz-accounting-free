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
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('chart_reports.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('chart_reports.subtitle') }}</p>
            </div>

            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('chart-of-account.chart-reports.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <form autocomplete="off" class="row g-2 mb-3" action="{{ route('chart-of-account.chart-reports.index') }}" method="GET">
                <div class="md:col-5">
                    <div class="input-group">
                        <input class="form-control" name="search" type="text" value="{{ $search }}"
                            placeholder="{{ __('chart_reports.form.search_placeholder') }}" />
                        <button class="btn btn-solid theme-secondary" type="submit">
                            <i data-lucide="search"></i>
                        </button>

                        @if ($search !== '')
                            <a class="btn btn-solid theme-danger" href="{{ route('chart-of-account.chart-reports.index') }}"
                                title="{{ __('common.actions.reset') }}">
                                <i data-lucide="x"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('chart_reports.table.column_code') }}</th>
                            <th>{{ __('chart_reports.table.column_name') }}</th>
                            <th>{{ __('chart_reports.table.column_slug') }}</th>
                            <th>{{ __('chart_reports.table.column_basis') }}</th>
                            <th class="text-center">{{ __('chart_reports.table.column_default') }}</th>
                            <th class="text-center">{{ __('chart_reports.table.column_rows') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($chartReports as $chartReport)
                            <tr>
                                <td class="fw-bold">{{ $chartReport->code ?? '-' }}</td>
                                <td>{{ $chartReport->name }}</td>
                                <td><code>{{ $chartReport->slug }}</code></td>
                                <td>{{ __('chart_reports.calculation_basis.' . $chartReport->calculation_basis) }}</td>
                                <td class="text-center">
                                    @if ($chartReport->is_default)
                                        <span class="badge theme-success">{{ __('chart_reports.status.default') }}</span>
                                    @else
                                        <span
                                            class="badge theme-secondary">{{ __('chart_reports.status.alternative') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge theme-info">{{ $chartReport->details_count }}</span>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-solid theme-info me-1"
                                        href="{{ route('chart-of-account.chart-reports.output', $chartReport) }}"
                                        title="{{ __('chart_reports.actions.view_output') }}">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('chart-of-account.chart-reports.edit', $chartReport) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif

                                    @if ($canRemove)
                                        <button class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-form-id="delete-report-{{ $chartReport->id }}" type="button"
                                            title="{{ __('common.actions.delete') }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off" class="d-none" id="delete-report-{{ $chartReport->id }}"
                                            action="{{ route('chart-of-account.chart-reports.destroy', $chartReport) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="7">{{ __('chart_reports.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $chartReports->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script type="module">
        document.addEventListener('click', function(event) {
            const deleteButton = event.target.closest('.btn-delete');

            if (!deleteButton) {
                return;
            }

            confirmDialog({
                title: @json(__('common.alerts.confirm_delete')),
                text: @json(__('chart_reports.alerts.delete_report')),
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
