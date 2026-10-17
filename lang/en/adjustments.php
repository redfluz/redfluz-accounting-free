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
    'title' => 'Sales Adjustments',
    'subtitle' => 'Manage and print debit notes and credit notes linked to sales invoices.',
    'table' => [
        'column_entry' => 'Entry',
    ],
    'form' => [
        'linked_invoice' => 'Linked Sales Invoice',
    ],
    'sub_type' => [
        'increase_adjustment' => 'Debit Note',
        'decrease_adjustment' => 'Credit Note',
    ],
    'messages' => [
        'design_required' => 'Create a sales-invoice PDF design before printing this adjustment.',
    ],
    'validation' => [
    ],
    'pdf' => [
        'debit_note_title' => 'DEBIT NOTE',
        'credit_note_title' => 'CREDIT NOTE',
        'issued_to' => 'Issued To',
        'linked_invoice' => 'Linked Invoice',
        'date' => 'Date',
        'description' => 'Description',
        'quantity' => 'Quantity',
        'unit_price' => 'Unit Price',
        'amount' => 'Amount',
        'total' => 'Total',
        'company_placeholder' => 'YOUR COMPANY',
    ],

    'cancelled_title' => 'Cancelled Adjustments',
    'cancelled_subtitle' => 'View all cancelled sales adjustments.',
    'history_title' => 'Posted Sales Adjustments',
    'history_subtitle' => 'View all posted sales adjustments.',
];
