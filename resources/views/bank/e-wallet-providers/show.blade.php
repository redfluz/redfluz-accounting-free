<?php

use App\Enums\FileAction;
use App\Enums\FileResources;

$canUpdate = auth()->user()->hasFilePermission(FileResources::E_WALLET_PROVIDERS, FileAction::UPDATE);

?>

@props(['provider', 'accounts'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $provider->name }}</h4>
                <p class="fg-secondary small mb-0">
                    {{ $provider->country ?? '-' }} &middot; {{ $provider->identifier_label ?? '-' }} &middot;
                    {{ $provider->chart ? $provider->chart->code.' - '.$provider->chart->name : '-' }}
                </p>
            </div>
            <div class="d-flex gap-2">
                @if ($canUpdate)
                    <a class="btn btn-solid theme-warning btn-sm" href="{{ route('bank.e-wallet-providers.edit', $provider) }}">
                        <i class="me-1" data-lucide="square-pen"></i>
                        {{ __('common.actions.edit') }}
                    </a>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('bank.e-wallet-providers.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <h6 class="fw-bold mb-3">{{ __('e_wallet_providers.accounts_title') }}</h6>
            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('e_wallet_providers.accounts_table.account') }}</th>
                            <th>{{ __('e_wallet_providers.accounts_table.number') }}</th>
                            <th>{{ __('e_wallet_providers.accounts_table.chart') }}</th>
                            <th>{{ __('e_wallet_providers.accounts_table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr>
                                <td>
                                    <a class="fw-bold text-decoration-none"
                                        href="{{ route('bank.banks.show', $account) }}">
                                        {{ $account->account_name }}
                                    </a>
                                </td>
                                <td><code>{{ $account->account_number ?? '-' }}</code></td>
                                <td>
                                    {{ $account->chart ? $account->chart->code.' - '.$account->chart->name : '-' }}
                                </td>
                                <td>
                                    <span class="badge {{ $account->is_active ? 'theme-success' : 'theme-secondary' }}">
                                        {{ $account->is_active ? __('e_wallet_providers.status.active') : __('e_wallet_providers.status.inactive') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="4">
                                    {{ __('e_wallet_providers.accounts_empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $accounts->links() }}</div>
        </div>
    </div>
@endsection
