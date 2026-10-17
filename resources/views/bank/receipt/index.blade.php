@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $resource = FileResources::PAYMENTS;
    $canApprove = auth()->user()->hasFilePermission($resource, FileAction::APPROVE);
    $canCancel = auth()->user()->hasFilePermission($resource, FileAction::CANCEL);
    $canUpdate = auth()->user()->hasFilePermission($resource, FileAction::UPDATE);
    $canDelete = auth()->user()->hasFilePermission($resource, FileAction::REMOVE);
@endphp

@props(['receipts', 'search', 'startDate', 'endDate'])

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('payments.workflow.receipt.title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('payments.workflow.receipt.subtitle') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-solid theme-danger btn-sm" href="{{ route('bank.receipts.cancelled.index') }}">
                    <i class="me-1" data-lucide="x-circle"></i>{{ __('ledgers.cancelled_title') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm" href="{{ route('bank.receipts.history.index') }}">
                    <i class="me-1" data-lucide="history"></i>{{ __('ledgers.history_title') }}
                </a>
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('bank.receipts.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
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
                            <form autocomplete="off" action="{{ route('bank.receipts.index') }}" method="GET">
                                <div class="row g-2 align-items-center">
                                    <div class="md:col-3">
                                        <input class="form-control" name="search" type="text"
                                            value="{{ $search }}" placeholder="{{ __('common.actions.search') }}" />
                                    </div>
                                    <div class="md:col-3">
                                        <label class="form-control form-adorn">
                                            <div class="form-adorn-icon" aria-hidden="true">
                                                <i data-lucide="calendar"></i>
                                            </div>
                                            <input class="form-ghost" name="start_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                                                value="{{ $startDate }}" placeholder="{{ __('financial_reports.form.start_date') }}" />
                                        </label>
                                    </div>
                                    <div class="md:col-3">
                                        <label class="form-control form-adorn">
                                            <div class="form-adorn-icon" aria-hidden="true">
                                                <i data-lucide="calendar"></i>
                                            </div>
                                            <input class="form-ghost" name="end_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                                                value="{{ $endDate }}" placeholder="{{ __('financial_reports.form.end_date') }}" />
                                        </label>
                                    </div>
                                    <div class="md:col-3 d-flex gap-2">
                                        <button class="btn btn-solid theme-primary flex-fill" type="submit">
                                            <i class="me-1" data-lucide="filter"></i>
                                            {{ __('common.actions.search') }}
                                        </button>
                                        <a class="btn btn-outline flex-fill border"
                                            href="{{ route('bank.receipts.index') }}">
                                            <i class="me-1" data-lucide="rotate-ccw"></i>
                                            {{ __('common.actions.reset') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

</details>

            <div class="d-flex mb-3 gap-2">
                @if ($canApprove)
                    <button class="btn btn-solid theme-success batch-action-button" data-action="approve"
                        data-confirm-message="{{ __('common.alerts.confirm_batch_approve') }}" type="button">
                        <i class="me-1" data-lucide="check-check"></i> {{ __('common.actions.batch_approve') }}
                    </button>
                @endif

                @if ($canCancel)
                    <button class="btn btn-solid theme-warning batch-action-button" data-action="cancel"
                        data-confirm-message="{{ __('common.alerts.confirm_batch_cancel') }}" type="button">
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
                                    <input class="check receipt-selection-checkbox" id="checkAll" type="checkbox" />
                                </th>
                            @endif
                            <th>{{ __('ledgers.table.column_document_number') }}</th>
                            <th>{{ __('ledgers.table.column_reference_number') }}</th>
                            <th>{{ __('ledgers.table.column_date') }}</th>
                            <th>{{ __('ledgers.table.column_company') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($receipts as $receipt)
                            <tr>
                                @if ($canApprove)
                                    <td class="text-center">
                                        @if ($receipt->isEditable())
                                            <input class="check receipt-checkbox receipt-selection-checkbox"
                                                form="batch-form" name="document_number[]" type="checkbox"
                                                value="{{ $receipt->document_number }}" />
                                        @endif
                                    </td>
                                @endif
                                <td class="fw-bold">{{ $receipt->document_number }}</td>
                                <td>{{ $receipt->reference_number ?? '-' }}</td>
                                <td>{{ $receipt->date->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $receipt->company_name ?: '-' }}</td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $receipt->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-sm btn-solid theme-info me-1"
                                            href="{{ route('bank.receipts.show', $receipt->document_number) }}"
                                            title="{{ __('payments.actions.view_and_print') }}">
                                            <i data-lucide="eye"></i>
                                        </a>

                                        @if ($receipt->isEditable() && $canApprove)
                                            <form autocomplete="off" action="{{ route('bank.receipts.approve', $receipt->document_number) }}"
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

                                        @if ($receipt->isEditable() && $canUpdate)
                                            <a class="btn btn-sm btn-solid theme-secondary"
                                                href="{{ route('bank.receipts.edit', $receipt->document_number) }}"
                                                title="{{ __('common.actions.edit') }}">
                                                <i data-lucide="pencil"></i>
                                            </a>
                                        @endif

                                        @if ($receipt->isEditable() && $canDelete)
                                            <form autocomplete="off" action="{{ route('bank.receipts.destroy', $receipt->document_number) }}"
                                                method="POST"
                                                data-confirm-message="{{ __('common.alerts.confirm_delete_message') }}"
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
                                <td colspan="{{ $canApprove ? 7 : 6 }}" class="text-center">
                                    {{ __('ledgers.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($receipts->count() > 0)
                        <tfoot class="bg-1 fw-bold border-top">
                            <tr>
                                <td class="text-end" colspan="{{ $canApprove ? 5 : 4 }}">
                                    {{ __('common.labels.total') }}:
                                </td>
                                <td class="fg-primary text-end">
                                    {{ number_format((float) $receipts->sum('total_amount'), 2) }}
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

            <div class="d-flex justify-content-end mt-3">{{ $receipts->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = Array.from(document.querySelectorAll('.receipt-checkbox'));

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
                    const checked = document.querySelectorAll('.receipt-checkbox:checked');
                    if (checked.length === 0) {
                        confirmDialog({
                            text: @json(__('common.alerts.no_selection')),
                            icon: 'warning',
                        })
                        return;
                    }

                    const action = button.dataset.action;
                    const routeUrl =
                        action === 'approve' ?
                        @json(route('bank.receipts.batch-approve')) :
                        @json(route('bank.receipts.batch-cancel'));

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
