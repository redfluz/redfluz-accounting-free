<?php

return [
    'title' => 'Product Categories',
    'subtitle' => 'Manage the first-level grouping used to organise products.',
    'confirm_delete' => 'Delete this unused category?',
    'form' => [
        'create_title' => 'Create Product Category',
        'edit_title' => 'Edit Product Category',
        'code' => 'Category Code',
        'name' => 'Category Name',
        'is_active' => 'Active',
    ],
    'table' => [
        'code' => 'Code',
        'name' => 'Category',
        'types' => 'Types',
        'products' => 'Products',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Active', 'inactive' => 'Inactive'],
    'validation' => [
        'in_use' => 'This category has product types or products. Deactivate it instead of deleting it.',
    ],
];
