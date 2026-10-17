<?php

return [
    'title' => 'Product Types',
    'subtitle' => 'Manage second-level product types and their parent categories.',
    'confirm_delete' => 'Delete this unused product type?',
    'form' => [
        'create_title' => 'Create Product Type',
        'edit_title' => 'Edit Product Type',
        'category' => 'Parent Category',
        'select_category' => 'Select category',
        'code' => 'Type Code',
        'name' => 'Type Name',
        'is_active' => 'Active',
    ],
    'table' => [
        'code' => 'Code',
        'name' => 'Product Type',
        'category' => 'Category',
        'products' => 'Products',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Active', 'inactive' => 'Inactive'],
    'validation' => [
        'in_use' => 'This product type is used by one or more products. Deactivate it instead of deleting it.',
    ],
];
