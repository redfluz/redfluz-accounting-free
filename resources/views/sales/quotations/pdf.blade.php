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

        .summary td {
            padding: 5px 0;
            vertical-align: top;
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

        .total td {
            padding-top: 10px;
        }

        .accent {
            color: #1e3a8a;
        }

        .notes {
            background: #f3f4f6;
            margin-top: 30px;
            padding: 15px;
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
                            {{ $setting->company_name ?: __('quotations.pdf.company_placeholder') }}
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
                    <div class="title">{{ __('quotations.pdf.title') }}</div>
                    <div class="text-right">{{ $quotation->document_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table class="summary">
            <tr>
                <td width="58%">
                    <div class="meta-label">{{ __('quotations.pdf.quoted_to') }}</div>
                    <div class="customer-name">{{ $quotation->company_name ?? '-' }}</div>
                </td>
                <td width="21%">
                    <div class="meta-label">{{ __('quotations.pdf.date') }}</div>
                    <div>{{ $quotation->date->format(Setting::getDefaultDate()) }}</div>
                </td>
                <td class="text-right" width="21%">
                    <div class="meta-label">{{ __('quotations.pdf.valid_until') }}</div>
                    <div>{{ $quotation->expiry_date?->format(Setting::getDefaultDate()) ?? '-' }}</div>
                </td>
            </tr>
            @if ($quotation->reference_number)
                <tr>
                    <td colspan="3">
                        <span class="meta-label">{{ __('quotations.pdf.reference_number') }}:</span>
                        {{ $quotation->reference_number }}
                    </td>
                </tr>
            @endif
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th>{{ __('quotations.pdf.description') }}</th>
                    <th class="text-right">{{ __('quotations.pdf.quantity') }}</th>
                    <th class="text-right">{{ __('quotations.pdf.unit_price') }}</th>
                    <th class="text-right">{{ __('quotations.pdf.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td>
                            {{ $line->product_name ?? ($line->description ?? '-') }}
                            @if ($line->product_name && $line->description)
                                <div class="muted">{{ $line->description }}</div>
                            @endif
                        </td>
                        <td class="text-right">{{ $line->quantity }}</td>
                        <td class="text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format((float) $line->total_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="total">
            <tr>
                <td>{{ __('quotations.pdf.total') }}</td>
                <td class="accent text-right">
                    {{ number_format((float) $lines->sum('total_amount'), 2) }}
                </td>
            </tr>
        </table>

        @if ($setting->company_tax_no)
            <div class="muted">{{ __('quotations.pdf.tax_number') }}: {{ $setting->company_tax_no }}</div>
        @endif

        @if ($quotation->notes)
            <div class="notes">
                <strong>{{ __('quotations.pdf.notes') }}</strong>
                <br />
                {{ $quotation->notes }}
            </div>
        @endif
    </div>

    </body>
</html>
