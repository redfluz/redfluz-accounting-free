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
    'title' => 'Chart Settings',
    'subtitle' => 'Configure default finance and chart of account settings.',
    'default_chart_accounts' => 'Default Chart of Accounts',
    'finance_period' => 'Finance Period',
    'banks_setup' => 'Banks Setup',
    'system_references' => 'System References',
    'enable_strict_journal_posting' => 'Enable strict journal posting',

    'table' => [
        'column_cogs_chart_uuid' => 'COGS Account',
        'column_income_chart_uuid' => 'Income Account',
        'column_bank_chart_uuid' => 'Bank Account',
        'column_cash_chart_uuid' => 'Cash Account',
        'column_cash_in_transit_chart_uuid' => 'Cash in Transit Account',
        'column_e_wallet_chart_uuid' => 'E-Wallet Account',
        'column_client_chart_uuid' => 'Client / Receivable Account',
        'column_vendor_chart_uuid' => 'Vendor / Payable Account',
        'column_inventory_chart_uuid' => 'Inventory Account',
        'column_stock_shortage_chart_uuid' => 'Inventory Shortage Expense Account',
        'column_stock_surplus_chart_uuid' => 'Inventory Surplus Gain Account',
        'column_deposit_chart_uuid' => 'Deposit Account',
        'column_input_tax_chart_uuid' => 'Input Tax Account',
        'column_output_tax_chart_uuid' => 'Output Tax Account',
        'column_retained_earnings_chart_uuid' => 'Retained Earnings Account',
        'column_disposal_gain_chart_uuid' => 'Disposal Gain Account',
        'column_disposal_loss_chart_uuid' => 'Disposal Loss Account',
        'column_retained_earnings_formula' => 'Retained Earnings Formula',
        'column_closing_date' => 'Closing Date',
        'column_start_finance_period_month' => 'Start Finance Period Month',
        'column_strict_journal_posting' => 'Strict Journal Posting',
    ],
    'form' => [
        'retained_earnings_formula' => 'Retained Earnings Formula',
        'retained_earnings_formula_help' => 'Controls the year-end amount transferred to the retained earnings account. Supported tags: CHART, TYPE, SUM_TYPE, GROUP, and SUM_GROUP.',
        'retained_earnings_formula_example' => 'Example: {SUM_TYPE:INCOME} - {SUM_TYPE:EXPENSES}',
        'cash_in_transit_help' => 'Holds cash that has left a drawer but has not yet been credited by a bank. Must be an asset account with no cash, bank or e-wallet account behind it.',
    ],
    'validation' => [
        'invalid_retained_earnings_formula' => 'Enter a valid retained earnings formula using supported account tags and mathematical operators.',
        'bank_chart_not_in_banks' => 'The selected bank account chart does not have a bank account.',
        'cash_chart_not_in_cash_accounts' => 'The selected cash account chart does not have a cash account.',
        'e_wallet_chart_not_in_e_wallet_accounts' => 'The selected e-wallet account chart does not have an e-wallet account.',
    ],
];
