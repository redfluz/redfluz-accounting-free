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

/**
 * One party statement grid, drawn with the general ledger grid standard.
 *
 * Expects $rows, the ordered ArLedger or ApLedger collection for one company,
 * $emptyMessage, the text shown when that collection holds nothing, and
 * $sourceUrls, the document each row came from keyed by row id. The reference
 * number and the chart account ride under the document number, and the type and
 * subtype ride in one cell, exactly as /ledgers presents them, so a statement
 * row and the ledger row behind it read the same way — and drill down to the
 * same document.
 */
?>
<div class="table-responsive rounded-5 overflow-hidden border">
    <table class="table-hover table-striped mb-0 table align-middle">
        <thead class="bg-1">
            <tr>
                <th scope="col">{{ __('ledgers.table.column_document_number') }}</th>
                <th scope="col">{{ __('ledgers.table.column_date') }}</th>
                <th scope="col">{{ __('ledgers.table.column_type') }}</th>
                <th scope="col">{{ __('ledgers.table.column_description') }}</th>
                <th scope="col" class="text-end">{{ __('ledgers.table.column_debit_amount') }}</th>
                <th scope="col" class="text-end">{{ __('ledgers.table.column_credit_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $ledger)
                <tr>
                    <td>
                        <div class="fw-bold">
                            @if (isset($sourceUrls[(string) $ledger->id]))
                                <a href="{{ $sourceUrls[(string) $ledger->id] }}"
                                    title="{{ __('ledgers.actions.view_source') }}">
                                    {{ $ledger->document_number }}
                                    <i class="small ms-1" data-lucide="external-link"></i>
                                </a>
                            @else
                                {{ $ledger->document_number }}
                            @endif
                            <br />
                            @if (filled($ledger->reference_number) && $ledger->reference_number !== $ledger->document_number)
                                <small class="badge theme-secondary">
                                    {{ __('ledgers.table.column_reference_number') }}:
                                    {{ $ledger->reference_number }}
                                </small>
                                <br />
                            @endif
                            <small class="badge theme-secondary">
                                {{ $ledger->chart_code ?? '' }} {{ $ledger->chart_name ?? '' }}
                            </small>
                        </div>
                    </td>
                    <td>{{ $ledger->date->format(\App\Models\System\Setting::getDefaultDate()) ?? '-' }}</td>
                    <td>
                        @php($type = $ledger->type->value ?? $ledger->type)
                        <span
                            class="badge {{ $type === 'invoice' ? 'theme-info' : ($type === 'payment' ? 'theme-success' : 'theme-secondary') }}">
                            {{ __('ledgers.type.' . $type) }} </span><br />
                        <small
                            class="badge theme-secondary">{{ __('ledgers.sub_type.' . ($ledger->subtype->value ?? $ledger->subtype)) }}</small>
                    </td>
                    <td>
                        <small>{{ $ledger->description ?? '-' }}</small>
                    </td>
                    <td class="fw-bold fg-success text-end">
                        {{ $ledger->debit_amount > 0 ? number_format($ledger->debit_amount, 2) : '-' }}
                    </td>
                    <td class="fw-bold fg-danger text-end">
                        {{ $ledger->credit_amount > 0 ? number_format($ledger->credit_amount, 2) : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="fg-secondary py-4 text-center" colspan="6">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="fw-bold text-end" colspan="4">{{ __('common.labels.total') }}:</td>
                <td class="fw-bold text-end">{{ number_format($rows->sum('debit_amount'), 2) }}</td>
                <td class="fw-bold text-end">{{ number_format($rows->sum('credit_amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
