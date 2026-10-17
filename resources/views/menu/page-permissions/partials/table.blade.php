@php
    use App\Models\Menu\PagePermission;

    $permissionKeys = ['can_read', 'can_add', 'can_update', 'can_remove', 'can_approve', 'can_cancel'];
@endphp
@props([
    /** @var PagePermission */
    'permissions',
])
<div class="table-responsive rounded-5 overflow-hidden border">
    <table class="table-hover table-striped mb-0 table align-middle">
        <thead class="bg-1">
            <tr>
                <th class="text-start">{{ __('page_permissions.table.column_role_id') }}</th>
                <th class="text-start">{{ __('page_permissions.table.column_folder_id') }}</th>
                <th class="text-start">{{ __('page_permissions.table.column_page_id') }}</th>
                @foreach ($permissionKeys as $key)
                    @php
                        $translationKey = "page_permissions.table.column_{$key}";
                        $fallbackLabel = ucwords(str_replace(['can_', '_'], ['', ' '], $key));
                        $label = __($translationKey) !== $translationKey ? __($translationKey) : $fallbackLabel;
                        $allChecked = $permissions->count() > 0 && $permissions->every(fn ($permission) => $permission->$key);
                    @endphp
                    <th style="width: 110px">
                        <div class="d-flex flex-column align-items-center gap-1">
                            <label class="text-capitalize small fw-bold" for="check-column-{{ $key }}">
                                {{ $label }}
                            </label>
                            <div class="m-0 p-0">
<div class="switch">
<input
    class="check-column-header"
    data-permission="{{ $key }}"
    id="check-column-{{ $key }}"
    role="switch"
    type="checkbox"
    {{ $allChecked ? 'checked' : '' }}
    switch />
</div>
                            </div>
                        </div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($permissions as $permission)
                <tr>
                    <td class="fw-semibold text-start">
                        {{ $permission->role?->name ?? 'Role #' . $permission->role_id }}
                    </td>
                    <td class="text-start">{{ $permission->page?->folder?->name ?? '-' }}</td>
                    <td class="text-start">{{ $permission->page?->name ?? 'Page #' . $permission->page_id }}</td>

                    @foreach ($permissionKeys as $key)
                        <td class="text-center">
                            <div class="switch d-inline-flex m-0 p-0">
                                <input
                                    class="check permission-checkbox float-none m-0"
                                    data-id="{{ $permission->id }}"
                                    data-permission="{{ $key }}"
                                    role="switch"
                                    type="checkbox"
                                    {{ $permission->$key ? 'checked' : '' }}
                                    switch />
                            </div>
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="fg-secondary py-4 text-center" colspan="9">{{ __('common.labels.not_found_record') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
