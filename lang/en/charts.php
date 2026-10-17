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
    'title' => 'Chart of Accounts',
    'subtitle' => 'Manage accounting system account codes and classifications.',
    'export_excel' => 'Export Excel',
    'yes' => 'Yes',
    'no' => 'No',
    'all_types' => 'All Types',
    'all_report_groups' => 'All Report Groups',
    'table' => [
        'column_code' => 'Account Code',
        'column_name' => 'Account Name',
        'column_type' => 'Chart Type',
        'column_report_group' => 'Report Group',
        'column_bank' => 'Bank',
        'column_debtor' => 'Debtor',
        'column_creditor' => 'Creditor',
    ],
    'form' => [
        'report_group' => 'Financial Statement Group',
        'report_group_help' => 'Controls where this account appears in financial reports. Available groups are filtered by the selected chart type.',
        'report_group_select_type' => 'Select a chart type first',
        'report_group_empty' => 'No report group is available for this chart type',
    ],
    'report_group' => [
        'property_plant_equipment' => 'Property, Plant and Equipment',
        'investment_properties' => 'Investment Properties',
        'biological_assets' => 'Biological Assets',
        'right_of_use_assets' => 'Right-of-Use Assets',
        'service_concession_assets' => 'Service Concession Assets',
        'intangible_assets' => 'Intangible Assets',
        'goodwill' => 'Goodwill',
        'investment_subsidiaries' => 'Investment in Subsidiaries',
        'investment_associates' => 'Investment in Associates',
        'investment_joint_ventures' => 'Investment in Joint Ventures',
        'other_investments' => 'Other Investments',
        'receivable' => 'Receivable',
        'contract_assets' => 'Contract Assets',
        'deferred_tax_assets' => 'Deferred Tax Assets',
        'derivative_financial_assets' => 'Derivative Financial Assets',
        'other_non_current_assets' => 'Other Non-current Assets',
        'inventories' => 'Inventories',
        'other_current_assets' => 'Other Current Assets',
        'current_tax_assets' => 'Current Tax Assets',
        'derivative_financial_assets_current' => 'Derivative Financial Assets (Current)',
        'cash_and_cash_equivalents' => 'Cash and Cash Equivalents',
        'service_concession_liabilities' => 'Service Concession Liabilities',
        'borrowings' => 'Borrowings',
        'lease_liabilities' => 'Lease Liabilities',
        'employee_benefits' => 'Employee Benefits',
        'provisions' => 'Provisions',
        'payable' => 'Payable',
        'contract_liabilities' => 'Contract Liabilities',
        'deferred_tax_liabilities' => 'Deferred Tax Liabilities',
        'derivative_financial_liabilities' => 'Derivative Financial Liabilities',
        'other_non_current_liabilities' => 'Other Non-current Liabilities',
        'current_tax_liabilities' => 'Current Tax Liabilities',
        'other_current_liabilities' => 'Other Current Liabilities',
        'issued_capital' => 'Issued Capital',
        'retained_earnings' => 'Retained Earnings',
        'treasury_shares' => 'Treasury Shares',
        'other_reserves' => 'Other Reserves',
        'non_controlling_interest' => 'Non-controlling Interest',
        'revenue' => 'Revenue',
        'cost_of_sales' => 'Cost of Sales',
        'other_income' => 'Other Income',
        'selling_distribution_expenses' => 'Selling and Distribution Expenses',
        'administrative_expenses' => 'Administrative Expenses',
        'research_development_expenses' => 'Research and Development Expenses',
        'other_expenses' => 'Other Expenses',
        'finance_income' => 'Finance Income',
        'finance_costs' => 'Finance Costs',
        'share_of_profit_associates_jv' => 'Share of Profit of Associates and Joint Ventures',
        'fair_valuation_gain_loss' => 'Fair Valuation Gain (Loss)',
        'tax_expense' => 'Tax Expense',
        'zakat' => 'Zakat',
        'discontinued_operations' => 'Discontinued Operations',
    ],
    'validation' => [
        'report_group_type_mismatch' => 'The selected report group is not valid for the :type chart type.',
        'in_use' => 'This account cannot be deleted because it is already used by :count ledger entries. Remove or reassign those entries first.',
        'linked_bank' => 'This account cannot be deleted because it is linked to :count money account(s). Reassign those accounts first.',
    ],
];
