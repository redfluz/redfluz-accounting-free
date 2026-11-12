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
    use Illuminate\Pagination\LengthAwarePaginator;

    $canApprove = auth()->user()->hasFilePermission(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::APPROVE);
    $canCancel = auth()->user()->hasFilePermission(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::CANCEL);
    $canRemove = auth()->user()->hasFilePermission(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::REMOVE);
@endphp

@props([
    /** @var LengthAwarePaginator<int, ManualPurchaseAdjustment> */
    'adjustments',
    /** @var string|null */
    'search',
    /** @var string|null */
    'startDate',
    /** @var string|null */
    'endDate',
    'indexRoute',
    'createRoute',
])

@extends('layouts.app')

@section('content')
    <style>
        .adjustment-selection-checkbox {
            width: 1.15rem;
            height: 1.15rem;
            margin-top: 0;
            cursor: pointer;
            background-color: #fff;
            border: 2px solid #6c757d;
            opacity: 1;
        }

        .adjustment-selection-checkbox:hover:not(:disabled),
        .adjustment-selection-checkbox:focus-visible {
            border-color: var(--danger-base);
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--danger-base) 20%, transparent);
        }

        .adjustment-selection-checkbox:checked,
        .adjustment-selection-checkbox:indeterminate {
            background-color: var(--danger-base);
            border-color: var(--danger-base);
        }

        .adjustment-selection-checkbox:disabled {
            cursor: not-allowed;
            background-color: #dee2e6;
            border-color: #adb5bd;
            opacity: 0.75;
        }
    </style>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-1 py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('manual_purchase_adjustments.index_title') }}</h4>
                <span class="fg-secondary small">{{ __('manual_purchase_adjustments.index_subtitle') }}</span>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-solid theme-primary btn-sm" href="{{ route($createRoute) }}">
                    <i class="me-1" data-lucide="plus"></i>{{ __('common.actions.add') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            @include('partials.date-range-filter', [
                'action' => route($indexRoute),
                'search' => $search,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'placeholder' => __('manual_purchase_adjustments.search_placeholder'),
            ])

            <div class="d-flex mb-3 gap-2">
                @if ($canApprove)
                    <button class="btn btn-solid theme-success batch-action-button"
                        data-confirm-message="{{ __('common.alerts.confirm_batch_approve') }}" data-action="approve"
                        type="button">
                        <i class="me-1" data-lucide="check-check"></i> {{ __('common.actions.batch_approve') }}
                    </button>
                @endif

                @if ($canCancel)
                    <button class="btn btn-solid theme-warning batch-action-button"
                        data-confirm-message="{{ __('common.alerts.confirm_batch_cancel') }}" data-action="cancel"
                        type="button">
                        <i class="me-1" data-lucide="ban"></i> {{ __('common.actions.batch_cancel') }}
                    </button>
                @endif
            </div>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-form table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            @if ($canApprove)
                                <th scope="col" class="text-center" style="width: 40px">
                                    <label class="visually-hidden" for="checkAll">CheckAll</label>
                                    <input class="check adjustment-selection-checkbox" id="checkAll" type="checkbox" />
                                </th>
                            @endif
                            <th scope="col">{{ __('ledgers.table.column_document_number') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_company') }}</th>
                            <th scope="col">{{ __('manual_purchase_adjustments.table.column_type') }}</th>
                            <th scope="col" class="text-end">
                                {{ __('manual_purchase_adjustments.table.column_total_debit') }}</th>
                            <th scope="col" class="text-end">
                                {{ __('manual_purchase_adjustments.table.column_total_credit') }}</th>
                            <th scope="col" class="text-center">{{ __('ledgers.table.column_lines') }}</th>
                            <th scope="col" class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($adjustments as $adjustment)
                            <tr>
                                @if ($canApprove)
                                    <td class="text-center">
                                        @if (!$adjustment->isPosted())
                                            <label class="visually-hidden" for="checkbox"> - </label>
                                            <input id="checkbox" class="check adjustment-checkbox adjustment-selection-checkbox"
                                                form="batch-form" name="document_numbers[]" type="checkbox"
                                                value="{{ $adjustment->document_number }}" />
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <a class="fw-bold text-decoration-none"
                                        href="{{ route('purchase.manual-adjustments.show', $adjustment->document_number) }}">{{ $adjustment->document_number }}</a>
                                </td>
                                <td>{{ $adjustment->date->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $adjustment->company->name ?? '-' }}</td>
                                <td>{{ __('ledgers.sub_type.' . $adjustment->subtype->value) }}</td>
                                <td class="text-end">{{ number_format((float) $adjustment->total_debit, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $adjustment->total_credit, 2) }}</td>
                                <td class="text-center">{{ $adjustment->charts_count }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-sm btn-solid theme-info me-1"
                                            href="{{ route('purchase.manual-adjustments.show', $adjustment->document_number) }}"
                                            title="{{ __('common.actions.view') }}">
                                            <i data-lucide="eye"></i>
                                        </a>

                                        @if (!$adjustment->isPosted() && $canApprove)
                                            <form autocomplete="off"
                                                action="{{ route('purchase.manual-adjustments.approve', $adjustment->document_number) }}"
                                                method="POST"
                                                data-confirm-message="{{ __('common.alerts.confirm_batch_approve') }}"
                                                data-confirm-tone="danger">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-solid theme-success" type="submit"
                                                    title="{{ __('common.actions.approve') }}">
                                                    <i data-lucide="book-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if (!$adjustment->isPosted() && $canRemove)
                                            <form autocomplete="off"
                                                action="{{ route('purchase.manual-adjustments.destroy', $adjustment->document_number) }}"
                                                method="POST"
                                                data-confirm-message="{{ __('manual_purchase_adjustments.delete_confirmation') }}"
                                                data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-solid theme-danger" type="submit"
                                                    title="{{ __('common.actions.delete') }}">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="{{ $canApprove ? 9 : 8 }}">
                                    {{ __('ledgers.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form autocomplete="off" class="d-none" id="batch-form" method="POST">
                @csrf
            </form>

            <div class="d-flex justify-content-end mt-3">{{ $adjustments->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = Array.from(document.querySelectorAll('.adjustment-checkbox'));

            const syncSelectionControls = function() {
                const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

                if (checkAll) {
                    checkAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
                    checkAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
                    checkAll.disabled = checkboxes.length === 0;
                }
            };

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = checkAll.checked;
                    });
                    syncSelectionControls();
                });
            }

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', syncSelectionControls);
            });

            syncSelectionControls();

            document.querySelectorAll('.batch-action-button').forEach(function(button) {
                button.addEventListener('click', function() {
                    const checked = document.querySelectorAll('.adjustment-checkbox:checked');
                    if (checked.length === 0) {
                        confirmDialog({
                            text: @json(__('common.alerts.no_selection')),
                            icon: 'warning',
                        });
                        return;
                    }

                    const action = button.dataset.action;
                    const routeUrl =
                        action === 'approve' ? @json(route('purchase.manual-adjustments.batch-approve')) :
                        @json(route('purchase.manual-adjustments.batch-cancel'));

                    confirmDialog({
                        text: button.dataset.confirmMessage,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: @json(__('common.actions.approve')),
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            const form = document.getElementById('batch-form');
                            form.action = routeUrl;
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endsection
