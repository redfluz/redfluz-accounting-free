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
    'balance_sheet' => [
        'title' => 'Balance Sheet',
        'subtitle' => 'As of :period using the configured report formula.',
    ],
    'profit_loss' => [
        'title' => 'Profit & Loss',
        'subtitle' => ':period using the configured report formula.',
        'formula_info' => 'Net Profit / Loss formula: Total Income - Total Expenses.',
    ],
    'trial_balance' => [
        'title' => 'Trial Balance',
        'subtitle' => 'Account balances and movements for :period.',
        'balanced' => 'Trial balance agrees.',
        'not_balanced' => 'Trial balance does not agree. Difference: :amount.',
    ],
    'budget_actual' => [
        'title' => 'Budget vs Actual',
        'subtitle' => 'Budget and posted actual performance for :period.',
        'all_periods' => 'Full Financial Year',
        'comparison_title' => 'Actual Performance by Financial Year',
        'comparison_help' => 'A selected financial period is compared with the same period number in each earlier year.',
        'favourable' => 'Favourable',
        'unfavourable' => 'Unfavourable',
    ],
    'cash_flow' => [
        'title' => 'Cash Flow',
        'subtitle' => 'Direct cash and bank movements for :period.',
        'method_info' => 'Only posted documents containing a cash or bank movement are included. Classification, row order, and totals follow the editable Cash Flow template in Financial Report Design.',
        'section' => [
            'operating' => 'Operating Activities',
            'investing' => 'Investing Activities',
            'financing' => 'Financing Activities',
        ],
        'net_section' => 'Net Cash from :section',
        'opening_balance' => 'Opening Cash and Bank Balance',
        'net_cash_flow' => 'Net Increase / (Decrease) in Cash',
        'closing_balance' => 'Closing Cash and Bank Balance',
        'reconciled' => 'Cash flow reconciles with the posted cash and bank movement.',
        'not_reconciled' => 'Cash flow does not reconcile. Difference: :amount.',
    ],
    'summary' => [
        'total_assets' => 'Assets',
        'total_liabilities' => 'Liabilities',
        'total_equity' => 'Equity',
        'total_income' => 'Income',
        'total_expenses' => 'Expenses',
        'net_profit_loss' => 'Net Profit',
        'budget_income' => 'Budgeted Income',
        'actual_income' => 'Actual Income',
        'budget_expenses' => 'Budgeted Expenses',
        'actual_expenses' => 'Actual Expenses',
        'net_budget' => 'Budgeted Net Profit',
        'net_actual' => 'Actual Net Profit',
        'net_variance' => 'Net Variance',
    ],
    'table' => [
        'column_account_code' => 'Account Code',
        'column_account' => 'Account',
        'column_amount' => 'Amount',
        'opening_debit' => 'Opening Debit',
        'opening_credit' => 'Opening Credit',
        'period_debit' => 'Period Debit',
        'period_credit' => 'Period Credit',
        'closing_debit' => 'Closing Debit',
        'closing_credit' => 'Closing Credit',
        'total' => 'Total',
        'column_type' => 'Type',
        'budget' => 'Budget',
        'actual' => 'Actual',
        'variance' => 'Variance',
        'variance_percent' => 'Variance %',
        'actual_for_year' => 'Actual :year',
        'change_from_year' => 'Change vs :year',
        'cash_movement' => 'Cash Movement',
    ],
    'form' => [
        'filters' => 'Filters',
        'main_view' => 'Main View',
        'report_period' => 'Report Period',
        'year' => 'Year',
        'month' => 'Month',
        'quarter' => 'Quarter',
        'as_of_date' => 'As of Date',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'financial_year' => 'Financial Year',
        'financial_period' => 'Financial Period',
        'compare_with' => 'Compare With',
        'no_comparison' => 'No Previous Year',
        'previous_one_year' => 'Previous 1 Year',
        'previous_two_years' => 'Previous 2 Years',
    ],
    'report_view' => [
        'normal' => 'Normal',
        'q1' => 'Quarter 1 (Jan–Mar)',
        'q2' => 'Quarter 2 (Apr–Jun)',
        'q3' => 'Quarter 3 (Jul–Sep)',
        'q4' => 'Quarter 4 (Oct–Dec)',
        'whole_year' => 'Whole Year (Jan–Dec)',
        'whole_year_compare' => 'Whole Year vs Last Year',
    ],
    'period' => [
        'year' => 'Year',
        'year_end' => 'Year End',
        'month' => 'Month',
        'month_end' => 'Month End',
        'quarter' => 'Quarter',
        'quarter_end' => 'Quarter End',
        'custom_date' => 'As At Date',
        'custom_range' => 'Custom Range',
        'specific_date' => 'Specific Date',
    ],
    'actions' => [
        'view_ledger' => 'View supporting ledger entries',
        'apply' => 'Apply',
        'reset' => 'Reset',
        'excel' => 'Excel',
        'pdf' => 'PDF',
        'clean_pdf' => 'Clean PDF',
    ],
    'labels' => [
        'horizontal_scroll' => 'Scroll through report months',
        'period' => 'Period',
        'as_of' => 'As of',
        'generated_at' => 'Generated',
        'formula' => 'Formula',
    ],
    'messages' => [
        'no_accounts' => 'No accounts are configured for this group.',
        'unmatched_accounts' => 'Unmatched ledger account references',
        'unmatched_accounts_help' => 'These ledger references could not be matched to the Chart of Accounts.',
        'no_trial_balance_rows' => 'No posted ledger balances or movements exist for this period.',
        'no_budget_actual_rows' => 'No budget or posted actual records exist for this period.',
        'no_cash_flow_rows' => 'No posted cash or bank movements exist for this section.',
    ],
    'errors' => [
        'row_token_not_found' => 'A report row references :row, which no longer exists in the chart of accounts.',
        'template_missing' => 'The financial report template :slug has not been configured.',
        'category_not_supported' => 'Report row :code uses a chart category, but chart categories are not configured in this system.',
        'category_token_not_supported' => 'Formula category :category cannot be resolved because chart categories are not configured.',
        'invalid_formula' => 'Report row :code contains an invalid formula.',
        'unknown_formula_group' => 'Unknown formula group: :group.',
        'invalid_resolved_formula' => 'The resolved formula is invalid: :formula.',
        'formula_execution_failed' => 'The formula could not be calculated: :formula.',
        'formula_not_numeric' => 'The formula did not return a number: :formula.',
        'chart_token_not_found' => 'Chart account :chart in the formula was not found.',
        'group_token_not_found' => 'Report group :group in the formula was not found.',
        'chart_type_token_not_found' => 'Chart type :type in the formula was not found.',
        'period_year_mismatch' => 'The selected financial period does not belong to the selected financial year.',
    ],
    'account_type' => [
        'income' => 'Income',
        'expenses' => 'Expense',
    ],
];
