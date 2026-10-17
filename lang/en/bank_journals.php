<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

return [
    'title' => 'Bank Journals',
    'subtitle' => 'First-time setup only (opening balances, initial data). For daily transactions, use receipts or payment vouchers.',
    'tabs' => [
        'pending' => 'Pending',
        'posted' => 'Posted',
        'cancelled' => 'Cancelled',
    ],
    'form' => [
        'create_title' => 'Create Bank Journal',
        'edit_title' => 'Edit Bank Journal',
        'account' => 'Account',
        'account_placeholder' => 'Select an account',
        'direction' => 'Direction',
        'direction_in' => 'Money In',
        'direction_out' => 'Money Out',
        'date' => 'Document Date',
        'amount' => 'Amount',
        'offset_chart' => 'Offsetting Account',
        'offset_chart_placeholder' => 'Select an offsetting account',
        'offset_chart_hint' => 'The chart account on the other side of the movement. It must differ from the money account itself.',
        'description' => 'Description',
        'notes' => 'Notes',
    ],
    'table' => [
        'column_direction' => 'Direction',
        'column_account' => 'Account',
        'column_offset' => 'Offsetting Account',
    ],
    'show' => [
        'posting_title' => 'Posting Preview',
        'posting_hint' => 'What approval will write: the money side against the offsetting side.',
        'column_side' => 'Side',
        'column_account' => 'Account',
        'money_in' => 'Money in',
        'money_out' => 'Money out',
        'restore_button' => 'Restore',
    ],
    'validation' => [
        'offset_same_as_account' => 'The offsetting account must be different from the money account itself.',
        'offset_invalid' => 'The selected offsetting account is invalid.',
        'account_inactive' => 'The selected money account is no longer active.',
        'crypto_account' => 'Crypto accounts cannot be moved with a bank journal.',
        'insufficient_balance' => 'The account balance does not cover the amount.',
        'account_already_posted' => 'This account already has a posted journal. Opening balance is a one-time setup.',
    ],
];
