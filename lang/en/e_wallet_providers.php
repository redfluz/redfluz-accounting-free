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
    'title' => 'E-Wallet Providers',
    'subtitle' => 'Manage the e-wallet issuers assigned to bank accounts.',
    'accounts_title' => 'Accounts using this provider',
    'accounts_empty' => 'No accounts use this provider.',
    'form' => [
        'create_title' => 'Create E-Wallet Provider',
        'edit_title' => 'Edit E-Wallet Provider',
        'name' => 'Provider Name',
        'country' => 'Country',
        'identifier_label' => 'Identifier Label',
        'chart' => 'Chart of Account',
        'chart_placeholder' => 'Select a chart of account',
        'is_active' => 'Active',
    ],
    'table' => [
        'name' => 'Provider',
        'country' => 'Country',
        'identifier_label' => 'Identifier',
        'chart' => 'Chart',
        'accounts' => 'Accounts',
        'status' => 'Status',
    ],
    'accounts_table' => [
        'account' => 'Account',
        'number' => 'Account Number',
        'chart' => 'Chart',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Active', 'inactive' => 'Inactive'],
    'validation' => [
        'provider_in_use' => 'This provider is used by one or more accounts. Deactivate it instead of deleting it.',
    ],
];
