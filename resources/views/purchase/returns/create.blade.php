@extends('layouts.app')
@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('purchase_returns.title') }}</h4>
            <p class="fg-secondary small mb-0">{{ __('purchase_returns.subtitle') }}</p>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off" method="POST" action="{{ route('purchase.returns.store') }}">
                @csrf
                <div class="row">
                    <div class="md:col-4 mb-3">
                        <label class="form-label" for="company_filter">{{ __('purchase_returns.company') }}</label>
                        <select class="form-control" id="company_filter" required>
                            <option value="" disabled hidden>{{ __('common.actions.select_option') }}</option>
                            @foreach ($groupedReceipts->keys() as $company)
                                <option value="{{ $company }}">{{ $company }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-4 mb-3">
                        <label class="form-label" for="document_filter">{{ __('purchase_returns.grn_document') }}</label>
                        <select class="form-control" id="document_filter" required disabled>
                            <option value="" disabled hidden>{{ __('common.actions.select_option') }}</option>
                        </select>
                    </div>
                    <div class="md:col-4 mb-3">
                        <label class="form-label" for="source_entry_id">{{ __('purchase_returns.receipt_line') }}</label>
                        <select class="form-control" name="source_entry_id" id="source_entry_id" required disabled>
                            <option value="" disabled hidden>{{ __('common.actions.select_option') }}</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="md:col-6 mb-3">
                        <label class="form-label" for="quantity">{{ __('purchase_returns.quantity') }}</label>
                        <label class="form-control form-adorn">
                            <input class="form-ghost" type="number" id="quantity" name="quantity" step="0.0001"
                                min="0.0001" value="{{ old('quantity') }}" required />
                        </label>
                    </div>
                    <div class="md:col-6 mb-3">
                        <label class="form-label" for="date">{{ __('purchase_returns.date') }}</label>
                        <label class="form-control form-adorn">
                            <div class="form-adorn-icon" aria-hidden="true">
                                <i data-lucide="calendar"></i>
                            </div>
                            <input class="form-ghost" type="text" data-bs-toggle="datepicker" autocomplete="off"
                                id="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                                min="{{ $postingDateMin ?? '' }}" max="{{ $postingDateMax ?? '' }}" required />
                        </label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="notes">{{ __('purchase_returns.notes') }}</label>
                    <textarea class="form-control" name="notes" id="notes">{{ old('notes') }}</textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <a class="btn btn-solid theme-secondary"
                        href="{{ route('purchase.returns.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                    </a>
                    <button class="btn btn-solid theme-accent" type="submit">
                        <i class="me-1" data-lucide="save"></i>{{ __('common.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const groupedReceipts = @json($groupedReceipts);

            const companyFilter = document.getElementById('company_filter');
            const documentFilter = document.getElementById('document_filter');
            const lineFilter = document.getElementById('source_entry_id');

            companyFilter.addEventListener('change', () => {
                const company = companyFilter.value;
                documentFilter.innerHTML = '<option value="" disabled hidden>{{ __('common.actions.select_option') }}</option>';
                lineFilter.innerHTML = '<option value="" disabled hidden>{{ __('common.actions.select_option') }}</option>';
                lineFilter.disabled = true;

                if (company && groupedReceipts[company]) {
                    Object.keys(groupedReceipts[company]).forEach(doc => {
                        const opt = document.createElement('option');
                        opt.value = doc;
                        opt.textContent = doc;
                        documentFilter.appendChild(opt);
                    });
                    documentFilter.disabled = false;
                } else {
                    documentFilter.disabled = true;
                }
            });

            documentFilter.addEventListener('change', () => {
                const company = companyFilter.value;
                const doc = documentFilter.value;
                lineFilter.innerHTML = '<option value="" disabled hidden>{{ __('common.actions.select_option') }}</option>';

                if (company && doc && groupedReceipts[company] && groupedReceipts[company][doc]) {
                    groupedReceipts[company][doc].forEach(line => {
                        const opt = document.createElement('option');
                        opt.value = line.id;
                        opt.textContent = `${line.product_name} — {{ $currencySymbol }}${line.reference_number || ''} — ${line.quantity}`;
                        lineFilter.appendChild(opt);
                    });
                    lineFilter.disabled = false;
                } else {
                    lineFilter.disabled = true;
                }
            });
        });
    </script>
@endsection
