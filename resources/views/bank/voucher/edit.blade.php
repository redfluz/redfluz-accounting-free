<?php

use App\Enums\FileAction;
use App\Enums\FileResources;

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

$canUpdate = auth()->user()->hasFilePermission(FileResources::PAYMENTS, FileAction::UPDATE);
$canDelete = auth()->user()->hasFilePermission(FileResources::PAYMENTS, FileAction::REMOVE);

?>

@props(['voucher', 'documentNumber', 'companies'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold m-0">{{ __('payments.workflow.payment_voucher.edit_title') }}</h5>
            <span class="fg-secondary small">{{ $documentNumber }}</span>
        </div>

        <form autocomplete="off" action="{{ route('bank.payment-vouchers.update', $documentNumber) }}" enctype="multipart/form-data" method="POST">
            @csrf
            @method('PUT')
            <input name="id" type="hidden" value="{{ $voucher->id }}" />
            <div class="card-body">
                @include('partials.alerts')
                @include('bank.voucher._form', ['voucher' => $voucher, 'companies' => $companies])
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a class="btn btn-solid theme-secondary" href="{{ route('bank.payment-vouchers.show', $documentNumber) }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('ledgers.back') }}
                </a>
                <div class="d-flex gap-2">
                    @if ($canDelete)
                        <form autocomplete="off" action="{{ route('bank.payment-vouchers.destroy', $documentNumber) }}" method="POST"
                            onsubmit="return confirm('{{ __('common.messages.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-solid theme-danger" type="submit">
                                <i class="me-1" data-lucide="trash-2"></i>
                                {{ __('common.actions.delete') }}
                            </button>
                        </form>
                    @endif
                    @if ($canUpdate)
                        <button class="btn btn-solid theme-primary" type="submit">
                            <i class="me-1" data-lucide="save"></i>
                            {{ __('common.actions.save') }}
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
@endsection
