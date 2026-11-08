@php
    use App\Models\ChartOfAccount\Chart;
    use App\Models\Inventory\StockLocation;
    use App\Models\Bank\CashAccount;
    use Illuminate\Database\Eloquent\Collection;
@endphp
@props([
    /** @var CashAccount|null */
    'cashAccount' => null,
    /** @var Collection<int, Chart> */
    'charts',
    /** @var Collection<int, StockLocation> */
    'stockLocations',
])

{{--
    Cash drawers hold physical money: no provider, no banking codes, no
    account number. Everything a drawer needs fits on one screen.
--}}

<div class="row g-3">
    <div class="md:col-6">
        <label class="form-label fw-bold" for="chart_id"
            >{{ __('banks.fields.chart') }} <span class="fg-danger">*</span></label>
        <select class="form-control @error('chart_id') is-invalid @enderror" id="chart_id" name="chart_id" required>
            <option value="">{{ __('banks.placeholders.cash_chart') }}</option>
            @foreach ($charts as $chart)
                <option value="{{ $chart->id }}" @selected(old('chart_id', $cashAccount?->chart_id) === $chart->id)>
                    {{ $chart->code }} - {{ $chart->name }}
                </option>
            @endforeach
        </select>
        @error('chart_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="name"
            >{{ __('banks.fields.cash_name') }} <span class="fg-danger">*</span></label>
        <div class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="building"></i>
            </div>
            <input
                class="form-ghost @error('name') is-invalid @enderror"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $cashAccount?->name) }}"
                required
            />
        </div>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="md:col-6">
        <label class="form-label fw-bold" for="asset_location_id"
            >{{ __('banks.fields.asset_location') }}</label>
        <select class="form-control @error('asset_location_id') is-invalid @enderror"
            id="asset_location_id" name="asset_location_id">
            <option value="">{{ __('common.actions.select_option') }}</option>
            @foreach ($stockLocations as $location)
                <option value="{{ $location->id }}"
                    @selected(old('asset_location_id', $cashAccount?->asset_location_id) === $location->id)>
                    {{ $location->name }}
                </option>
            @endforeach
        </select>
        @error('asset_location_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
