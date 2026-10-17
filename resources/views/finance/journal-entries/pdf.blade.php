@php use App\Models\System\Setting; @endphp
@php
    use App\Enums\LedgerStatus;

    $isPosted = $journalEntry->status === LedgerStatus::POSTED;
    $statusLabel =
        $journalEntry->reversal_of_id !== null
            ? __('journal_entries.reversal_status')
            : ($journalEntry->reversal !== null
                ? __('journal_entries.reversed_status')
                : ($isPosted
                    ? __('journal_entries.status_approved')
                    : __('journal_entries.status_pending')));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8" />
    <title>{{ $journalEntry->document_number }}</title>
    <style>
        @page {
            margin: 28px 34px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            font-size: 11px;
        }

        .header {
            border-bottom: 3px solid #1f4e79;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .brand {
            font-size: 20px;
            font-weight: bold;
            color: #1f4e79;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            text-align: right;
        }

        .muted {
            color: #64748b;
        }

        .meta {
            width: 100%;
            margin-bottom: 18px;
        }

        .meta td {
            width: 25%;
            padding: 8px;
            background: #f4f7fa;
            border: 1px solid #d9e1e8;
        }

        .label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            margin-bottom: 3px;
        }

        table.lines {
            width: 100%;
            border-collapse: collapse;
        }

        .lines th {
            background: #1f4e79;
            color: #fff;
            padding: 8px;
            text-align: left;
        }

        .lines td {
            border-bottom: 1px solid #d9e1e8;
            padding: 8px;
        }

        .lines .amount {
            text-align: right;
        }

        .lines tfoot td {
            font-weight: bold;
            border-top: 2px solid #1f4e79;
            border-bottom: 4px double #1f4e79;
        }

        .notes {
            margin-top: 18px;
            padding: 10px;
            background: #f4f7fa;
        }

        .audit-signature {
            height: 34px;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            text-align: center;
            color: #64748b;
            font-size: 9px;
        }
        .company-header {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .company-info {
            font-size: 9px;
            color: #555;
        }
    </style>
</head>

<body>
    @include('partials.pdf-company-header')
    <table class="header" style="width: 100%">
        <tr>
            <td>
                @if ($setting->logo_path)
                    <img
                        alt=""
                        src="{{ public_path('storage/' . $setting->logo_path) }}"
                        style="max-height: 48px; max-width: 150px"

                    /><br />
                @endif
                <span class="brand">{{ $setting->company_name ?? config('app.name') }}</span><br />
                <span class="muted">{{ $setting->address }}</span>
            </td>
            <td class="title">{{ __('journal_entries.pdf_title') }}</td>
        </tr>
    </table>
    <table class="meta">
        <tr>
            <td>
                <span class="label">{{ __('journal_entries.table.column_document_number') }}</span
                ><strong>{{ $journalEntry->document_number }}</strong>
            </td>
            <td>
                <span class="label">{{ __('journal_entries.table.column_reference_number') }}</span
                ><strong>{{ $journalEntry->reference_number ?? '-' }}</strong>
            </td>
            <td>
                <span class="label">{{ __('journal_entries.table.column_date') }}</span
                ><strong>{{ $journalEntry->date->format(Setting::getDefaultDate()) }}</strong>
            </td>
            <td>
                <span class="label">{{ __('journal_entries.table.column_status') }}</span
                ><strong>{{ $statusLabel }}</strong>
            </td>
        </tr>
    </table>
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 6%">#</th>
                <th style="width: 22%">{{ __('journal_entries.table.column_account') }}</th>
                <th>{{ __('journal_entries.table.column_description') }}</th>
                <th class="amount" style="width: 16%">{{ __('journal_entries.table.column_debit_amount') }}</th>
                <th class="amount" style="width: 16%">{{ __('journal_entries.table.column_credit_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($journalEntry->charts as $item)
                @if (filled($item->debit_acc) ||
                        filled($item->credit_acc) ||
                        filled($item->description) ||
                        (float) $item->debit_amount > 0 ||
                        (float) $item->credit_amount > 0)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            {{ $item->debit_acc ?? ($item->credit_acc ?? '-') }} - {{ $item->debit_acc_desc ?? ($item->credit_acc_desc ?? '-') }}
                        </td>
                        <td>{{ $item->description ?? '-' }}</td>
                        <td class="amount">{{ number_format((float) $item->debit_amount, 2) }}</td>
                        <td class="amount">{{ number_format((float) $item->credit_amount, 2) }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="amount" colspan="3">{{ __('common.labels.total') }}</td>
                <td class="amount">{{ number_format((float) $journalEntry->total_debit, 2) }}</td>
                <td class="amount">{{ number_format((float) $journalEntry->total_credit, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @if (filled($journalEntry->notes))
        <div class="notes">
            <span class="label">{{ __('journal_entries.table.column_notes') }}</span>{{ $journalEntry->notes }}
        </div>
    @endif

    <h2 style="margin-top: 18px; font-size: 13px; padding-bottom: 4px; border-bottom: 1px solid #d9e1e8;">{{ __('journal_entries.audit.role') }}</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="width: 130px; border: 1px solid #d9e1e8; padding: 5px 7px; background: #f4f7fa; text-align: left;">{{ __('journal_entries.audit.role') }}</th>
                <th style="width: 160px; border: 1px solid #d9e1e8; padding: 5px 7px; background: #f4f7fa; text-align: left;">{{ __('journal_entries.audit.name') }}</th>
                <th style="width: 110px; border: 1px solid #d9e1e8; padding: 5px 7px; background: #f4f7fa; text-align: left;">{{ __('journal_entries.audit.date') }}</th>
                <th style="border: 1px solid #d9e1e8; padding: 5px 7px; background: #f4f7fa; text-align: left;">{{ __('journal_entries.audit.signature') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['label' => __('journal_entries.audit.created_by'), 'name' => $journalEntry->created_name, 'at' => $journalEntry->created_at],
                ['label' => __('journal_entries.audit.approved_by'), 'name' => $journalEntry->approved_name, 'at' => $journalEntry->approved_at],
                ['label' => __('journal_entries.audit.cancelled_by'), 'name' => $journalEntry->cancelled_name, 'at' => $journalEntry->cancelled_at],
            ] as $row)
                <tr>
                    <td style="border: 1px solid #d9e1e8; padding: 5px 7px;">{{ $row['label'] }}</td>
                    <td style="border: 1px solid #d9e1e8; padding: 5px 7px;">{{ $row['name'] ?? '-' }}</td>
                    <td style="border: 1px solid #d9e1e8; padding: 5px 7px;">{{ $row['at']?->format(Setting::getDefaultDate('long_date_format')) ?? '-' }}</td>
                    <td class="audit-signature" style="border: 1px solid #d9e1e8; padding: 5px 7px;"></td>
                </tr>
            @endforeach

            @if ($journalEntry->undone_name !== null || $journalEntry->undone_at !== null)
                <tr>
                    <td style="border: 1px solid #d9e1e8; padding: 5px 7px;">{{ __('journal_entries.audit.undone_by') }}</td>
                    <td style="border: 1px solid #d9e1e8; padding: 5px 7px;">{{ $journalEntry->undone_name ?? '-' }}</td>
                    <td style="border: 1px solid #d9e1e8; padding: 5px 7px;">{{ $journalEntry->undone_at?->format(Setting::getDefaultDate('long_date_format')) ?? '-' }}</td>
                    <td class="audit-signature" style="border: 1px solid #d9e1e8; padding: 5px 7px;"></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">{{ __('journal_entries.generated_at', ['date' => now()->format(Setting::getDefaultDate('long_date_format'))]) }}</div>
</body>
</html>
