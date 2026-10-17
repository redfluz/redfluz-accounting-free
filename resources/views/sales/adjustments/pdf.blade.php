@php use App\Models\System\Setting; @endphp
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

<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <style>
        @page {
            margin: 0;
        }

        body {
            color: #1f2937;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            margin: 0;
        }

        .header {
            background: #1e3a8a;
            color: #ffffff;
            padding: 42px 48px;
        }

        .content {
            padding: 40px 48px;
        }

        .company-name {
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 1px;
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .meta-label {
            color: #6b7280;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .customer-name {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .summary {
            margin: 26px 0 30px;
        }

        .item-table {
            margin-top: 28px;
        }

        .item-table th {
            background: #f3f4f6;
            font-size: 10px;
            padding: 11px 9px;
            text-align: left;
        }

        .item-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 13px 9px;
        }

        .text-right {
            text-align: right;
        }

        .total {
            border-top: 2px solid #1e3a8a;
            font-size: 15px;
            font-weight: bold;
            margin-left: auto;
            margin-top: 24px;
            width: 230px;
        }

        .accent {
            color: #1e3a8a;
        }

        .footer {
            background: #f3f4f6;
            bottom: 0;
            box-sizing: border-box;
            padding: 14px 48px;
            position: fixed;
            text-align: center;
            width: 100%;
        }
    </style>
    <title></title>
</head>

<body>
    @php
        $subtype = $adjustment->subtype->value ?? $adjustment->subtype;
        $isDebitNote = $subtype === \App\Enums\LedgerSubType::INCREASE_ADJUSTMENT->value;
    @endphp

    <div class="header">
        <table>
            <tr>
                <td>
                    @if ($setting->logo_path)
                        <img
                            src="{{ public_path('storage/' . $setting->logo_path) }}"
                            alt="{{ $setting->company_name }}"
                            style="max-height: 55px; max-width: 180px; margin-bottom: 8px"
                        />
                    @else
                        <div class="company-name">
                            {{ $setting->company_name ?: __('adjustments.pdf.company_placeholder') }}
                        </div>
                    @endif

                    @if ($setting->company_email)
                        <div>{{ $setting->company_email }}</div>
                    @endif
                    @if ($setting->company_phone)
                        <div>{{ $setting->company_phone }}</div>
                    @endif
                    @if ($setting->company_fax)
                        <div>{{ $setting->company_fax }}</div>
                    @endif
                    @if ($setting->company_website)
                        <div>{{ $setting->company_website }}</div>
                    @endif
                    @if ($setting->company_registration_no)
                        <div>{{ $setting->company_registration_no }}</div>
                    @endif
                    @if ($setting->company_tax_no)
                        <div>{{ $setting->company_tax_no }}</div>
                    @endif
                    @if ($setting->address)
                        <div>{{ $setting->address }}</div>
                    @endif
                </td>
                <td>
                    <div class="title">
                        @if ($isDebitNote)
                            {{ __('adjustments.pdf.debit_note_title') }}
                        @else
                            {{ __('adjustments.pdf.credit_note_title') }}
                        @endif
                    </div>
                    <div class="text-right">{{ $adjustment->document_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table class="summary">
            <tr>
                <td width="65%">
                    <div class="meta-label">{{ __('adjustments.pdf.issued_to') }}</div>
                    <div class="customer-name">{{ $adjustment->company_name ?? '-' }}</div>
                    <div class="muted">
                        {{ __('adjustments.pdf.linked_invoice') }}: {{ $adjustment->reference_number }}
                    </div>
                </td>
                <td class="text-right">
                    <span class="muted">{{ __('adjustments.pdf.date') }}:</span>
                    {{ $adjustment->date->format(Setting::getDefaultDate()) }}
                </td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th>{{ __('adjustments.pdf.description') }}</th>
                    <th class="text-right">{{ __('adjustments.pdf.quantity') }}</th>
                    <th class="text-right">{{ __('adjustments.pdf.unit_price') }}</th>
                    <th class="text-right">{{ __('adjustments.pdf.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('adjustments.sub_type.' . $subtype) }}</td>
                    <td class="text-right">{{ $firstLine?->quantity ?? '-' }}</td>
                    <td class="text-right">{{ $firstLine ? number_format((float) $firstLine->unit_price, 2) : '-' }}</td>
                    <td class="text-right">{{ number_format((float) $adjustment->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="total">
            <tr>
                <td>{{ __('adjustments.pdf.total') }}</td>
                <td class="accent text-right">{{ number_format((float) $adjustment->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    </body>
</html>
