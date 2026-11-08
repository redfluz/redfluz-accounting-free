@php
    use App\Models\ChartOfAccount\Chart;
    use App\Models\Bank\EWalletAccount;
    use Illuminate\Database\Eloquent\Collection;
@endphp
@props([
    /** @var EWalletAccount|null */
    'eWalletAccount' => null,
    /** @var Collection<int, Chart> */
    'charts',
])

<div class="row g-3">
    <div class="md:col-6">
        <label class="form-label fw-bold" for="chart_id"
            >{{ __('banks.fields.chart') }} <span class="fg-danger">*</span></label>
        <select class="form-control @error('chart_id') is-invalid @enderror" id="chart_id" name="chart_id" required>
            <option value="">{{ __('banks.placeholders.ewallet_chart') }}</option>
            @foreach ($charts as $chart)
                <option value="{{ $chart->id }}" @selected(old('chart_id', $eWalletAccount?->chart_id) === $chart->id)>
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
            >{{ __('banks.fields.ewallet_name') }} <span class="fg-danger">*</span></label>
        <div class="form-control form-adorn">
            <div class="form-adorn-icon" aria-hidden="true">
                <i data-lucide="building"></i>
            </div>
            <input
                class="form-ghost @error('name') is-invalid @enderror"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $eWalletAccount?->name) }}"
                required
            />
        </div>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
