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
    'title' => 'Company Management',
    'subtitle' => 'Manage registered client companies and business details.',
    'table' => [
        'column_name' => 'Company Name',
        'column_email' => 'Email Address',
        'column_phone' => 'Phone Number',
        'column_address' => 'Address',
    ],
    'logo' => [
        'label' => 'Company logo',
        'preview' => 'Image preview',
        'upload_hint' => 'JPG, PNG or WebP, maximum 2 MB.',
        'replace_hint' => 'Upload a new image to replace the current logo. Maximum 2 MB.',
        'alt' => ':company logo preview',
    ],
    'roles' => [
        'title' => 'Company Roles',
        'is_debtor' => 'Debtor / Customer',
        'is_creditor' => 'Creditor / Supplier',
        'is_courier' => 'Courier / Carrier',
    ],
    'tabs' => [
        'ar_statement' => 'AR Statement',
        'ap_statement' => 'AP Statement',
    ],
    'statement' => [
        'document_number' => 'Document No.',
        'applied_to' => 'Applied To',
        'product' => 'Product',
        'unknown_product' => 'Unknown product',
        'type' => 'Type',
        'date' => 'Date',
        'debit' => 'Debit',
        'credit' => 'Credit',
        'balance' => 'Balance',
        'closing_balance' => 'Closing Balance',
    ],
    'messages' => [
        'no_ar_transactions' => 'No accounts receivable transactions found for this company.',
        'no_ap_transactions' => 'No accounts payable transactions found for this company.',
    ],
    'show' => [
        'tabs' => [
            'calendar' => 'Calendar',
            'contacts' => 'Contacts',
            'addresses' => 'Addresses',
            'files' => 'Files',
        ],
        'calendar' => [
            'previous' => 'Previous',
            'next' => 'Next',
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'event_details' => ':type Details',
            'date' => 'Date',
            'description' => 'Description',
            'close' => 'Close',
        ],
        'event_types' => [
            'appointment' => 'Appointment',
            'transaction' => 'Transaction',
        ],
        'messages' => [
            'no_contacts' => 'No contacts are associated with this company.',
            'no_appointments' => 'No appointments are scheduled for this company.',
            'no_files' => 'No files are associated with this company.',
        ],
    ],
    'addresses' => [
        'title' => 'Company Branches / Addresses',
        'page_title' => 'Company Addresses',
        'subtitle' => 'Manage multiple billing and delivery addresses for each company.',
        'main_address' => 'Main Address',
        'company_placeholder' => 'Company',
        'all_companies' => 'All companies',
        'search_placeholder' => 'Search branch, phone, or address',
        'create_title' => 'Add Address for :company',
        'edit_title' => 'Edit Address for :company',
        'fields' => [
            'company' => 'Company',
            'name' => 'Branch / Address Name',
            'phone' => 'Phone Number',
            'address' => 'Address',
            'is_default' => 'Use as the default delivery address',
            'default' => 'Default',
        ],
        'actions' => ['add' => 'Add Address', 'manage' => 'Manage Addresses'],
        'messages' => [
            'created' => 'Company address created successfully.',
            'updated' => 'Company address updated successfully.',
            'deleted' => 'Company address deleted successfully.',
            'delete_confirm' => 'Delete this company address?',
            'empty' => 'No additional branches or addresses have been saved.',
        ],
    ],
    // Deletion is refused while these records still reference the entry.
    'validation' => [
        'in_use' => 'This company cannot be deleted because it is already used by: :references.',
    ],
    'usage' => [
        'ledger_entries' => 'ledger entries',
        'customer_ledger_entries' => 'customer ledger entries',
        'supplier_ledger_entries' => 'supplier ledger entries',
        'product_movements' => 'stock movements',
        'purchase_orders' => 'purchase orders',
        'sales_orders' => 'sales orders',
        'quotation_lines' => 'quotation lines',
        'delivery_orders' => 'delivery orders',
        'courier_deliveries' => 'courier deliveries',
        'supplied_products' => 'products it supplies',
    ],
];
