@php use App\Models\System\Setting; @endphp
{{--
    The audit trail at the foot of a document: who prepared it, who approved it,
    who cancelled it, and who brought a cancellation back.

    One table, one row per decision, each with the name, when it happened, and a
    box to sign. One table rather than one per decision because the questions
    asked of a posted document are the same three: who prepared it, who approved
    it, and who changed their mind. A row that has not happened says so rather
    than being left blank, because an empty box reads as "signed but the name was
    not recorded" and a row marked as not having happened is simply true.

    The names are snapshots taken at the time of each decision, so this reads the
    same years later even if a user has since been renamed or removed. That is the
    whole reason the name is stored alongside the id.
--}}
@props(['document'])

@php
    $rows = [
        [
            'label' => __('payment_vouchers.audit.created_by'),
            'name' => $document->created_name,
            'at' => $document->created_at,
        ],
        [
            'label' => __('payment_vouchers.audit.approved_by'),
            'name' => $document->approved_name,
            'at' => $document->approved_at,
        ],
        [
            'label' => __('payment_vouchers.audit.cancelled_by'),
            'name' => $document->cancelled_name,
            'at' => $document->cancelled_at,
        ],
    ];

    // A cancellation that was undone is worth a row of its own: a voucher that
    // was cancelled and then brought back was looked at twice, and the record
    // should say so rather than showing only the cancellation.
    if ($document->undone_name !== null || $document->undone_at !== null) {
        $rows[] = [
            'label' => __('payment_vouchers.audit.undone_by'),
            'name' => $document->undone_name,
            'at' => $document->undone_at,
        ];
    }
@endphp

<table class="table table-bordered mb-0 audit-table">
    <thead class="bg-1">
        <tr>
            <th class="audit-role">{{ __('payment_vouchers.audit.role') }}</th>
            <th class="audit-name">{{ __('payment_vouchers.audit.name') }}</th>
            <th class="audit-date">{{ __('payment_vouchers.audit.date') }}</th>
            <th class="audit-signature">{{ __('payment_vouchers.audit.signature') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td class="audit-role">{{ $row['label'] }}</td>
                <td class="audit-name">
                    {{ $row['name'] ?? '-' }}
                </td>
                <td class="audit-date">
                    {{ $row['at']?->format(Setting::getDefaultDate('long_date_format')) ?? '-' }}
                </td>
                <td class="audit-signature">
                    {{-- The box is deliberately empty. This is where a wet
                         signature goes on a printed copy, so it must not be
                         pre-filled with anything. --}}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>