<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */
?>

@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::CHART_SETTINGS, FileAction::UPDATE);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card md:col-10 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.edit') }} {{ __('chart_settings.title') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('chart-of-account.chart-settings.update', $chartSetting) }}" method="POST">
                @csrf
                @method('PUT')

                <ul class="nav nav-tabs mb-4" id="chartSettingTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="chart-account-tab" data-bs-toggle="tab"
                            data-bs-target="#chart-account" type="button" role="tab" aria-controls="chart-account"
                            aria-selected="true">
                            {{ __('chart_settings.default_chart_accounts') }}
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="finance-period-tab" data-bs-toggle="tab"
                            data-bs-target="#finance-period" type="button" role="tab" aria-controls="finance-period"
                            aria-selected="false">
                            {{ __('chart_settings.finance_period') }}
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="banks-setup-tab" data-bs-toggle="tab"
                            data-bs-target="#banks-setup" type="button" role="tab" aria-controls="banks-setup"
                            aria-selected="false">
                            {{ __('chart_settings.banks_setup') }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="chartSettingTabsContent">
                    {{-- ====================================================== --}}
                    {{-- Default Chart of Accounts --}}
                    {{-- ====================================================== --}}

                    <div class="tab-pane fade show active" id="chart-account" role="tabpanel"
                        aria-labelledby="chart-account-tab" tabindex="0">
                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="cogs_chart_uuid">
                                    {{ __('chart_settings.table.column_cogs_chart_uuid') }}
                                </label>

                                <select class="form-control @error('cogs_chart_uuid') is-invalid @enderror"
                                    id="cogs_chart_uuid" name="cogs_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($cogsCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('cogs_chart_uuid', $chartSetting->cogs_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('cogs_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="inventory_chart_uuid">
                                    {{ __('chart_settings.table.column_inventory_chart_uuid') }}
                                </label>

                                <select class="form-control @error('inventory_chart_uuid') is-invalid @enderror"
                                    id="inventory_chart_uuid" name="inventory_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($inventoryCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('inventory_chart_uuid', $chartSetting->inventory_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('inventory_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="stock_shortage_chart_uuid">
                                    {{ __('chart_settings.table.column_stock_shortage_chart_uuid') }}
                                </label>

                                <select class="form-control @error('stock_shortage_chart_uuid') is-invalid @enderror"
                                    id="stock_shortage_chart_uuid" name="stock_shortage_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($stockShortageCharts as $chart)
                                        <option value="{{ $chart->id }}" @selected(old('stock_shortage_chart_uuid', $chartSetting->stock_shortage_chart_uuid) === $chart->id)>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('stock_shortage_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="stock_surplus_chart_uuid">
                                    {{ __('chart_settings.table.column_stock_surplus_chart_uuid') }}
                                </label>

                                <select class="form-control @error('stock_surplus_chart_uuid') is-invalid @enderror"
                                    id="stock_surplus_chart_uuid" name="stock_surplus_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($stockSurplusCharts as $chart)
                                        <option value="{{ $chart->id }}" @selected(old('stock_surplus_chart_uuid', $chartSetting->stock_surplus_chart_uuid) === $chart->id)>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('stock_surplus_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="input_tax_chart_uuid">
                                    {{ __('chart_settings.table.column_input_tax_chart_uuid') }}
                                </label>

                                <select class="form-control @error('input_tax_chart_uuid') is-invalid @enderror"
                                    id="input_tax_chart_uuid" name="input_tax_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($inputTaxCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('input_tax_chart_uuid', $chartSetting->input_tax_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('input_tax_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="output_tax_chart_uuid">
                                    {{ __('chart_settings.table.column_output_tax_chart_uuid') }}
                                </label>

                                <select class="form-control @error('output_tax_chart_uuid') is-invalid @enderror"
                                    id="output_tax_chart_uuid" name="output_tax_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($outputTaxCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('output_tax_chart_uuid', $chartSetting->output_tax_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('output_tax_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="income_chart_uuid">
                                    {{ __('chart_settings.table.column_income_chart_uuid') }}
                                </label>

                                <select class="form-control @error('income_chart_uuid') is-invalid @enderror"
                                    id="income_chart_uuid" name="income_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($incomeCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('income_chart_uuid', $chartSetting->income_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('income_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="client_chart_uuid">
                                    {{ __('chart_settings.table.column_client_chart_uuid') }}
                                </label>

                                <select class="form-control @error('client_chart_uuid') is-invalid @enderror"
                                    id="client_chart_uuid" name="client_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($clientCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('client_chart_uuid', $chartSetting->client_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('client_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="vendor_chart_uuid">
                                    {{ __('chart_settings.table.column_vendor_chart_uuid') }}
                                </label>

                                <select class="form-control @error('vendor_chart_uuid') is-invalid @enderror"
                                    id="vendor_chart_uuid" name="vendor_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($vendorCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('vendor_chart_uuid', $chartSetting->vendor_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('vendor_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="retained_earnings_chart_uuid">
                                    {{ __('chart_settings.table.column_retained_earnings_chart_uuid') }}
                                </label>

                                <select class="form-control @error('retained_earnings_chart_uuid') is-invalid @enderror"
                                    id="retained_earnings_chart_uuid" name="retained_earnings_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($retainedEarningsCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('retained_earnings_chart_uuid', $chartSetting->retained_earnings_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('retained_earnings_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="disposal_gain_chart_uuid">
                                    {{ __('chart_settings.table.column_disposal_gain_chart_uuid') }}
                                </label>

                                <select class="form-control @error('disposal_gain_chart_uuid') is-invalid @enderror"
                                    id="disposal_gain_chart_uuid" name="disposal_gain_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($disposalGainCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('disposal_gain_chart_uuid', $chartSetting->disposal_gain_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('disposal_gain_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="disposal_loss_chart_uuid">
                                    {{ __('chart_settings.table.column_disposal_loss_chart_uuid') }}
                                </label>

                                <select class="form-control @error('disposal_loss_chart_uuid') is-invalid @enderror"
                                    id="disposal_loss_chart_uuid" name="disposal_loss_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($disposalLossCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('disposal_loss_chart_uuid', $chartSetting->disposal_loss_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('disposal_loss_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" for="retained_earnings_formula">
                                {{ __('chart_settings.form.retained_earnings_formula') }}
                            </label>

                            <textarea class="form-control font-monospace @error('retained_earnings_formula') is-invalid @enderror"
                                id="retained_earnings_formula" name="retained_earnings_formula" rows="4" required>{{ old('retained_earnings_formula', $chartSetting->retained_earnings_formula) }}</textarea>
                            <div class="form-text">{{ __('chart_settings.form.retained_earnings_formula_help') }}</div>
                            <div class="form-text font-monospace">
                                {{ __('chart_settings.form.retained_earnings_formula_example') }}
                            </div>

                            @error('retained_earnings_formula')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- ====================================================== --}}
                    {{-- Finance Period --}}
                    {{-- ====================================================== --}}

                    <div class="tab-pane fade" id="finance-period" role="tabpanel" aria-labelledby="finance-period-tab"
                        tabindex="0">
                        <div class="row">
                            <div class="md:col-4 mb-3">
                                <label class="form-label fw-bold" for="start_finance_period_month">
                                    {{ __('chart_settings.table.column_start_finance_period_month') }}
                                </label>

                                <select class="form-control @error('start_finance_period_month') is-invalid @enderror"
                                    id="start_finance_period_month" name="start_finance_period_month" required>
                                    @for ($month = 1; $month <= 12; $month++)
                                        <option value="{{ $month }}"
                                            {{ (int) old('start_finance_period_month', $chartSetting->start_finance_period_month) === $month ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($month)->format('F') }}
                                        </option>
                                    @endfor
                                </select>

                                @error('start_finance_period_month')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-4 mb-3">
                                <label class="form-label fw-bold" for="closing_date">
                                    {{ __('chart_settings.table.column_closing_date') }}
                                </label>

                                <label class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true">
                                        <i data-lucide="calendar"></i>
                                    </div>
                                    <input class="form-ghost @error('closing_date') is-invalid @enderror" id="closing_date"
                                        name="closing_date" type="text" data-bs-toggle="datepicker" autocomplete="off"
                                        value="{{ old('closing_date', $chartSetting->closing_date->format('Y-m-d')) }}" />
                                </label>

                                @error('closing_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-4 mb-3">
                                <label class="form-label fw-bold d-block">
                                    {{ __('chart_settings.table.column_strict_journal_posting') }}
                                </label>

                                <div class="mt-2">
                                    <input name="strict_journal_posting" type="hidden" value="0" />

                                    <div class="switch">
                                        <input id="strict_journal_posting" name="strict_journal_posting" type="checkbox"
                                            value="1"
                                            {{ old('strict_journal_posting', $chartSetting->strict_journal_posting) ? 'checked' : '' }}
                                            role="switch" switch />
                                    </div>

                                    <label for="strict_journal_posting">
                                        {{ __('chart_settings.enable_strict_journal_posting') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ====================================================== --}}
                    {{-- Banks Setup --}}
                    {{-- ====================================================== --}}

                    <div class="tab-pane fade" id="banks-setup" role="tabpanel" aria-labelledby="banks-setup-tab"
                        tabindex="0">
                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="bank_chart_uuid">
                                    {{ __('chart_settings.table.column_bank_chart_uuid') }}
                                </label>

                                <select class="form-control @error('bank_chart_uuid') is-invalid @enderror"
                                    id="bank_chart_uuid" name="bank_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($bankCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('bank_chart_uuid', $chartSetting->bank_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('bank_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="deposit_chart_uuid">
                                    {{ __('chart_settings.table.column_deposit_chart_uuid') }}
                                </label>

                                <select class="form-control @error('deposit_chart_uuid') is-invalid @enderror"
                                    id="deposit_chart_uuid" name="deposit_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($depositCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('deposit_chart_uuid', $chartSetting->deposit_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('deposit_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="cash_chart_uuid">
                                    {{ __('chart_settings.table.column_cash_chart_uuid') }}
                                </label>

                                <select class="form-control @error('cash_chart_uuid') is-invalid @enderror"
                                    id="cash_chart_uuid" name="cash_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($cashCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('cash_chart_uuid', $chartSetting->cash_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('cash_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="e_wallet_chart_uuid">
                                    {{ __('chart_settings.table.column_e_wallet_chart_uuid') }}
                                </label>

                                <select class="form-control @error('e_wallet_chart_uuid') is-invalid @enderror"
                                    id="e_wallet_chart_uuid" name="e_wallet_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($eWalletCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('e_wallet_chart_uuid', $chartSetting->e_wallet_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('e_wallet_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="md:col-6 mb-3">
                                <label class="form-label fw-bold" for="cash_in_transit_chart_uuid">
                                    {{ __('chart_settings.table.column_cash_in_transit_chart_uuid') }}
                                </label>

                                <select class="form-control @error('cash_in_transit_chart_uuid') is-invalid @enderror"
                                    id="cash_in_transit_chart_uuid" name="cash_in_transit_chart_uuid">
                                    <option value="">{{ __('common.actions.select_option') }}</option>

                                    @foreach ($cashInTransitCharts as $chart)
                                        <option value="{{ $chart->id }}"
                                            {{ old('cash_in_transit_chart_uuid', $chartSetting->cash_in_transit_chart_uuid) === $chart->id ? 'selected' : '' }}>
                                            {{ $chart->code ? $chart->code . ' - ' : '' }}{{ $chart->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <div class="form-text">{{ __('chart_settings.form.cash_in_transit_help') }}</div>

                                @error('cash_in_transit_chart_uuid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between border-top mt-3 pt-3">
                    <a class="btn btn-solid theme-secondary"
                        href="{{ route('chart-of-account.chart-settings.index') }}">
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
