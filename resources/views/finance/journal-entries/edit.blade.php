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

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Models\Finance\JournalEntry;

$canApprove = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);
$canRemove = auth()->user()->hasFilePermission(FileResources::JOURNAL_ENTRIES, FileAction::REMOVE);

?>

@props([
    /** @var JournalEntry */
    'journalEntry',
    'charts',
    'chartTypes',
])

@extends('layouts.app')

@section('content')
    <div class="card md:col-11 mx-auto shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('journal_entries.edit_journal_title') }} - {{ $journalEntry->document_number }}
            </h4>
            <span class="badge {{ $journalEntry->status === DocumentStatus::POSTED ? 'theme-success' : 'theme-warning' }}">
                {{ $journalEntry->status === DocumentStatus::POSTED ? __('journal_entries.status_approved') : __('journal_entries.status_pending') }}
            </span>
        </div>

        <div class="card-body">
            @include('partials.errors')

            <form autocomplete="off" action="{{ route('finance.journal-entries.update', $journalEntry) }}" method="POST">
                @csrf
                @method('PUT')
                <input name="id" type="hidden" value="{{ $journalEntry->id }}" />
                @include('finance.journal-entries._form')
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <a class="btn btn-solid theme-secondary" href="{{ route('finance.journal-entries.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') ?? 'Back' }}
                    </a>

                    <div class="d-flex gap-2">
                        @if ($canRemove)
                            <button class="btn btn-sm btn-solid theme-danger btn-delete" data-id="{{ $journalEntry->id }}"
                                type="button" title="{{ __('common.actions.delete') }}">
                                <i data-lucide="trash-2"></i>
                            </button>
                        @endif

                        @if ($journalEntry->status !== DocumentStatus::POSTED && $canApprove)
                            <button class="btn btn-solid theme-success" type="button"
                                onclick="document.getElementById('approve-form').submit()">
                                <i class="me-1" data-lucide="check"></i>
                                {{ __('journal_entries.approve_button') }}
                            </button>
                        @endif

                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>
                            {{ __('common.actions.save') }}
                        </button>
                    </div>
                </div>
            </form>

            @if ($journalEntry->status !== DocumentStatus::POSTED)
                <form autocomplete="off" class="d-none" id="approve-form"
                    action="{{ route('finance.journal-entries.approve', $journalEntry) }}" method="POST">
                    @csrf
                    @method('PATCH')
                </form>
            @endif

            <form autocomplete="off" class="d-none" id="delete-form" action="{{ route('finance.journal-entries.destroy', $journalEntry) }}"
                method="POST">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    @include('finance.journal-entries._coa-picker')

    <script type="module">
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete');
            if (deleteBtn) {
                const dataId = deleteBtn.getAttribute('data-id');
                confirmDialog({
                    title: "{{ __('common.alerts.confirm_delete') }}",
                    text: "{{ __('common.alerts.confirm_delete_message') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "{{ __('common.alerts.confirm_delete_yes') }}",
                    confirmTone: 'danger',
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + dataId).submit();
                    }
                });
            }
        });
    </script>
@endsection
