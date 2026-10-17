@php use App\Models\System\Setting; @endphp
@extends('layouts.app')
@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ $return->document_number }}</h4>
                <p class="fg-secondary small mb-0">
                    {{ $return->company_name }} | {{ __('purchase_returns.' . $return->status->value) }}
                </p>
            </div>
            <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('purchase.returns.index') }}"
                ><i class="me-1" data-lucide="arrow-left"></i>{{ __('common.actions.back') }}</a>
        </div>
        <div class="card-body">
            @include('partials.errors')
            <p>
                {{ __('purchase_returns.receipt') }}:
                <a href="{{ route('purchase.goods-receipts.show', $receipt->document_number) }}">{{ $receipt->document_number }}</a>
                |
                <a href="{{ route('purchase.invoices.show', $return->reference_number) }}">{{ $return->reference_number }}</a>
            </p>
            <table class="table-form table">
                <tbody>
                    <tr>
                        <th>{{ $return->product_name }}</th>
                        <td>{{ $return->quantity }}</td>
                        <td>{{ $return->date->format(Setting::getDefaultDate()) }}</td>
                    </tr>
                </tbody>
            </table>
            <p>{{ $return->notes }}</p>
            @if ($return->status === \App\Enums\ProductMovementStatus::PENDING)
                <div class="d-flex gap-2">
                    <form autocomplete="off" method="POST" action="{{ route('purchase.returns.approve', $return->document_number) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-solid theme-success" type="submit">
                            <i class="me-1" data-lucide="circle-check"></i>{{ __('common.actions.post') }}
                        </button>
                    </form>
                    <form autocomplete="off" method="POST" action="{{ route('purchase.returns.destroy', $return->document_number) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-solid theme-danger" type="submit">
                            <i class="me-1" data-lucide="trash-2"></i>{{ __('common.actions.delete') }}
                        </button>
                    </form>
                </div>
            @else
                <div class="alert alert theme-info">{{ __('purchase_returns.next') }}</div>
                <a class="btn btn-solid theme-primary" href="{{ route('purchase.adjustments.create') }}"
                    ><i class="me-1" data-lucide="sliders-horizontal"></i>{{ __('purchase_returns.adjustment') }}</a>
                <a class="btn btn-solid theme-primary" href="{{ route('bank.supplier-refunds.create') }}"
                    ><i class="me-1" data-lucide="hand-coins"></i>{{ __('purchase_returns.refund') }}</a>
            @endif
        </div>
    </div>
@endsection
