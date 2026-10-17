@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::PURCHASE_ORDER_SETTINGS, FileAction::UPDATE);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold mb-0">{{ __('purchase_order_settings.title') }}</h4>
            <p class="fg-secondary small mb-0">{{ __('purchase_order_settings.subtitle') }}</p>
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <form autocomplete="off" action="{{ route('purchase.purchase-order-settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <ul class="nav nav-tabs mb-4" id="purchaseOrderSettingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link active"
                            data-bs-toggle="tab"
                            data-bs-target="#general-pane"
                            type="button"
                            role="tab"
                        >
                            {{ __('purchase_order_settings.tabs.general') }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="general-pane" role="tabpanel">
                        <div class="row mb-4">
                            <div class="md:col-4">
                                <label
                                    class="form-label fw-bold"
                                    for="default_credit_term_days"
                                >{{ __('purchase_order_settings.default_credit_term_days') }}</label>
                                <div class="input-group">
                                    <input
                                        class="form-control"
                                        id="default_credit_term_days"
                                        name="default_credit_term_days"
                                        type="number"
                                        value="{{ old('default_credit_term_days', $setting->default_credit_term_days) }}"
                                        min="0"
                                        max="365"
                                        required
                                        @disabled(! $canUpdate)
                                    /><span class="input-group-text">{{ __('purchase_order_settings.days') }}</span>
                                </div>
                            </div>
                            <div class="md:col-4">
                                <label class="form-label fw-bold" for="default_required_days">
                                    {{ __('purchase_order_settings.default_required_days') }}
                                </label>
                                <div class="input-group">
                                    <input
                                        class="form-control"
                                        id="default_required_days"
                                        name="default_required_days"
                                        type="number"
                                        value="{{ old('default_required_days', $setting->default_required_days) }}"
                                        min="0"
                                        max="365"
                                        required
                                        @disabled(! $canUpdate)
                                    /><span class="input-group-text">{{ __('purchase_order_settings.days') }}</span>
                                </div>
                                <div class="form-text">
                                    {{ __('purchase_order_settings.default_required_days_help') }}
                                </div>
                            </div>
                            <div class="md:col-4">
                                <label class="form-label fw-bold" for="default_rfq_response_days">
                                    {{ __('purchase_order_settings.default_rfq_response_days') }}
                                </label>
                                <div class="input-group">
                                    <input
                                        class="form-control"
                                        id="default_rfq_response_days"
                                        name="default_rfq_response_days"
                                        type="number"
                                        value="{{ old('default_rfq_response_days', $setting->default_rfq_response_days) }}"
                                        min="1"
                                        max="365"
                                        required
                                        @disabled(! $canUpdate)
                                    /><span class="input-group-text">{{ __('purchase_order_settings.days') }}</span>
                                </div>
                                <div class="form-text">
                                    {{ __('purchase_order_settings.default_rfq_response_days_help') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($canUpdate)
                    <div class="mt-4 text-end">
                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>{{ __('common.actions.save') }}
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection

