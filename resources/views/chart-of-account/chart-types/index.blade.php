@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\ChartOfAccount\ChartType;

    $canCreate = auth()->user()->hasFilePermission(FileResources::CHART_TYPES, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::CHART_TYPES, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::CHART_TYPES, FileAction::REMOVE);
@endphp
@props([
    /** @var ChartType */
    'chartType',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('chart_types.title') }}
                <p class="fg-secondary small mb-0">{{ __('chart_types.subtitle') }}</p>
            </h4>
            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('chart-of-account.chart-types.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route('chart-of-account.chart-types.index') }}">
                <div class="md:col-4">
                    <div class="input-group">
                        <input class="form-control" name="search" type="text" value="{{ $search }}"
                            placeholder="{{ __('common.actions.search') }}" />
                        <button class="btn btn-solid theme-secondary" type="submit">
                            <i class="" data-lucide="search"></i>
                        </button>
                        @if ($search)
                            <a class="btn btn-solid theme-danger" href="{{ route('chart-of-account.chart-types.index') }}"
                                title="{{ __('common.actions.reset') }}">
                                <i class="" data-lucide="x"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('chart_types.table.column_name') }}</th>
                            <th class="text-center">{{ __('chart_types.table.column_flags') }}</th>
                            <th class="col-date">{{ __('common.labels.created_at') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($chartTypes as $chartType)
                            <tr>
                                <td class="fw-bold">{{ $chartType->name }}</td>
                                <td class="text-center">
                                    @if ($chartType->isAsset)
                                        <span class="badge theme-secondary me-1"> {{ __('chart_types.flags.asset') }}
                                        </span>
                                    @endif
                                    @if ($chartType->isLiability)
                                        <span class="badge theme-warning fg-black me-1">
                                            {{ __('chart_types.flags.liability') }}
                                        </span>
                                    @endif
                                    @if ($chartType->isEquity)
                                        <span class="badge theme-info fg-black me-1">
                                            {{ __('chart_types.flags.equity') }}
                                        </span>
                                    @endif
                                    @if ($chartType->isIncome)
                                        <span class="badge theme-success me-1">
                                            {{ __('chart_types.flags.income') }}
                                        </span>
                                    @endif
                                    @if ($chartType->isExpenses)
                                        <span class="badge theme-danger me-1">
                                            {{ __('chart_types.flags.expenses') }}
                                        </span>
                                    @endif
                                    @if ($chartType->isDefault)
                                        <span class="badge theme-secondary"> {{ __('chart_types.flags.default') }} </span>
                                    @endif
                                </td>
                                <td>{{ $chartType->created_at->format(Setting::getDefaultDate()) ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('chart-of-account.chart-types.edit', $chartType) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $chartType->id }}" type="button"
                                            title="{{ __('common.actions.delete') }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off" class="d-none" id="delete-form-{{ $chartType->id }}"
                                            action="{{ route('chart-of-account.chart-types.destroy', $chartType) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="5">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $chartTypes->links() }}</div>
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
