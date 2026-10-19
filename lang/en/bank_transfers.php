<?php

declare(strict_types=1);

return [
    'title' => 'Bank Transfers',
    'subtitle' => 'Record cash in transit reaching a bank account',

    'tabs' => [
        'pending' => 'Pending',
        'posted' => 'Approved',
        'cancelled' => 'Cancelled',
    ],

    'table' => [
        'column_from' => 'From Transit',
        'column_to' => 'To Bank Account',
    ],

    'form' => [
        'create_title' => 'New Bank Transfer',
        'edit_title' => 'Edit Bank Transfer',
        'bank_account' => 'Bank Account',
        'bank_account_placeholder' => 'Select a bank account',
        'amount' => 'Amount',
        'date' => 'Date',
        'reference_number' => 'Bank Reference',
        'reference_hint' => 'The deposit slip or credit advice number, used to match this against the bank statement.',
        'description' => 'Description',
        'notes' => 'Notes',
        'from' => 'From',
        'available' => 'Available in transit:',
        'attachment' => 'Deposit Receipt',
        'attachment_hint' => 'The deposit slip, credit advice or a photo of the statement line showing the bank has credited this. Leave empty on edit to keep the current file.',
        'attachment_current' => 'Currently attached:',
    ],

    'show' => [
        'posting' => 'Posting',
        'posted_explanation' => 'The bank account has been debited and cash in transit credited, clearing the money out of transit and into the bank.',
        'not_posted' => 'Not yet posted to the ledgers. The bank has not been credited yet.',
        'attachment' => 'Deposit Receipt',
        'debit' => 'Debit',
        'credit' => 'Credit',
    ],

    'validation' => [
        'account_inactive' => 'That bank account is not available.',
        'transit_empty' => 'Nothing is in cash in transit, so there is nothing to transfer.',
        'exceeds_transit' => 'Cash in transit only holds :available.',
    ],
];
