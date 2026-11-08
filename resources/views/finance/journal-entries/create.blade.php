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

@props(['charts'])

@extends('layouts.app')

@section('content')
    <div class="card md:col-11 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('journal_entries.create_journal_title') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('finance.journal-entries.store') }}" method="POST" novalidate>
                @csrf
                @include('finance.journal-entries._form', ['journalEntry' => null])
                <div class="d-flex justify-content-between border-top pt-3">
                    <a class="btn btn-solid theme-secondary" href="{{ route('finance.journal-entries.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') ?? 'Back' }}
                    </a>
                    <button class="btn btn-solid theme-accent" type="submit">
                        <i class="me-1" data-lucide="save"></i>
                        {{ __('common.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
