@php use App\Models\System\Setting; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ __('ledgers.cancelled_title') }} - {{ __('receipts.title') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            margin: 0;
            padding: 14px;
        }

        h1 {
            font-size: 14px;
            margin: 0 0 2px;
        }

        .muted {
            color: #6c757d;
            font-size: 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #c8c8c8;
            padding: 4px 5px;
            text-align: left;
        }

        th {
            background: #f1f3f5;
            font-weight: bold;
        }

        .text-end {
            text-align: right;
        }
    </style>
</head>
<body>
    @if ($setting?->company_name)
        <div class="muted">{{ $setting->company_name }}</div>
    @endif

    <h1>{{ __('receipts.title') }} - {{ __('ledgers.cancelled_title') }}</h1>
    <div class="muted">
        @if ($startDate || $endDate)
            {{ $startDate ?? '...' }} - {{ $endDate ?? '...' }} |
        @endif
        {{ now()->format(Setting::getDefaultDate('long_date_format')) }}
    </div>
    
</body>
</html>