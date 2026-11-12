@props([
    /** @var FolderPermission */
    'permissions',
])
<div class="table-responsive rounded-5 overflow-hidden border">
    <table class="table-hover table-striped mb-0 table align-middle">
        <thead class="bg-1">
        <tr>
            <th scope="col" class="sm:col">{{ __('folder_permissions.table.column_role_id') }}</th>
            <th scope="col">{{ __('folder_permissions.table.column_folder_id') }}</th>
            <th scope="col" class="col-action text-center">
                <div class="d-flex flex-column align-items-center gap-1">
                    <label class="fw-bold" for="check-all-header">
                        {{ __('folder_permissions.table.column_can_read') }}
                    </label>
                    <div class="d-flex justify-content-center">
                        <div class="switch">
                            <input
                                id="check-all-header"
                                type="checkbox"
                                role="switch"
                                @checked($permissions->count() > 0 && $permissions->every(fn($permission) => $permission->can_read)) />
                        </div>
                    </div>
                </div>
            </th>
        </tr>
        </thead>
        <tbody>
        @forelse ($permissions as $permission)
            <tr>
                <td class="fw-semibold w-md">{{ $permission->role->name ?? 'Role #' . $permission->role_id }}</td>
                <td>
                    @if ($permission->folder->icon)
                        <i class="fa {{ $permission->folder->icon }} me-1"></i>
                    @endif
                    {{ $permission->folder->name ?? 'Folder #' . $permission->folder_id }}
                </td>
                <td class="col-action text-center">
                    <div class="d-flex justify-content-center">
                        <div class="switch">
                            <input
                                class="permission-checkbox"
                                data-id="{{ $permission->id }}"
                                type="checkbox"
                                role="switch"
                                @checked($permission->can_read) />
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="fg-secondary py-4 text-center" colspan="3">{{ __('common.labels.not_found_record') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
