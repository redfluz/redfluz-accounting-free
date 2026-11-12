@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use Carbon\Carbon;
@endphp
@props(['documentNumber', 'lines', 'invoice', 'canDeductInventory' => false, 'deductableLines' => collect()])

@php
    $canUpdate = auth()
        ->user()
        ->hasFilePermission(FileResources::SALES_INVOICES, FileAction::UPDATE);
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
        ->hasFilePermission(FileResources::SALES_INVOICES, FileAction::APPROVE)
        && $invoice->undone_at === null;
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
                        data-bs-target="#undoWizardModal" type="button">
                        <i data-lucide="undo-2"></i> {{ __('journal_entries.reverse_button') }}
                    </button>
                @endif
                @if ($canDeductInventory && $canUpdate)
                    <button class="btn btn-sm btn-solid theme-warning me-1" type="button"
                        data-bs-toggle="dialog" data-bs-target="#deductInventoryModal"
                        title="{{ __('invoices.deduct_inventory.label') }}">
                        <i data-lucide="package-minus"></i>
                    </button>
                @endif
                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('sales.invoices.history.pdf', $documentNumber) }}" target="_blank">
                    <i class="me-1" data-lucide="printer"></i>
                    {{ __('common.actions.print_pdf') }}
                </a>
                <a class="btn btn-solid theme-primary btn-sm text-nowrap"
                    href="{{ route('sales.invoices.history.clone', $documentNumber) }}">
                    <i data-lucide="copy"></i> {{ __('common.actions.clone') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('sales.invoices.history.index') }}">
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
                        <strong>{{ Carbon::parse($invoice->date)->format(Setting::getDefaultDate()) }}</strong>
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
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col">{{ __('ledgers.table.column_product') }}</th>
                            <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                            <th scope="col" class="text-end">{{ __('ledgers.table.column_quantity') }}</th>
                            <th scope="col" class="text-end">{{ __('ledgers.table.column_unit_price') }}</th>
                            <th scope="col" class="text-end">{{ __('ledgers.table.column_discount_amount') }}</th>
                            <th scope="col" class="text-end">{{ __('ledgers.table.column_tax_amount') }}</th>
                            <th scope="col" class="text-end">{{ __('ledgers.table.column_total_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            <tr>
                                <td>{{ $line->product?->name ?? '-' }}</td>
                                <td>{{ $line->description ?? '-' }}</td>
                                <td class="text-end">
                                    {{ $line->quantity }}
                                </td>
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
                            <th scope="row" class="text-end" colspan="6">{{ __('ledgers.table.column_subtotal_amount') }}</th>
                            <th scope="row" class="text-end">{{ number_format($subtotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th scope="row" class="text-end" colspan="6">{{ __('ledgers.table.column_discount_amount') }}</th>
                            <th scope="row" class="text-end">{{ number_format($discountTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th scope="row" class="text-end" colspan="6">{{ __('ledgers.table.column_tax_amount') }}</th>
                            <th scope="row" class="text-end">{{ number_format($taxTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th scope="row" class="text-end" colspan="6">{{ __('common.labels.total') }}</th>
                            <th scope="row" class="fg-primary fs-2xl text-end">{{ number_format($grandTotal, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @if ($canReverse)
        <dialog class="dialog dialog-lg" id="reverseJournalModal" aria-labelledby="reverseJournalModalLabel">
            <form method="POST" action="{{ route('sales.invoices.reverse', $documentNumber) }}">
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

    @if ($canDeductInventory && $canUpdate)
        <dialog class="dialog dialog-lg" id="deductInventoryModal" aria-labelledby="deductInventoryModalLabel">
            <form method="POST" action="{{ route('sales.invoices.deduct-inventory', $documentNumber) }}"
                data-confirm-message="{{ __('invoices.deduct_inventory.confirm') }}" data-confirm-tone="danger">
                @csrf
                <div class="dialog-header">
                    <h5 class="dialog-title" id="deductInventoryModalLabel">
                        {{ __('invoices.deduct_inventory.title') }}
                    </h5>
                    <button class="btn-close" data-bs-dismiss="dialog" type="button"
                        aria-label="{{ __('common.actions.close') }}"></button>
                </div>
                <div class="dialog-body">
                    <div class="alert alert theme-warning">{{ __('invoices.deduct_inventory.description') }}</div>
                    @error('line_ids')
                        <div class="alert alert theme-danger">{{ $message }}</div>
                    @enderror
                    <div class="table-responsive rounded border">
                        <table class="table-hover table-striped mb-0 table align-middle">
                            <thead class="bg-1">
                                <tr>
                                    <th scope="col" style="width: 48px;">
                                        <input class="form-check-input" id="deduct-select-all" type="checkbox">
                                    </th>
                                    <th scope="col">{{ __('ledgers.table.column_product') }}</th>
                                    <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                                    <th scope="col" class="text-end">{{ __('invoices.deduct_inventory.quantity') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deductableLines as $deductLine)
                                    <tr>
                                        <td>
                                            <input class="form-check-input deduct-line-checkbox" type="checkbox"
                                                name="line_ids[]" value="{{ $deductLine->id }}"
                                                id="deduct-line-{{ $deductLine->id }}"
                                                @checked(in_array($deductLine->id, old('line_ids', $deductableLines->pluck('id')->all()), true))>
                                        </td>
                                        <td>
                                            <label class="mb-0" for="deduct-line-{{ $deductLine->id }}">
                                                {{ $deductLine->product->name ?? ($deductLine->product_name ?? '-') }}
                                            </label>
                                        </td>
                                        <td>{{ $deductLine->description ?? '-' }}</td>
                                        <td class="text-end">{{ $deductLine->quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="dialog-footer">
                    <button class="btn btn-solid theme-secondary" data-bs-dismiss="dialog" type="button">
                        {{ __('common.actions.cancel') }}</button>
                    <button class="btn btn-solid theme-primary" type="submit" name="deduct_all" value="1">
                        <i data-lucide="package-minus"></i> {{ __('invoices.deduct_inventory.deduct_all') }}
                    </button>
                    <button class="btn btn-solid theme-warning" type="submit">
                        <i data-lucide="package-minus"></i> {{ __('invoices.deduct_inventory.deduct_selected') }}
                    </button>
                </div>
            </form>
        </dialog>
        @if ($errors->has('line_ids'))
            <script>
                document.addEventListener('DOMContentLoaded', () =>
                    bootstrap.Dialog.getOrCreateInstance(document.getElementById('deductInventoryModal')).show(),
                );
            </script>
        @endif
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('deductInventoryModal');
                const selectAll = document.getElementById('deduct-select-all');
                if (!modal || !selectAll) return;
                const boxes = () => Array.from(modal.querySelectorAll('.deduct-line-checkbox'));
                const sync = () => {
                    const all = boxes();
                    selectAll.checked = all.length > 0 && all.every((b) => b.checked);
                };
                selectAll.addEventListener('change', () => boxes().forEach((b) => b.checked = selectAll.checked));
                modal.addEventListener('change', (e) => {
                    if (e.target.classList.contains('deduct-line-checkbox')) sync();
                });
                sync();
            });
        </script>
    @endif
@endsection
