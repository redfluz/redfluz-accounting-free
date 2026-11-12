@php
    use App\Models\Sales\SalesInvoice;
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use Carbon\Carbon;

    $canViewInvoices = auth()->user()->hasFilePermission(FileResources::SALES_INVOICES);
@endphp
@props([
    /** @var SalesInvoice */
    'invoice',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('customer_outstanding.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('customer_outstanding.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <div class="accordion mb-4 accordion-gap">
                <details class="accordion-item">
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="list-filter"></i>
                        <span class="fw-bold">{{ __('financial_reports.form.filters') }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                        <form autocomplete="off" action="{{ route('sales.customer-outstanding.index') }}" method="GET">
                            <div class="row g-2 align-items-end">
                                <div class="md:col-3">
                                    <label class="form-label small mb-1"
                                           for="search">{{ __('common.actions.search') }}</label>
                                    <input class="form-control" id="search" name="search" value="{{ $search }}"
                                           placeholder="{{ __('customer_outstanding.search_placeholder') }}"/>
                                </div>
                                <div class="md:col-2">
                                    <label
                                        class="form-label small mb-1"
                                        for="start_date">{{ __('customer_outstanding.invoice_date_from') }}</label>
                                    <div class="form-control form-adorn">
                                        <div class="form-adorn-icon" aria-hidden="true">
                                            <i data-lucide="calendar"></i>
                                        </div>
                                        <input class="form-ghost" id="start_date" name="start_date" type="text"
                                               data-bs-toggle="datepicker" autocomplete="off"
                                               value="{{ $startDate }}"
                                               placeholder="{{ __('financial_reports.form.start_date') }}"/>
                                    </div>
                                </div>
                                <div class="md:col-2">
                                    <label
                                        class="form-label small mb-1"
                                        for="end_date">{{ __('customer_outstanding.invoice_date_to') }}</label>
                                    <div class="form-control form-adorn">
                                        <div class="form-adorn-icon" aria-hidden="true">
                                            <i data-lucide="calendar"></i>
                                        </div>
                                        <input class="form-ghost" id="end_date" name="end_date" type="text"
                                               data-bs-toggle="datepicker" autocomplete="off"
                                               value="{{ $endDate }}"
                                               placeholder="{{ __('financial_reports.form.end_date') }}"/>
                                    </div>
                                </div>
                                <div class="md:col-2">
                                    <label
                                        class="form-label small mb-1"
                                        for="as_of_date">{{ __('customer_outstanding.as_of_date') }}</label>
                                    <input class="form-control" id="as_of_date" name="as_of_date" type="text"
                                           data-bs-toggle="datepicker" autocomplete="off"
                                           value="{{ $asOfDate->toDateString() }}"/>
                                </div>
                                <div class="md:col-2">
                                    <label
                                        class="form-label small mb-1"
                                        for="status">{{ __('customer_outstanding.status') }}</label>
                                    <select class="form-control" id="status" name="status">
                                        @foreach (['outstanding', 'overdue', 'due_soon'] as $option)
                                            <option value="{{ $option }}" @selected($status === $option)>
                                                {{ __('customer_outstanding.statuses.' . $option) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-1 d-flex gap-2">
                                    <button class="btn btn-solid theme-primary" type="submit"
                                            title="{{ __('common.actions.search') }}">
                                        <i data-lucide="search"></i>
                                    </button>
                                    <a class="btn btn-outline border"
                                       href="{{ route('sales.customer-outstanding.index') }}"
                                       title="{{ __('common.actions.reset') }}">
                                        <i data-lucide="rotate-ccw"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                </details>

                <div class="table-responsive rounded-5 overflow-hidden border">
                    <table class="table-hover table-striped mb-0 table align-middle">
                        <thead class="bg-1">
                        <tr>
                            <th scope="col">{{ __('customer_outstanding.reference') }}</th>
                            <th scope="col">{{ __('customer_outstanding.customer') }}</th>
                            <th scope="col" class="text-end">{{ __('customer_outstanding.items_delivered') }}</th>
                            <th scope="col" class="text-end">{{ __('customer_outstanding.delivery_completion') }}</th>
                            <th scope="col">{{ __('customer_outstanding.invoice_date') }}</th>
                            <th scope="col">{{ __('customer_outstanding.credit_term') }}</th>
                            <th scope="col">{{ __('customer_outstanding.due_date') }}</th>
                            <th scope="col" class="text-end">{{ __('customer_outstanding.invoice_amount') }}</th>
                            <th scope="col" class="text-end">{{ __('customer_outstanding.paid_amount') }}</th>
                            <th scope="col" class="text-end">{{ __('customer_outstanding.balance') }}</th>
                            <th scope="col">{{ __('customer_outstanding.status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($invoices as $invoice)
                            @php
                                $dueDate = $invoice->due_date;
                                $isOverdue = $dueDate !== null && $dueDate->lt($asOfDate);
                            @endphp
                            <tr>
                                <td>
                                    @if ($canViewInvoices)
                                        <a class="fw-bold text-decoration-none"
                                           href="{{ route('sales.invoices.show', $invoice->document_number) }}">{{ $invoice->document_number }}</a>
                                    @else
                                        <span class="fw-bold">{{ $invoice->document_number }}</span>
                                    @endif
                                </td>
                                <td>{{ $invoice->company_name ?: '-' }}</td>
                                <td class="text-end">
                                    @if ($invoice->ordered_quantity > 0)
                                        {{ number_format((float) $invoice->delivered_quantity, 2) }} /
                                        {{ number_format((float) $invoice->ordered_quantity, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{ $invoice->delivery_completion !== null ? number_format((float) $invoice->delivery_completion, 1) . '%' : '-' }}
                                </td>
                                <td>{{ Carbon::parse($invoice->invoice_date)->format(Setting::getDefaultDate()) }}</td>
                                <td>
                                    {{ $invoice->due_date ? Carbon::parse($invoice->invoice_date)->diffInDays(Carbon::parse($invoice->due_date)) . ' ' . __('customer_outstanding.days') : '-' }}
                                </td>
                                <td>{{ $dueDate?->format(Setting::getDefaultDate()) ?? '-' }}</td>
                                <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $invoice->invoice_amount, 2) }}</td>
                                <td class="text-end">
                                    @php
                                        $paidPercent = $invoice->invoice_amount > 0
                                            ? min(100, ($invoice->paid_amount / $invoice->invoice_amount) * 100)
                                            : 0;
                                    @endphp
                                    <div class="d-flex align-items-center gap-2 justify-content-end">
                                        <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                            <div
                                                class="progress-bar {{ $paidPercent >= 100 ? 'theme-success' : ($paidPercent > 0 ? 'theme-primary' : '') }}"
                                                role="progressbar"
                                                style="width: {{ $paidPercent }}%"
                                                aria-valuenow="{{ $paidPercent }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100"></div>
                                        </div>
                                        <span
                                            class="small text-nowrap">{{ $currencySymbol }} {{ number_format((float) $invoice->paid_amount, 2) }}</span>
                                    </div>
                                </td>
                                <td class="fw-bold text-end">
                                    {{ $currencySymbol }} {{ number_format((float) $invoice->balance_amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $isOverdue ? 'theme-danger' : 'theme-warning' }}">
                                        {{ $isOverdue ? __('customer_outstanding.statuses.overdue') : __('customer_outstanding.statuses.outstanding') }}
                                    </span>
                                    @if ($isOverdue)
                                        <div class="fg-danger small">
                                            {{ $invoice->days_overdue }} {{ __('customer_outstanding.days_overdue') }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="11">
                                    {{ __('customer_outstanding.empty') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot class="bg-1">
                        <tr class="fw-bold">
                            <td colspan="2">{{ __('customer_outstanding.grand_total') }}</td>
                            <td class="text-end">
                                {{ number_format((float) $totals['delivered_quantity'], 2) }} /
                                {{ number_format((float) $totals['ordered_quantity'], 2) }}
                            </td>
                            <td class="text-end">
                                {{ $totals['ordered_quantity'] > 0 ? number_format(($totals['delivered_quantity'] / $totals['ordered_quantity']) * 100, 1) . '%' : '-' }}
                            </td>
                            <td colspan="3"></td>
                            <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $totals['invoice_amount'], 2) }}</td>
                            <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $totals['paid_amount'], 2) }}</td>
                            <td class="text-end">{{ $currencySymbol }} {{ number_format((float) $totals['balance_amount'], 2) }}</td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">{{ $invoices->links() }}</div>
            </div>
        </div>
@endsection
