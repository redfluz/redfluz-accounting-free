@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use Carbon\Carbon;
    use Illuminate\Pagination\LengthAwarePaginator;

    $canCancel = auth()->user()->hasFilePermission(FileResources::GOODS_RECEIPT_ADJUSTMENTS, FileAction::CANCEL);
@endphp

@props([
    /** @var LengthAwarePaginator<int, \App\Models\Inventory\ProductLedger> */
    'entries',
    /** @var string|null */
    'search',
    /** @var string|null */
    'startDate',
    /** @var string|null */
    'endDate',
])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('goods_receipt_adjustments.history_title') }}</h4>
                <span class="fg-secondary small">{{ __('goods_receipt_adjustments.history_subtitle') }}</span>
            </div>
            <div class="d-flex gap-1">
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('purchase.goods-receipt-adjustments.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            @include('partials.date-range-filter', [
                'action' => route('purchase.goods-receipt-adjustments.history.index'),
                'search' => $search,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'placeholder' => __('goods_receipt_adjustments.search_placeholder'),
            ])

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('goods_receipts.table.column_document_number') }}</th>
                            <th>{{ __('goods_receipts.form.purchase_invoice') }}</th>
                            <th>{{ __('goods_receipts.table.column_date') }}</th>
                            <th>{{ __('goods_receipts.table.column_company') }}</th>
                            <th class="text-end">{{ __('goods_receipt_adjustments.column_increase') }}</th>
                            <th class="text-end">{{ __('goods_receipt_adjustments.column_decrease') }}</th>
                            <th class="text-end">{{ __('goods_receipts.table.column_lines') }}</th>
                            <th class="text-center">{{ __('goods_receipts.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="fw-bold">{{ $entry->document_number }}</td>
                                <td>
                                    <a href="{{ route('purchase.goods-receipts.show', $entry->document_number) }}">
                                        {{ $entry->reference_number }}
                                    </a>
                                </td>
                                <td>{{ Carbon::parse($entry->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $entry->company_name ?? '-' }}</td>
                                <td class="text-end fg-success">
                                    {{ (float) $entry->increase_quantity > 0 ? '+'.number_format((float) $entry->increase_quantity, 4) : '-' }}
                                </td>
                                <td class="text-end fg-danger">
                                    {{ (float) $entry->decrease_quantity > 0 ? '-'.number_format((float) $entry->decrease_quantity, 4) : '-' }}
                                </td>
                                <td class="text-end">{{ $entry->line_count }}</td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a
                                            class="btn btn-solid theme-primary btn-sm"
                                            href="{{ route('purchase.goods-receipts.show', $entry->document_number) }}"
                                            title="{{ __('common.actions.view') }}"
                                        >
                                            <i data-lucide="file-text"></i>
                                        </a>
                                        @if ($canCancel)
                                            <form autocomplete="off"
                                                action="{{ route('purchase.goods-receipt-adjustments.cancel', $entry->id) }}"
                                                method="POST"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    class="btn btn-sm btn-outline theme-danger"
                                                    type="submit"
                                                    title="{{ __('common.actions.cancel') }}"
                                                    data-confirm-message="{{ __('goods_receipt_adjustments.cancel_confirmation') }}"
                                                >
                                                    <i data-lucide="x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="8">
                                    {{ __('goods_receipt_adjustments.history_empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($entries->hasPages())
                <div class="mt-3">{{ $entries->links() }}</div>
            @endif
        </div>
    </div>
@endsection