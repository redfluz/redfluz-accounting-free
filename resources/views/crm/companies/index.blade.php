@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Crm\Company;

    $canCreate = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::REMOVE);
@endphp
@props([
    /** @var Company */
    'company',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('companies.title') }}
                <p class="fg-secondary small mb-0">{{ __('companies.subtitle') }}</p>
            </h4>
            <div class="d-flex gap-2">
                <a class="btn btn-solid theme-primary btn-sm" href="{{ route('crm.company-addresses.index') }}">
                    <i class="me-1" data-lucide="map-pin-house"></i>
                    {{ __('companies.addresses.page_title') }}
                </a>
                @if ($canCreate)
                    <a class="btn btn-solid theme-success btn-sm" href="{{ route('crm.companies.create') }}">
                        <i class="me-1" data-lucide="plus"></i>
                        {{ __('common.actions.add') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')
            <!-- Search Bar -->
            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route('crm.companies.index') }}">
                <div class="md:col-4">
                    <div class="input-group">
                        <input
                            class="form-control"
                            name="search"
                            type="text"
                            value="{{ $search }}"
                            placeholder="{{ __('common.actions.search') }}"
                        />
                        <button class="btn btn-solid theme-secondary" type="submit">
                            <i class="" data-lucide="search"></i>
                        </button>
                        @if ($search)
                            <a
                                class="btn btn-solid theme-danger"
                                href="{{ route('crm.companies.index') }}"
                                title="{{ __('common.actions.reset') }}"
                            >
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
                            <th>{{ __('companies.table.column_name') }}</th>
                            <th>{{ __('companies.table.column_email') }}</th>
                            <th>{{ __('companies.table.column_phone') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($company->logo_path)
                                            <img
                                                class="rounded border"
                                                src="{{ asset('storage/' . $company->logo_path) }}"
                                                alt="{{ $company->name }} logo"
                                                style="width: 38px; height: 38px; object-fit: contain"
                                            />
                                        @else
                                            <span
                                                class="bg-2 fg-secondary d-inline-flex align-items-center justify-content-center rounded"
                                                style="width: 38px; height: 38px"
                                                ><i class="" data-lucide="building-2"></i></span>
                                        @endif
                                        <span class="fw-bold">{{ $company->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $company->email ?? '-' }}</td>
                                <td>{{ $company->phone ?? '-' }}</td>
                                <td class="text-nowrap text-center" style="width: 230px !important">
                                    <a
                                        class="btn btn-sm btn-solid theme-info me-1"
                                        href="{{ route('crm.companies.show', $company) }}"
                                        title="{{ __('common.actions.view') }}"
                                    >
                                        <i data-lucide="eye"></i>
                                    </a>
                                    <a
                                        class="btn btn-sm btn-solid theme-secondary me-1"
                                        href="{{ route('crm.company-addresses.index', ['company' => $company->id]) }}"
                                        title="{{ __('companies.addresses.actions.manage') }}"
                                    >
                                        <i data-lucide="map-pin-house"></i>
                                    </a>
                                    @if ($canUpdate)
                                        <a
                                            class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('crm.companies.edit', $company) }}"
                                            title="{{ __('common.actions.edit') }}"
                                        >
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button
                                            class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $company->id }}"
                                            type="button"
                                            title="{{ __('common.actions.delete') }}"
                                        >
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off"
                                            class="d-none"
                                            id="delete-form-{{ $company->id }}"
                                            action="{{ route('crm.companies.destroy', $company) }}"
                                            method="POST"
                                        >
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="6">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $companies->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script type="module">
        document.addEventListener('click', function (e) {
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
