@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::CHART_TYPES, FileAction::UPDATE);

    $currentType = match (true) {
        (bool) $chartType->isAsset => 'asset',
        (bool) $chartType->isLiability => 'liability',
        (bool) $chartType->isEquity => 'equity',
        (bool) $chartType->isIncome => 'income',
        (bool) $chartType->isExpenses => 'expenses',
        (bool) $chartType->isBank => 'bank',
        default => null,
    };

    $selectedType = old('account_type', $currentType);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.edit') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('chart-of-account.chart-types.update', $chartType) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-bold" for="name">
                        {{ __('chart_types.table.column_name') }}
                    </label>

                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $chartType->name) }}"
                            required
                        />
                    </label>

                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold"> {{ __('chart_types.table.column_name') }} </label>

                    <div class="row g-3">
                        <div class="md:col-4">
                            <input
                                class="check"
                                id="isAsset"
                                name="account_type"
                                type="radio"
                                value="asset"
                                {{ $selectedType === 'asset' ? 'checked' : '' }}
                                required
                            />
                            <label for="isAsset">
                                {{ __('chart_types.flags.asset') }}
                            </label>
                        </div>

                        <div class="md:col-4">
                            <input
                                class="check"
                                id="isLiability"
                                name="account_type"
                                type="radio"
                                value="liability"
                                {{ $selectedType === 'liability' ? 'checked' : '' }}
                            />
                            <label for="isLiability">
                                {{ __('chart_types.flags.liability') }}
                            </label>
                        </div>

                        <div class="md:col-4">
                            <input
                                class="check"
                                id="isEquity"
                                name="account_type"
                                type="radio"
                                value="equity"
                                {{ $selectedType === 'equity' ? 'checked' : '' }}
                            />
                            <label for="isEquity">
                                {{ __('chart_types.flags.equity') }}
                            </label>
                        </div>

                        <div class="md:col-4">
                            <input
                                class="check"
                                id="isIncome"
                                name="account_type"
                                type="radio"
                                value="income"
                                {{ $selectedType === 'income' ? 'checked' : '' }}
                            />
                            <label for="isIncome">
                                {{ __('chart_types.flags.income') }}
                            </label>
                        </div>

                        <div class="md:col-4">
                            <input
                                class="check"
                                id="isExpenses"
                                name="account_type"
                                type="radio"
                                value="expenses"
                                {{ $selectedType === 'expenses' ? 'checked' : '' }}
                            />
                            <label for="isExpenses">
                                {{ __('chart_types.flags.expenses') }}
                            </label>
                        </div>
                    </div>

                    @error('account_type')
                        <div class="fg-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('chart-of-account.chart-types.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>

                    @if ($canUpdate)
                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>
                            {{ __('common.actions.save') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
