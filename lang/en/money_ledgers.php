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

return [
    'cash' => [
        'title' => 'Cash Ledger',
        'subtitle' => 'What is in each cash account.',
    ],
    'bank' => [
        'title' => 'Bank Ledger',
        'subtitle' => 'What is in each bank account.',
    ],
    'e_wallet' => [
        'title' => 'E-Wallet Ledger',
        'subtitle' => 'What is on each e-wallet account.',
    ],
    'kinds' => [
        'bank' => 'Bank',
        'cash' => 'Cash',
        'e_wallet' => 'E-Wallet',
        'crypto' => 'Crypto',
    ],
    'default' => 'Default',
    'all_accounts' => 'All accounts',
    'no_entries' => 'No movements have been posted to this ledger yet.',
    'no_accounts' => 'No :kind account has been created yet, so this ledger has nothing to show.',
    'balance_summary' => 'In :in / Out :out',
    'fields' => [
        'account' => 'Account',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'date' => 'Date',
        'document_number' => 'Document Number',
        'reference_number' => 'Reference Number',
        'money_in' => 'Money In',
        'money_out' => 'Money Out',
        'balance' => 'Balance',
        'description' => 'Description',
    ],
    'actions' => [
        'manage_accounts' => 'Accounts',
        'create_account' => 'Create Account',
    ],
];
