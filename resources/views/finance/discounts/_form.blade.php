<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h4 class="fw-bold mb-0">{{ $formTitle }}</h4>
    </div>
    <div class="card-body">
        <form autocomplete="off" action="{{ $formAction }}" method="POST">
            @csrf
            @if ($discount !== null)
                <input name="id" type="hidden" value="{{ $discount->id }}" />
            @endif
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif
            <div class="row g-3">
                <div class="md:col-4">
                    <label class="form-label fw-bold" for="code">{{ __('discounts.form.code') }}</label>
                    <label class="form-control form-adorn">
                        <input class="form-ghost @error('code') is-invalid @enderror" id="code" name="code"
                            value="{{ old('code', $discount?->code) }}" maxlength="50" required />
                    </label>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="md:col-8">
                    <label class="form-label fw-bold" for="name">{{ __('discounts.form.name') }}</label>
                    <label class="form-control form-adorn">
                        <input class="form-ghost @error('name') is-invalid @enderror" id="name" name="name"
                            value="{{ old('name', $discount?->name) }}" required />
                    </label>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="md:col-6">
                    <label class="form-label fw-bold" for="type">{{ __('discounts.form.type') }}</label>
                    <select class="form-control @error('type') is-invalid @enderror" id="type" name="type"
                        required>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(old('type', $discount?->type?->value ?? 'percent') === $type->value)>
                                {{ __('discounts.type.' . $type->value) }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="md:col-6">
                    <label class="form-label fw-bold" for="value">{{ __('discounts.form.value') }}</label>
                    <label class="form-control form-adorn">
                        <input class="form-ghost @error('value') is-invalid @enderror" id="value" name="value"
                            type="number" value="{{ old('value', $discount?->value ?? '0') }}" min="0"
                            step="0.0001" required />
                    </label>
                    @error('value')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="md:col-6">
                    <label class="form-label fw-bold"
                        for="starts_at">{{ __('discounts.form.starts_at') }}</label>
                    <label class="form-control form-adorn">
                        <div class="form-adorn-icon" aria-hidden="true">
                            <i data-lucide="calendar"></i>
                        </div>
                        <input class="form-ghost @error('starts_at') is-invalid @enderror" id="starts_at" name="starts_at"
                            type="text" data-bs-toggle="datepicker" autocomplete="off"
                            value="{{ old('starts_at', $discount?->starts_at?->format('Y-m-d')) }}" />
                    </label>
                    @error('starts_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="md:col-6">
                    <label class="form-label fw-bold"
                        for="ends_at">{{ __('discounts.form.ends_at') }}</label>
                    <label class="form-control form-adorn">
                        <div class="form-adorn-icon" aria-hidden="true">
                            <i data-lucide="calendar"></i>
                        </div>
                        <input class="form-ghost @error('ends_at') is-invalid @enderror" id="ends_at" name="ends_at"
                            type="text" data-bs-toggle="datepicker" autocomplete="off"
                            value="{{ old('ends_at', $discount?->ends_at?->format('Y-m-d')) }}" />
                    </label>
                    @error('ends_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="switch">
                        <input id="is_active" name="is_active" type="checkbox" value="1"
                            @checked(old('is_active', $discount?->is_active ?? true)) role="switch" switch />
                    </div>
                    <label for="is_active">{{ __('discounts.form.is_active') }}</label>
                </div>
            </div>
            <div class="d-flex justify-content-between border-top mt-4 pt-3">
                <a class="btn btn-solid theme-secondary"
                    href="{{ route('finance.discounts.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}
                </a>
                <button class="btn btn-solid theme-primary" type="submit">{{ __('discounts.form.save') }}</button>
            </div>
        </form>
    </div>
</div>
