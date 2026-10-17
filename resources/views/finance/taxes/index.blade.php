@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canAdd = auth()->user()->hasFilePermission(FileResources::TAXES, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::TAXES, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::TAXES, FileAction::REMOVE);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('taxes.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('taxes.subtitle') }}</p>
            </div>
            @if ($canAdd)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('finance.taxes.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('taxes.table.code') }}</th>
                            <th>{{ __('taxes.table.name') }}</th>
                            <th class="text-end">{{ __('taxes.table.rate') }}</th>
                            <th>{{ __('taxes.table.mode') }}</th>
                            <th>{{ __('taxes.table.status') }}</th>
                            <th class="text-center">{{ __('taxes.table.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($taxes as $tax)
                            <tr>
                                <td class="fw-bold">{{ $tax->code }}</td>
                                <td>{{ $tax->name }}</td>
                                <td class="text-end">{{ number_format((float) $tax->rate, 4) }}%</td>
                                <td>{{ __('ledgers.tax_mode.' . $tax->default_mode->value) }}</td>
                                <td>
                                    <span class="badge {{ $tax->is_active ? 'theme-success' : 'theme-secondary' }}">
                                        {{ __('taxes.status.' . ($tax->is_active ? 'active' : 'inactive')) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        @if ($canUpdate)
                                            <a class="btn btn-sm btn-solid theme-warning me-1"
                                                href="{{ route('finance.taxes.edit', $tax) }}"
                                                title="{{ __('common.actions.edit') }}">
                                                <i data-lucide="square-pen"></i>
                                            </a>
                                        @endif
                                        @if ($canRemove)
                                            <form autocomplete="off" action="{{ route('finance.taxes.destroy', $tax) }}" method="POST"
                                                data-confirm-message="{{ __('taxes.messages.delete_confirm') }}"
                                                data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-solid theme-danger btn-sm" type="submit">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="6">
                                    {{ __('taxes.messages.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
