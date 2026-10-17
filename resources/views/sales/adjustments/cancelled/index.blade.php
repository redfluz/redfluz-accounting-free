@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use Carbon\Carbon;
    use Illuminate\Pagination\LengthAwarePaginator;

@endphp

@props([
    /** @var LengthAwarePaginator<int, \App\Models\Sales\SalesAdjustment> */
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
                <h4 class="fw-bold mb-0">{{ __('adjustments.cancelled_title') }}</h4>
                <span class="fg-secondary small">{{ __('adjustments.cancelled_subtitle') }}</span>
            </div>
            <div class="d-flex gap-1">
                <a
                    class="btn btn-solid theme-success btn-sm"
                    href="{{ route('sales.adjustments.cancelled.export-excel', request()->query()) }}"
                >
                    <i class="me-1" data-lucide="file-spreadsheet"></i>{{ __('common.actions.export_excel') }}
                </a>
                <a
                    class="btn btn-solid theme-info btn-sm"
                    href="{{ route('sales.adjustments.cancelled.export-pdf', request()->query()) }}"
                >
                    <i class="me-1" data-lucide="file-text"></i>{{ __('common.actions.export_pdf') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('sales.adjustments.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            @include('partials.date-range-filter', [
                'action' => route('sales.adjustments.cancelled.index'),
                'search' => $search,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('ledgers.table.column_document_number') }}</th>
                            <th>{{ __('ledgers.table.column_reference_number') }}</th>
                            <th>{{ __('ledgers.table.column_date') }}</th>
                            <th>{{ __('ledgers.table.column_subtype') }}</th>
                            <th>{{ __('ledgers.table.column_company') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td>
                                    <a
                                        class="fw-bold text-decoration-none"
                                        href="{{ route('sales.adjustments.cancelled.show', $entry->document_number) }}"
                                    >{{ $entry->document_number }}</a>
                                </td>
                                <td>{{ $entry->reference_number ?? '-' }}</td>
                                <td>{{ Carbon::parse($entry->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ __('ledgers.sub_type.' . ($entry->subtype->value ?? $entry->subtype)) }}</td>
                                <td>{{ $entry->company?->name ?? '-' }}</td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $entry->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a
                                            class="btn btn-sm btn-solid theme-info"
                                            href="{{ route('sales.adjustments.cancelled.show', $entry->document_number) }}"
                                            title="{{ __('common.actions.view') }}"
                                        >
                                            <i data-lucide="eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="7">{{ __('ledgers.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $entries->links() }}</div>
        </div>
    </div>
@endsection
