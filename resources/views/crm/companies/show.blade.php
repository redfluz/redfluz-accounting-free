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
use App\Models\Crm\CompanyFile;
use Carbon\Carbon;
use App\Models\System\Setting;

$canAddAddress = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::ADD);
$canUpdateAddress = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::UPDATE);
$canRemoveAddress = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::REMOVE);
$canAddContact = auth()->user()->hasFilePermission(FileResources::CONTACTS, FileAction::ADD);
$canUpdateContact = auth()->user()->hasFilePermission(FileResources::CONTACTS, FileAction::UPDATE);
$canRemoveContact = auth()->user()->hasFilePermission(FileResources::CONTACTS, FileAction::REMOVE);

?>
@props([
    /** @var CompanyFile */
    'companyFile',
])
@extends('layouts.app')

@section('content')
    <div class="card mb-4 shadow-sm">
        <div class="card-body py-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="fg-primary me-2" data-lucide="building-2"></i>
                        {{ $company->name }}
                    </h3>
                    <p class="fg-secondary mb-2">
                        <i class="me-1" data-lucide="mail"></i>
                        {{ $company->email ?? '-' }} |
                        <i class="me-1 ms-2" data-lucide="phone"></i>
                        {{ $company->phone ?? '-' }}
                    </p>
                    <p class="fg-secondary small mb-0">
                        <i class="me-1" data-lucide="map-pin"></i>
                        {{ $company->address ?? '-' }}
                    </p>
                </div>
                <a class="btn btn-solid theme-secondary" href="{{ route('crm.companies.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Sub-Navigation Tabs --}}
    <ul class="nav nav-tabs mb-3" id="companyTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-pane"
                type="button">
                <i class="me-1" data-lucide="calendar"></i>
                {{ __('companies.show.tabs.calendar') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts-pane" type="button">
                <i class="me-1" data-lucide="users"></i>
                {{ __('companies.show.tabs.contacts') }} ({{ $company->contacts->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses-pane"
                type="button">
                <i class="me-1" data-lucide="map-pin-house"></i>
                {{ __('companies.show.tabs.addresses') }} ({{ $company->addresses->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="ar-tab" data-bs-toggle="tab" data-bs-target="#ar-pane" type="button">
                <i class="me-1" data-lucide="receipt"></i>
                {{ __('companies.tabs.ar_statement') }} ({{ $arLedgers->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="ap-tab" data-bs-toggle="tab" data-bs-target="#ap-pane" type="button">
                <i class="me-1" data-lucide="file-output"></i>
                {{ __('companies.tabs.ap_statement') }} ({{ $apLedgers->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files-pane" type="button">
                <i class="me-1" data-lucide="files"></i>
                {{ __('companies.show.tabs.files') }} ({{ $company->files->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content card border-0 p-3 shadow-sm" id="companyTabContent">
        {{-- TAB 1: Native HTML Calendar View --}}
        <div class="tab-pane fade show active" id="calendar-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a class="btn btn-solid theme-secondary btn-sm"
                    href="?month={{ $calendarDate->copy()->subMonth()->format('Y-m') }}">
                    <i class="me-1" data-lucide="chevron-left"></i>
                    {{ __('companies.show.calendar.previous') }}
                </a>
                <h4 class="fw-bold mb-0">{{ $calendarDate->translatedFormat('F Y') }}</h4>
                <a class="btn btn-solid theme-secondary btn-sm"
                    href="?month={{ $calendarDate->copy()->addMonth()->format('Y-m') }}">
                    {{ __('companies.show.calendar.next') }}
                    <i class="ms-1" data-lucide="chevron-right"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table-bordered table" style="table-layout: fixed; min-width: 800px">
                    <thead class="bg-1 text-center">
                        <tr>
                            <th scope="col" style="width: 14.28%">{{ __('companies.show.calendar.sunday') }}</th>
                            <th scope="col" style="width: 14.28%">{{ __('companies.show.calendar.monday') }}</th>
                            <th scope="col" style="width: 14.28%">{{ __('companies.show.calendar.tuesday') }}</th>
                            <th scope="col" style="width: 14.28%">{{ __('companies.show.calendar.wednesday') }}</th>
                            <th scope="col" style="width: 14.28%">{{ __('companies.show.calendar.thursday') }}</th>
                            <th scope="col" style="width: 14.28%">{{ __('companies.show.calendar.friday') }}</th>
                            <th scope="col" style="width: 14.28%">{{ __('companies.show.calendar.saturday') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            {{-- Print empty cells for days before the 1st of the month --}}
                            @for ($i = 0; $i < $startDayOfWeek; $i++)
                                <td class="bg-2"></td>
                            @endfor

                            {{-- Loop through actual days of the month --}}
                            @for ($day = 1; $day <= $daysInMonth; $day++)
                                @php
                                    $currentDateStr = $calendarDate
                                        ->copy()
                                        ->addDays($day - 1)
                                        ->format('Y-m-d');
                                    $dayEvents = $groupedEvents->get($currentDateStr, []);
                                    $isToday = $currentDateStr === Carbon::now()->format('Y-m-d');
                                @endphp

                                <td class="{{ $isToday ? 'bg-primary bg-10' : '' }} align-top" style="height: 120px">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold {{ $isToday ? 'fg-primary' : 'fg-secondary' }}">
                                            {{ $day }}
                                        </span>
                                    </div>

                                    {{-- Render Events for this Day --}}
                                    <div class="d-flex flex-column gap-1">
                                        @foreach ($dayEvents as $event)
                                            <!-- ADDED: cursor-pointer, calendar-event-badge class, and data attributes for popup -->
                                            <div class="badge theme-{{ $event['color'] }} calendar-event-badge text-wrap px-2 py-1 text-start"
                                                data-type="{{ __('companies.show.event_types.' . $event['type']) }}"
                                                data-title="{{ $event['title'] }}"
                                                data-date="{{ Carbon::parse($event['date'])->translatedFormat('d M Y') }}"
                                                data-color="{{ $event['color'] }}" style="cursor: pointer">
                                                <i class="me-1" data-lucide="{{ $event['icon'] }}"
                                                    style="width: 12px; height: 12px"></i>
                                                {{ Str::limit($event['title'], 25) }}
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- Break to a new row if it's Saturday (end of week) --}}
                                @if (($day + $startDayOfWeek) % 7 == 0)
                        </tr>
                        <tr>
                            @endif
                            @endfor

                            {{-- Fill in remaining empty cells at the end of the month --}}
                            @while (($day + $startDayOfWeek - 1) % 7 != 0)
                                <td class="bg-2"></td>
                                @php $day++; @endphp
                            @endwhile
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 2: Contacts --}}
        <div class="tab-pane fade" id="contacts-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">{{ __('companies.show.tabs.contacts') }}</h5>
                @if ($canAddContact)
                    <a class="btn btn-solid theme-success btn-sm"
                        href="{{ route('crm.contacts.create', ['company' => $company->id]) }}">
                        <i class="me-1" data-lucide="user-plus"></i>
                        {{ __('common.actions.add') }} {{ __('companies.show.tabs.contacts') }}
                    </a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col">{{ __('contacts.table.column_name') }}</th>
                            <th scope="col">{{ __('contacts.table.column_email') }}</th>
                            <th scope="col">{{ __('contacts.table.column_phone') }}</th>
                            <th scope="col">{{ __('contacts.table.column_position') }}</th>
                            <th scope="col" class="text-end">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($company->contacts as $contact)
                            <tr>
                                <td class="fw-bold">{{ $contact->name }}</td>
                                <td>{{ $contact->email ?? '-' }}</td>
                                <td>{{ $contact->phone ?? '-' }}</td>
                                <td>{{ $contact->position ?? '-' }}</td>
                                <td class="text-nowrap text-end">
                                    @if ($canUpdateContact)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('crm.contacts.edit', $contact) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemoveContact)
                                        <form autocomplete="off" class="d-inline"
                                            action="{{ route('crm.contacts.destroy', $contact) }}" method="POST"
                                            data-confirm-message="{{ __('common.alerts.confirm_delete_message') }}"
                                            data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-solid theme-danger" type="submit"
                                                title="{{ __('common.actions.delete') }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-3 text-center" colspan="5">
                                    {{ __('companies.show.messages.no_contacts') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="addresses-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">{{ __('companies.addresses.title') }}</h5>
                @if ($canAddAddress)
                    <a class="btn btn-solid theme-success btn-sm"
                        href="{{ route('crm.company-addresses.create', $company) }}">
                        <i class="me-1" data-lucide="plus"></i>
                        {{ __('companies.addresses.actions.add') }}
                    </a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col">{{ __('companies.addresses.fields.name') }}</th>
                            <th scope="col">{{ __('companies.addresses.fields.phone') }}</th>
                            <th scope="col">{{ __('companies.addresses.fields.address') }}</th>
                            <th scope="col">{{ __('companies.addresses.fields.default') }}</th>
                            <th scope="col" class="text-end">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($company->addresses as $address)
                            <tr>
                                <td class="fw-bold">{{ $address->name }}</td>
                                <td>{{ $address->phone ?? '-' }}</td>
                                <td>{!! nl2br(e($address->address)) !!}</td>
                                <td>
                                    @if ($address->is_default)
                                        <span
                                            class="badge theme-success">{{ __('companies.addresses.fields.default') }}</span>
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($canUpdateAddress)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('crm.company-addresses.edit', $address) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemoveAddress)
                                        <form autocomplete="off" class="d-inline"
                                            action="{{ route('crm.company-addresses.destroy', $address) }}"
                                            method="POST"
                                            data-confirm-message="{{ __('companies.addresses.messages.delete_confirm') }}"
                                            data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-solid theme-danger" type="submit"
                                                title="{{ __('common.actions.delete') }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-3 text-center" colspan="5">
                                    {{ __('companies.addresses.messages.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 3: Accounts Receivable Statement --}}
        <div class="tab-pane fade" id="ar-pane" role="tabpanel" aria-labelledby="ar-tab">
            <div class="accordion mb-3 accordion-gap">
                <details class="accordion-item" @if (filled($arFilters['search'] ?? null) || filled($arFilters['start_date'] ?? null) || filled($arFilters['end_date'] ?? null) || filled($arFilters['type'] ?? null)) open @endif>
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="list-filter"></i>
                        <span class="fw-bold">{{ __('common.actions.filter') }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                        <form autocomplete="off" class="row g-2" method="GET" action="{{ route('crm.companies.show', $company) }}">
                            <input type="hidden" name="tab" value="ar" />
                            @if (request('month'))
                                <input type="hidden" name="month" value="{{ request('month') }}" />
                            @endif
                            @foreach (['ap_search', 'ap_start_date', 'ap_end_date', 'ap_type'] as $keep)
                                @if (filled(request($keep)))
                                    <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}" />
                                @endif
                            @endforeach
                            <div class="md:col-3">
                                <input class="form-control" name="ar_search" type="text" value="{{ $arFilters['search'] ?? '' }}" placeholder="{{ __('common.actions.search') }}" aria-label="{{ __('common.actions.search') }}" />
                            </div>
                            <div class="md:col-2">
                                <select class="form-control" name="ar_type" aria-label="{{ __('ledgers.all_types') }}">
                                    <option value="">{{ __('ledgers.all_types') }}</option>
                                    @foreach (\App\Enums\Finance\LedgerType::cases() as $ledgerType)
                                        <option value="{{ $ledgerType->value }}" @selected(($arFilters['type'] ?? '') === $ledgerType->value)>{{ __('ledgers.type.' . $ledgerType->value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-2">
                                <div class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true"><i data-lucide="calendar"></i></div>
                                    <input class="form-ghost" name="ar_start_date" type="text" data-bs-toggle="datepicker" autocomplete="off" value="{{ $arFilters['start_date'] ?? '' }}" placeholder="{{ __('financial_reports.form.start_date') }}" />
                                </div>
                            </div>
                            <div class="md:col-2">
                                <div class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true"><i data-lucide="calendar"></i></div>
                                    <input class="form-ghost" name="ar_end_date" type="text" data-bs-toggle="datepicker" autocomplete="off" value="{{ $arFilters['end_date'] ?? '' }}" placeholder="{{ __('financial_reports.form.end_date') }}" />
                                </div>
                            </div>
                            <div class="md:col-3 d-flex gap-1">
                                <button class="btn btn-solid theme-secondary flex-grow-1" type="submit"><i class="me-1" data-lucide="search"></i> {{ __('common.actions.filter') }}</button>
                                @if (filled($arFilters['search'] ?? null) || filled($arFilters['start_date'] ?? null) || filled($arFilters['end_date'] ?? null) || filled($arFilters['type'] ?? null))
                                    <a class="btn btn-solid theme-secondary" href="{{ route('crm.companies.show', array_filter(array_merge(['company' => $company, 'tab' => 'ar', 'month' => request('month')], collect(['ap_search','ap_start_date','ap_end_date','ap_type'])->mapWithKeys(fn($k)=>[$k=>request($k)])->all()))) }}" title="{{ __('common.actions.reset') }}"><i data-lucide="x"></i></a>
                                @endif
                            </div>
                        </form>
                    </div>
                </details>
            </div>
            <div class="accordion mb-3 accordion-gap">
                <details class="accordion-item">
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="bar-chart-3"></i>
                        <span class="fw-bold">Summary</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body py-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex flex-wrap align-items-center gap-4 gap-lg-5">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-uppercase small fg-secondary">IN</span>
                                    <span class="fs-5 fw-semibold fg-success">{{ number_format((float) ($arTotals['debit'] ?? 0), 2) }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-uppercase small fg-secondary">OUT</span>
                                    <span class="fs-5 fw-semibold fg-danger">{{ number_format((float) ($arTotals['credit'] ?? 0), 2) }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-uppercase small fg-secondary">BALANCE</span>
                                    <span class="fs-5 fw-semibold">{{ number_format((float) ($arTotals['balance'] ?? 0), 2) }}</span>
                                </div>
                            </div>
                            <div id="ar-summary-spark" style="width: 140px; height: 120px;"></div>
                        </div>
                    </div>
                </details>
            </div>
            @include('crm.companies._statement-table', [
                'rows' => $arLedgers,
                'emptyMessage' => __('companies.messages.no_ar_transactions'),
                'sourceUrls' => $sourceUrls,
            ])
        </div>

        {{-- TAB 4: Accounts Payable Statement --}}
        <div class="tab-pane fade" id="ap-pane" role="tabpanel" aria-labelledby="ap-tab">
            <div class="accordion mb-3 accordion-gap">
                <details class="accordion-item" @if (filled($apFilters['search'] ?? null) || filled($apFilters['start_date'] ?? null) || filled($apFilters['end_date'] ?? null) || filled($apFilters['type'] ?? null)) open @endif>
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="list-filter"></i>
                        <span class="fw-bold">{{ __('common.actions.filter') }}</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body">
                        <form autocomplete="off" class="row g-2" method="GET" action="{{ route('crm.companies.show', $company) }}">
                            <input type="hidden" name="tab" value="ap" />
                            @if (request('month'))
                                <input type="hidden" name="month" value="{{ request('month') }}" />
                            @endif
                            @foreach (['ar_search', 'ar_start_date', 'ar_end_date', 'ar_type'] as $keep)
                                @if (filled(request($keep)))
                                    <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}" />
                                @endif
                            @endforeach
                            <div class="md:col-3">
                                <input class="form-control" name="ap_search" type="text" value="{{ $apFilters['search'] ?? '' }}" placeholder="{{ __('common.actions.search') }}" aria-label="{{ __('common.actions.search') }}" />
                            </div>
                            <div class="md:col-2">
                                <select class="form-control" name="ap_type" aria-label="{{ __('ledgers.all_types') }}">
                                    <option value="">{{ __('ledgers.all_types') }}</option>
                                    @foreach (\App\Enums\Finance\LedgerType::cases() as $ledgerType)
                                        <option value="{{ $ledgerType->value }}" @selected(($apFilters['type'] ?? '') === $ledgerType->value)>{{ __('ledgers.type.' . $ledgerType->value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-2">
                                <div class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true"><i data-lucide="calendar"></i></div>
                                    <input class="form-ghost" name="ap_start_date" type="text" data-bs-toggle="datepicker" autocomplete="off" value="{{ $apFilters['start_date'] ?? '' }}" placeholder="{{ __('financial_reports.form.start_date') }}" />
                                </div>
                            </div>
                            <div class="md:col-2">
                                <div class="form-control form-adorn">
                                    <div class="form-adorn-icon" aria-hidden="true"><i data-lucide="calendar"></i></div>
                                    <input class="form-ghost" name="ap_end_date" type="text" data-bs-toggle="datepicker" autocomplete="off" value="{{ $apFilters['end_date'] ?? '' }}" placeholder="{{ __('financial_reports.form.end_date') }}" />
                                </div>
                            </div>
                            <div class="md:col-3 d-flex gap-1">
                                <button class="btn btn-solid theme-secondary flex-grow-1" type="submit"><i class="me-1" data-lucide="search"></i> {{ __('common.actions.filter') }}</button>
                                @if (filled($apFilters['search'] ?? null) || filled($apFilters['start_date'] ?? null) || filled($apFilters['end_date'] ?? null) || filled($apFilters['type'] ?? null))
                                    <a class="btn btn-solid theme-secondary" href="{{ route('crm.companies.show', array_filter(array_merge(['company' => $company, 'tab' => 'ap', 'month' => request('month')], collect(['ar_search','ar_start_date','ar_end_date','ar_type'])->mapWithKeys(fn($k)=>[$k=>request($k)])->all()))) }}" title="{{ __('common.actions.reset') }}"><i data-lucide="x"></i></a>
                                @endif
                            </div>
                        </form>
                    </div>
                </details>
            </div>
            <div class="accordion mb-3 accordion-gap">
                <details class="accordion-item">
                    <summary class="accordion-header">
                        <i class="me-2" data-lucide="bar-chart-3"></i>
                        <span class="fw-bold">Summary</span>
                        <i class="accordion-icon" data-lucide="chevron-down"></i>
                    </summary>
                    <div class="accordion-body py-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex flex-wrap align-items-center gap-4 gap-lg-5">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-uppercase small fg-secondary">IN</span>
                                    <span class="fs-5 fw-semibold fg-success">{{ number_format((float) ($apTotals['debit'] ?? 0), 2) }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-uppercase small fg-secondary">OUT</span>
                                    <span class="fs-5 fw-semibold fg-danger">{{ number_format((float) ($apTotals['credit'] ?? 0), 2) }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-uppercase small fg-secondary">BALANCE</span>
                                    <span class="fs-5 fw-semibold">{{ number_format((float) ($apTotals['balance'] ?? 0), 2) }}</span>
                                </div>
                            </div>
                            <div id="ap-summary-spark" style="width: 140px; height: 120px;"></div>
                        </div>
                    </div>
                </details>
            </div>
            @include('crm.companies._statement-table', [
                'rows' => $apLedgers,
                'emptyMessage' => __('companies.messages.no_ap_transactions'),
                'sourceUrls' => $sourceUrls,
            ])
        </div>

        {{-- TAB 5: Files --}}
        <div class="tab-pane fade" id="files-pane" role="tabpanel">
            <div class="table-responsive">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col">{{ __('company_files.table.column_company') }}</th>
                            <th scope="col">{{ __('company_files.table.column_title') }}</th>
                            <th scope="col">{{ __('company_files.table.column_attachment') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($company->files as $companyFile)
                            <tr>
                                <td><span class="fw-bold">{{ $companyFile->company->name ?? '-' }}</span></td>
                                <td>
                                    <a class="text-decoration-none fw-bold"
                                        href="{{ route('crm.company-files.show', $companyFile) }}">
                                        {{ $companyFile->title }}
                                    </a>
                                </td>
                                <td>
                                    @if ($companyFile->file_path)
                                        @if (str_starts_with($companyFile->mime_type ?? '', 'image/'))
                                            <a data-fancybox="gallery" data-caption="{{ $companyFile->title }}"
                                                href="{{ route('crm.company-files.attachment', $companyFile) }}">
                                                <img class="img-thumbnail me-1 rounded border"
                                                    src="{{ route('crm.company-files.attachment', $companyFile) }}"
                                                    alt="{{ $companyFile->title }}"
                                                    style="max-height: 40px; cursor: pointer" />
                                            </a>
                                        @else
                                            <a class="badge theme-secondary text-decoration-none"
                                                href="{{ route('crm.company-files.attachment', $companyFile) }}"
                                                target="_blank">
                                                <i class="me-1" data-lucide="paperclip"></i>
                                                {{ $companyFile->file_name }}
                                            </a>
                                        @endif
                                    @else
                                        <span class="fg-secondary small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-3 text-center" colspan="5">
                                    {{ __('companies.show.messages.no_files') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const eventBadges = document.querySelectorAll('.calendar-event-badge');
            const eventDetails = @json(__('companies.show.calendar.event_details', ['type' => '__TYPE__']));
            const dateLabel = @json(__('companies.show.calendar.date'));
            const descriptionLabel = @json(__('companies.show.calendar.description'));
            const closeLabel = @json(__('companies.show.calendar.close'));

            eventBadges.forEach((badge) => {
                badge.addEventListener('click', function() {
                    const type = this.getAttribute('data-type');
                    const title = this.getAttribute('data-title');
                    const date = this.getAttribute('data-date');
                    const color = this.getAttribute('data-color');

                    if (typeof confirmDialog !== 'undefined') {
                        confirmDialog({
                            title: `<span style="font-size: 1.25rem;">${eventDetails.replace('__TYPE__', type)}</span>`,
                            html: `
                            <div class="text-start mt-3 p-3 bg-2 rounded border">
                                <div class="mb-2">
                                    <span class="fg-secondary small text-uppercase fw-bold">${dateLabel}</span><br>
                                    <span class="fs-2xl">${date}</span>
                                </div>
                                <div>
                                    <span class="fg-secondary small text-uppercase fw-bold">${descriptionLabel}</span><br>
                                    <span class="fs-2xl fw-semibold">${title}</span>
                                </div>
                            </div>
                        `,
                            icon: 'info',
                            confirmTone: 'secondary',
                            confirmButtonText: closeLabel,
                        });
                    } else {
                        alert(
                            `--- ${type} ---\n\n${dateLabel}: ${date}\n${descriptionLabel}: ${title}`
                        );
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab');
            if (tab) {
                const btn = document.querySelector(`[data-bs-target="#${tab}-pane"]`);
                if (btn && window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(btn).show();
                } else if (btn) {
                    btn.click();
                }
            }

            if (typeof echarts === 'undefined') return;
            function renderSpark(id, inVal, outVal, balVal) {
                const el = document.getElementById(id);
                if (!el) return;
                const chart = echarts.init(el);
                const balLabel = balVal >= 0 ? 'BALANCE' : 'BALANCE (-)';
                const grad = (c1, c2) => new echarts.graphic.LinearGradient(0, 0, 0, 1, [{ offset: 0, color: c1 }, { offset: 1, color: c2 }]);
                const data = [];
                if (Number(inVal) > 0) data.push({ value: inVal, name: 'IN', itemStyle: { color: grad('#34d399', '#059669') } });
                if (Number(outVal) > 0) data.push({ value: outVal, name: 'OUT', itemStyle: { color: grad('#fb7185', '#e11d48') } });
                if (Number(balVal) !== 0) data.push({ value: Math.abs(balVal), name: balLabel, itemStyle: { color: balVal >= 0 ? grad('#60a5fa', '#2563eb') : grad('#fbbf24', '#ea580c') } });
                if (data.length === 0) data.push({ value: 1, name: 'NO DATA', itemStyle: { color: grad('#e9ecef', '#dee2e6') }, tooltip: { show: false } });
                chart.setOption({
                    animation: true,
                    tooltip: { trigger: 'item', formatter: (p) => p.name === 'NO DATA' ? '' : `${p.name}: ${p.value}` },
                    series: [{
                        type: 'pie',
                        roseType: data.length === 1 && data[0].name === 'NO DATA' ? false : 'area',
                        radius: ['16%', '92%'],
                        center: ['50%', '50%'],
                        avoidLabelOverlap: false,
                        itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2, shadowBlur: 12, shadowColor: 'rgba(0,0,0,0.15)' },
                        emphasis: { scale: true, scaleSize: 6, itemStyle: { shadowBlur: 20, shadowColor: 'rgba(0,0,0,0.25)' } },
                        label: { show: false },
                        labelLine: { show: false },
                        data
                    }]
                });
                window.addEventListener('resize', () => chart.resize());
            }
            renderSpark('ar-summary-spark', {{ (float) ($arTotals['debit'] ?? 0) }}, {{ (float) ($arTotals['credit'] ?? 0) }}, {{ (float) ($arTotals['balance'] ?? 0) }});
            renderSpark('ap-summary-spark', {{ (float) ($apTotals['debit'] ?? 0) }}, {{ (float) ($apTotals['credit'] ?? 0) }}, {{ (float) ($apTotals['balance'] ?? 0) }});
        });
    </script>
@endpush
