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
    'title' => 'Chart Types',
    'subtitle' => 'Manage classifications and attributes for chart accounts.',
    'table' => [
        'column_name' => 'Name',
        'column_type' => 'Type',
        'column_flags' => 'Account Classifications',
    ],
    'flags' => [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expenses' => 'Expenses',
        'default' => 'Default',
        'bank' => 'Bank',
    ],
    'validation' => [
        'in_use' => 'This account type cannot be deleted because :count account(s) are still assigned to it. Reassign them first.',
    ],
];
