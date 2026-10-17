<?php

return [
    'title' => 'Product Packages',
    'subtitle' => 'Reusable product and quantity templates for quotations and sales orders.',
    'create_title' => 'Create Product Package',
    'edit_title' => 'Edit Product Package',
    'empty' => 'No product packages were found.',
    'delete_confirm' => 'Delete this product package?',
    'form' => [
        'code' => 'Package Code',
        'name' => 'Package Name',
        'description' => 'Description',
        'active' => 'Available in sales forms',
        'product' => 'Product',
        'quantity' => 'Quantity',
    ],
    'table' => [
        'items' => 'Items',
        'status' => 'Status',
        'current_price' => 'Current Unit Price',
    ],
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'sales_form' => [
        'label' => 'Product Package',
        'placeholder' => 'Choose a package',
        'apply' => 'Apply Package',
        'manage' => 'Manage Packages',
        'hint' => 'Copies the package products and quantities into the lines below using current prices.',
        'replace_confirm' => 'Applying this package will replace the current product lines. Continue?',
    ],
    'validation' => [
        'items_required' => 'Add at least one product to the package.',
        'quantity_required' => 'Enter a quantity for every selected product.',
    ],
];
