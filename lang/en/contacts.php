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
    'title' => 'Contact Persons',
    'subtitle' => 'Manage contact persons attached to client companies.',
    'table' => [
        'column_company' => 'Company',
        'column_name' => 'Contact',
        'column_email' => 'Email Address',
        'column_phone' => 'Phone Number',
        'column_position' => 'Job Position',
    ],
    // Deletion is refused while these records still reference the entry.
    'validation' => [
        'in_use' => 'This contact cannot be deleted because it is already used by: :references.',
    ],
    'usage' => [
        'ledger_entries' => 'ledger entries',
        'customer_ledger_entries' => 'customer ledger entries',
        'supplier_ledger_entries' => 'supplier ledger entries',
        'purchase_orders' => 'purchase orders',
        'sales_orders' => 'sales orders',
        'quotation_lines' => 'quotation lines',
    ],
];
