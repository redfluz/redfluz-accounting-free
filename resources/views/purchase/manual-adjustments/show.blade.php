{{--
 ============================================================================
 Copyright (c) 2026 redfluz. All Rights Reserved.
 PROPRIETARY AND CONFIDENTIAL
 ============================================================================
--}}

@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Purchase\ManualPurchaseAdjustment;
    use App\Models\System\Setting;

    $canApprove = auth()->user()->hasFilePermission(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::APPROVE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::REMOVE);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::UPDATE);
    $canReverse = $canApprove && $isPosted && $adjustment->undone_at === null && $adjustment->document_original === null;
@endphp

@props(['adjustment', 'isPosted', 'indexRoute', 'reversalNumber'])

@extends('layouts.app')

@section('content')
    @php
        /** @var ManualPurchaseAdjustment $adjustment */
        $documentNumber = $adjustment->document_number;
        $totalDebit = $adjustment->charts->sum(fn($line) => (float) $line->debit_amount);
        $totalCredit = $adjustment->charts->sum(fn($line) => (float) $line->credit_amount);
    @endphp
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-1 py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $documentNumber }}</h4>
                <span class="fg-secondary small">{{ $adjustment->company->name ?? '-' }} |
                    {{ $adjustment->date->format(Setting::getDefaultDate()) }}</span>
            </div>
            <div class="d-flex gap-2">
                @if ($isPosted)
                    @if ($adjustment->document_original !== null)
                        <span class="badge theme-info d-flex align-items-center">
                            {{ __('journal_entries.reversal_status') }}
                        </span>
                    @elseif ($adjustment->undone_at !== null)
                        <span class="badge theme-danger d-flex align-items-center">
                            {{ __('journal_entries.reversed_status') }}
                        </span>
                    @else
                        <span class="badge theme-success d-flex align-items-center">
                            {{ __('journal_entries.status_approved') }}
                        </span>
                    @endif
                    @if ($canReverse)
                        <button class="btn btn-solid theme-warning btn-sm text-nowrap" data-bs-toggle="dialog"
                            data-bs-target="#reverseJournalModal" type="button">
                            <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                        </button>
                    @endif
                @elseif ($canApprove)
                    <form autocomplete="off" action="{{ route('purchase.manual-adjustments.approve', $documentNumber) }}"
                        method="POST">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success btn-sm text-nowrap" type="submit">
                            <i data-lucide="circle-check"></i>
                            {{ __('common.actions.post') }}
                        </button>
                    </form>
                @endif

                @if (!$isPosted && $canUpdate)
                    <a class="btn btn-sm btn-solid theme-primary"
                        href="{{ route('purchase.manual-adjustments.edit', $documentNumber) }}"
                        title="{{ __('common.actions.edit') }}">
                        <i data-lucide="square-pen"></i>
                    </a>
                @endif

                @if (!$isPosted && $canRemove)
                    <form autocomplete="off" action="{{ route('purchase.manual-adjustments.destroy', $documentNumber) }}"
                        method="POST" data-confirm-message="{{ __('manual_purchase_adjustments.delete_confirmation') }}"
                        data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-solid theme-danger btn-sm text-nowrap" type="submit">
                            <i data-lucide="trash-2"></i>
                            {{ __('common.actions.delete') }}
                        </button>
                    </form>
                @endif

                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('purchase.manual-adjustments.pdf', $documentNumber) }}" target="_blank">
                    <i class="me-1" data-lucide="printer"></i>
                    {{ __('common.actions.print_pdf') }}
                </a>

                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route($indexRoute) }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            @include('partials.errors')

            @if ($adjustment->document_original !== null)
                <div class="alert alert theme-info">
                    {{ __('journal_entries.reversal_of') }}:
                    <a class="alert-link"
                        href="{{ route('purchase.manual-adjustments.history.show', $adjustment->document_original) }}">{{ $adjustment->document_original }}</a>
                </div>
            @elseif ($adjustment->undone_at !== null && $reversalNumber !== null)
                <div class="alert alert theme-warning">
                    {{ __('journal_entries.reversed_by') }}:
                    <a class="alert-link"
                        href="{{ route('purchase.manual-adjustments.history.show', $reversalNumber) }}">{{ $reversalNumber }}</a>
                </div>
            @endif

            <div class="row mb-4">
                <div class="md:col-6">
                    <span class="fg-secondary small">{{ __('manual_purchase_adjustments.form.note_type') }}:</span>
                    <span class="fw-bold">{{ __('ledgers.sub_type.' . $adjustment->subtype->value) }}</span>
                </div>
                @if ($adjustment->reference_number)
                    <div class="md:col-6">
                        <span class="fg-secondary small">{{ __('purchase_adjustments.form.linked_invoice') }}:</span>
                        @if ($adjustment->purchase_invoice_id)
                            <a class="fw-bold" href="{{ route('purchase.invoices.show', $adjustment->reference_number) }}">
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
                            <th scope="col">{{ __('manual_purchase_adjustments.table.column_account') }}</th>
                            <th scope="col">{{ __('manual_purchase_adjustments.table.column_account_name') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                            <th scope="col" class="text-end">{{ __('manual_purchase_adjustments.table.column_debit') }}
                            </th>
                            <th scope="col" class="text-end">{{ __('manual_purchase_adjustments.table.column_credit') }}
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
    @if ($canReverse)
        <dialog class="dialog" id="reverseJournalModal" aria-labelledby="reverseJournalModalLabel">
            <form autocomplete="off" method="POST"
                action="{{ route('purchase.manual-adjustments.reverse', $documentNumber) }}">
                @csrf
                @method('PATCH')
                <div class="dialog-header">
                    <h5 class="dialog-title" id="reverseJournalModalLabel">
                        {{ __('manual_purchase_adjustments.reverse_title') }}
                    </h5>
                    <button class="btn-close" data-bs-dismiss="dialog" type="button"
></button>
                </div>
                <div class="dialog-body">
                    <div class="alert theme-warning">{{ __('manual_purchase_adjustments.reverse_explanation') }}</div>
                    @error('reversal_date')
                        <div class="alert alert theme-danger">{{ $message }}</div>
                    @enderror
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
                        type="submit" data-confirm-message="{{ __('manual_purchase_adjustments.confirm_reverse') }}">
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
