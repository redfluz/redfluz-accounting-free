@php
    use App\Models\Finance\JournalEntry;
@endphp
@props([
    /** @var JournalEntry|null */
    'journalEntry' => null,
    'charts',
    'chartTypes',
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
    <table class="table-striped table-sm mb-0 table align-middle" id="lines-table">
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
            @for ($i = 0; $i < $perPageGridDetail; $i++)
                @php
                    if ($isEditing) {
                        $item = $journalEntry->charts->get($i);
                        $defaultChartId = $item?->chart_id;
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
                    $selectedChart = filled($selectedChartId)
                        ? $charts->first(fn ($chart) => (string) $chart->id === (string) $selectedChartId)
                        : null;
                @endphp
                <tr data-line-index="{{ $i }}">
                    <td>
                        <label class="visually-hidden" for="line-{{ $i }}-chart_id">{{ __('journal_entries.table.column_account') }}</label>
                        <div
                            class="d-flex gap-2 align-items-center @error('items.' . $i . '.chart_id') is-invalid @enderror">
                            <input id="line-{{ $i }}-chart_id" type="hidden" name="items[{{ $i }}][chart_id]"
                                class="line-chart-id" value="{{ $selectedChartId }}" />
                            <div
                                class="line-chart-display form-control form-control-sm d-flex align-items-center justify-content-between">
                                <span class="line-chart-label text-truncate">
                                    {{ $selectedChart ? $selectedChart->code . ' - ' . $selectedChart->name : '' }}
                                </span>
                            </div>
                            <button
                                class="btn btn-sm btn-solid theme-primary line-chart-choose-btn flex-shrink-0"
                                type="button" data-line-index="{{ $i }}">
                                <i data-lucide="search"></i>
                            </button>
                        </div>
                        @error('items.' . $i . '.chart_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>
                    <td>
                        <div class="form-control form-adorn">
                            <label class="visually-hidden" for="line-{{ $i }}-description">{{ __('journal_entries.table.column_description') }}</label>
                            <input class="form-ghost form-control-sm"
                                id="line-{{ $i }}-description"
                                name="items[{{ $i }}][description]" type="text"
                                value="{{ old('items.' . $i . '.description', $defaultDescription) }}" />
                        </div>
                    </td>
                    <td>
                        <div class="form-control form-adorn">
                            <label class="visually-hidden" for="line-{{ $i }}-debit_amount">{{ __('journal_entries.table.column_debit_amount') }}</label>
                            <input class="form-ghost form-control-sm debit-input text-end"
                                id="line-{{ $i }}-debit_amount"
                                name="items[{{ $i }}][debit_amount]" type="number"
                                value="{{ old('items.' . $i . '.debit_amount', $defaultDebit) }}"
                                step="0.01" min="0" oninput="calculateTotals()" />
                        </div>
                    </td>
                    <td>
                        <div class="form-control form-adorn">
                            <label class="visually-hidden" for="line-{{ $i }}-credit_amount">{{ __('journal_entries.table.column_credit_amount') }}</label>
                            <input class="form-ghost form-control-sm credit-input text-end"
                                id="line-{{ $i }}-credit_amount"
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

        document.addEventListener('DOMContentLoaded', function() {
            calculateTotals();

            const chartsSearchUrl = @json(route('finance.journal-entries.charts.search'));
            const typeToSearchText = @json(__('journal_entries.coa_picker.type_to_search'));
            const noResultsText = @json(__('journal_entries.coa_picker.no_results'));
            const searchErrorText = @json(__('journal_entries.coa_picker.search_error'));

            const coaPickerDialog = document.getElementById('coaPickerDialog');
            const coaSearchInput = document.getElementById('coa-search-input');
            const coaTypeFilter = document.getElementById('coa-type-filter');
            const coaSearchResults = document.getElementById('coa-search-results-table');
            let currentCoaLineIndex = null;
            let coaSearchTimeout = null;

            function renderCoaPlaceholder(text) {
                coaSearchResults.querySelector('tbody').innerHTML =
                    '<tr><td colspan="3" class="text-center text-muted" style="font-size: 1.05rem; padding: 1rem;">' +
                    text + '</td></tr>';
            }

            function searchCoa() {
                const search = coaSearchInput.value;
                const typeId = coaTypeFilter.value;

                if (search.length < 2 && !typeId) {
                    renderCoaPlaceholder(typeToSearchText);
                    return;
                }

                fetch(chartsSearchUrl + '?q=' + encodeURIComponent(search) +
                        '&type_id=' + encodeURIComponent(typeId))
                    .then(response => response.json())
                    .then(charts => {
                        if (charts.length === 0) {
                            renderCoaPlaceholder(noResultsText);
                            return;
                        }

                        coaSearchResults.querySelector('tbody').innerHTML = charts.map(chart => `
                            <tr style="cursor: pointer; font-size: 1.05rem;"
                                data-chart-id="${chart.id}"
                                data-chart-code="${chart.code.replace(/"/g, '&quot;')}"
                                data-chart-name="${chart.name.replace(/"/g, '&quot;')}">
                                <td style="padding: 1rem;">${chart.code}</td>
                                <td style="padding: 1rem;">${chart.name}</td>
                                <td style="padding: 1rem;">${chart.type || '-'}</td>
                            </tr>
                        `).join('');

                        coaSearchResults.querySelectorAll('tbody tr[data-chart-id]').forEach(row => {
                            row.addEventListener('click', function() {
                                selectCoa(
                                    this.dataset.chartId,
                                    this.dataset.chartCode,
                                    this.dataset.chartName
                                );
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Error searching accounts:', error);
                        coaSearchResults.querySelector('tbody').innerHTML =
                            '<tr><td colspan="3" class="text-center text-danger" style="font-size: 1.05rem; padding: 1rem;">' +
                            searchErrorText + '</td></tr>';
                    });
            }

            window.selectCoa = function(chartId, chartCode, chartName) {
                if (currentCoaLineIndex === null) {
                    return;
                }

                const row = document.querySelector(
                    `#lines-table tbody tr[data-line-index="${currentCoaLineIndex}"]`);
                if (!row) {
                    return;
                }

                const chartInput = row.querySelector('.line-chart-id');
                const chartLabel = row.querySelector('.line-chart-label');

                if (chartInput) chartInput.value = chartId;
                if (chartLabel) chartLabel.textContent = chartCode + ' - ' + chartName;

                window.bootstrap.Dialog.getOrCreateInstance(coaPickerDialog).hide();
            };

            document.querySelectorAll('.line-chart-choose-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    currentCoaLineIndex = this.dataset.lineIndex;
                    coaSearchInput.value = '';
                    coaTypeFilter.value = '';
                    renderCoaPlaceholder(typeToSearchText);
                    window.bootstrap.Dialog.getOrCreateInstance(coaPickerDialog).show();
                    setTimeout(() => coaSearchInput.focus(), 100);
                });
            });

            coaSearchInput.addEventListener('input', () => {
                clearTimeout(coaSearchTimeout);
                coaSearchTimeout = setTimeout(searchCoa, 300);
            });

            coaTypeFilter.addEventListener('change', searchCoa);
        });
    </script>
@endsection
