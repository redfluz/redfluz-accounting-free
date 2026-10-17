@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Menu\Folder;
    use App\Models\Menu\PageGrouping;
    use App\Models\Menu\PageGroupingPermission;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::PAGE_GROUPING_PERMISSIONS, FileAction::UPDATE);
@endphp
@props([
    /** @var PageGroupingPermission */
    'permissions',
    /** @var Folder */
    'folders',
    /** @var Roles */
    'roles',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('page_grouping_permissions.title') }}
                <p class="fg-secondary small mb-0">{{ __('page_grouping_permissions.subtitle') }}</p>
            </h4>
            @if ($canUpdate)
                <div>
                    <button class="btn btn-solid theme-accent" id="btn-save-changes" disabled>
                        <i class="me-1" data-lucide="save"></i>
                        {{ __('common.actions.save') }}
                        <span class="badge d-none ms-1" id="pending-badge">0</span>
                    </button>
                </div>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4 rounded border p-3">
                <div class="md:col-4">
                    <label class="form-label fw-bold" for="filter-role">
                        {{ __('page_grouping_permissions.filter_roles') }}
                    </label>
                    <select class="form-control" id="filter-role">
                        <option value="">{{ __('common.labels.all') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-4">
                    <label class="form-label fw-bold" for="filter-folder">
                        {{ __('page_grouping_permissions.filter_folder') }}
                    </label>
                    <select class="form-control" id="filter-folder">
                        <option value="">{{ __('common.labels.all') }}</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="btn-group">
                    <button class="btn btn-sm btn-solid theme-success" id="btn-check-all" type="button">
                        <i class="me-1" data-lucide="check"></i>
                        {{ __('common.actions.check_all') }}
                    </button>
                    <button class="btn btn-sm btn-solid theme-danger" id="btn-uncheck-all" type="button">
                        <i class="me-1" data-lucide="x"></i>
                        {{ __('common.actions.clear_all') }}
                    </button>
                </div>
                <small class="badge theme-secondary" id="record-count"> Showing {{ $permissions->count() }} record(s)
                </small>
            </div>

            <div id="table-wrapper">
                @include('menu.page-grouping-permissions.partials.table')
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roleSelect = document.getElementById('filter-role');
            const folderSelect = document.getElementById('filter-folder');
            const tableWrapper = document.getElementById('table-wrapper');
            const recordCount = document.getElementById('record-count');
            const btnCheckAll = document.getElementById('btn-check-all');
            const btnUncheckAll = document.getElementById('btn-uncheck-all');
            const btnSave = document.getElementById('btn-save-changes');
            const pendingBadge = document.getElementById('pending-badge');

            let pendingChanges = {};

            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

            const showToast = (icon, title) => {
                if (typeof confirmDialog !== 'undefined') {
                    confirmDialog({
                        icon: icon,
                        title: title,
                        showConfirmButton: false,
                        timer: 2000,
                    });
                }
            };

            const updateSaveButton = () => {
                const count = Object.keys(pendingChanges).length;
                btnSave.disabled = count === 0;

                if (count > 0) {
                    pendingBadge.textContent = count;
                    pendingBadge.classList.remove('d-none');
                } else {
                    pendingBadge.classList.add('d-none');
                }
            };

            const clearPendingChanges = () => {
                pendingChanges = {};
                updateSaveButton();
            };

            const fetchPermissions = async () => {
                if (Object.keys(pendingChanges).length > 0) {
                    const confirmLeave = confirm(
                        'You have unsaved changes. Changing filters will lose them. Continue?');
                    if (!confirmLeave) return;
                }

                try {
                    const response = await axios.get(
                        "{{ route('menu.page-grouping-permissions.index') }}", {
                            params: {
                                role_id: roleSelect.value,
                                folder_id: folderSelect.value,
                            },
                        });

                    tableWrapper.innerHTML = response.data.html;
                    recordCount.textContent = `Showing ${response.data.count} record(s)`;
                    clearPendingChanges();
                } catch (error) {
                    console.error('Fetch error:', error);
                    showToast('error', 'Failed to reload table data.');
                }
            };

            roleSelect.addEventListener('change', fetchPermissions);
            folderSelect.addEventListener('change', fetchPermissions);

            tableWrapper.addEventListener('change', (event) => {
                const target = event.target;

                if (target.classList.contains('permission-checkbox')) {
                    const id = target.dataset.id;

                    if (target.dataset.initialState === undefined) {
                        target.dataset.initialState = target.checked ? 0 : 1;
                    }

                    const currentValue = target.checked ? 1 : 0;

                    if (currentValue === parseInt(target.dataset.initialState)) {
                        delete pendingChanges[id];
                    } else {
                        pendingChanges[id] = currentValue;
                    }

                    updateSaveButton();
                }

                if (target.id === 'check-all-header') {
                    setAllCheckboxes(target.checked);
                }
            });

            const setAllCheckboxes = (status) => {
                const checkboxes = document.querySelectorAll('.permission-checkbox');
                checkboxes.forEach((cb) => {
                    if (cb.checked !== status) {
                        cb.checked = status;
                        cb.dispatchEvent(
                            new Event('change', {
                                bubbles: true,
                            }),
                        );
                    }
                });
            };

            btnCheckAll.addEventListener('click', () => setAllCheckboxes(true));
            btnUncheckAll.addEventListener('click', () => setAllCheckboxes(false));

            btnSave.addEventListener('click', async () => {
                const changeCount = Object.keys(pendingChanges).length;
                if (changeCount === 0) return;

                btnSave.disabled = true;
                btnSave.innerHTML =
                    `<span class="spinner-border spinner-sm me-1" role="status"></span> ${@json(__('page_grouping_permissions.saving'))}`;

                try {
                    await axios.post("{{ route('menu.page-grouping-permissions.bulk-update') }}", {
                        changes: pendingChanges,
                    });

                    showToast(
                        'success',
                        {!! \Illuminate\Support\Js::from(__('page_grouping_permissions.updated')) !!}.replace(':count', String(changeCount)),
                    );

                    document.querySelectorAll('.permission-checkbox').forEach((cb) => {
                        cb.dataset.initialState = cb.checked ? 1 : 0;
                    });

                    clearPendingChanges();
                } catch (error) {
                    console.error('Save error:', error);
                    showToast('error', @json(__('page_grouping_permissions.update_failed')));
                } finally {
                    btnSave.innerHTML =
                        `<i class="me-1" data-lucide="save"></i> ${@json(__('page_grouping_permissions.save_changes'))} <span id="pending-badge" class="badge ms-1 d-none">0</span>`;
                    updateSaveButton();
                }
            });
        });
    </script>
@endsection
