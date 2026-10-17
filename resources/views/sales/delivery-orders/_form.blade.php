@php
    use App\Enums\DeliveryOrderStatus;
    use App\Enums\ShippingStatus;

    $existingLines = $deliveryOrder?->lines?->keyBy('sales_order_line_id') ?? collect();
    $isPosted = $deliveryOrder?->status === DeliveryOrderStatus::POSTED;
    $companyAddresses = $selectedSalesOrder?->company?->addresses ?? collect();
    $selectedCompanyAddressId = old(
        'company_address_id',
        $deliveryOrder?->company_address_id ?? $companyAddresses->firstWhere('is_default', true)?->id,
    );
    $selectedCompanyAddress = $companyAddresses->firstWhere('id', $selectedCompanyAddressId);
@endphp
@if ($deliveryOrder !== null)
    <input name="id" type="hidden" value="{{ $deliveryOrder->id }}" />
@endif
<ul class="nav nav-tabs mb-4" id="delivery-order-tabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-delivery-order-tab="general" type="button">
            {{ __('delivery_orders.tabs.general') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-delivery-order-tab="attachment" type="button">
            {{ __('delivery_orders.tabs.attachment') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link disabled" type="button" disabled>{{ __('delivery_orders.tabs.payment') }}</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-delivery-order-tab="shipping" type="button">
            {{ __('delivery_orders.tabs.shipping') }}
        </button>
    </li>
</ul>
<div class="row g-3 mb-4">
    <div class="md:col-6" data-delivery-order-section="general">
        <label class="form-label" for="sales_order_id">{{ __('delivery_orders.form.sales_order') }}</label>
        <label class="form-control form-adorn">
            <input class="form-ghost" id="sales_order_id"
                value="{{ $selectedSalesOrder->document_number }} - {{ $selectedSalesOrder->company_name }}" readonly />
        </label>
        <input name="sales_order_id" type="hidden" value="{{ $selectedSalesOrder->id }}" />
    </div>
    <div class="md:col-6 d-none" data-delivery-order-section="shipping">
        <label class="form-label" for="stock_location_id">{{ __('delivery_orders.form.stock_location') }}</label>
        <select class="form-control @error('stock_location_id') is-invalid @enderror" id="stock_location_id"
            name="stock_location_id" required @disabled($isPosted)>
            <option value="">{{ __('common.actions.select_option') }}</option>
            @foreach ($stockLocations as $location)
                <option value="{{ $location->id }}" @selected(old('stock_location_id', $deliveryOrder?->stock_location_id ?? $stockLocations->first()?->id) === $location->id)>
                    {{ $location->code }} - {{ $location->name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('delivery_orders.messages.stock_location') }}</div>
        @error('stock_location_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-3" data-delivery-order-section="general">
        <label class="form-label" for="delivery_date">{{ __('delivery_orders.form.delivery_date') }}</label><label
            class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="calendar"></i>
            </div>
            <input
                class="form-ghost" id="delivery_date" name="delivery_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                value="{{ old('delivery_date', $deliveryOrder?->delivery_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                required @readonly($isPosted) />
        </label>
    </div>
    <div class="md:col-3 d-none" data-delivery-order-section="shipping">
        <label class="form-label" for="shipping_status">{{ __('delivery_orders.form.shipping_status') }}</label><select
            class="form-control" id="shipping_status" name="shipping_status">
            @foreach (ShippingStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('shipping_status', $deliveryOrder?->shipping_status?->value ?? ShippingStatus::PENDING->value) === $status->value)>
                    {{ __('delivery_orders.shipping_status.' . $status->value) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="md:col-6 d-none" data-delivery-order-section="shipping">
        <label class="form-label" for="company_address_id">{{ __('delivery_orders.form.company_address') }}</label>
        <select class="form-control @error('company_address_id') is-invalid @enderror" id="company_address_id"
            name="company_address_id">
            <option value="">{{ __('delivery_orders.form.custom_address') }}</option>
            @foreach ($companyAddresses as $address)
                <option value="{{ $address->id }}" data-address="{{ $address->address }}"
                    data-phone="{{ $address->phone }}" @selected($selectedCompanyAddressId === $address->id)>
                    {{ $address->name }}{{ $address->is_default ? ' — ' . __('delivery_orders.form.default_address') : '' }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('delivery_orders.messages.company_address') }}</div>
        @error('company_address_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6 d-none" data-delivery-order-section="shipping">
        <label class="form-label" for="recipient_name">{{ __('delivery_orders.form.recipient_name') }}</label>
        <label class="form-control form-adorn">
            <input
                class="form-ghost @error('recipient_name') is-invalid @enderror" id="recipient_name"
                name="recipient_name"
                value="{{ old('recipient_name', $deliveryOrder?->recipient_name ?? ($selectedSalesOrder?->contact_name ?? $selectedSalesOrder?->company_name)) }}"
                required />
        </label>
        @error('recipient_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6 d-none" data-delivery-order-section="shipping">
        <label class="form-label" for="recipient_phone">{{ __('delivery_orders.form.recipient_phone') }}</label>
        <label class="form-control form-adorn">
            <input
                class="form-ghost @error('recipient_phone') is-invalid @enderror" id="recipient_phone"
                name="recipient_phone"
                value="{{ old('recipient_phone', $deliveryOrder?->recipient_phone ?? ($selectedCompanyAddress?->phone ?? $selectedSalesOrder?->company?->phone)) }}" />
        </label>
        @error('recipient_phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12 d-none" data-delivery-order-section="shipping">
        <label class="form-label" for="delivery_address">{{ __('delivery_orders.form.delivery_address') }}</label>
        <textarea class="form-control @error('delivery_address') is-invalid @enderror" id="delivery_address"
            name="delivery_address" rows="3" required>{{ old('delivery_address', $deliveryOrder?->delivery_address ?? ($selectedCompanyAddress?->address ?? $selectedSalesOrder?->company?->address)) }}</textarea>
        <div class="form-text">{{ __('delivery_orders.messages.address_per_delivery') }}</div>
        @error('delivery_address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-4 d-none" data-delivery-order-section="shipping">
        <label class="form-label" for="courier_company_id">{{ __('delivery_orders.form.courier') }}</label>
        <select class="form-control @error('courier_company_id') is-invalid @enderror" id="courier_company_id"
            name="courier_company_id">
            <option value="">{{ __('common.actions.select_option') }}</option>
            @foreach ($courierCompanies as $courierCompany)
                <option value="{{ $courierCompany->id }}" @selected(old('courier_company_id', $deliveryOrder?->courier_company_id) === $courierCompany->id)>
                    {{ $courierCompany->name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('delivery_orders.messages.courier_company') }}</div>
        @error('courier_company_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-4 d-none" data-delivery-order-section="shipping">
        <label class="form-label"
            for="tracking_number">{{ __('delivery_orders.form.tracking_number') }}</label><label
            class="form-control form-adorn">
            <input
                class="form-ghost"
                id="tracking_number" name="tracking_number"
                value="{{ old('tracking_number', $deliveryOrder?->tracking_number) }}" />
        </label>
    </div>
    <div class="md:col-4 d-none" data-delivery-order-section="shipping">
        <label class="form-label" for="shipping_date">{{ __('delivery_orders.form.shipping_date') }}</label><label
            class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="calendar"></i>
            </div>
            <input
                class="form-ghost" id="shipping_date" name="shipping_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                value="{{ old('shipping_date', $deliveryOrder?->shipping_date?->format('Y-m-d')) }}" />
        </label>
    </div>
</div>
<div class="d-none mb-4" data-delivery-order-section="attachment">
    <label class="form-label" for="attachment">{{ __('delivery_orders.form.attachment') }}</label><input
        class="form-control" id="attachment" name="attachment" type="file"
        accept="{{ \App\Enums\UploadType::DOCUMENT_AND_IMAGES->accept() }}" />
    @if ($deliveryOrder?->attachment_path)
        <div class="form-text">{{ basename($deliveryOrder->attachment_path) }}</div>
    @endif
</div>
<div class="d-none mb-4" data-delivery-order-section="shipping">
    <label class="form-label" for="notes">{{ __('delivery_orders.form.notes') }}</label>
    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $deliveryOrder?->notes) }}</textarea>
</div>
@if ($isPosted)
    <div class="table-responsive border-top mb-4 rounded border pt-4" id="delivery-order-lines-section">
        <table class="table-form table-hover table-striped mb-0 table align-middle">
            <thead class="bg-1">
                <tr>
                    <th>{{ __('delivery_orders.form.stock_item') }}</th>
                    <th>{{ __('sales_orders.form.description') }}</th>
                    <th class="text-end">{{ __('delivery_orders.form.quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveryOrder->lines as $line)
                    <tr>
                        <td>
                            <strong>{{ $line->product_name }}</strong><small
                                class="fg-secondary d-block">{{ $line->product_sku }}</small>
                        </td>
                        <td>{{ $line->description ?? '-' }}</td>
                        <td class="text-end">{{ $line->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif ($selectedSalesOrder)
    <div class="table-responsive border-top mb-4 rounded border pt-4" id="delivery-order-lines-section">
        <table class="table-form table-striped mb-0 table align-middle" id="delivery-lines">
            <thead class="bg-1">
                <tr>
                    <th>{{ __('delivery_orders.form.stock_item') }}</th>
                    <th class="text-end">{{ __('delivery_orders.form.ordered') }}</th>
                    <th class="text-end">{{ __('delivery_orders.form.previously_delivered') }}</th>
                    <th class="text-end">{{ __('delivery_orders.form.available') }}</th>
                    <th style="width: 180px">{{ __('delivery_orders.form.quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($selectedSalesOrder->lines->where('is_service', false) as $index => $line)
                    @php($remaining = bcsub((string) $line->ordered_quantity, (string) $line->delivered_quantity, 4))
                    @if (bccomp($remaining, '0.0000', 4) === 1)
                        <tr>
                            <td>
                                <strong>{{ $line->product_name }}</strong><small
                                    class="fg-secondary d-block">{{ $line->product_sku }}</small><input
                                    name="lines[{{ $index }}][sales_order_line_id]" type="hidden"
                                    value="{{ $line->id }}" />
                            </td>
                            <td class="text-end">{{ $line->ordered_quantity }}</td>
                            <td class="text-end">{{ $line->delivered_quantity }}</td>
                            <td class="fw-bold text-end">{{ $remaining }}</td>
                            <td>
                                <label class="form-control form-adorn">
                                    <input class="form-ghost delivery-quantity"
                                        name="lines[{{ $index }}][quantity]" type="number"
                                        value="{{ old("lines.$index.quantity", $existingLines->get($line->id)?->quantity ?? $remaining) }}"
                                        min="0" max="{{ $remaining }}" step="0.0001" />
                                </label>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endif
<div class="d-flex justify-content-between">
    <a class="btn btn-solid theme-secondary" href="{{ route('sales.delivery-orders.index') }}">
        <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
    </a>
    <button class="btn btn-solid theme-accent" type="submit" @disabled($selectedSalesOrder === null)>
        <i class="me-1" data-lucide="save"></i>
        {{ $deliveryOrder === null ? __('common.actions.save') : __('common.actions.update') }}
    </button>
</div>
@push('scripts')
    <script>
        let deliveryOrderInvalidTabActivated = false;
        const deliveryOrderForm = document.querySelector('[data-delivery-order-section]')?.closest('form');
        deliveryOrderForm?.addEventListener(
            'invalid',
            (event) => {
                if (deliveryOrderInvalidTabActivated) return;

                const section = event.target.closest('[data-delivery-order-section]');
                const sectionName =
                    section?.dataset.deliveryOrderSection ??
                    (event.target.closest('#delivery-order-lines-section') ? 'general' : null);
                const tab = sectionName ? document.querySelector(`[data-delivery-order-tab="${sectionName}"]`) : null;
                if (tab) {
                    deliveryOrderInvalidTabActivated = true;
                    tab.click();
                    setTimeout(() => (deliveryOrderInvalidTabActivated = false), 0);
                }
            },
            true,
        );

        document.querySelectorAll('[data-delivery-order-tab]').forEach((tab) =>
            tab.addEventListener('click', () => {
                document
                    .querySelectorAll('[data-delivery-order-tab]')
                    .forEach((item) => item.classList.toggle('active', item === tab));
                document
                    .querySelectorAll('[data-delivery-order-section]')
                    .forEach((section) =>
                        section.classList.toggle(
                            'd-none',
                            section.dataset.deliveryOrderSection !== tab.dataset.deliveryOrderTab,
                        ),
                    );
                document
                    .getElementById('delivery-order-lines-section')
                    ?.classList.toggle('d-none', tab.dataset.deliveryOrderTab !== 'general');
            }),
        );

        document.getElementById('company_address_id')?.addEventListener('change', (event) => {
            const option = event.target.selectedOptions[0];
            if (!option?.value) return;

            document.getElementById('delivery_address').value = option.dataset.address ?? '';
            document.getElementById('recipient_phone').value = option.dataset.phone ?? '';
        });
    </script>
@endpush
