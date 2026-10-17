@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Menu\Folder;
    use App\Models\Menu\Page;
    use App\Models\Menu\PagePermission;
    use App\Models\System\Role;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::PAGE_PERMISSIONS, FileAction::UPDATE);
@endphp
@props([
    /** @var Folder */
    'folders',
    /** @var PagePermission */
    'permissions',
    /** @var Page */
    'pages',
    /** @var Role */
    'roles',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('page_permissions.title') }}
                <p class="fg-secondary small mb-0">{{ __('page_permissions.subtitle') }}</p>
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
            <!-- Filter Controls -->
            <div class="row g-3 mb-4 rounded border p-3">
                <div class="md:col-4">
                    <label class="form-label fw-bold" for="filter-role">
                        {{ __('page_permissions.filter_roles') }}
                    </label>
                    <select class="form-control" id="filter-role">
                        <option value="">{{ __('common.labels.all') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(request('role_id') === $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-4">
                    <label class="form-label fw-bold" for="filter-folder">
                        {{ __('page_permissions.filter_folders') }}
                    </label>
                    <select class="form-control" id="filter-folder">
                        <option value="">{{ __('common.labels.all') }}</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" @selected(request('folder_id') === $folder->id)>
                                {{ $folder->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-4">
                    <label class="form-label fw-bold" for="filter-file">
                        {{ __('page_permissions.filter_files') }}
                    </label>
                    <select class="form-control" id="filter-file">
                        <option value="">{{ __('common.labels.all') }}</option>
                        @foreach ($pages as $page)
                            <option data-folder-id="{{ $page->folder_id }}" value="{{ $page->id }}"
                                @selected(request('page_id') === $page->id)>
                                {{ $page->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Toolbar / Bulk Action Controls -->
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
            <div class="table-responsive" id="table-wrapper">
                @include('menu.page-permissions.partials.table')
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roleSelect = document.getElementById('filter-role');
            const folderSelect = document.getElementById('filter-folder');
            const fileSelect = document.getElementById('filter-file');
            const tableWrapper = document.getElementById('table-wrapper');
            const recordCount = document.getElementById('record-count');
            const btnCheckAll = document.getElementById('btn-check-all');
            const btnUncheckAll = document.getElementById('btn-uncheck-all');
            const btnSave = document.getElementById('btn-save-changes');
            const pendingBadge = document.getElementById('pending-badge');

            // Track changes: { "permissionId": { "permission_name": 1 or 0 } }
            let pendingChanges = {};

            // Axios default headers
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
            }

            // Toast helper (SweetAlert2 with fallback)
            const showToast = (icon, title) => {
                if (typeof window.confirmDialog !== 'undefined') {
                    window.confirmDialog({
                        icon: icon,
                        title: title,
                        showConfirmButton: false,
                        timer: 2500,
                    });
                } else {
                    alert(title);
                }
            };

            // Update Save Button state & badge count
            const updateSaveButton = () => {
                let totalModifiedCells = 0;
                Object.values(pendingChanges).forEach((permissions) => {
                    totalModifiedCells += Object.keys(permissions).length;
                });

                btnSave.disabled = totalModifiedCells === 0;

                if (totalModifiedCells > 0) {
                    pendingBadge.textContent = totalModifiedCells;
                    pendingBadge.classList.remove('d-none');
                } else {
                    pendingBadge.classList.add('d-none');
                }
            };

            const clearPendingChanges = () => {
                pendingChanges = {};
                updateSaveButton();
            };

            const filterPageOptions = () => {
                const folderId = folderSelect.value;
                const selectedOption = fileSelect.selectedOptions[0];

                Array.from(fileSelect.options).forEach((option) => {
                    option.hidden = option.value !== '' && folderId !== '' && option.dataset
                        .folderId !== folderId;
                });

                if (selectedOption?.hidden) {
                    fileSelect.value = '';
                }
            };

            // 1. Dynamic Filter Fetching
            const fetchPermissions = async () => {
                if (Object.keys(pendingChanges).length > 0) {
                    if (!confirm('You have unsaved changes. Continue without saving?')) return;
                }

                try {
                    const response = await axios.get("{{ route('menu.page-permissions.index') }}", {
                        params: {
                            role_id: roleSelect.value,
                            folder_id: folderSelect.value,
                            page_id: fileSelect.value,
                        },
                    });

                    tableWrapper.innerHTML = response.data.html;
                    recordCount.textContent = `Showing ${response.data.count} record(s)`;
                    clearPendingChanges();
                } catch (error) {
                    console.error('Fetch error:', error);
                    showToast('error', 'Failed to reload file permissions.');
                }
            };

            roleSelect?.addEventListener('change', fetchPermissions);
            folderSelect?.addEventListener('change', () => {
                filterPageOptions();
                fetchPermissions();
            });
            fileSelect?.addEventListener('change', fetchPermissions);
            filterPageOptions();

            // 2. Change Tracking Delegation
            tableWrapper.addEventListener('change', (event) => {
                const target = event.target;

                // Single Permission Checkbox
                if (target.classList.contains('permission-checkbox')) {
                    const id = target.dataset.id;
                    const perm = target.dataset.permission;

                    if (target.dataset.initialState === undefined) {
                        target.dataset.initialState = target.checked ? 0 : 1;
                    }

                    const currentValue = target.checked ? 1 : 0;
                    const initialState = parseInt(target.dataset.initialState);

                    if (!pendingChanges[id]) {
                        pendingChanges[id] = {};
                    }

                    if (currentValue === initialState) {
                        delete pendingChanges[id][perm];
                        if (Object.keys(pendingChanges[id]).length === 0) {
                            delete pendingChanges[id];
                        }
                    } else {
                        pendingChanges[id][perm] = currentValue;
                    }

                    updateSaveButton();
                }

                // Column Header Toggle (e.g., Check/Uncheck all "Read" column checkboxes)
                if (target.classList.contains('check-column-header')) {
                    const perm = target.dataset.permission;
                    const status = target.checked;
                    setColumnCheckboxes(perm, status);
                }
            });

            const setColumnCheckboxes = (permissionKey, status) => {
                const checkboxes = document.querySelectorAll('.permission-checkbox');
                checkboxes.forEach((cb) => {
                    if (permissionKey === 'all' || cb.dataset.permission === permissionKey) {
                        if (cb.checked !== status) {
                            cb.checked = status;
                            cb.dispatchEvent(
                                new Event('change', {
                                    bubbles: true,
                                }),
                            );
                        }
                    }
                });
            };

            btnCheckAll?.addEventListener('click', () => setColumnCheckboxes('all', true));
            btnUncheckAll?.addEventListener('click', () => setColumnCheckboxes('all', false));

            // 3. Save Button Batch Submit
            btnSave.addEventListener('click', async () => {
                if (Object.keys(pendingChanges).length === 0) return;

                btnSave.disabled = true;
                btnSave.innerHTML =
                    `<span class="spinner-border spinner-sm me-1"></span> ${@json(__('page_permissions.saving'))}`;

                try {
                    await axios.post("{{ route('menu.page-permissions.bulk-update') }}", {
                        changes: pendingChanges,
                    });

                    showToast('success', @json(__('page_permissions.updated')));

                    // Re-sync initial states
                    document.querySelectorAll('.permission-checkbox').forEach((cb) => {
                        cb.dataset.initialState = cb.checked ? 1 : 0;
                    });

                    clearPendingChanges();
                } catch (error) {
                    console.error('Save error:', error.response || error);
                    showToast('error', @json(__('page_permissions.update_failed')));
                } finally {
                    btnSave.innerHTML =
                        `<i class="me-1" data-lucide="save"></i> {{ __('common.actions.save') }} <span id="pending-badge" class="badge ms-1 d-none">0</span>`;
                    updateSaveButton();
                }
            });
        });
    </script>
@endsection
