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
    'title' => 'Brands',
    'subtitle' => 'Manage the manufacturers and brands assigned to products.',
    'products_title' => 'Products using this brand',
    'products_empty' => 'No products use this brand.',
    'form' => [
        'create_title' => 'Create Brand',
        'edit_title' => 'Edit Brand',
        'name' => 'Brand Name',
        'description' => 'Description',
        'is_active' => 'Active',
        'none' => 'No Brand',
    ],
    'table' => [
        'name' => 'Brand',
        'description' => 'Description',
        'products' => 'Products',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Active', 'inactive' => 'Inactive'],
    'validation' => [
        'brand_in_use' => 'This brand is used by one or more products. Deactivate it instead of deleting it.',
        'name_already_exists' => 'A brand with this name already exists.',
    ],
];
