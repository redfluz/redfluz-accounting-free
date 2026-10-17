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
    'tabs' => ['general' => 'General', 'attachment' => 'Attachment', 'payment' => 'Payment', 'shipping' => 'Shipping', 'accounting' => 'Accounting'],
    'title' => 'Quotations',
    'subtitle' => 'Prepare customer quotations and continue accepted quotations into fulfilment or service invoicing.',
    'table' => [
        'column_quotation_number' => 'Quotation Number',
        'column_date' => 'Date',
        'column_expiry_date' => 'Valid Until',
        'column_company' => 'Company',
        'column_reference_number' => 'Customer PO Number',
        'column_status' => 'Status',
        'column_total_amount' => 'Total Amount',
        'column_actions' => 'Action',
        'column_entry' => 'Entry',
        'converted_to' => 'Converted to',
    ],
    'form' => [
        'create_title' => 'Create Quotation',
        'edit_title' => 'Edit Quotation',
        'search_placeholder' => 'Search quotation number, customer PO number, or company',
        'company' => 'Company',
        'contact' => 'Contact',
        'product' => 'Product',
        'description' => 'Description',
        'attachment' => 'Quotation Attachment',
        'reference_number' => 'Customer PO Number',
        'date' => 'Quotation Date',
        'expiry_date' => 'Valid Until',
        'status' => 'Status',
        'quantity' => 'Quantity',
        'unit_price' => 'Unit Price',
        'total_amount' => 'Total Amount',
        'debit_account' => 'Debit Account',
        'credit_account' => 'Credit Account',
        'debit_amount' => 'Debit Amount',
        'credit_amount' => 'Credit Amount',
        'use_default_account' => 'Use configured default account',
        'notes' => 'Description / Notes',
        'quick_company_name' => 'Customer name',
    ],
    'status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
        'converted' => 'Converted',
    ],
    'actions' => [
        'view' => 'View',
        'convert' => 'Convert to Invoice',
        'convert_sales_order' => 'Create Sales Order',
        'open_invoice' => 'Open Invoice',
        'quick_company' => 'Quick add customer',
        'add_company' => 'Add Customer',
    ],
    'messages' => [
        'empty' => 'No quotations were found.',
        'accepted_required' => 'Only an accepted quotation can be converted into a sales invoice.',
        'convert_confirmation' => 'Convert this quotation into a pending sales invoice?',
        'convert_sales_order_confirmation' => 'Create a draft Sales Order from this quotation?',
        'design_required' => 'Create a sales invoice design before printing this quotation.',
        'lines_required' => 'Add at least one product or description line.',
        'line_values_required' => 'Enter both quantity and unit price for each completed line.',
    ],
    'validation' => [
        'company_name_required' => 'Enter the customer name.',
        'company_create_failed' => 'The customer could not be created. Please try again.',
    ],
    'pdf' => [
        'title' => 'QUOTATION',
        'quoted_to' => 'Quoted To',
        'date' => 'Quotation Date',
        'valid_until' => 'Valid Until',
        'reference_number' => 'Customer PO Number',
        'description' => 'Description',
        'quantity' => 'Quantity',
        'unit_price' => 'Unit Price',
        'amount' => 'Amount',
        'total' => 'Total',
        'tax_number' => 'Tax Number',
        'notes' => 'Terms / Notes',
        'company_placeholder' => 'Company Name',
    ],
];
