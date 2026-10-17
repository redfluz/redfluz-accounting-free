@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Purchase\PurchaseInvoice;
    use Carbon\Carbon;
    use Illuminate\Pagination\LengthAwarePaginator;

    $canApprove = auth()->user()->hasFilePermission(FileResources::PURCHASE_INVOICES, FileAction::APPROVE);
    $canCancel = auth()->user()->hasFilePermission(FileResources::PURCHASE_INVOICES, FileAction::CANCEL);
    $canRemove = auth()->user()->hasFilePermission(FileResources::PURCHASE_INVOICES, FileAction::REMOVE);
@endphp

@props([
    /** @var LengthAwarePaginator<int, PurchaseInvoice> */
    'entries',
    /** @var string|null */
    'search',
    /** @var string|null */
    'startDate',
    /** @var string|null */
    'endDate',
    'subtype',
    'indexRoute',
    'createRoute',
])

@extends('layouts.app')

@section('content')
    <style>
        .invoice-selection-checkbox {
            width: 1.15rem;
            height: 1.15rem;
            margin-top: 0;
            cursor: pointer;
            background-color: #fff;
            border: 2px solid #6c757d;
            opacity: 1;
        }

        .invoice-selection-checkbox:hover:not(:disabled),
        .invoice-selection-checkbox:focus-visible {
            border-color: var(--danger-base);
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--danger-base) 20%, transparent);
        }

        .invoice-selection-checkbox:checked,
        .invoice-selection-checkbox:indeterminate {
            background-color: var(--danger-base);
            border-color: var(--danger-base);
        }

        .invoice-selection-checkbox:disabled {
            cursor: not-allowed;
            background-color: #dee2e6;
            border-color: #adb5bd;
            opacity: 0.75;
        }
    </style>

    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('ledgers.workflow.' . $subtype->value . '.title') }}</h4>
                <span class="fg-secondary small"> {{ __('ledgers.workflow.' . $subtype->value . '.subtitle') }} </span>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-solid theme-danger btn-sm" href="{{ route('purchase.invoices.cancelled.index') }}">
                    <i class="me-1" data-lucide="x-circle"></i>{{ __('ledgers.cancelled_title') }}
                </a>
                <a class="btn btn-solid theme-info btn-sm" href="{{ route('purchase.invoices.history.index') }}">
                    <i class="me-1" data-lucide="history"></i>{{ __('ledgers.history_title') }}
                </a>
                <a class="btn btn-solid theme-success btn-sm" href="{{ route($createRoute) }}">
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
                'placeholder' => __('ledgers.search_placeholder'),
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
                                        class="check invoice-selection-checkbox"
                                        id="checkAll"
                                        type="checkbox"
                                    />
                                </th>
                            @endif
                            <th>{{ __('ledgers.table.column_document_number') }}</th>
                            <th>{{ __('ledgers.table.column_date') }}</th>
                            <th>{{ __('ledgers.table.column_company') }}</th>
                            <th class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                            <th class="text-center">{{ __('ledgers.table.column_payment_status') }}</th>
                            <th class="text-center">{{ __('ledgers.table.column_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                @if ($canApprove)
                                    <td class="text-center">
                                        @if (! $entry->isPosted())
                                            <input
                                                class="check invoice-checkbox invoice-selection-checkbox"
                                                form="batch-form"
                                                name="document_numbers[]"
                                                type="checkbox"
                                                value="{{ $entry->document_number }}"
                                            />
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <a
                                        class="fw-bold text-decoration-none"
                                        href="{{ route('purchase.invoices.show', $entry->document_number) }}"
                                    >{{ $entry->document_number }}</a>
                                </td>
                                <td>{{ Carbon::parse($entry->date)->format(Setting::getDefaultDate()) }}</td>
                                <td>{{ $entry->company_name ?? '-' }}</td>
                                <td class="fw-bold fg-primary text-end">
                                    {{ number_format((float) $entry->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $paidAmount = (float) ($entry->paid_amount ?? 0);
                                        $totalAmount = (float) $entry->total_amount;
                                        $paidPercent = $totalAmount > 0 ? min(100, ($paidAmount / $totalAmount) * 100) : 0;
                                    @endphp
                                    @if ($paidPercent >= 100)
                                        <span class="badge theme-success">{{ __('ledgers.payment_status.paid') }}</span>
                                    @elseif ($paidPercent > 0)
                                        <span class="badge theme-danger">{{ __('ledgers.payment_status.partial') }}</span>
                                    @else
                                        <span class="badge theme-secondary">{{ __('ledgers.payment_status.unpaid') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a
                                            class="btn btn-sm btn-solid theme-info me-1"
                                            href="{{ route('purchase.invoices.show', $entry->document_number) }}"
                                            title="{{ __('common.actions.view') }}"
                                        >
                                            <i data-lucide="eye"></i>
                                        </a>

                                        @if (! $entry->isPosted() && $canApprove)
                                            <form autocomplete="off"
                                                action="{{ route('purchase.invoices.approve', $entry->document_number) }}"
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

                                        @if (! $entry->isPosted() && $canRemove)
                                            <form autocomplete="off"
                                                action="{{ route('purchase.invoices.destroy', $entry->document_number) }}"
                                                method="POST"
                                                data-confirm-message="{{ __('ledgers.delete_invoice_confirmation') }}" data-confirm-tone="danger"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="btn btn-sm btn-solid theme-danger"
                                                    type="submit"
                                                    title="{{ __('common.actions.delete') }}"
                                                >
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="{{ $canApprove ? 7 : 6 }}">
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

            <div class="d-flex justify-content-end mt-3">{{ $entries->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = Array.from(document.querySelectorAll('.invoice-checkbox'));

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
                    const checked = document.querySelectorAll('.invoice-checkbox:checked');
                    if (checked.length === 0) {
                        confirmDialog({
                            text: @json(__('common.alerts.no_selection')),
                            icon: 'warning',
                        })
                        return;
                    }

                    const action = button.dataset.action;
                    const routeUrl =
                        action === 'approve' ? @json(route('purchase.invoices.batch-approve')) : @json(route('purchase.invoices.batch-cancel'));

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
