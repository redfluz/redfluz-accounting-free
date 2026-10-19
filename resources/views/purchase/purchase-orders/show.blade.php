@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canApprove = auth()->user()->hasFilePermission(FileResources::PURCHASE_ORDERS, FileAction::APPROVE);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::PURCHASE_ORDERS, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::PURCHASE_ORDERS, FileAction::REMOVE);
    $canCreateInvoice = auth()->user()->hasFilePermission(FileResources::PURCHASE_INVOICES, FileAction::ADD);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $purchaseOrder->document_number }}</h4>
                <p class="fg-secondary small mb-0">
                    {{ $purchaseOrder->company_name }} | {{ __('purchase_orders.status.' . ($purchaseOrder->cancelled_at ? 'cancelled' : ($purchaseOrder->is_received ? 'received' : ($purchaseOrder->is_partial_received ? 'partially_received' : ($purchaseOrder->approved_at ? 'approved' : 'draft'))))) }}
                </p>
            </div>
            <div class="d-flex gap-2">
                @if ($purchaseOrder->approved_at === null)
                    @if ($canUpdate)
                        <a class="btn btn-solid theme-warning btn-sm"
                            href="{{ route('purchase.purchase-orders.edit', $purchaseOrder) }}"><i class="me-1"
                                data-lucide="square-pen"></i>{{ __('common.actions.edit') }}</a>
                    @endif
                    @if ($canApprove)
                        <form autocomplete="off" action="{{ route('purchase.purchase-orders.approve', $purchaseOrder) }}"
                            method="POST">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-solid theme-success btn-sm" type="submit">
                                <i class="me-1" data-lucide="circle-check"></i>{{ __('common.actions.approve') }}
                            </button>
                        </form>
                    @endif
                    @if ($canRemove)
                        <form autocomplete="off" action="{{ route('purchase.purchase-orders.destroy', $purchaseOrder) }}"
                            method="POST" data-confirm-message="{{ __('purchase_orders.messages.delete_confirm') }}"
                            data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-solid theme-danger btn-sm" type="submit">
                                <i class="me-1" data-lucide="trash-2"></i>{{ __('common.actions.delete') }}
                            </button>
                        </form>
                    @endif
                @endif
                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('purchase.purchase-orders.pdf', $purchaseOrder) }}" target="_blank">
                    <i class="me-1" data-lucide="printer"></i>{{ __('common.actions.print_pdf') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('purchase.purchase-orders.index') }}"><i
                        class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')

            @include('partials.errors')

            <div class="row g-3 mb-4">
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small class="badge theme-secondary">{{ __('purchase_orders.form.date') }}</small>
                        <div class="fw-bold">{{ $purchaseOrder->date->format(Setting::getDefaultDate()) }}</div>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small class="badge theme-secondary">{{ __('purchase_orders.form.expected_date') }}</small>
                        <div class="fw-bold">{{ $purchaseOrder->expected_date?->format(Setting::getDefaultDate()) ?? '-' }}</div>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="rounded border p-3">
                        <small class="badge theme-secondary">{{ __('purchase_orders.form.contact') }}</small>
                        <div class="fw-bold">{{ $purchaseOrder->contact_name ?? '-' }}</div>
                        <small class="badge theme-secondary">{{ __('purchase_orders.form.supplier_reference') }}:
                            {{ $purchaseOrder->supplier_reference_number ?? '-' }}</small>
                    </div>
                </div>
            </div>
            <div class="md:col-3">
                <div class="rounded border p-3">
                    <small class="badge theme-secondary">{{ __('purchase_orders.form.credit_term_days') }}</small>
                    <div class="fw-bold">
                        {{ $purchaseOrder->credit_term_days }} {{ __('purchase_order_settings.days') }} |
                        {{ $purchaseOrder->due_date?->format(Setting::getDefaultDate()) ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive rounded border">
            <table class="table-hover table-striped mb-0 table align-middle">
                <thead class="bg-1">
                    <tr>
                        <th>{{ __('purchase_orders.form.description') }}</th>
                        <th class="text-end">{{ __('purchase_orders.form.quantity') }}</th>
                        <th class="text-end">{{ __('purchase_orders.form.unit_price') }}</th>
                        <th class="text-end">{{ __('purchase_orders.table.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchaseOrder->lines as $line)
                        <tr>
                            <td>{{ $line->description ?? '-' }}</td>
                            <td class="text-end">{{ $line->quantity }}</td>
                            <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $line->unit_price, 2) }}</td>
                            <td class="fw-bold text-end">{{ $currencySymbol }} {{ number_format((float) $line->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-1">
                    <tr>
                        <th class="text-end" colspan="3">{{ __('purchase_orders.form.grand_total') }}</th>
                        <th class="text-end">{{ $currencySymbol }} {{ number_format((float) $purchaseOrder->total_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        @if ($purchaseOrder->notes)
            <div class="mt-3 rounded border p-3">
                <small class="badge theme-secondary">{{ __('purchase_orders.form.notes') }}</small>
                <div>{{ $purchaseOrder->notes }}</div>
            </div>
        @endif

        {{--
              The controller eager loads attachments, but this page never rendered
              them, so a file attached to an order could be uploaded and then never
              seen or downloaded again. Mirrors the goods receipt detail page.
          --}}
        @if ($purchaseOrder->attachments->isNotEmpty())
            <h6 class="fw-bold mt-4">{{ __('purchase_orders.form.attachments') }}</h6>
            <div class="list-group">
                @foreach ($purchaseOrder->attachments as $attachment)
                    <div class="list-group-item d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="fw-bold">{{ $attachment->original_name }}</div>
                            <small class="badge theme-secondary">
                                {{ number_format($attachment->file_size / 1024, 1) }} KB
                                @if ($attachment->uploaded_by_name)
                                    | {{ $attachment->uploaded_by_name }}
                                @endif
                                | {{ $attachment->created_at->format(Setting::getDefaultDate('long_date_format')) }}
                            </small>
                        </div>
                        <a class="btn btn-solid theme-primary btn-sm"
                            href="{{ route('purchase.purchase-orders.attachments.download', [$purchaseOrder, $attachment]) }}">
                            <i class="me-1" data-lucide="download"></i>
                            {{ __('common.actions.download') }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
        @if (
            $purchaseOrder->approved_at !== null && ! $purchaseOrder->is_partial_received && ! $purchaseOrder->is_received &&
                ! $purchaseOrder->invoice &&
                $canCreateInvoice)
            <div class="mt-3 rounded border p-3">
                <h6 class="fw-bold">{{ __('purchase_orders.messages.awaiting_sales_order') }}</h6>
                <form autocomplete="off" class="row g-3 align-items-end"
                    action="{{ route('purchase.purchase-orders.purchase-invoice', $purchaseOrder) }}" method="POST">
                    @csrf
                    <div class="md:col-5">
                        <label class="form-label"
                            for="supplier_sales_order_number">{{ __('purchase_orders.form.supplier_sales_order_number') }}</label><input
                            class="form-control" id="supplier_sales_order_number" name="supplier_sales_order_number"
                            value="{{ old('supplier_sales_order_number', $purchaseOrder->supplier_sales_order_number) }}"
                            required />
                    </div>
                    <div class="md:col-3">
                        <label class="form-label"
                            for="invoice_date">{{ __('purchase_orders.form.invoice_date') }}</label><input
                            class="form-control" id="invoice_date" name="invoice_date" type="text"
                            data-bs-toggle="datepicker" autocomplete="off"
                            value="{{ old('invoice_date', now()->format('Y-m-d')) }}" required />
                    </div>
                    <div class="md:col-4">
                        <button class="btn btn-solid theme-success" type="submit">
                            <i class="me-1" data-lucide="plus"></i>{{ __('common.actions.add') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif
        @if ($purchaseOrder->invoice)
            <div class="alert alert theme-success mb-0 mt-3">
                {{ __('purchase_orders.messages.invoice_created') }}:
                <a class="alert-link"
                    href="{{ route('purchase.invoices.show', $purchaseOrder->invoice->document_number) }}">{{ $purchaseOrder->invoice->document_number }}</a>
            </div>
        @endif
        <div class="alert mb-0 mt-3 border">{{ __('purchase_orders.messages.no_posting') }}</div>
    </div>
    </div>
@endsection
