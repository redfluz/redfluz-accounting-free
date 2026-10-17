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
    'title' => 'Role Management',
    'subtitle' => 'Define user roles and access control levels across the intranet.',
    'table' => [
        'column_id' => 'ID',
        'column_name' => 'Name',
        'column_dashboard' => 'Dashboard',
        'column_description' => 'Description',
        'column_user_name' => 'User Name',
        'column_email' => 'Email',
        'column_department' => 'Department',
        'column_created_at' => 'Created At',
        'column_updated_at' => 'Updated At',
        'column_is_default' => 'Is Default',
    ],
    'grid' => [
        'assigned_user' => 'User',
    ],
    'show' => [
        'title' => 'Role Details',
        'subtitle' => 'View every user and department assigned to this role.',
        'total_users' => 'Total Users',
        'users_title' => 'Assigned Users',
        'empty_users' => 'No users are assigned to this role.',
    ],
];
