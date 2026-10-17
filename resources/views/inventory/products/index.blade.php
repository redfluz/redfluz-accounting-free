<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

use App\Enums\FileAction;
use App\Enums\FileResources;

$canCreate = auth()->user()->hasFilePermission(FileResources::PRODUCTS, FileAction::ADD);
$canUpdate = auth()->user()->hasFilePermission(FileResources::PRODUCTS, FileAction::UPDATE);
$canRemove = auth()->user()->hasFilePermission(FileResources::PRODUCTS, FileAction::REMOVE);
?>

@props(['products', 'search'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('products.title') }}
                <p class="fg-secondary small mb-0">{{ __('products.subtitle') }}</p>
            </h4>
            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('inventory.products.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route('inventory.products.index') }}">
                <div class="md:col-4">
                    <div class="input-group">
                        <input class="form-control" name="search" type="text" value="{{ $search }}"
                            placeholder="{{ __('common.actions.search') }}" />
                        <button class="btn btn-solid theme-secondary" type="submit">
                            <i data-lucide="search"></i>
                        </button>
                        @if ($search)
                            <a class="btn btn-solid theme-danger" href="{{ route('inventory.products.index') }}"
                                title="{{ __('common.actions.reset') }}">
                                <i data-lucide="x"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('products.table.column_sku') }}</th>
                            <th>{{ __('products.table.column_name') }}</th>
                            <th>{{ __('products.table.column_brand') }}</th>
                            <th>{{ __('products.table.column_category') }}</th>
                            <th>{{ __('products.table.column_product_type') }}</th>
                            <th>{{ __('products.table.column_type') }}</th>
                            <th class="text-center">{{ __('products.table.column_variants') }}</th>
                            <th class="text-end">{{ __('products.table.column_base_price') }}</th>
                            <th class="text-end">{{ __('products.table.column_selling_price') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td><code>{{ $product->sku }}</code></td>
                                <td class="fw-bold">{{ $product->name }}</td>
                                <td>{{ $product->brand?->name ?? '-' }}</td>
                                <td>{{ $product->category?->name ?? '-' }}</td>
                                <td>{{ $product->type?->name ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $product->is_service ? 'theme-info' : 'theme-success' }}">
                                        {{ $product->is_service ? __('products.types.service') : __('products.types.physical') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge theme-secondary">
                                        {{ trans_choice('products.variants.count', $product->variants_count, ['count' => $product->variants_count]) }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $product->base_price, 2) }}</td>
                                <td class="fg-success fw-bold text-end">
                                    {{ $currencySymbol }} {{ number_format((float) $product->selling_price, 2) }}
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-solid theme-info me-1"
                                        href="{{ route('inventory.products.show', $product) }}"
                                        title="{{ __('common.actions.view') }}">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('inventory.products.edit', $product) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $product->id }}" type="button"
                                            title="{{ __('common.actions.delete') }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off" class="d-none" id="delete-form-{{ $product->id }}"
                                            action="{{ route('inventory.products.destroy', $product) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="10">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $products->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script type="module">
        document.addEventListener('click', function(event) {
            const deleteButton = event.target.closest('.btn-delete');

            if (!deleteButton) {
                return;
            }

            const productId = deleteButton.getAttribute('data-id');

            confirmDialog({
                title: "{{ __('common.alerts.confirm_delete') }}",
                text: "{{ __('common.alerts.confirm_delete_message') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('common.alerts.confirm_delete_yes') }}",
                confirmTone: 'danger',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + productId).submit();
                }
            });
        });
    </script>
@endsection
