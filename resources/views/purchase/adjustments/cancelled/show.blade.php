@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    $canApprove = auth()->user()->hasFilePermission(FileResources::PURCHASE_ADJUSTMENTS, FileAction::APPROVE);
@endphp
@php($subtype = $adjustment->subtype->value ?? $adjustment->subtype)

@props(['documentNumber', 'entries', 'adjustment'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">
                    {{ __('purchase_adjustments.sub_type.' . $subtype) }} | {{ $adjustment->company_name ?? '-' }}
                </span>
            </div>
            <div class="d-flex gap-2">
                <span class="badge theme-danger d-flex align-items-center"> {{ __('ledgers.status_cancelled') }} </span>
                @if ($canApprove)
                    <form autocomplete="off" action="{{ route('purchase.adjustments.cancelled.uncancel', $documentNumber) }}" method="POST"
                        data-confirm-message="{{ __('common.messages.uncancel_confirm') }}" data-confirm-tone="danger">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-solid theme-warning"
                            title="{{ __('common.actions.undo') }}">
                            <i data-lucide="undo-2"></i>{{ __('common.actions.undo') }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('purchase.adjustments.cancelled.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="md:col-4">
                    <div class="h-100 rounded border p-3">
                        <span
                            class="fg-secondary small d-block">{{ __('purchase_adjustments.form.linked_invoice') }}</span>
                        <strong>{{ $adjustment->reference_number }}</strong>
                    </div>
                </div>
                <div class="md:col-4">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_date') }}</span>
                        <strong>{{ $adjustment->date->format(Setting::getDefaultDate()) }}</strong>
                    </div>
                </div>
                <div class="md:col-4">
                    <div class="h-100 rounded border p-3">
                        <span class="fg-secondary small d-block">{{ __('ledgers.table.column_total_amount') }}</span>
                        <strong class="fg-primary">{{ number_format((float) $adjustment->total_amount, 2) }}</strong>
                    </div>
                </div>
            </div>

            <div class="table-responsive rounded border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('purchase_adjustments.table.column_entry') }}</th>
                            <th>{{ __('ledgers.table.column_debit_acc') }}</th>
                            <th>{{ __('ledgers.table.column_credit_acc') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $entry->debit_acc ?? '-' }}
                                    @if ($entry->debit_acc_desc)
                                        <span class="fg-secondary small d-block">{{ $entry->debit_acc_desc }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $entry->credit_acc ?? '-' }}
                                    @if ($entry->credit_acc_desc)
                                        <span class="fg-secondary small d-block">{{ $entry->credit_acc_desc }}</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $entry->debit_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $entry->credit_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($adjustment->notes)
                <div class="mt-4 rounded border p-3">
                    <span class="fg-secondary small d-block">{{ __('ledgers.table.column_notes') }}</span>
                    {{ $adjustment->notes }}
                </div>
            @endif
        </div>
    </div>
@endsection
