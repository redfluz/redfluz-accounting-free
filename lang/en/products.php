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
    'title' => 'Product Catalogue',
    'subtitle' => 'Manage stock items, images, costs, and selling prices.',
    'tabs' => [
        'details' => 'General',
        'variants' => 'Variants',
        'attachments' => 'Attachments',
        'movement' => 'Movement',
    ],
    'attachments' => [
        'title' => 'Product Images',
        'hint' => 'Upload up to 10 JPG, PNG, or WebP images. Each image may be up to 2 MB.',
        'existing' => 'Current Images',
        'new' => 'Add Images',
        'remove' => 'Remove',
        'empty' => 'No images have been uploaded for this product.',
        'shared_hint' => 'Variants use the images attached to the main product.',
    ],
    'variants' => [
        'title' => 'Product Variants',
        'hint' => 'Each completed row becomes a separately selectable stock item with its own SKU. Leave unused rows empty.',
        'row' => 'Variant :number',
        'optional' => 'Optional',
        'name' => 'Variant Name',
        'name_example' => 'Example: Red / XL',
        'details' => 'Variant Details',
        'details_example' => 'Example: Colour: Red, Size: XL',
        'use_main_selling_price' => 'Use main sales price',
        'total_in' => 'Total In',
        'total_out' => 'Total Out',
        'total' => 'Total',
        'same_price' => 'Main price',
        'own_price' => 'Own price',
        'empty' => 'No variants have been created for this product.',
        'count' => ':count variant|:count variants',
    ],
    'validation' => [
        'invalid_variant' => 'One or more variants do not belong to this product.',
        'variant_sku_exists' => 'One or more variant SKUs are already in use.',
        'variant_sku_matches_product' => 'A variant SKU cannot be the same as the main product SKU.',
        'variant_selling_price_required' => 'Enter a sales price or select Use main sales price.',
        'attachment_upload_failed' => 'One or more product images could not be uploaded.',
        'invalid_attachment' => 'One or more selected images do not belong to this product.',
        'maximum_attachments' => 'A product may have no more than :max images.',
        'in_use' => 'This product cannot be deleted because it is already used by: :references.',
    ],
    'usage' => [
        'stock_movements' => 'stock movements',
        'ledger_entries' => 'ledger entries',
        'customer_ledger_entries' => 'customer ledger entries',
        'supplier_ledger_entries' => 'supplier ledger entries',
        'purchase_order_lines' => 'purchase order lines',
        'sales_order_lines' => 'sales order lines',
        'delivery_order_lines' => 'delivery order lines',
        'quotation_lines' => 'quotation lines',
    ],
    'form' => [
        'brand' => 'Brand',
        'brand_hint' => 'Optional for repair and other service items.',
        'supplier' => 'Set Supplier',
        'no_supplier' => 'No supplier selected',
        'supplier_hint' => 'This creditor is used as the set supplier when purchasing this product.',
        'category' => 'Category',
        'product_type' => 'Product Type',
        'select_category' => 'Select category',
        'select_type' => 'Select type',
        'classification_hint' => 'Types are filtered by the selected first-level category.',
        'manage_categories' => 'Manage categories',
        'manage_types' => 'Manage types',
        'default_tax' => 'Default Tax',
        'default_discount' => 'Default Discount',
        'commercial_defaults_hint' => 'These defaults are copied to a new document line. Users may override that line without changing this product.',
        'warranty' => 'Default Warranty',
        'warranty_hint' => 'Copied to Sales Order lines. No warranty blocks RMA; unlimited has no expiry.',
        'create_title' => 'Create Product or Service',
        'edit_title' => 'Edit Product or Service',
        'is_service' => 'This item is a service',
        'is_service_hint' => 'Service items do not create product movement or Cost of Goods Sold entries.',
        'image_preview' => 'Image Preview',
        'default_acquisition_type' => 'Default Purchase Purpose',
        'reorder_level' => 'Reorder Level',
        'reorder_quantity' => 'Reorder Quantity',
        'reorder_hint' => 'Reorder applies only to stock for sale. Internal-use and fixed-asset purchases do not trigger stock reorder.',
        'barcode' => 'Barcode',
        'mpn' => 'Manufacturer Part Number (MPN)',
    ],
    'warranty' => ['none' => 'No warranty', 'unlimited' => 'Unlimited warranty', 'months' => ':count months'],
    'types' => [
        'physical' => 'Physical Product',
        'service' => 'Service',
    ],
    'table' => [
        'column_image' => 'Image',
        'column_sku' => 'SKU',
        'column_name' => 'Product Name',
        'column_brand' => 'Brand',
        'column_category' => 'Category',
        'column_product_type' => 'Product Type',
        'column_type' => 'Item Type',
        'column_base_price' => 'Base Price',
        'column_selling_price' => 'Selling Price',
        'column_description' => 'Description',
        'column_variants' => 'Variants',
    ],
    'movement' => [
        'title' => 'Product Movement',
        'subtitle' => 'Track confirmed receipts, invoice issues, pending movements, and stock balance.',
        'current_balance' => 'Current Saleable Stock',
        'total_received' => 'Total Received',
        'total_issued' => 'Total Issued',
        'pending' => 'Pending In / Out',
        'date' => 'Date',
        'document' => 'Document',
        'item' => 'Product / Variant',
        'source' => 'Movement Source',
        'location' => 'Branch / Warehouse',
        'company' => 'Company',
        'status' => 'Status',
        'bucket' => 'Stock Bucket',
        'quantity_in' => 'In',
        'quantity_out' => 'Out',
        'unit_cost' => 'Cost',
        'running_balance' => 'Stock Balance',
        'empty' => 'No movement has been recorded for this product.',
        'location_title' => 'Stock by Location',
        'location_subtitle' => 'Available saleable stock at each branch or warehouse. Totals include product variants.',
        'location_code' => 'Location Code',
        'location_name' => 'Branch / Warehouse',
        'location_type' => 'Type',
        'available' => 'Available Stock',
        'pending_in' => 'Pending In',
        'pending_out' => 'Pending Out',
        'location_empty' => 'No accessible stock locations were found.',
        'sources' => [
            'sales_order' => 'Sales Order Reservation',
            'delivery_order' => 'Delivery Order',
            'stock_transfer' => 'Stock Transfer',
            'stock_take' => 'Stock Take',
            'purchase_return' => 'Purchase Return',
            'goods_receipt' => 'Goods Received Note',
            'sales_invoice' => 'Sales Invoice',
            'sales_return' => 'Sales Return',
            'rma_receipt' => 'RMA Receipt',
            'rma_replacement' => 'RMA Interchange',
            'rma_return_to_stock' => 'RMA Return to Stock',
            'rma_write_off' => 'RMA Write-off',
        ],
        'buckets' => [
            'saleable' => 'Saleable',
            'quarantine' => 'Quarantine',
            'repair' => 'Repair',
            'damaged' => 'Damaged',
        ],
        'statuses' => [
            'pending' => 'Pending',
            'active' => 'Active',
            'cancelled' => 'Cancelled',
        ],
    ],
];
