@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\System\User;

    $canCreate = auth()->user()->hasFilePermission(FileResources::USERS, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::USERS, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::USERS, FileAction::REMOVE);
@endphp
@props([
    /** @var User */
    'user',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('users.title') }}
                <p class="fg-secondary small mb-0">{{ __('users.subtitle') }}</p>
            </h4>
            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('system.users.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>

        <div class="card-body">
            @include('partials.alerts')
            <!-- Search Bar -->
            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route('system.users.index') }}">
                <div class="md:col-4">
                    <div class="input-group">
                        <input
                            class="form-control"
                            id="search"
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
                                href="{{ route('system.users.index') }}"
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
                            <th>{{ __('users.table.column_name') }}</th>
                            <th class="col-email">{{ __('users.table.column_email') }}</th>
                            <th class="col-date">{{ __('common.labels.created_at') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="fw-bold">
                                    {{ $user->name }}
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->created_at->format(Setting::getDefaultDate()) ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if ($canUpdate)
                                        <a
                                            class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('system.users.edit', $user) }}"
                                            title="{{ __('common.actions.edit') }}"
                                        >
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button
                                            class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $user->id }}"
                                            type="button"
                                            title="{{ __('common.actions.delete') }}"
                                        >
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off"
                                            class="d-none"
                                            id="delete-form-{{ $user->id }}"
                                            action="{{ route('system.users.destroy', $user) }}"
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
                                <td class="fg-secondary py-4 text-center" colspan="4">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $users->links() }}</div>
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
