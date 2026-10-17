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
    'title' => 'Receipt Designs',
    'subtitle' => 'Configure PDF layouts for single-payment receipts.',
    'table' => [
        'column_name' => 'Design Name',
        'column_layout' => 'Layout',
        'column_is_default' => 'Default',
    ],
    'form' => [
        'create_title' => 'Create Receipt Design',
        'edit_title' => 'Edit Receipt Design',
        'name' => 'Design Name',
        'layout' => 'Receipt Layout',
        'standard_layout' => 'Standard Receipt',
        'compact_layout' => 'Compact Receipt',
        'primary_color' => 'Primary Color',
        'secondary_color' => 'Secondary Color',
        'accent_color' => 'Accent Color',
        'font_family' => 'Font',
        'signature_label' => 'Signature Label',
        'footer_text' => 'Footer Text',
        'show_logo' => 'Show company logo',
        'show_bank_details' => 'Show bank/payment details',
        'show_signature' => 'Show signature area',
        'set_default' => 'Set as receipt default',
    ],
    'status' => [
        'default' => 'Default',
    ],
    'actions' => [
        'preview_pdf' => 'Preview PDF',
    ],
    'messages' => [
        'created' => 'Receipt design created.',
        'updated' => 'Receipt design updated.',
    ],
    'pdf' => [
        'title' => 'OFFICIAL RECEIPT',
        'received_from' => 'Received From',
        'received_amount' => 'Received Amount',
        'receipt_date' => 'Receipt Date',
        'payment_method' => 'Payment Method',
        'linked_invoice' => 'Linked Invoice',
        'bank_account' => 'Bank Account',
        'authorised_signature' => 'Authorised Signature',
        'bank_details_placeholder' => 'Bank account details will appear in this area.',
        'company_placeholder' => 'YOUR COMPANY',
        'sample_customer' => 'Sample Customer Sdn. Bhd.',
        'sample_payment_method' => 'Bank Transfer',
    ],
];
