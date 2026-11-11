@php
    use App\Enums\Finance\LedgerSubType;

    $adjustment = $adjustment ?? null;
    $selectedInvoice = $selectedInvoice ?? null;
@endphp

<ul class="sales-document-tabs nav nav-tabs" id="manual-adjustment-form-tabs">
    <li class="nav-item">
        <button class="nav-link active" data-form-section="general" type="button">
            {{ __('sales_orders.tabs.general') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-form-section="attachment" type="button">
            {{ __('sales_orders.tabs.attachment') }}
        </button>
    </li>
</ul>

<div class="sales-tab-workspace">
    <div class="sales-tab-panel sales-tab-panel-active" id="manual-adjustment-general-section">
        <div class="row g-3 mb-4">
            <div class="md:col-6">
                <label class="form-label fw-bold"
                    for="date">{{ __('ledgers.table.column_date') }}</label>
                <div class="form-control form-adorn">
                    <div class="form-adorn-icon" aria-hidden="true">
                        <i data-lucide="calendar"></i>
                    </div>
                    <input class="form-ghost @error('date') is-invalid @enderror" id="date"
                        name="date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                        value="{{ old('date', $adjustment?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        min="{{ $postingDateMin ?? '' }}" max="{{ $postingDateMax ?? '' }}" required />
                </div>
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold"
                    for="subtype">{{ __('manual_sales_adjustments.form.note_type') }}</label>
                <select class="form-control @error('subtype') is-invalid @enderror" id="subtype"
                    name="subtype" required>
                    <option value="" disabled hidden
                        @if (!old('subtype', $adjustment?->subtype?->value)) selected @endif>
                        {{ __('common.actions.select_option') }}
                    </option>
                    <option value="{{ LedgerSubType::INCREASE_ADJUSTMENT->value }}"
                        @selected(old('subtype', $adjustment?->subtype?->value) === LedgerSubType::INCREASE_ADJUSTMENT->value)>
                        {{ LedgerSubType::INCREASE_ADJUSTMENT->label() }}
                    </option>
                    <option value="{{ LedgerSubType::DECREASE_ADJUSTMENT->value }}"
                        @selected(old('subtype', $adjustment?->subtype?->value) === LedgerSubType::DECREASE_ADJUSTMENT->value)>
                        {{ LedgerSubType::DECREASE_ADJUSTMENT->label() }}
                    </option>
                </select>
                @error('subtype')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="md:col-6">
                <label class="form-label fw-bold"
                    for="company_id">{{ __('ledgers.table.column_company') }}</label>
                <select class="form-control @error('company_id') is-invalid @enderror" id="company_id"
                    name="company_id" onchange="filterContactsByCompany(); handleCompanyChange();">
                    <option value="" disabled hidden
                        @if (!old('company_id', $adjustment?->company_id)) selected @endif>
                        {{ __('common.actions.select_option') }}
                    </option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id', $adjustment?->company_id) == $company->id)>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
                @error('company_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="md:col-6">
                <label class="form-label fw-bold"
                    for="contact_id">{{ __('ledgers.table.column_contact') }}</label>
                <select class="form-control @error('contact_id') is-invalid @enderror" id="contact_id"
                    name="contact_id">
                    <option value="" disabled hidden
                        @if (!old('contact_id', $adjustment?->contact_id)) selected @endif>
                        {{ __('common.actions.select_option') }}
                    </option>
                    @foreach ($contacts as $contact)
                        <option data-company-id="{{ $contact->company_id }}" value="{{ $contact->id }}"
                            @selected(old('contact_id', $adjustment?->contact_id) == $contact->id)>
                            {{ $contact->name }}
                        </option>
                    @endforeach
                </select>
                @error('contact_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="md:col-6">
                <label class="form-label fw-bold"
                    for="sales_invoice_id">{{ __('adjustments.form.linked_invoice') }}</label>
                <select class="form-control @error('sales_invoice_id') is-invalid @enderror"
                    id="sales_invoice_id" name="sales_invoice_id" required>
                    <option value="">{{ __('common.actions.select_option') }}</option>
                    @if ($selectedInvoice)
                        <option value="{{ $selectedInvoice->id }}" selected>
                            {{ $selectedInvoice->document_number }} - {{ $selectedInvoice->date?->format('Y-m-d') }}
                        </option>
                    @endif
                </select>
                @error('sales_invoice_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12">
                <label class="form-label fw-bold"
                    for="notes">{{ __('ledgers.table.column_notes') }}</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2">{{ old('notes', $adjustment?->notes) }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="sales-tab-panel" id="manual-adjustment-attachment-section">
        <div class="row g-3 mb-4">
            <div class="md:col-6">
                <label class="form-label fw-bold"
                    for="attachment">{{ __('ledgers.table.column_attachment_path') }}</label><input
                    class="form-control @error('attachment') is-invalid @enderror" id="attachment"
                    name="attachment" type="file" accept=".pdf,.jpg,.png,.jpeg" />
                @error('attachment')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<hr class="my-4" />

<h6 class="fw-bold mb-3">{{ __('manual_sales_adjustments.detail_lines_title') }}</h6>

<div class="table-responsive mb-3 rounded-3 border">
    <table class="table-form mb-0 table align-middle" id="lines-table">
        <thead class="bg-1">
            <tr>
                <th scope="col" style="width: 30%">
                    {{ __('manual_sales_adjustments.table.column_account') }}</th>
                <th scope="col" style="width: 35%">{{ __('ledgers.table.column_description') }}</th>
                <th scope="col" class="text-end" style="width: 17.5%">
                    {{ __('manual_sales_adjustments.table.column_debit') }}
                </th>
                <th scope="col" class="text-end" style="width: 17.5%">
                    {{ __('manual_sales_adjustments.table.column_credit') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @php
                $adjustmentCharts = $adjustment?->charts?->values() ?? collect();

                // The grid is the detail rows the request accepts, and a
                // stored document is never shown with fewer rows than it has.
                $lineCount = max($perPageGridDetail, $adjustmentCharts->count());
            @endphp
            @for ($i = 0; $i < $lineCount; $i++)
                @php
                    $line = $adjustmentCharts->get($i);
                    $selectedChartId = old('lines.' . $i . '.chart_id', $line?->chart_id);
                    $selectedChart = $charts->firstWhere('id', $selectedChartId);
                @endphp
                <tr data-line-index="{{ $i }}">
                    <td>
                        <div
                            class="d-flex gap-2 align-items-center @error('lines.' . $i . '.chart_id') is-invalid @enderror">
                            <input id="line-{{ $i }}-chart_id" type="hidden" name="lines[{{ $i }}][chart_id]"
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
                        @error('lines.' . $i . '.chart_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>
                    <td>
                        <label class="visually-hidden" for="line-{{ $i }}-description">
                            {{ __('ledgers.table.column_description') }}</label>
                        <div class="form-control form-adorn">
                            <input id="line-{{ $i }}-description" class="form-ghost form-control-sm"
                                name="lines[{{ $i }}][description]" type="text"
                                value="{{ old('lines.' . $i . '.description', $line?->line_description) }}">
                        </div>
                    </td>
                    <td>
                        <label class="visually-hidden" for="line-{{ $i }}-debit_amount">
                            {{ __('manual_sales_adjustments.table.column_debit') }}</label>
                        <div class="form-control form-adorn">
                            <input id="line-{{ $i }}-debit_amount"
                                class="form-ghost form-control-sm debit-input text-end"
                                name="lines[{{ $i }}][debit_amount]" type="number"
                                step="0.01" min="0"
                                value="{{ old('lines.' . $i . '.debit_amount', number_format((float) ($line?->debit_amount ?? 0), 2, '.', '')) }}"
                                oninput="calculateTotals()">
                        </div>
                    </td>
                    <td>
                        <label class="visually-hidden" for="line-{{ $i }}-credit_amount">
                            {{ __('manual_sales_adjustments.table.column_credit') }}</label>
                        <div class="form-control form-adorn">
                            <input id="line-{{ $i }}-credit_amount"
                                class="form-ghost form-control-sm credit-input text-end"
                                name="lines[{{ $i }}][credit_amount]" type="number"
                                step="0.01" min="0"
                                value="{{ old('lines.' . $i . '.credit_amount', number_format((float) ($line?->credit_amount ?? 0), 2, '.', '')) }}"
                                oninput="calculateTotals()">
                        </div>
                    </td>
                </tr>
            @endfor
        </tbody>
        <tfoot class="bg-1 fw-bold">
            <tr>
                <th scope="col" class="text-end" colspan="2">{{ __('common.labels.total') }}:</th>
                <th scope="col" class="fg-primary text-end" id="total-debit">0.00</th>
                <th scope="col" class="fg-primary text-end" id="total-credit">0.00</th>
            </tr>
        </tfoot>
    </table>
</div>

<div class="d-flex justify-content-end mb-4">
    <div id="balance-indicator" class="fw-bold"></div>
</div>

@push('scripts')
    <script>
        function calculateTotals() {
            let debitTotal = 0;
            let creditTotal = 0;

            document.querySelectorAll('.debit-input').forEach(function(input) {
                debitTotal += parseFloat(input.value) || 0;
            });

            document.querySelectorAll('.credit-input').forEach(function(input) {
                creditTotal += parseFloat(input.value) || 0;
            });

            document.getElementById('total-debit').textContent = debitTotal.toFixed(2);
            document.getElementById('total-credit').textContent = creditTotal.toFixed(2);

            const indicator = document.getElementById('balance-indicator');
            if (debitTotal === 0 && creditTotal === 0) {
                indicator.textContent = '';
                indicator.className = 'fw-bold';
            } else if (Math.abs(debitTotal - creditTotal) < 0.005) {
                indicator.textContent = @json(__('manual_sales_adjustments.balance_status_balanced'));
                indicator.className = 'fw-bold fg-success';
            } else {
                const diff = (debitTotal - creditTotal).toFixed(2);
                indicator.textContent = @json(__('manual_sales_adjustments.balance_status_unbalanced')) + ' (' + diff + ')';
                indicator.className = 'fw-bold fg-danger';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            calculateTotals();
            initializeInvoiceSelect();

            // Account picker modal functionality
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
                const typeToSearchText = @json(__('manual_sales_adjustments.coa_picker.type_to_search'));
                const noResultsText = @json(__('manual_sales_adjustments.coa_picker.no_results'));
                const searchErrorText = @json(__('manual_sales_adjustments.coa_picker.search_error'));

                if (search.length < 2 && !typeId) {
                    renderCoaPlaceholder(typeToSearchText);
                    return;
                }

                fetch('{{ route('sales.manual-adjustments.charts.search') }}?q=' + encodeURIComponent(search) +
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
                if (currentCoaLineIndex === null) return;

                const row = document.querySelector(
                    `#lines-table tbody tr[data-line-index="${currentCoaLineIndex}"]`);
                if (!row) return;

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
                    renderCoaPlaceholder(@json(__('manual_sales_adjustments.coa_picker.type_to_search')));
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

        const invoiceOptionsUrl = @json(route('sales.manual-adjustments.invoices'));
        const invoiceSelectText = @json(__('common.actions.select_option'));

        function initializeInvoiceSelect() {
            if (typeof window.initCombobox !== 'function') {
                return;
            }

            const referenceSelect = document.getElementById('sales_invoice_id');

            if (referenceSelect.autoComplete) {
                referenceSelect.autoComplete.destroy();
            }

            window.initCombobox(referenceSelect, {
                placeholder: invoiceSelectText,
                load(query, callback) {
                    const companyId = document.getElementById('company_id').value;

                    if (!companyId) {
                        callback();

                        return;
                    }

                    const url = new URL(invoiceOptionsUrl, window.location.origin);
                    url.searchParams.set('company_id', companyId);
                    url.searchParams.set('search', query);

                    fetch(url, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error('Unable to load invoice options.');
                            }

                            return response.json();
                        })
                        .then((payload) => callback(payload.results ?? []))
                        .catch(() => callback());
                },
            });
        }

        function handleCompanyChange() {
            const referenceSelect = document.getElementById('sales_invoice_id');

            if (!referenceSelect || !referenceSelect.autoComplete) {
                return;
            }

            referenceSelect.autoComplete.clear();

            if (document.getElementById('company_id').value) {
                referenceSelect.autoComplete.load('');
            }
        }

        function filterContactsByCompany() {
            const companyId = document.getElementById('company_id').value;
            const contactSelect = document.getElementById('contact_id');
            contactSelect.querySelectorAll('option').forEach(function(option) {
                if (!option.value) return;
                option.hidden = companyId && option.dataset.companyId !== companyId;
            });
            if (contactSelect.value && contactSelect.selectedOptions[0]?.hidden) {
                contactSelect.value = '';
            }
        }
    </script>
@endpush
