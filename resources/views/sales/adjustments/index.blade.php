<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

use App\Enums\FileAction;
use App\Enums\FileResources;
use Carbon\Carbon;

?>
@php use App\Models\System\Setting; @endphp

@props(['adjustments', 'search', 'startDate', 'endDate'])

@php
    $canApprove = auth()->user()->hasFilePermission(FileResources::SALES_ADJUSTMENTS, FileAction::APPROVE);
    $canCancel = auth()->user()->hasFilePermission(FileResources::SALES_ADJUSTMENTS, FileAction::CANCEL);
@endphp

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

    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('adjustments.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('adjustments.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-solid theme-danger btn-sm" href="{{ route('sales.adjustments.cancelled.index') }}">
                    <i class="me-1" data-lucide="x-circle"></i>{{ __('adjustments.cancelled_title') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm" href="{{ route('sales.adjustments.history.index') }}">
                    <i class="me-1" data-lucide="history"></i>{{ __('adjustments.history_title') }}
                </a>
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('sales.adjustments.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            @include('partials.date-range-filter', [
                'action' => route('sales.adjustments.index'),
                'search' => $search,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])

            <div class="d-flex mb-3 gap-2">
                @if ($canApprove)
                    <button
                        class="btn btn-solid theme-success batch-action-button"
                        data-action="approve"
                        data-confirm-message="{{ __('common.alerts.confirm_batch_approve') }}"
                        type="button"
                    >
                        <i class="me-1" data-lucide="check-check"></i> {{ __('common.actions.batch_approve') }}
                    </button>
                @endif

                @if ($canCancel)
                    <button
                        class="btn btn-solid theme-warning batch-action-button"
                        data-action="cancel"
                        data-confirm-message="{{ __('common.alerts.confirm_batch_cancel') }}"
                        type="button"
                    >
                        <i class="me-1" data-lucide="ban"></i> {{ __('common.actions.batch_cancel') }}
                    </button>
                @endif
            </div>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            @if ($canApprove)
                                <th class="text-center" style="width: 40px">
                                    <input
                                        class="check adjustment-selection-checkbox"
                                        id="checkAll"
                                        type="checkbox"
                                    />
                                </th>
                            @endif
                            <th>{{ __('ledgers.table.column_document_number') }}</th>
                            <th>{{ __('ledgers.table.column_reference_number') }}</th>
                            <th>{{ __('ledgers.table.column_date') }}</th>
                            <th>{{ __('ledgers.table.column_subtype') }}</th>
                            <th>{{ __('ledgers.table.column_company') }}</th>
                            <th>{{ __('ledgers.table.column_notes') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($adjustments as $adjustment)
                            @php($subtype = $adjustment->subtype->value ?? $adjustment->subtype)
                            <tr>
                                @if ($canApprove)
                                    <td class="text-center">
                                        @if (! $adjustment->is_posted)
                                            <input
                                                class="check adjustment-checkbox adjustment-selection-checkbox"
                                                form="batch-form"
                                                name="document_numbers[]"
                                                type="checkbox"
                                                value="{{ $adjustment->document_number }}"
                                            />
                                        @endif
                                    </td>
                                @endif
                                <td class="fw-bold">{{ $adjustment->document_number }}</td>
                                <td>{{ $adjustment->reference_number }}</td>
                                <td>{{ Carbon::parse($adjustment->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>
                                    <span class="badge theme-info"> {{ __('adjustments.sub_type.' . $subtype) }} </span>
                                </td>
                                <td>{{ $adjustment->company_name ?? '-' }}</td>
                                <td>{{ $adjustment->notes ?? '-' }}</td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $adjustment->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a
                                            class="btn btn-sm btn-solid theme-info me-1"
                                            href="{{ route('sales.adjustments.show', $adjustment->document_number) }}"
                                            title="{{ __('common.actions.view') }}"
                                        >
                                            <i data-lucide="eye"></i>
                                        </a>

                                        @if (! $adjustment->is_posted && $canApprove)
                                            <form autocomplete="off"
                                                action="{{ route('sales.adjustments.approve', $adjustment->document_number) }}"
                                                method="POST"
                                                data-confirm-message="{{ __('common.alerts.confirm_batch_approve') }}" data-confirm-tone="danger"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    class="btn btn-sm btn-solid theme-success"
                                                    type="submit"
                                                    title="{{ __('common.actions.approve') }}"
                                                >
                                                    <i data-lucide="book-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="{{ $canApprove ? 9 : 8 }}">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($adjustments->count() > 0)
                        <tfoot class="bg-1 fw-bold border-top">
                            <tr>
                                <td class="text-end" colspan="{{ $canApprove ? 7 : 6 }}">
                                    {{ __('common.labels.total') }}:
                                </td>
                                <td class="fg-primary text-end">
                                    {{ number_format((float) $adjustments->sum('total_amount'), 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
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
        document.addEventListener('DOMContentLoaded', function () {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = Array.from(document.querySelectorAll('.adjustment-checkbox'));

            const syncSelectionControls = function () {
                const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

                if (checkAll) {
                    checkAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
                    checkAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
                    checkAll.disabled = checkboxes.length === 0;
                }
            };

            if (checkAll) {
                checkAll.addEventListener('change', function () {
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

            document.querySelectorAll('.batch-action-button').forEach(function (button) {
                button.addEventListener('click', function () {
                    const checked = document.querySelectorAll('.adjustment-checkbox:checked');
                    if (checked.length === 0) {
                        confirmDialog({
                            text: @json(__('common.alerts.no_selection')),
                            icon: 'warning',
                        })
                        return;
                    }

                    const action = button.dataset.action;
                    const routeUrl =
                        action === 'approve' ? @json(route('sales.adjustments.batch-approve')) : @json(route('sales.adjustments.batch-cancel'));

                    confirmDialog({
                        text: button.dataset.confirmMessage,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: @json(__('common.actions.approve')),
                    }).then(function (result) {
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
