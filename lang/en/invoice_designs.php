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
    'title' => 'Invoice Designs',
    'subtitle' => 'Configure PDF layouts for sales and purchase invoices.',
    'table' => [
        'column_name' => 'Design Name',
        'column_document_type' => 'Document Type',
        'column_layout' => 'Layout',
        'column_is_default' => 'Default',
    ],
    'form' => [
        'create_title' => 'Create Invoice Design',
        'edit_title' => 'Edit Invoice Design',
        'name' => 'Design Name',
        'document_type' => 'Document Type',
        'sales_invoice' => 'Sales Invoice',
        'purchase_invoice' => 'Purchase Invoice',
        'choose_layout' => 'Choose a Layout',
        'primary_color' => 'Primary Color',
        'secondary_color' => 'Secondary Color',
        'accent_color' => 'Accent Color',
        'font_family' => 'Font',
        'footer_text' => 'Footer Text',
        'show_logo' => 'Show company logo',
        'show_bank_details' => 'Show bank details',
        'show_tax_details' => 'Show tax details',
        'set_default' => 'Set as default',
    ],
    'status' => [
        'default' => 'Default',
    ],
    'layouts' => [
        'classic' => ['label' => 'Classic Blue', 'description' => 'Split header, lined table'],
        'modern' => ['label' => 'Modern Teal', 'description' => 'Banner header, zebra table'],
        'minimal' => ['label' => 'Minimal Slate', 'description' => 'Compact header, minimal table'],
    ],
    'actions' => [
        'preview_pdf' => 'Preview PDF',
    ],
    'messages' => [
        'created' => 'Invoice design created.',
        'updated' => 'Invoice design updated.',
    ],
    'pdf' => [
        'sales_title' => 'INVOICE',
        'purchase_title' => 'PURCHASE INVOICE',
        'bill_to' => 'Bill To',
        'date' => 'Date',
        'due_date' => 'Due Date',
        'description' => 'Description',
        'quantity' => 'Qty',
        'unit_price' => 'Unit Price',
        'amount' => 'Amount',
        'total' => 'Total',
        'payment_details' => 'Payment Details',
        'tax_details_placeholder' => 'Tax details will appear in this area.',
        'bank_details_placeholder' => 'Bank account details will appear in this area.',
        'company_placeholder' => 'YOUR COMPANY',
        'sample_customer' => 'Sample Customer Sdn. Bhd.',
        'sample_address' => 'Kuala Lumpur, Malaysia',
        'sample_line_one' => 'Professional service package',
        'sample_line_two' => 'Monthly support service',
    ],
];
