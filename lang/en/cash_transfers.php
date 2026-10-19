<?php

declare(strict_types=1);

return [
    'title' => 'Cash Transfers',
    'subtitle' => 'Move the surplus above a drawer\'s float into cash in transit',

    'tabs' => [
        'pending' => 'Pending',
        'posted' => 'Approved',
        'cancelled' => 'Cancelled',
    ],

    'table' => [
        'column_cash_account' => 'Cash Account',
        'column_to' => 'Into Transit',
    ],

    'form' => [
        'create_title' => 'New Cash Transfer',
        'edit_title' => 'Edit Cash Transfer',
        'cash_account' => 'Cash Account',
        'cash_account_placeholder' => 'Select a cash account',
        'amount' => 'Amount',
        'date' => 'Date',
        'reference_number' => 'Reference',
        'description' => 'Description',
        'notes' => 'Notes',
        'to' => 'Into',
        'holding' => 'holding',
        'surplus_hint' => 'Defaults to what the drawer holds above its float. It can be less, never more.',
        'surplus_available' => 'Surplus available: :amount',
        'no_surplus' => 'This drawer has nothing above its float to transfer.',
        'transit_balance' => 'Currently in transit:',
    ],

    'show' => [
        'posting' => 'Posting',
        'posted_explanation' => 'The drawer has been credited and cash in transit debited. No bank ledger has moved: the money is between the drawer and the bank until a bank transfer records the bank crediting it.',
        'not_posted' => 'Not yet posted to the ledgers. Nothing has moved.',
        'debit' => 'Debit',
        'credit' => 'Credit',
    ],

    'validation' => [
        'account_inactive' => 'That cash account is not available.',
        'no_surplus' => 'This drawer holds nothing above its float, so there is nothing to transfer.',
        'exceeds_surplus' => 'This drawer has only :surplus above its float.',
    ],
];
