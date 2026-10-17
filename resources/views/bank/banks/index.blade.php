@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::BANKS, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::BANKS, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::BANKS, FileAction::REMOVE);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $listTitle ?? __('banks.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ $listSubtitle ?? __('banks.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
                @if ($canCreate)
                    <a class="btn btn-solid theme-success btn-sm" href="{{ $createUrl ?? route('bank.banks.create') }}"
                        ><i class="me-1" data-lucide="plus"></i>{{ __('common.actions.add') }}</a>
                @endif
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route($indexRoute ?? 'bank.banks.index') }}">
                <div class="md:col-5">
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
                        @if ($search !== '')
                            <a class="btn btn-solid theme-danger" href="{{ route($indexRoute ?? 'bank.banks.index') }}"
                                ><i class="" data-lucide="x"></i></a>
                        @endif
                    </div>
                </div>
            </form>
            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('banks.fields.name') }}</th>
                            <th>{{ __('banks.fields.account_name') }}</th>
                            <th>{{ __('banks.fields.account_number') }}</th>
                            <th>{{ __('banks.fields.chart') }}</th>
                            <th>{{ __('banks.fields.swift_code') }}</th>
                            <th>{{ __('banks.fields.is_active') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($banks as $bank)
                            <tr>
                                <td class="fw-bold">{{ $bank->name }}</td>
                                <td>{{ $bank->account_name }}</td>
                                <td>{{ $bank->account_number }}</td>
                                <td>{{ $bank->chart?->code ?? '-' }} - {{ $bank->chart?->name ?? '-' }}</td>
                                <td>{{ $bank->swift_code ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $bank->is_active ? 'theme-success' : 'theme-secondary' }}">{{ $bank->is_active ? __('banks.status.active') : __('banks.status.inactive') }}</span>
                                </td>
                                <td class="text-nowrap text-center">
                                    <a
                                        class="btn btn-sm btn-solid theme-info me-1"
                                        href="{{ route('bank.banks.show', $bank) }}"
                                        title="{{ __('common.actions.view') }}"
                                        ><i data-lucide="eye"></i
                                    ></a>
                                    @if ($canUpdate)
                                        <a
                                            class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route($editRoute ?? 'bank.banks.edit', $bank) }}"
                                            title="{{ __('common.actions.edit') }}"
                                            ><i data-lucide="square-pen"></i
                                        ></a>
                                    @endif
                                    @if ($canRemove)
                                        <button
                                            class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $bank->id }}"
                                            type="button"
                                            title="{{ __('common.actions.delete') }}"
                                        >
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                        <form autocomplete="off"
                                            class="d-none"
                                            id="delete-form-{{ $bank->id }}"
                                            action="{{ route('bank.banks.destroy', $bank) }}"
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
                                <td class="fg-secondary py-4 text-center" colspan="7">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $banks->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script type="module">
        document.addEventListener('click', function (event) {
            const button = event.target.closest('.btn-delete');
            if (!button) return;
            confirmDialog({
                title: "{{ __('common.alerts.confirm_delete') }}",
                text: "{{ __('common.alerts.confirm_delete_message') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('common.alerts.confirm_delete_yes') }}",
                confirmTone: 'danger',
            }).then((result) => {
                if (result.isConfirmed) document.getElementById('delete-form-' + button.dataset.id).submit();
            });
        });
    </script>
@endsection
