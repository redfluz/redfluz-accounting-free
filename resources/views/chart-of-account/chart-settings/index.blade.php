@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::CHART_SETTINGS, FileAction::UPDATE);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('chart_settings.title') }}

                <p class="fg-secondary small mb-0">{{ __('chart_settings.subtitle') }}</p>
            </h4>
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>

                            <th>Income</th>

                            <th>Bank</th>

                            <th>{{ __('chart_settings.table.column_cash_chart_uuid') }}</th>

                            <th>{{ __('chart_settings.table.column_cash_in_transit_chart_uuid') }}</th>

                            <th>{{ __('chart_settings.table.column_e_wallet_chart_uuid') }}</th>

                            <th>Client</th>

                            <th>Vendor</th>

                            <th>{{ __('chart_settings.table.column_input_tax_chart_uuid') }}</th>

                            <th>{{ __('chart_settings.table.column_output_tax_chart_uuid') }}</th>

                            @if ($canUpdate)
                                <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($chartSettings as $chartSetting)
                            <tr>

                                <td>
                                    @if ($chartSetting->incomeChart)
                                        <span class="fw-semibold"> {{ $chartSetting->incomeChart->code }} </span>
                                        -
                                        {{ $chartSetting->incomeChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($chartSetting->bankChart)
                                        <span class="fw-semibold"> {{ $chartSetting->bankChart->code }} </span>
                                        -
                                        {{ $chartSetting->bankChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($chartSetting->cashChart)
                                        <span class="fw-semibold"> {{ $chartSetting->cashChart->code }} </span>
                                        -
                                        {{ $chartSetting->cashChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($chartSetting->cashInTransitChart)
                                        <span class="fw-semibold"> {{ $chartSetting->cashInTransitChart->code }} </span>
                                        -
                                        {{ $chartSetting->cashInTransitChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($chartSetting->eWalletChart)
                                        <span class="fw-semibold"> {{ $chartSetting->eWalletChart->code }} </span>
                                        -
                                        {{ $chartSetting->eWalletChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($chartSetting->clientChart)
                                        <span class="fw-semibold"> {{ $chartSetting->clientChart->code }} </span>
                                        -
                                        {{ $chartSetting->clientChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($chartSetting->vendorChart)
                                        <span class="fw-semibold"> {{ $chartSetting->vendorChart->code }} </span>
                                        -
                                        {{ $chartSetting->vendorChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($chartSetting->inputTaxChart)
                                        <span class="fw-semibold">{{ $chartSetting->inputTaxChart->code }}</span>
                                        - {{ $chartSetting->inputTaxChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($chartSetting->outputTaxChart)
                                        <span class="fw-semibold">{{ $chartSetting->outputTaxChart->code }}</span>
                                        - {{ $chartSetting->outputTaxChart->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                @if ($canUpdate)
                                    <td class="text-center">
                                        <a
                                            class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('chart-of-account.chart-settings.edit', $chartSetting) }}"
                                            title="{{ __('common.actions.edit') }}"
                                        >
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    </td>
                                @endif
                            </tr>

                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="{{ $canUpdate ? 10 : 9 }}">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">{{ $chartSettings->links() }}</div>
        </div>
    </div>
@endsection
