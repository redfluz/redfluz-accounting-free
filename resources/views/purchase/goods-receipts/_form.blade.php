@php
    use App\Enums\UploadType;
@endphp

@props([
    'purchaseInvoices',
    'products',
    'selectedInvoiceNumber',
    'invoiceLines',
    'invoiceSummary',
    'postingDateMin',
    'postingDateMax',
    'stockLocations',
    'selectedStockLocationId',
    'goodsReceipt' => null,
    'entries' => null,
    'attachments' => null,
    'documentNumber' => null,
])

<div class="row g-3 mb-4">
    @if ($goodsReceipt === null)
        <div class="md:col-5">
            <label class="form-label fw-bold" for="purchase_order_number">
                {{ __('goods_receipts.form.purchase_invoice') }}
            </label>
            <select class="form-control @error('purchase_order_number') is-invalid @enderror"
                id="purchase_order_number" name="purchase_order_number" data-combobox
                data-create-url="{{ route('purchase.goods-receipts.create') }}" required>
                <option value="">{{ __('common.actions.select_option') }}</option>
                @foreach ($purchaseInvoices as $purchaseInvoice)
                    <option value="{{ $purchaseInvoice->document_number }}" @selected(old('purchase_order_number', $selectedInvoiceNumber) === $purchaseInvoice->document_number)>
                        {{ $purchaseInvoice->document_number }} - {{ $purchaseInvoice->company_name }} ({{ $currencySymbol }}
                        {{ number_format((float) $purchaseInvoice->total_amount, 2) }})
                    </option>
                @endforeach
            </select>
            <div class="form-text">{{ __('goods_receipts.form.purchase_invoice_hint') }}</div>
            @error('purchase_order_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @else
        <div class="md:col-5">
            <label class="form-label fw-bold" for="purchase_order_display">
                {{ __('goods_receipts.form.purchase_invoice') }}
            </label>
            <div class="form-control form-adorn">
                <input class="form-ghost" id="purchase_order_display" type="text"
                    value="{{ $selectedInvoiceNumber }} - {{ $goodsReceipt->company_name }}" readonly />
            </div>
            <input name="purchase_order_number" type="hidden" value="{{ $selectedInvoiceNumber }}" />
        </div>
    @endif

    <div class="md:col-3">
        <label class="form-label fw-bold" for="date">
            {{ __('goods_receipts.form.received_date') }}
        </label>
        <div class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="calendar"></i>
            </div>
            <input class="form-ghost @error('date') is-invalid @enderror" id="date" name="date"
                type="text" data-bs-toggle="datepicker" autocomplete="off"
                value="{{ old('date', $goodsReceipt?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                min="{{ $postingDateMin ?? '' }}" max="{{ $postingDateMax ?? '' }}" required />
        </div>
        @error('date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="md:col-4">
        <label class="form-label fw-bold" for="stock_location_id">
            {{ __('goods_receipts.form.stock_location') }}
        </label>
        <select class="form-control @error('stock_location_id') is-invalid @enderror" id="stock_location_id"
            name="stock_location_id" required>
            <option value="">{{ __('common.actions.select_option') }}</option>
            @foreach ($stockLocations as $stockLocation)
                <option value="{{ $stockLocation->id }}" @selected(old('stock_location_id', $selectedStockLocationId) === $stockLocation->id)>
                    {{ $stockLocation->code }} - {{ $stockLocation->name }}
                </option>
            @endforeach
        </select>
        @error('stock_location_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@if ($stockLocations->isEmpty())
    <div class="alert alert theme-danger">{{ __('goods_receipts.messages.no_stock_location') }}</div>
@endif

@if ($goodsReceipt === null && $selectedInvoiceNumber === '')
    <div class="alert alert theme-info mb-4">
        <i class="me-1" data-lucide="info"></i>
        {{ __('goods_receipts.messages.select_invoice') }}
    </div>
@elseif ($invoiceLines->isEmpty())
    <div class="alert alert theme-warning mb-{{ $goodsReceipt ? '3' : '4' }}">
        <i class="me-1" data-lucide="triangle-alert"></i>
        {{ __('goods_receipts.messages.manual_product_required') }}
    </div>

    @if ($goodsReceipt)
        <div class="table-responsive rounded border">
            <table class="table-hover table-striped mb-0 table align-middle">
                <thead class="bg-1">
                    <tr>
                        <th scope="col">{{ __('goods_receipts.form.product') }}</th>
                        <th scope="col" style="width: 22%">{{ __('goods_receipts.form.quantity') }}</th>
                        <th scope="col" style="width: 22%">{{ __('goods_receipts.form.unit_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($lineNumber = 0; $lineNumber < $perPageGridDetail; $lineNumber++)
                        @php
                            $entry = $entries?->get($lineNumber);
                        @endphp
                        <tr>
                            <td>
                                <label class="visually-hidden"
                                    for="line-{{ $lineNumber }}-product_id">{{ __('goods_receipts.form.product') }}</label>
                                <select
                                    id="line-{{ $lineNumber }}-product_id"
                                    class="form-control @error('lines.' . $lineNumber . '.product_id') is-invalid @enderror"
                                    name="lines[{{ $lineNumber }}][product_id]" data-combobox>
                                    <option value="">{{ __('common.actions.select_option') }}</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" @selected(old("lines.{$lineNumber}.product_id", $entry?->product_id) === $product->id)>
                                            {{ $product->sku }} - {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('lines.' . $lineNumber . '.product_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <div class="form-control form-adorn">
                                    <label class="visually-hidden"
                                        for="line-{{ $lineNumber }}-quantity">{{ __('goods_receipts.form.quantity') }}</label>
                                    <input
                                        id="line-{{ $lineNumber }}-quantity"
                                        class="form-ghost @error('lines.' . $lineNumber . '.quantity') is-invalid @enderror"
                                        name="lines[{{ $lineNumber }}][quantity]" type="number"
                                        value="{{ old("lines.{$lineNumber}.quantity", $entry?->quantity) }}"
                                        min="0"
                                        max="{{ $invoiceSummary['remaining_quantity'] ?? 0 }}"
                                        step="0.0001" />
                                </div>
                                @error('lines.' . $lineNumber . '.quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <div class="form-control form-adorn">
                                    <label class="visually-hidden"
                                        for="line-{{ $lineNumber }}-unit_cost">{{ __('goods_receipts.form.unit_cost') }}</label>
                                    <input
                                        id="line-{{ $lineNumber }}-unit_cost"
                                        class="form-ghost @error('lines.' . $lineNumber . '.unit_cost') is-invalid @enderror"
                                        name="lines[{{ $lineNumber }}][unit_cost]" type="number"
                                        value="{{ old("lines.{$lineNumber}.unit_cost", $entry?->unit_cost ?? $invoiceSummary['unit_cost'] ?? '') }}"
                                        min="0" step="0.01" />
                                </div>
                                @error('lines.' . $lineNumber . '.unit_cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    @else
        @if ($invoiceSummary !== null)
            <div class="row g-3 mb-3">
                <div class="md:col-4">
                    <div class="bg-2 rounded border p-3">
                        <div class="fg-secondary small">{{ __('goods_receipts.form.ordered_quantity') }}</div>
                        <div class="fs-xl fw-bold">
                            {{ number_format((float) $invoiceSummary['ordered_quantity'], 4) }}
                        </div>
                    </div>
                </div>
                <div class="md:col-4">
                    <div class="bg-2 rounded border p-3">
                        <div class="fg-secondary small">{{ __('goods_receipts.form.previously_received') }}</div>
                        <div class="fs-xl fw-bold">
                            {{ number_format((float) $invoiceSummary['received_quantity'], 4) }}
                        </div>
                    </div>
                </div>
                <div class="md:col-4">
                    <div class="bg-2 rounded border p-3">
                        <div class="fg-secondary small">{{ __('goods_receipts.form.remaining_quantity') }}</div>
                        <div class="fs-xl fw-bold">
                            {{ number_format((float) $invoiceSummary['remaining_quantity'], 4) }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="table-responsive rounded border">
            <table class="table-hover table-striped mb-0 table align-middle">
                <thead class="bg-1">
                    <tr>
                        <th scope="col">{{ __('goods_receipts.form.product') }}</th>
                        <th scope="col" style="width: 22%">{{ __('goods_receipts.form.quantity') }}</th>
                        <th scope="col" style="width: 22%">{{ __('goods_receipts.form.unit_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($lineNumber = 0; $lineNumber < $perPageGridDetail; $lineNumber++)
                        <tr data-line-index="{{ $lineNumber }}">
                            <td>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="lines[{{ $lineNumber }}][product_id]"
                                        class="grn-product-id"
                                        value="{{ old("lines.{$lineNumber}.product_id") }}">
                                    <div
                                        class="grn-product-display form-control d-flex align-items-center justify-content-between">
                                        <span
                                            class="grn-product-label text-truncate">{{ old("lines.{$lineNumber}.product_id") ? $products->firstWhere('id', old("lines.{$lineNumber}.product_id"))?->sku . ' - ' . $products->firstWhere('id', old("lines.{$lineNumber}.product_id"))?->name : __('goods_receipts.form.no_product_selected') }}</span>
                                    </div>
                                    <button
                                        class="btn btn-solid theme-primary grn-product-choose-btn flex-shrink-0"
                                        type="button" data-line-index="{{ $lineNumber }}">
                                        <i data-lucide="search"></i>
                                    </button>
                                </div>
                                @error('lines.' . $lineNumber . '.product_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <div class="form-control form-adorn">
                                    <label class="visually-hidden"
                                        for="line-{{ $lineNumber }}-quantity">{{ __('goods_receipts.form.quantity') }}</label>
                                    <input
                                        id="line-{{ $lineNumber }}-quantity"
                                        class="form-ghost @error('lines.' . $lineNumber . '.quantity') is-invalid @enderror"
                                        name="lines[{{ $lineNumber }}][quantity]" type="number"
                                        value="{{ old("lines.{$lineNumber}.quantity") }}"
                                        min="0"
                                        max="{{ $invoiceSummary['remaining_quantity'] ?? 0 }}"
                                        step="0.0001" />
                                </div>
                                @error('lines.' . $lineNumber . '.quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <div class="form-control form-adorn">
                                    <label class="visually-hidden"
                                        for="line-{{ $lineNumber }}-unit_cost">{{ __('goods_receipts.form.unit_cost') }}</label>
                                    <input
                                        id="line-{{ $lineNumber }}-unit_cost"
                                        class="form-ghost @error('lines.' . $lineNumber . '.unit_cost') is-invalid @enderror"
                                        name="lines[{{ $lineNumber }}][unit_cost]" type="number"
                                        value="{{ old("lines.{$lineNumber}.unit_cost", $invoiceSummary['unit_cost'] ?? '') }}"
                                        min="0" step="0.01" />
                                </div>
                                @error('lines.' . $lineNumber . '.unit_cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    @endif
@else
    <div class="table-responsive rounded border">
        <table class="table-hover table-striped mb-0 table align-middle">
            <thead class="bg-1">
                <tr>
                    <th scope="col">{{ __('goods_receipts.form.product') }}</th>
                    <th scope="col" class="text-end">{{ __('goods_receipts.form.ordered_quantity') }}</th>
                    <th scope="col" class="text-end">{{ __('goods_receipts.form.previously_received') }}</th>
                    <th scope="col" class="text-end">{{ __($goodsReceipt ? 'goods_receipts.form.available_quantity' : 'goods_receipts.form.remaining_quantity') }}</th>
                    <th scope="col" style="width: 18%">{{ __('goods_receipts.form.quantity') }}</th>
                    <th scope="col" class="text-end">{{ __('goods_receipts.form.unit_cost') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoiceLines as $lineNumber => $invoiceLine)
                    @php
                        $currentQuantity = $entries
                            ->where('product_id', $invoiceLine['product_id'])
                            ->sum('quantity');
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $invoiceLine['product_name'] }}</div>
                            <div class="fg-secondary small">{{ $invoiceLine['product_sku'] }}</div>
                            <input name="lines[{{ $lineNumber }}][product_id]" type="hidden"
                                value="{{ $invoiceLine['product_id'] }}" />
                        </td>
                        <td class="text-end">
                            {{ number_format((float) $invoiceLine['ordered_quantity'], 4) }}
                        </td>
                        <td class="text-end">
                            {{ number_format((float) $invoiceLine['received_quantity'], 4) }}
                        </td>
                        <td class="fw-bold text-end">
                            {{ number_format((float) $invoiceLine['remaining_quantity'], 4) }}
                        </td>
                        <td>
                            <div class="form-control form-adorn">
                                <input
                                    class="form-ghost @error('lines.' . $lineNumber . '.quantity') is-invalid @enderror"
                                    name="lines[{{ $lineNumber }}][quantity]" type="number"
                                    value="{{ old("lines.{$lineNumber}.quantity", $goodsReceipt ? $currentQuantity : $invoiceLine['remaining_quantity']) }}"
                                    min="0" max="{{ $invoiceLine['remaining_quantity'] }}"
                                    step="0.0001" @if (!$goodsReceipt) @disabled((float) $invoiceLine['remaining_quantity'] <= 0) @endif />
                            </div>
                            @error('lines.' . $lineNumber . '.quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </td>
                        <td class="text-end">
                            {{ $currencySymbol }} {{ number_format((float) $invoiceLine['unit_cost'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="mt-3">
    <label class="form-label fw-bold" for="goods-receipt-attachments">
        {{ __('goods_receipts.form.attachments') }}
    </label>
    <input accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}"
        class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
        id="goods-receipt-attachments" name="attachments[]" type="file" multiple />
    <div class="form-text">{{ __('goods_receipts.form.attachments_hint') }}</div>
    @error('attachments')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('attachments.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

@if ($goodsReceipt !== null && $attachments->isNotEmpty())
    <div class="mt-3">
        <h6 class="fw-bold">{{ __('goods_receipts.form.existing_attachments') }}</h6>
        <div class="list-group">
            @foreach ($attachments as $attachment)
            <label class="list-group-item d-flex align-items-center justify-content-between gap-3">
                <span>
                    <span class="fw-bold d-block">{{ $attachment->original_name }}</span>
                    <small class="badge theme-secondary">
                        {{ number_format($attachment->file_size / 1024, 1) }} KB
                        @if ($attachment->uploaded_by_name)
                            | {{ $attachment->uploaded_by_name }}
                        @endif
                    </small>
                </span>
                <span class="mb-0">
                    <input class="check" id="delete-goods-receipt-attachment-{{ $attachment->id }}"
                        name="delete_attachments[]" type="checkbox" value="{{ $attachment->id }}"
                        @checked(in_array($attachment->id, old('delete_attachments', []), true)) />
                    <span class="fg-danger">
                        {{ __('goods_receipts.actions.remove_attachment') }}
                    </span>
                </span>
            </label>
            @endforeach
        </div>
    </div>
@endif

<div class="mt-3">
    <label class="form-label fw-bold" for="notes">
        {{ __('goods_receipts.form.notes') }}
    </label>
    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $goodsReceipt?->notes) }}</textarea>
    @error('notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex justify-content-between mt-4">
    <a class="btn btn-solid theme-secondary" href="{{ route('purchase.goods-receipts.' . ($goodsReceipt ? 'show' : 'index'), $goodsReceipt ? $documentNumber : null) }}">
        <i class="me-1" data-lucide="arrow-left"></i>
        {{ __('common.actions.back') }}
    </a>
    <button class="btn btn-solid theme-accent" type="submit" @disabled(
        $stockLocations->isEmpty() ||
            $selectedInvoiceNumber === '' ||
            $invoiceSummary === null ||
            (float) ($invoiceSummary['remaining_quantity'] ?? 0) <= 0)>
        <i class="me-1" data-lucide="save"></i>
        {{ __($goodsReceipt ? 'common.actions.update' : 'common.actions.save') }}
    </button>
</div>
