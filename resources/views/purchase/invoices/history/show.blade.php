@php use App\Models\System\Setting; @endphp
@props(['documentNumber', 'lines', 'invoice'])

@php
    $invoice = $lines->first();
    $subtotal = $lines->sum(
        fn($line) => (float) $line->subtotal_amount !== 0.0
            ? (float) $line->subtotal_amount
            : (float) $line->quantity * (float) $line->unit_price,
    );
    $discountTotal = $lines->sum('discount_amount');
    $taxTotal = $lines->sum('tax_amount');
    $grandTotal = $lines->sum(
        fn($line) => (float) $line->line_total_amount !== 0.0
            ? (float) $line->line_total_amount
            : (float) $line->total_amount,
    );
    $canReverse = auth()
        ->user()
        ->hasFilePermission(\App\Enums\FileResources::PURCHASE_INVOICES, \App\Enums\FileAction::APPROVE)
        && ! $invoice->undone_at;
@endphp

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <small class="badge theme-secondary">{{ $invoice->company_name ?? '-' }} | {{ $invoice->date }}</small>
            </div>
            <div class="d-flex gap-2">
                <span class="badge theme-success d-flex align-items-center">
                    {{ __('journal_entries.status_approved') }}
                </span>
                @if ($canReverse)
                    <button class="btn btn-solid theme-warning btn-sm text-nowrap" data-bs-toggle="dialog"
                        data-bs-target="#reverseJournalModal" type="button">
                        <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                    </button>
                @endif
                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('purchase.invoices.pdf', $documentNumber) }}" target="_blank">
                    <i class="me-1" data-lucide="printer"></i>
                    {{ __('common.actions.print_pdf') }}
                </a>
                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('purchase.invoices.history.clone', $documentNumber) }}">
                    <i data-lucide="copy"></i> {{ __('common.actions.clone') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('purchase.invoices.history.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="row g-3 mb-4">
                <div class="md:col-3">
                    <div class="bg-2 rounded p-3">
                        <small class="fg-secondary d-block">{{ __('ledgers.table.column_document_number') }}</small>
                        <strong>{{ $invoice->document_number }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="bg-2 rounded p-3">
                        <small class="fg-secondary d-block">{{ __('ledgers.table.column_reference_number') }}</small>
                        <strong>{{ $invoice->reference_number ?? '-' }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="bg-2 rounded p-3">
                        <small class="fg-secondary d-block">{{ __('ledgers.table.column_date') }}</small>
                        <strong>{{ \Carbon\Carbon::parse($invoice->date)->format(Setting::getDefaultDate()) }}</strong>
                    </div>
                </div>
                <div class="md:col-3">
                    <div class="bg-2 rounded p-3">
                        <small class="fg-secondary d-block">{{ __('ledgers.table.column_company') }}</small>
                        <strong>{{ $invoice->company_name ?? '-' }}</strong>
                    </div>
                </div>
            </div>

            <div class="table-responsive rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('ledgers.table.column_product') }}</th>
                            <th>{{ __('ledgers.table.column_description') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_quantity') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_unit_price') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_discount_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_tax_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            <tr>
                                <td>{{ $line->product?->name ?? '-' }}</td>
                                <td>{{ $line->description ?? '-' }}</td>
                                <td class="text-end">{{ $line->quantity }}</td>
                                <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->discount_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->tax_amount, 2) }}</td>
                                <td class="fw-bold text-end">
                                    {{ number_format(
                                        (float) $line->line_total_amount !== 0.0 ? (float) $line->line_total_amount : (float) $line->total_amount,
                                        2,
                                    ) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-1">
                        <tr>
                            <th class="text-end" colspan="6">{{ __('ledgers.table.column_subtotal_amount') }}</th>
                            <th class="text-end">{{ number_format($subtotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">{{ __('ledgers.table.column_discount_amount') }}</th>
                            <th class="text-end">{{ number_format($discountTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">{{ __('ledgers.table.column_tax_amount') }}</th>
                            <th class="text-end">{{ number_format($taxTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="text-end" colspan="6">{{ __('common.labels.total') }}</th>
                            <th class="fg-primary fs-2xl text-end">{{ number_format($grandTotal, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @if ($canReverse)
        <dialog class="dialog" id="reverseJournalModal" aria-labelledby="reverseJournalModalLabel">
            <form method="POST" action="{{ route('purchase.invoices.reverse', $documentNumber) }}">
                @csrf
                @method('PATCH')
                <div class="dialog-header">
                    <h5 class="dialog-title" id="reverseJournalModalLabel">
                        {{ __('journal_entries.reverse_title') }}
                    </h5>
                    <button class="btn-close" data-bs-dismiss="dialog" type="button"
                        aria-label="{{ __('common.actions.close') }}"></button>
                </div>
                <div class="dialog-body">
                    <div class="alert alert theme-warning">{{ __('journal_entries.reverse_explanation') }}</div>
                    <div class="alert alert theme-danger">{{ __('journal_entries.reverse_product_ledger_warning') }}</div>
                    <div class="mb-3">
                        <label class="form-label" for="reversal_date">{{ __('journal_entries.reversal_date') }}</label>
                        <label class="form-control form-adorn">
                            <div class="form-adorn-icon" aria-hidden="true">
                                <i data-lucide="calendar"></i>
                            </div>
                            <input class="form-ghost @error('reversal_date') is-invalid @enderror" id="reversal_date"
                                name="reversal_date" type="text" data-bs-toggle="datepicker"
                                value="{{ old('reversal_date', now()->toDateString()) }}"
                                min="{{ $postingDateMin ?? '' }}" max="{{ $postingDateMax ?? '' }}" required />
                        </label>
                        @error('reversal_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label" for="reason">{{ __('journal_entries.reversal_reason') }}</label>
                        <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="4"
                            maxlength="1000" placeholder="{{ __('journal_entries.reversal_reason_placeholder') }}" required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="dialog-footer">
                    <button class="btn btn-solid theme-secondary" data-bs-dismiss="dialog" type="button">
                        {{ __('common.actions.cancel') }}</button><button class="btn btn-solid theme-warning"
                        type="submit" data-confirm-message="{{ __('journal_entries.confirm_reverse') }}">
                        <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                    </button>
                </div>
            </form>
        </dialog>
        @if ($errors->has('reversal_date') || $errors->has('reason'))
            <script>
                document.addEventListener('DOMContentLoaded', () =>
                    bootstrap.Dialog.getOrCreateInstance(document.getElementById('reverseJournalModal')).show(),
                );
            </script>
        @endif
    @endif
@endsection
