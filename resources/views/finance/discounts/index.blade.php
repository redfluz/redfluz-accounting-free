@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canAdd = auth()->user()->hasFilePermission(FileResources::DISCOUNTS, FileAction::ADD);
    $canRemove = auth()->user()->hasFilePermission(FileResources::DISCOUNTS, FileAction::REMOVE);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::DISCOUNTS, FileAction::UPDATE);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('discounts.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('discounts.subtitle') }}</p>
            </div>
            @if ($canAdd)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('finance.discounts.create') }}"><i class="me-1"
                        data-lucide="plus"></i>{{ __('common.actions.add') }}</a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('discounts.table.code') }}</th>
                            <th>{{ __('discounts.table.name') }}</th>
                            <th>{{ __('discounts.table.type') }}</th>
                            <th>{{ __('discounts.table.value') }}</th>
                            <th>{{ __('discounts.table.date_range') }}</th>
                            <th>{{ __('discounts.table.status') }}</th>
                            <th class="text-end">{{ __('discounts.table.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($discounts as $discount)
                            <tr>
                                <td>{{ $discount->code }}</td>
                                <td>{{ $discount->name }}</td>
                                <td>{{ __('discounts.type.' . $discount->type->value) }}</td>
                                <td>
                                    {{ number_format((float) $discount->value, $discount->type->value === 'percent' ? 4 : 2) }}{{ $discount->type->value === 'percent' ? '%' : '' }}
                                </td>
                                <td>
                                    {{ $discount->starts_at?->format(Setting::getDefaultDate()) ?? '-' }} -
                                    {{ $discount->ends_at?->format(Setting::getDefaultDate()) ?? '-' }}
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $discount->is_active ? 'theme-success' : 'theme-secondary' }}">{{ __('discounts.status.' . ($discount->is_active ? 'active' : 'inactive')) }}</span>
                                </td>
                                <td class="text-end">
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('finance.discounts.edit', $discount) }}"
                                            title="{{ __('common.actions.edit') }}"><i data-lucide="square-pen"></i></a>
                                    @endif
                                    @if ($canRemove)
                                        <form autocomplete="off" class="d-inline" action="{{ route('finance.discounts.destroy', $discount) }}"
                                            method="POST"
                                            data-confirm-message="{{ __('discounts.messages.delete_confirm') }}"
                                            data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-solid theme-danger btn-sm" type="submit">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="7">
                                    {{ __('discounts.messages.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
