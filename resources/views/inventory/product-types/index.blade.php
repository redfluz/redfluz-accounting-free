<?php

use App\Enums\FileAction;
use App\Enums\FileResources;

$canCreate = auth()->user()->hasFilePermission(FileResources::PRODUCT_TYPES, FileAction::ADD);
$canUpdate = auth()->user()->hasFilePermission(FileResources::PRODUCT_TYPES, FileAction::UPDATE);
$canRemove = auth()->user()->hasFilePermission(FileResources::PRODUCT_TYPES, FileAction::REMOVE);

?>

@props(['types', 'search'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('product_types.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('product_types.subtitle') }}</p>
            </div>
            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('inventory.product-types.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <form autocomplete="off" class="row g-2 mb-4" method="GET" action="{{ route('inventory.product-types.index') }}">
                <div class="md:col-5">
                    <input class="form-control" name="search" type="text" value="{{ $search }}"
                        placeholder="{{ __('common.actions.search') }}" />
                </div>
            </form>
            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('product_types.table.code') }}</th>
                            <th>{{ __('product_types.table.name') }}</th>
                            <th>{{ __('product_types.table.category') }}</th>
                            <th class="text-center">{{ __('product_types.table.products') }}</th>
                            <th>{{ __('product_types.table.status') }}</th>
                            <th class="text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($types as $type)
                            <tr>
                                <td><code>{{ $type->code }}</code></td>
                                <td class="fw-bold">{{ $type->name }}</td>
                                <td>{{ $type->category->name }}</td>
                                <td class="text-center">{{ $type->products_count }}</td>
                                <td>
                                    <span class="badge {{ $type->is_active ? 'theme-success' : 'theme-secondary' }}">
                                        {{ $type->is_active ? __('product_types.status.active') : __('product_types.status.inactive') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('inventory.product-types.edit', $type) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $type->id }}" type="button"
                                            title="{{ __('common.actions.delete') }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                        <form autocomplete="off" class="d-none" id="delete-form-{{ $type->id }}"
                                            action="{{ route('inventory.product-types.destroy', $type) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="6">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $types->links() }}</div>
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
