@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\System\Role;

    $canCreate = auth()->user()->hasFilePermission(FileResources::ROLES, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::ROLES, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::ROLES, FileAction::REMOVE);
@endphp
@props([
    /** @var Role */
    'role',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('roles.title') }}
                <p class="fg-secondary small mb-0">{{ __('roles.subtitle') }}</p>
            </h4>
            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('system.roles.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>

        <div class="card-body">
            @include('partials.alerts')
            <!-- Search Bar -->
            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route('system.roles.index') }}">
                <div class="md:col-4">
                    <div class="input-group">
                        <input class="form-control" name="search" type="text" value="{{ $search }}"
                            placeholder="{{ __('common.actions.search') }}" />
                        <button class="btn btn-solid theme-secondary" type="submit">
                            <i class="" data-lucide="search"></i>
                        </button>
                        @if ($search)
                            <a class="btn btn-solid theme-danger" href="{{ route('system.roles.index') }}"
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
                            <th>{{ __('roles.table.column_name') }}</th>
                            <th>{{ __('roles.table.column_dashboard') }}</th>
                            <th class="w-md">{{ __('roles.grid.assigned_user') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="fw-bold">
                                    <a class="text-decoration-none" href="{{ route('system.roles.show', $role) }}">
                                        {{ $role->name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge theme-secondary"> {{ $role->dashboard->label() }} </span>
                                </td>
                                <td>
                                    <a class="badge theme-info text-decoration-none"
                                        href="{{ route('system.roles.show', $role) }}">
                                        {{ $role->users_count }} {{ __('common.labels.users') }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-solid theme-info me-1"
                                        href="{{ route('system.roles.show', $role) }}"
                                        title="{{ __('common.actions.view') }}">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('system.roles.edit', $role) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $role->id }}" type="button"
                                            title="{{ __('common.actions.delete') }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off" class="d-none" id="delete-form-{{ $role->id }}"
                                            action="{{ route('system.roles.destroy', $role) }}" method="POST">
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
            <div class="d-flex justify-content-end mt-3">{{ $roles->links() }}</div>
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
