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

$canCreate = auth()->user()->hasFilePermission(FileResources::PAYMENTS, FileAction::ADD);

?>

@props(['companies', 'refundNumber'])

@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold m-0">{{ __('payments.workflow.supplier_refund.create_title') }}</h5>
            <span class="fg-secondary small">{{ $refundNumber }}</span>
        </div>

        <form autocomplete="off" action="{{ route('bank.supplier-refunds.store') }}" enctype="multipart/form-data" method="POST">
            @csrf
            <div class="card-body">
                @include('partials.alerts')
                @include('bank.supplier-refund._form', ['refund' => null, 'companies' => $companies])
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a class="btn btn-solid theme-secondary" href="{{ route('bank.supplier-refunds.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('ledgers.back') }}
                </a>
                @if ($canCreate)
                    <button class="btn btn-solid theme-primary" id="refund-submit" type="submit">
                        <i class="me-1" data-lucide="save"></i>
                        {{ __('payments.actions.save') }}
                    </button>
                @endif
            </div>
        </form>
    </div>
@endsection
