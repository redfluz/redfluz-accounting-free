{{--
 ============================================================================
 Copyright (c) 2026 redfluz. All Rights Reserved.
 PROPRIETARY AND CONFIDENTIAL
 ============================================================================
--}}

@php
    use App\Enums\FileResources;
    use App\Models\Sales\ManualSalesAdjustment;
    use App\Models\System\Setting;
    use Illuminate\Pagination\LengthAwarePaginator;
@endphp

@props([
    /** @var LengthAwarePaginator<int, ManualSalesAdjustment> $entries */
    'entries',
    /** @var string|null $search */
    'search',
    /** @var string|null $startDate */
    'startDate',
    /** @var string|null $endDate */
    'endDate',
])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-1 py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('manual_sales_adjustments.cancelled_title') }}</h4>
                <span class="fg-secondary small">{{ __('manual_sales_adjustments.cancelled_subtitle') }}</span>
            </div>
            <div class="d-flex gap-1">
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('sales.manual-adjustments.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            @include('partials.date-range-filter', [
                'action' => route('sales.manual-adjustments.cancelled.index'),
                'search' => $search,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'placeholder' => __('manual_sales_adjustments.search_placeholder'),
            ])

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col">{{ __('ledgers.table.column_document_number') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_reference_number') }}</th>
                            <th scope="col">{{ __('manual_sales_adjustments.form.note_type') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_company') }}</th>
                            <th scope="col" class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th scope="col" class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td>
                                    <a class="fw-bold text-decoration-none"
                                        href="{{ route('sales.manual-adjustments.cancelled.show', $entry->document_number) }}">{{ $entry->document_number }}</a>
                                </td>
                                <td>{{ $entry->reference_number ?? '-' }}</td>
                                <td>{{ __('ledgers.sub_type.' . $entry->subtype->value) }}</td>
                                <td>{{ $entry->date->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $entry->company_name ?? ($entry->company?->name ?? '-') }}</td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $entry->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-sm btn-solid theme-info"
                                            href="{{ route('sales.manual-adjustments.cancelled.show', $entry->document_number) }}"
                                            title="{{ __('common.actions.view') }}">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <a class="btn btn-sm btn-solid theme-primary"
                                            href="{{ route('sales.manual-adjustments.cancelled.pdf', $entry->document_number) }}"
                                            target="_blank" title="{{ __('common.actions.print_pdf') }}">
                                            <i data-lucide="printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="7">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $entries->links() }}</div>
        </div>
    </div>
@endsection
