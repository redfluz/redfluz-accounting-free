<?php

use App\Enums\FileAction;
use App\Enums\FileResources;

$canCreate = auth()->user()->hasFilePermission(FileResources::E_WALLET_PROVIDERS, FileAction::ADD);
$canUpdate = auth()->user()->hasFilePermission(FileResources::E_WALLET_PROVIDERS, FileAction::UPDATE);
$canRemove = auth()->user()->hasFilePermission(FileResources::E_WALLET_PROVIDERS, FileAction::REMOVE);

?>

@props(['providers', 'search'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('e_wallet_providers.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('e_wallet_providers.subtitle') }}</p>
            </div>
            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('bank.e-wallet-providers.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <form autocomplete="off" class="row g-2 mb-4" method="GET" action="{{ route('bank.e-wallet-providers.index') }}">
                <div class="md:col-5">
                    <input class="form-control" name="search" type="text" value="{{ $search }}"
                        placeholder="{{ __('common.actions.search') }}" />
                </div>
            </form>
            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('e_wallet_providers.table.name') }}</th>
                            <th>{{ __('e_wallet_providers.table.country') }}</th>
                            <th>{{ __('e_wallet_providers.table.identifier_label') }}</th>
                            <th>{{ __('e_wallet_providers.table.chart') }}</th>
                            <th class="text-center">{{ __('e_wallet_providers.table.accounts') }}</th>
                            <th>{{ __('e_wallet_providers.table.status') }}</th>
                            <th class="text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($providers as $provider)
                            <tr>
                                <td class="fw-bold">{{ $provider->name }}</td>
                                <td>{{ $provider->country ?? '-' }}</td>
                                <td>{{ $provider->identifier_label ?? '-' }}</td>
                                <td>
                                    {{ $provider->chart ? $provider->chart->code.' - '.$provider->chart->name : '-' }}
                                </td>
                                <td class="text-center">{{ $provider->accounts_count }}</td>
                                <td>
                                    <span class="badge {{ $provider->is_active ? 'theme-success' : 'theme-secondary' }}">
                                        {{ $provider->is_active ? __('e_wallet_providers.status.active') : __('e_wallet_providers.status.inactive') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-solid theme-info me-1"
                                        href="{{ route('bank.e-wallet-providers.show', $provider) }}">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('bank.e-wallet-providers.edit', $provider) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $provider->id }}" type="button"
                                            title="{{ __('common.actions.delete') }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                        <form autocomplete="off" class="d-none" id="delete-form-{{ $provider->id }}"
                                            action="{{ route('bank.e-wallet-providers.destroy', $provider) }}" method="POST">
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
            <div class="d-flex justify-content-end mt-3">{{ $providers->links() }}</div>
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
