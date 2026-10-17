@php
    use App\Models\System\Setting;
    use App\Enums\GoodsReceiptCompletion;
    use App\Enums\ProductMovementStatus;
@endphp

@props(['documentNumber', 'lines', 'adjusted'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('goods_receipts.adjustment.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('goods_receipts.adjustment.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-solid theme-secondary btn-sm"
                    href="{{ route('purchase.goods-receipts.show', $documentNumber) }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="alert alert theme-info d-flex align-items-start gap-2" role="alert">
                <i class="mt-1" data-lucide="info"></i>
                <div>
                    <strong>{{ $documentNumber }}</strong>
                    &mdash; {{ __('goods_receipts.adjustment.subtitle') }}
                </div>
            </div>

            <form autocomplete="off" action="{{ route('purchase.goods-receipts.adjust.store', $documentNumber) }}" method="POST">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="md:col-4">
                        <label class="form-label fw-bold" for="date">
                            {{ __('goods_receipts.form.received_date') }}
                        </label>
                        <input class="form-control @error('date') is-invalid @enderror" id="date" name="date"
                            type="text" data-bs-toggle="datepicker" autocomplete="off" value="{{ old('date', date('Y-m-d')) }}" />
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="md:col-8">
                        <label class="form-label fw-bold" for="notes">{{ __('common.actions.notes') }}</label>
                        <input class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                            type="text" value="{{ old('notes') }}" />
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="table-responsive rounded-5 overflow-hidden border mb-3">
                    <table class="table-hover table-striped mb-0 table align-middle">
                        <thead class="bg-1">
                            <tr>
                                <th><code>{{ __('goods_receipts.table.column_sku') }}</code></th>
                                <th>{{ __('goods_receipts.table.column_product') }}</th>
                                <th class="text-end">{{ __('goods_receipts.adjustment.column_current') }}</th>
                                <th class="text-end">{{ __('goods_receipts.adjustment.column_max') }}</th>
                                <th class="text-end">{{ __('goods_receipts.adjustment.column_headroom') }}</th>
                                <th class="text-end" style="width: 180px;">
                                    {{ __('goods_receipts.adjustment.column_corrected') }}
                                </th>
                                <th class="text-end">{{ __('goods_receipts.adjustment.column_difference') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $line)
                                @php($current = (float) $line['current_quantity'])
                                <tr>
                                    <td><code>{{ $line['product_sku'] }}</code></td>
                                    <td>{{ $line['product_name'] }}</td>
                                    <td class="text-end">{{ number_format($current, 4) }}</td>
                                    <td class="text-end">{{ number_format((float) $line['max_quantity'], 4) }}</td>
                                    <td class="text-end" data-headroom-for="{{ $loop->index }}">
                                        {{ number_format((float) $line['headroom_quantity'], 4) }}
                                    </td>
                                    <td class="text-end">
                                        <input
                                            class="form-control form-control-sm text-end @error("lines.{$loop->index}.quantity") is-invalid @enderror"
                                            name="lines[{{ $loop->index }}][quantity]" type="number" step="0.0001"
                                            min="0" max="{{ $current }}"
                                            data-current="{{ $current }}" data-max="{{ $line['max_quantity'] }}"
                                            value="{{ old("lines.{$loop->index}.quantity", number_format($current, 4, '.', '')) }}" />
                                        <input name="lines[{{ $loop->index }}][entry_id]" type="hidden"
                                            value="{{ $line['id'] }}" />
                                        @error("lines.{$loop->index}.quantity")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="text-end" data-difference-for="{{ $loop->index }}">
                                        {{ number_format($current, 4) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end">
                    <button class="btn btn-solid theme-accent" type="submit">
                        <i class="me-1" data-lucide="save"></i>
                        {{ __('common.actions.save') }}
                    </button>
                </div>
            </form>

            <hr class="my-4" />

            <h6 class="fw-bold mb-3">{{ __('goods_receipts.adjustment.recorded') }}</h6>

            @forelse ($adjusted as $correction)
                <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                    <div>
                        <code>{{ $correction->product_sku }}</code>
                        <span class="fg-secondary small ms-2">
                            {{ $correction->date?->format(Setting::getDefaultDate()) }}
                        </span>
                        @if ($correction->status === \App\Enums\ProductMovementStatus::ACTIVE)
                            <span class="badge theme-success ms-2">{{ __('product_ledgers.status.active') }}</span>
                        @else
                            <span class="badge theme-warning fg-black ms-2">{{ __('product_ledgers.status.pending') }}</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-end">
                            {{ $correction->direction === \App\Enums\ProductMovementDirection::IN ? '+' : '-' }}
                            {{ number_format((float) $correction->quantity, 4) }}
                        </span>
                        @if ($correction->status === \App\Enums\ProductMovementStatus::PENDING)
                            <form autocomplete="off"
                                action="{{ route('purchase.goods-receipts.adjust.destroy', [$documentNumber, $correction->id]) }}"
                                method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline theme-danger" type="submit">
                                    <i class="me-1" data-lucide="trash-2"></i>
                                    {{ __('goods_receipts.adjustment.discard') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="fg-secondary small">{{ __('goods_receipts.adjustment.no_corrections') }}</p>
            @endforelse

            @if ($adjusted->contains(fn($correction): bool => $correction->status === \App\Enums\ProductMovementStatus::PENDING))
                <form autocomplete="off" action="{{ route('purchase.goods-receipts.adjust.approve', $documentNumber) }}" method="POST"
                    class="mt-3">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-solid theme-success" type="submit"
                        data-confirm-message="{{ __('goods_receipts.messages.approve_confirmation') }}">
                        <i class="me-1" data-lucide="circle-check"></i>
                        {{ __('goods_receipts.adjustment.post_corrections') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                var rows = @json($lines->mapWithKeys(fn($line, $index) => [$index => (float) $line['current_quantity']]));

                document.querySelectorAll('input[name$="[quantity]"]').forEach(function(input) {
                    var match = input.name.match(/lines\[(\d+)\]\[quantity\]/);
                    if (!match) {
                        return;
                    }
                    var index = match[1];
                    var current = rows[index] ?? 0;

                    var max = parseFloat(input.dataset.max);

                    var update = function() {
                        var entered = parseFloat(input.value);
                        if (isNaN(entered)) {
                            return;
                        }

                        var headroom = max - entered;
                        var headroomCell = document.querySelector('[data-headroom-for="' + index + '"]');
                        if (headroomCell) {
                            // No colour on the figure: the clamped 0.0000 plus the
                            // `.is-invalid` input beside it are the over-limit cue.
                            headroomCell.textContent = headroom < 0 ? '0.0000' : headroom.toFixed(4);
                        }

                        var difference = entered - current;
                        var cell = document.querySelector('[data-difference-for="' + index + '"]');
                        if (!cell) {
                            return;
                        }
                        // Direction is shown by the sign, not by colour.
                        cell.textContent = (difference > 0 ? '+' : '') + difference.toFixed(4);

                        // Soft client side warning only. The server rejects it too.
                        input.classList.toggle('is-invalid', entered > max);
                    };

                    input.addEventListener('input', update);
                    update();
                });
            })();
        </script>
    @endpush
@endsection
