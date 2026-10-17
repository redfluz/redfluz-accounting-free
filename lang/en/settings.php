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
    'title' => 'System Settings',
    'subtitle' => 'Company details printed on your documents, invoices and PDFs.',
    'read_only' => 'You do not have permission to change the system settings.',

    'section' => [
        'company' => 'Company',
        'address' => 'Address',
        'document' => 'Documents',
    ],

    'fields' => [
        'mobile' => 'Mobile',
        'postcode' => 'Postcode',
        'city' => 'City',
        'state' => 'State',
        'country' => 'Country',
    ],

    'table' => [
        'column_company_name' => 'Name',
        'column_company_email' => 'Email',
        'column_company_phone' => 'Phone',
        'column_company_fax' => 'Fax',
        'column_company_website' => 'Website',
        'column_company_registration_no' => 'Registration No.',
        'column_company_tax_no' => 'Tax No.',
        'column_currency' => 'Currency',
        'column_logo_path' => 'Logo',
        'column_address' => 'Address',
        'column_short_date_format' => 'Short Date Format',
        'column_long_date_format' => 'Long Date Format',
    ],

    'help' => [
        'short_date_format' => 'e.g. d/m/Y = 14/10/2026, Y-m-d = 2026-10-14',
        'long_date_format' => 'e.g. d/m/Y H:i:s = 14/10/2026 15:30:00',
    ],
];
