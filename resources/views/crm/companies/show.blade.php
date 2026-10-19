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
                            <th style="width: 14.28%">{{ __('companies.show.calendar.sunday') }}</th>
                            <th style="width: 14.28%">{{ __('companies.show.calendar.monday') }}</th>
                            <th style="width: 14.28%">{{ __('companies.show.calendar.tuesday') }}</th>
                            <th style="width: 14.28%">{{ __('companies.show.calendar.wednesday') }}</th>
                            <th style="width: 14.28%">{{ __('companies.show.calendar.thursday') }}</th>
                            <th style="width: 14.28%">{{ __('companies.show.calendar.friday') }}</th>
                            <th style="width: 14.28%">{{ __('companies.show.calendar.saturday') }}</th>
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
                            <th>{{ __('contacts.table.column_name') }}</th>
                            <th>{{ __('contacts.table.column_email') }}</th>
                            <th>{{ __('contacts.table.column_phone') }}</th>
                            <th>{{ __('contacts.table.column_position') }}</th>
                            <th class="text-end">{{ __('common.labels.actions') }}</th>
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
                            <th>{{ __('companies.addresses.fields.name') }}</th>
                            <th>{{ __('companies.addresses.fields.phone') }}</th>
                            <th>{{ __('companies.addresses.fields.address') }}</th>
                            <th>{{ __('companies.addresses.fields.default') }}</th>
                            <th class="text-end">{{ __('common.labels.actions') }}</th>
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
            <div class="table-responsive">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('companies.statement.document_number') }}</th>
                            <th>{{ __('companies.statement.date') }}</th>
                            <th>{{ __('companies.statement.applied_to') }}</th>
                            <th>{{ __('companies.statement.product') }}</th>
                            <th>{{ __('companies.statement.type') }}</th>
                            <th class="text-end">{{ __('companies.statement.debit') }}</th>
                            <th class="text-end">{{ __('companies.statement.credit') }}</th>
                            <th class="text-end">{{ __('companies.statement.balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($arLedgers as $ledger)
                            @php($subtype = $ledger->subtype->value ?? $ledger->subtype)

                            <tr>
                                <td class="fw-bold">{{ $ledger->document_number }}</td>
                                <td>{{ $ledger->date->format(Setting::getDefaultDate()) }}</td>
                                <td>
                                    @if ($ledger->reference_number && $ledger->reference_number !== $ledger->document_number)
                                        {{ $ledger->reference_number }}
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td>
                                    @if ($ledger->product_id)
                                        <a href="{{ route('inventory.products.show', $ledger->product_id) }}">
                                            {{ $ledger->product_name ?? __('companies.statement.unknown_product') }}
                                        </a>
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge theme-info"> {{ __('ledgers.sub_type.' . $subtype) }} </span>
                                </td>
                                <td class="text-end">{{ number_format((float) $ledger->debit_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $ledger->credit_amount, 2) }}</td>
                                <td class="fw-bold text-end">
                                    <x-money :value="$ledger->running_balance" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-3 text-center" colspan="8">
                                    {{ __('companies.messages.no_ar_transactions') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($arLedgers->isNotEmpty())
                        <tfoot class="bg-1 fw-bold">
                            <tr>
                                <td class="text-uppercase text-end" colspan="5">
                                    {{ __('companies.statement.closing_balance') }}:
                                </td>
                                <td class="text-end">{{ number_format((float) $arTotals['debit'], 2) }}</td>
                                <td class="text-end">{{ number_format((float) $arTotals['credit'], 2) }}</td>
                                <td class="fs-xl text-end">
                                    <x-money :value="$arTotals['balance']" />
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- TAB 4: Accounts Payable Statement --}}
        <div class="tab-pane fade" id="ap-pane" role="tabpanel" aria-labelledby="ap-tab">
            <div class="table-responsive">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('companies.statement.document_number') }}</th>
                            <th>{{ __('companies.statement.date') }}</th>
                            <th>{{ __('companies.statement.applied_to') }}</th>
                            <th>{{ __('companies.statement.product') }}</th>
                            <th>{{ __('companies.statement.type') }}</th>

                            <th class="text-end">{{ __('companies.statement.debit') }}</th>
                            <th class="text-end">{{ __('companies.statement.credit') }}</th>
                            <th class="text-end">{{ __('companies.statement.balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($apLedgers as $ledger)
                            @php($subtype = $ledger->subtype->value ?? $ledger->subtype)

                            <tr>
                                <td class="fw-bold">{{ $ledger->document_number }}</td>
                                <td>{{ $ledger->date->format(Setting::getDefaultDate('short_date_format')) }}</td>
                                <td class="text-center">
                                    @if ($ledger->reference_number && $ledger->reference_number !== $ledger->document_number)
                                        {{ $ledger->reference_number }}
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($ledger->product_id)
                                        <a href="{{ route('inventory.products.show', $ledger->product_id) }}">
                                            {{ $ledger->product_name ?? __('companies.statement.unknown_product') }}
                                        </a>
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge theme-warning">
                                        {{ __('ledgers.sub_type.' . $subtype) }}
                                    </span>
                                </td>

                                <td class="text-end">{{ number_format((float) $ledger->debit_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $ledger->credit_amount, 2) }}</td>
                                <td class="fw-bold text-end">
                                    <x-money :value="$ledger->running_balance" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-3 text-center" colspan="8">
                                    {{ __('companies.messages.no_ap_transactions') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($apLedgers->isNotEmpty())
                        <tfoot class="bg-1 fw-bold">
                            <tr>
                                <td class="text-uppercase text-end" colspan="5">
                                    {{ __('companies.statement.closing_balance') }}:
                                </td>
                                <td class="text-end">{{ number_format((float) $apTotals['debit'], 2) }}</td>
                                <td class="text-end">{{ number_format((float) $apTotals['credit'], 2) }}</td>
                                <td class="fs-xl text-end">
                                    <x-money :value="$apTotals['balance']" />
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- TAB 5: Files --}}
        <div class="tab-pane fade" id="files-pane" role="tabpanel">
            <div class="table-responsive">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('company_files.table.column_company') }}</th>
                            <th>{{ __('company_files.table.column_title') }}</th>
                            <th>{{ __('company_files.table.column_attachment') }}</th>
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

                    let iconColor = '#9f1239';
                    if (color === 'success') iconColor = '#198754';
                    if (color === 'info') iconColor = '#0dcaf0';

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
@endpush
