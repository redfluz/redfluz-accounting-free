@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::ROLES, FileAction::UPDATE);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('roles.show.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('roles.show.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
                @if ($canUpdate)
                    <a class="btn btn-solid theme-warning btn-sm" href="{{ route('system.roles.edit', $role) }}">
                        <i class="me-1" data-lucide="square-pen"></i>
                        {{ __('common.actions.edit') }}
                    </a>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('system.roles.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="row g-3 mb-4">
                <div class="md:col-5">
                    <div class="rounded-5 h-100 border p-3">
                        <div class="fg-secondary small mb-1">{{ __('roles.table.column_name') }}</div>
                        <div class="fs-xl fw-bold">{{ $role->name }}</div>
                    </div>
                </div>
                <div class="md:col-4">
                    <div class="rounded-5 h-100 border p-3">
                        <div class="fg-secondary small mb-1">{{ __('roles.table.column_dashboard') }}</div>
                        <span class="badge theme-secondary">{{ $role->dashboard->label() }}</span>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="rounded-5 h-100 border p-3">
                        <div class="fg-secondary small mb-1">{{ __('roles.show.total_users') }}</div>
                        <div class="fs-xl fw-bold">{{ $role->users->count() }}</div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3">{{ __('roles.show.users_title') }}</h6>
            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('roles.table.column_user_name') }}</th>
                            <th>{{ __('roles.table.column_email') }}</th>
                            <th>{{ __('roles.table.column_department') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($role->users as $user)
                            <tr>
                                <td class="fw-bold">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->department->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="3">
                                    {{ __('roles.show.empty_users') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
