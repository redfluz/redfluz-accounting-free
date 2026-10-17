@props([
    /** @var PageGroupingPermission */
    'permissions',
])
<div class="table-responsive rounded-5 overflow-hidden border">
    <table class="table-hover table-striped mb-0 table align-middle">
        <thead class="bg-1">
            <tr>
                <th class="sm:col">{{ __('page_grouping_permissions.table.column_role_id') }}</th>
                <th>{{ __('page_grouping_permissions.table.column_page_grouping_id') }}</th>
                <th class="col-action text-center">
                    <div class="d-flex flex-column align-items-center gap-1">
                        <label class="fw-bold" for="check-all-header">
                            {{ __('page_grouping_permissions.table.column_can_read') }}
                        </label>
                        <div class="m-0 p-0">
<div class="switch">
<input
    id="check-all-header"
    type="checkbox"
    role="switch"
    {{ $permissions->count() > 0 && $permissions->every(fn($permission) => $permission->can_read) ? 'checked' : '' }}
    switch />
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
                        @if ($permission->pageGrouping->icon)
                            <i class="me-1" data-lucide="{{ $permission->pageGrouping->icon }}"></i>
                        @endif
                        {{ $permission->pageGrouping->name ?? 'Grouping #' . $permission->page_grouping_id }}
                    </td>
                    <td class="col-action text-center">
                        <div class="switch d-inline-flex m-0 p-0">
                            <input
                                class="check permission-checkbox float-none m-0"
                                data-id="{{ $permission->id }}"
                                type="checkbox"
                                role="switch"
                                {{ $permission->can_read ? 'checked' : '' }}
                                switch />
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
