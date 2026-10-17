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
    'title' => 'Financial Report Designs',
    'subtitle' => 'Manage formula-driven financial report templates and their ordered rows.',
    'create_title' => 'Create Financial Report Design',
    'edit_title' => 'Edit Financial Report Design',
    'create_row_title' => 'Add Report Row',
    'edit_row_title' => 'Edit Report Row',
    'rows_title' => 'Report Rows',
    'rows_subtitle' => 'Rows are calculated and displayed in ascending line order.',
    'empty' => 'No financial report designs found.',
    'rows_empty' => 'No report rows configured for this design.',
    'table' => [
        'column_code' => 'Code',
        'column_name' => 'Name',
        'column_slug' => 'Report Slug',
        'column_basis' => 'Calculation',
        'column_default' => 'Default',
        'column_rows' => 'Rows',
        'column_line' => 'Line',
        'column_parent' => 'Parent',
        'column_row_type' => 'Row Type',
        'column_source' => 'Account Source',
        'column_formula' => 'Formula',
    ],
    'form' => [
        'search_placeholder' => 'Search code, name, or report slug...',
        'report_help' => 'Create the report first, then configure its ordered rows on the edit screen.',
        'code' => 'Report Code',
        'name' => 'Report Name',
        'slug' => 'Report Slug',
        'slug_help' => 'Use a unique slug for a custom report, or a standard slug for an alternative standard design.',
        'calculation_basis' => 'Calculation Basis',
        'calculation_basis_help' => 'Choose how account values are calculated for this report.',
        'is_default' => 'Use as the default design for this report slug',
        'line' => 'Line Number',
        'row_code' => 'Row Code',
        'row_name' => 'Row Name',
        'parent' => 'Parent Row',
        'no_parent' => 'No parent row',
        'row_type' => 'Row Type',
        'total_type' => 'Total Style',
        'chart' => 'Chart Account',
        'chart_type' => 'Chart Type',
        'formula' => 'Formula',
        'formula_help' => 'Build the formula using account balances, chart type totals, report group totals, or an earlier formula row.',
        'formula_examples' => [
            'account' => 'One account balance',
            'type' => 'Total for every account under one chart type',
            'group' => 'Total for every account under one report group',
            'row' => 'Value calculated by an earlier formula row',
            'calculation' => 'Example net profit calculation',
            'aliases' => 'TYPE and GROUP are accepted aliases for SUM_TYPE and SUM_GROUP.',
            'operators' => 'Combine tags using +, -, *, /, and parentheses.',
            'available_types' => 'Available SUM_TYPE values',
            'available_groups' => 'Available SUM_GROUP values',
        ],
        'report_group' => 'Report Group',
        'report_group_help' => 'The group key selects accounts. The row name remains the editable label shown in the report.',
        'notes' => 'Notes',
        'select' => 'Please select',
    ],
    'actions' => [
        'add_report' => 'New Report Design',
        'add_row' => 'Add Report Row',
        'view_output' => 'View Output',
    ],
    'output_title' => 'Report Output',
    'calculation_basis' => [
        'period' => 'Period Activity',
        'as_of' => 'Balance as of Date',
        'cash_flow' => 'Direct Cash Flow',
    ],
    'status' => [
        'default' => 'Default',
        'alternative' => 'Alternative',
    ],
    'row_type' => [
        'charts' => 'Chart Account',
        'formula' => 'Formula',
        'notes' => 'Notes',
        'none' => 'Heading',
        'categories' => 'Category',
        'type' => 'Chart Type',
        'group' => 'Report Group',
    ],
    'total_type' => [
        'sub_total' => 'Sub Total',
        'grand_total' => 'Grand Total',
        'none' => 'Normal Row',
    ],
    'chart_type' => [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expenses' => 'Expenses',
        'none' => 'None',
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
    'alerts' => [
        'delete_report' => 'This will remove the report design and all of its configured rows.',
        'delete_row' => 'This will remove the selected report row.',
    ],
    'validation' => [
        'invalid_formula' => 'The formula contains an invalid tag or mathematical expression.',
        'parent_same_report' => 'The parent row must belong to the same report design.',
        'parent_cycle' => 'The selected parent would create a circular report hierarchy.',
    ],
];
