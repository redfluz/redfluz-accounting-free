@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Enums\ProductMovementDirection;
    use Carbon\Carbon;
    use Illuminate\Pagination\LengthAwarePaginator;

    $canRestore = auth()->user()->hasFilePermission(FileResources::GOODS_RECEIPT_ADJUSTMENTS, FileAction::CANCEL);
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
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">
                    {{ $entries->isNotEmpty() ? Carbon::parse($entries->first()->date)->format(Setting::getDefaultDate()) : '' }}
                </span>
            </div>
            <div class="d-flex gap-1">
                @if ($canRestore && $entryId !== null)
                    <form autocomplete="off" action="{{ route('purchase.goods-receipt-adjustments.uncancel', $entryId) }}" method="POST"
                        data-confirm-message="{{ __('goods_receipt_adjustments.restore_confirmation') }}"
                        data-confirm-tone="danger">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm btn-outline theme-success" type="submit"
                            title="{{ __('goods_receipt_adjustments.restore') }}">
                            <i class="me-1" data-lucide="rotate-ccw"></i>
                            {{ __('goods_receipt_adjustments.restore') }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm"
                    href="{{ route('purchase.goods-receipt-adjustments.cancelled.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="alert alert theme-secondary d-flex align-items-center gap-2" role="alert">
                <i data-lucide="info"></i>
                <div>{{ __('goods_receipt_adjustments.cancelled_subtitle') }}</div>
            </div>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('goods_receipts.table.column_sku') }}</th>
                            <th>{{ __('goods_receipts.table.column_product') }}</th>
                            <th class="text-end">{{ __('goods_receipts.adjustment.column_current') }}</th>
                            <th class="text-end">{{ __('goods_receipts.adjustment.column_difference') }}</th>
                            <th>{{ __('goods_receipts.line_state.column_state') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php($isIncrease = $entry->direction === ProductMovementDirection::IN)
                            <tr>
                                <td><code>{{ $entry->product_sku }}</code></td>
                                <td>{{ $entry->product_name }}</td>
                                <td class="text-end">{{ number_format((float) $entry->quantity, 4) }}</td>
                                <td class="text-end">
                                    {{ $isIncrease ? '+' : '-' }}{{ number_format((float) $entry->quantity, 4) }}
                                </td>
                                <td>
                                    <span class="badge theme-secondary">
                                        {{ __('goods_receipts.line_state.pending_correction') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="5">
                                    {{ __('goods_receipt_adjustments.cancelled_empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
