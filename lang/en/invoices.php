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
    'title' => 'Invoices',
    'subtitle' => 'Manage single-product sales and purchase invoices.',
    'table' => [
        'column_entry' => 'Entry',
    ],
    'form' => [
        'create_title' => 'Create Invoice',
        'edit_title' => 'Edit Sales Invoice',
        'accounting_hint' => 'Leave an account blank to use the configured default chart account.',
        'quick_customer_name' => 'Customer name',
        'quick_supplier_name' => 'Supplier name',
    ],
    'sub_type' => [
        'sales_invoice' => 'Sales Invoice',
        'purchase_invoice' => 'Purchase Invoice',
    ],
    'actions' => [
        'quick_customer' => 'Quick add customer',
        'add_customer' => 'Add Customer',
        'quick_supplier' => 'Quick add supplier',
        'add_supplier' => 'Add Supplier',
    ],
    'messages' => [
        'design_required' => 'Create an invoice design for this document type before printing.',
    ],
    'deduct_inventory' => [
        'label' => 'Deduct Inventory',
        'help' => 'Reduce stock when this invoice is posted. Leave off for credit-term sales where the goods are still in the warehouse and a delivery order will reduce stock on dispatch.',
        'confirm' => 'Deduct stock for this invoice? This can only be done once and cannot be undone by editing the invoice.',
    ],
    'validation' => [
        'accounts_must_differ' => 'The debit and credit accounts must be different.',
        'contact_company_invalid' => 'The selected contact does not belong to the selected company.',
        'debit_account_invalid' => 'The selected debit account is not valid for this invoice type.',
        'credit_account_invalid' => 'The selected credit account is not valid for this invoice type.',
        'attachment_type_invalid' => 'The attachment must be a PNG, JPG, JPEG, PDF, DOC, or DOCX file.',
        'company_name_required' => 'Enter the company name.',
        'company_create_failed' => 'The company could not be created. Please try again.',
    ],
    'pdf' => [
        'sales_invoice_title' => 'SALES INVOICE',
        'purchase_invoice_title' => 'PURCHASE INVOICE',
        'company' => 'Company',
        'date' => 'Date',
        'description' => 'Description',
        'quantity' => 'Quantity',
        'unit_price' => 'Unit Price',
        'amount' => 'Amount',
        'total' => 'Total',
        'tax_number' => 'Tax Number',
        'payment_details' => 'Payment Details',
        'bank_details_placeholder' => 'Please refer to the company payment instructions.',
        'company_placeholder' => 'Company Name',
    ],
];
