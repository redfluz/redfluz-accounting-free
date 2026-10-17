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
    'title' => 'Business dashboard',
    'kpi' => [
        'revenue_collected' => 'Revenue Collected',
        'pending_revenue' => 'Pending Revenue',
        'total_transactions' => 'Total Transactions',
        'metrics_count' => 'Companies / Contacts / Products',
    ],
    'charts' => [
        'revenue' => 'Revenue',
        'revenue_by_month' => 'Revenue by Month',
        'ledger_status_split' => 'Ledger Status Split',
        'revenue_by_type' => 'Revenue by Subtype',
        'top_products' => 'Top Products',
        'revenue_by_company' => 'Revenue by Company',
    ],
    'recent' => [
        'title' => 'Transactions for :month',
        'entries_this_month' => 'Entries this month',
        'total_for' => 'Total for :month',
        'no_entries_month' => 'No transactions recorded for :month.',
        'no_entries' => 'No ledger entries yet.',
        'table' => [
            'no_title' => 'Untitled',
            'date' => 'Date',
            'company' => 'Company',
            'product' => 'Product',
            'contact' => 'Contact',
            'type' => 'Type / Subtype',
            'total' => 'Total',
            'status' => 'Status',
        ],
    ],
    'outstanding' => [
        'ap_title' => 'Top 5 Supplier Outstanding',
        'ar_title' => 'Top 5 Customer Outstanding',
        'no_ap' => 'No supplier outstanding balances.',
        'no_ar' => 'No customer outstanding balances.',
    ],
    'status' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
        'posted' => 'Posted',
    ],
];
