<?php

use App\Enums\FileAction;
use App\Enums\FileResources;

$canUpdate = auth()->user()->hasFilePermission(FileResources::BRANDS, FileAction::UPDATE);

?>

@props(['brand', 'products'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $brand->name }}</h4>
                <p class="fg-secondary small mb-0">{{ $brand->description ?? '-' }}</p>
            </div>
            <div class="d-flex gap-2">
                @if ($canUpdate)
                    <a class="btn btn-solid theme-warning btn-sm" href="{{ route('inventory.brands.edit', $brand) }}">
                        <i class="me-1" data-lucide="square-pen"></i>
                        {{ __('common.actions.edit') }}
                    </a>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('inventory.brands.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <h6 class="fw-bold mb-3">{{ __('brands.products_title') }}</h6>
            <div class="table-responsive rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('products.table.column_sku') }}</th>
                            <th>{{ __('products.table.column_name') }}</th>
                            <th>{{ __('products.table.column_type') }}</th>
                            <th class="text-end">{{ __('products.table.column_selling_price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td><code>{{ $product->sku }}</code></td>
                                <td>
                                    <a class="fw-bold text-decoration-none"
                                        href="{{ route('inventory.products.show', $product) }}">
                                        {{ $product->name }}
                                    </a>
                                </td>
                                <td>
                                    {{ $product->is_service ? __('products.types.service') : __('products.types.physical') }}
                                </td>
                                <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $product->selling_price, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="4">
                                    {{ __('brands.products_empty') }}
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
