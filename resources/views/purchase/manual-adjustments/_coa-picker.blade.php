{{-- Account Picker Dialog --}}
<dialog class="dialog" id="coaPickerDialog"
    style="width: 90vw; max-width: 1200px; height: 85vh; max-height: 800px; padding: 0;">
    <div class="dialog-header">
        <h5 class="mb-0">{{ __('manual_purchase_adjustments.coa_picker.title') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="dialog"></button>
    </div>
    <div class="dialog-body d-flex flex-column" style="min-height: 0;">
        <div class="row g-3 mb-3 flex-shrink-0">
            <div class="md:col-8">
                <label class="visually-hidden" for="coa-search-input"> - </label>
                <input type="text" class="form-control form-control-lg" id="coa-search-input"
                    placeholder="{{ __('manual_purchase_adjustments.coa_picker.search_placeholder') }}"
                    style="font-size: 1.1rem;" autocomplete="off">
            </div>
            <div class="md:col-4">
                <label class="visually-hidden" for="coa-type-filter"> - </label>
                <select class="form-control form-control-lg" id="coa-type-filter" style="font-size: 1.1rem;">
                    <option value="">{{ __('manual_purchase_adjustments.coa_picker.all_types') }}</option>
                    @foreach ($charts->pluck('chartType')->filter()->unique('id')->sortBy('name') as $chartType)
                        <option value="{{ $chartType->id }}">{{ $chartType->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive flex-fill" style="overflow-y: auto; min-height: 0;">
            <table class="table table-hover" id="coa-search-results-table">
                <thead class="sticky-top bg-body">
                    <tr>
                        <th scope="col" style="font-size: 1.1rem; padding: 1rem;">
                            {{ __('manual_purchase_adjustments.coa_picker.code') }}</th>
                        <th scope="col" style="font-size: 1.1rem; padding: 1rem;">
                            {{ __('manual_purchase_adjustments.coa_picker.name') }}</th>
                        <th scope="col" style="font-size: 1.1rem; padding: 1rem;">
                            {{ __('manual_purchase_adjustments.coa_picker.type') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="3" class="text-center text-muted" style="font-size: 1.05rem; padding: 1rem;">
                            {{ __('manual_purchase_adjustments.coa_picker.type_to_search') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="dialog-footer">
        <button type="button" class="btn btn-solid theme-secondary" data-bs-dismiss="dialog">
            {{ __('common.actions.close') }}
        </button>
    </div>
</dialog>
