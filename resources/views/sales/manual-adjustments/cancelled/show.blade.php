{{--
 ============================================================================
 Copyright (c) 2026 redfluz. All Rights Reserved.
 PROPRIETARY AND CONFIDENTIAL
 ============================================================================
--}}

@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Finance\ManualSalesAdjustment;
    use App\Models\System\Setting;

    $canApprove = auth()->user()->hasFilePermission(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::APPROVE);
@endphp

@props(['adjustment', 'documentNumber'])

@extends('layouts.app')

@section('content')
    @php
        /** @var ManualSalesAdjustment $adjustment */
        $totalDebit = $adjustment->charts->sum(fn($line) => (float) $line->debit_amount);
        $totalCredit = $adjustment->charts->sum(fn($line) => (float) $line->credit_amount);
    @endphp
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-1 py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">{{ $adjustment->company_name ?? ($adjustment->company?->name ?? '-') }} |
                    {{ $adjustment->date->format(Setting::getDefaultDate()) }}</span>
            </div>
            <div class="d-flex gap-2">
                <span class="badge theme-danger d-flex align-items-center">
                    {{ __('ledgers.status_cancelled') }}
                </span>
                @if ($canApprove)
                    <form autocomplete="off"
                        action="{{ route('sales.manual-adjustments.cancelled.uncancel', $documentNumber) }}" method="POST"
                        data-confirm-message="{{ __('common.messages.uncancel_confirm') }}" data-confirm-tone="danger">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-solid theme-warning"
                            title="{{ __('common.actions.undo') }}">
                            <i data-lucide="undo-2"></i>{{ __('common.actions.undo') }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm"
                    href="{{ route('sales.manual-adjustments.cancelled.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')

            <div class="row mb-4">
                <div class="md:col-6">
                    <span class="fg-secondary small">{{ __('manual_sales_adjustments.form.note_type') }}:</span>
                    <span class="fw-bold">{{ $adjustment->subtype->label() }}</span>
                </div>
                @if ($adjustment->reference_number)
                    <div class="md:col-6">
                        <span class="fg-secondary small">{{ __('adjustments.form.linked_invoice') }}:</span>
                        @if ($adjustment->sales_invoice_id)
                            <a class="fw-bold" href="{{ route('sales.invoices.show', $adjustment->reference_number) }}">
                                {{ $adjustment->reference_number }}
                            </a>
                        @else
                            <span>{{ $adjustment->reference_number }}</span>
                        @endif
                    </div>
                @endif
            </div>

            @if ($adjustment->notes)
                <div class="mb-3">
                    <span class="fg-secondary small">{{ __('ledgers.table.column_notes') }}:</span>
                    <span>{{ $adjustment->notes }}</span>
                </div>
            @endif

            <div class="table-responsive mb-4 rounded-3 border">
                <table class="table-form mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col">{{ __('manual_sales_adjustments.table.column_account') }}</th>
                            <th scope="col">{{ __('manual_sales_adjustments.table.column_account_name') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                            <th scope="col" class="text-end">{{ __('manual_sales_adjustments.table.column_debit') }}
                            </th>
                            <th scope="col" class="text-end">{{ __('manual_sales_adjustments.table.column_credit') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($adjustment->charts as $line)
                            <tr>
                                <td>{{ $line->chart_code ?? '-' }}</td>
                                <td>{{ $line->chart_name ?? '-' }}</td>
                                <td>{{ $line->line_description ?? '-' }}</td>
                                <td class="text-end">
                                    @if ((float) $line->debit_amount > 0)
                                        {{ number_format((float) $line->debit_amount, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ((float) $line->credit_amount > 0)
                                        {{ number_format((float) $line->credit_amount, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-1">
                        <tr>
                            <th scope="col" class="text-end" colspan="3">{{ __('common.labels.total') }}</th>
                            <th scope="col" class="fg-primary text-end">{{ number_format($totalDebit, 2) }}</th>
                            <th scope="col" class="fg-primary text-end">{{ number_format($totalCredit, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
