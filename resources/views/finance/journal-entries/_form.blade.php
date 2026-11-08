@php
    use App\Models\Finance\JournalEntry;
@endphp
@props([
    /** @var JournalEntry|null */
    'journalEntry' => null,
    'charts',
])

@php
    $isEditing = $journalEntry !== null;
@endphp

<div class="row mb-4">
    <div class="md:col-4">
        <label class="form-label fw-bold" for="date">
            {{ __('journal_entries.table.column_date') }}
        </label>
        <div class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="calendar"></i>
            </div>
            <input class="form-ghost @error('date') is-invalid @enderror" id="date" name="date"
                type="text" data-bs-toggle="datepicker" autocomplete="off"
                value="{{ old('date', $isEditing ? $journalEntry->date->format('Y-m-d') : ($prefillDate ?? now()->format('Y-m-d'))) }}"
                min="{{ $postingDateMin ?? '' }}" max="{{ $postingDateMax ?? '' }}" required />
        </div>
        @error('date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-4">
        <label class="form-label fw-bold" for="reference_number">
            {{ __('journal_entries.table.column_reference_number') }}
        </label>
        <div class="form-control form-adorn">
            <input class="form-ghost @error('reference_number') is-invalid @enderror" id="reference_number"
                name="reference_number" type="text"
                value="{{ old('reference_number', $isEditing ? $journalEntry->reference_number : ($prefillReference ?? '')) }}" />
        </div>
        @error('reference_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-4">
        <label class="form-label fw-bold" for="notes">
            {{ __('journal_entries.table.column_notes') }}
        </label>
        <div class="form-control form-adorn">
            <input class="form-ghost @error('notes') is-invalid @enderror" id="notes" name="notes"
                type="text" value="{{ old('notes', $isEditing ? $journalEntry->notes : ($prefillNotes ?? '')) }}" />
        </div>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="table-responsive rounded-5 mb-3 border">
    <table class="table-striped table-sm mb-0 table align-middle">
        <thead class="bg-1">
            <tr>
                <th scope="col" style="width: 30%">{{ __('journal_entries.table.column_account') }}</th>
                <th scope="col" style="width: 35%">{{ __('journal_entries.table.column_description') }}</th>
                <th scope="col" class="text-end" style="width: 15%">
                    {{ __('journal_entries.table.column_debit_amount') }}
                </th>
                <th scope="col" class="text-end" style="width: 15%">
                    {{ __('journal_entries.table.column_credit_amount') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < 10; $i++)
                @php
                    if ($isEditing) {
                        $item = $journalEntry->charts->get($i);
                        $defaultChartId = $item?->debit_id ?? $item?->credit_id;
                        $defaultDescription = $item?->description;
                        $defaultDebit = number_format((float) ($item?->debit_amount ?? 0), 2, '.', '');
                        $defaultCredit = number_format((float) ($item?->credit_amount ?? 0), 2, '.', '');
                    } else {
                        $defaultChartId = $prefillItems[$i]['chart_id'] ?? null;
                        $defaultDescription = $prefillItems[$i]['description'] ?? '';
                        $defaultDebit = $prefillItems[$i]['debit_amount'] ?? '0.00';
                        $defaultCredit = $prefillItems[$i]['credit_amount'] ?? '0.00';
                    }
                    $selectedChartId = old('items.' . $i . '.chart_id', $defaultChartId);
                @endphp
                <tr>
                    <td>
                        <label class="visually-hidden" for="chart_id_{{ $i }}">{{ __('journal_entries.table.column_account') }}</label>
                        <select
                            class="form-control form-control-sm js-chart-select @error('items.' . $i . '.chart_id') is-invalid @enderror"
                            id="chart_id_{{ $i }}"
                            name="items[{{ $i }}][chart_id]">
                            <option value="">{{ __('common.actions.select_option') }}</option>
                            @if (filled($selectedChartId) && !$charts->contains(fn($chart) => (string) $chart->id === (string) $selectedChartId))
                                <option value="{{ $selectedChartId }}" selected>
                                    {{ $selectedChartId }}
                                </option>
                            @endif
                            @foreach ($charts as $chart)
                                <option value="{{ $chart->id }}" @selected((string) $selectedChartId === (string) $chart->id)>
                                    {{ $chart->code }}{{ $chart->name ? ' - ' . $chart->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('items.' . $i . '.chart_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>
                    <td>
                        <div class="form-control form-adorn">
                            <label class="visually-hidden" for="description_{{ $i }}">{{ __('journal_entries.table.column_description') }}</label>
                            <input class="form-ghost form-control-sm"
                                id="description_{{ $i }}"
                                name="items[{{ $i }}][description]" type="text"
                                value="{{ old('items.' . $i . '.description', $defaultDescription) }}" />
                        </div>
                    </td>
                    <td>
                        <div class="form-control form-adorn">
                            <label class="visually-hidden" for="debit_{{ $i }}">{{ __('journal_entries.table.column_debit_amount') }}</label>
                            <input class="form-ghost form-control-sm debit-input text-end"
                                id="debit_{{ $i }}"
                                name="items[{{ $i }}][debit_amount]" type="number"
                                value="{{ old('items.' . $i . '.debit_amount', $defaultDebit) }}"
                                step="0.01" min="0" oninput="calculateTotals()" />
                        </div>
                    </td>
                    <td>
                        <div class="form-control form-adorn">
                            <label class="visually-hidden" for="credit_{{ $i }}">{{ __('journal_entries.table.column_credit_amount') }}</label>
                            <input class="form-ghost form-control-sm credit-input text-end"
                                id="credit_{{ $i }}"
                                name="items[{{ $i }}][credit_amount]" type="number"
                                value="{{ old('items.' . $i . '.credit_amount', $defaultCredit) }}"
                                step="0.01" min="0" oninput="calculateTotals()" />
                        </div>
                    </td>
                </tr>
            @endfor
        </tbody>
        <tfoot class="bg-1 fw-bold">
            <tr>
                <td class="text-end" colspan="2">{{ __('common.labels.total') ?? 'Total' }}:</td>
                <td class="text-end" id="totalDebitDisplay">0.00</td>
                <td class="text-end" id="totalCreditDisplay">0.00</td>
            </tr>
        </tfoot>
    </table>
</div>

@section('script')
    <script>
        function calculateTotals() {
            let debitTotal = 0;
            let creditTotal = 0;

            document.querySelectorAll('.debit-input').forEach((input) => {
                debitTotal += parseFloat(input.value) || 0;
            });

            document.querySelectorAll('.credit-input').forEach((input) => {
                creditTotal += parseFloat(input.value) || 0;
            });

            document.getElementById('totalDebitDisplay').textContent = debitTotal.toFixed(2);
            document.getElementById('totalCreditDisplay').textContent = creditTotal.toFixed(2);
        }

        function initializeCoaSelects() {
            if (typeof window.initCombobox !== 'function') {
                return;
            }

            document.querySelectorAll('.js-chart-select').forEach((element) => {
                if (element.dataset.comboboxBound) {
                    return;
                }

                window.initCombobox(element, {
                    placeholder: @json(__('common.actions.select_option')),
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeCoaSelects();
            calculateTotals();
        });
    </script>
@endsection
