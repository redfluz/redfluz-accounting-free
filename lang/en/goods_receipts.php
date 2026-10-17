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
    'title' => 'Goods Received Notes',
    'subtitle' => 'Record physical products received against approved purchase orders.',
    'table' => [
        'column_document_number' => 'GRN Number',
        'column_purchase_invoice' => 'Purchase Order',
        'column_date' => 'Received Date',
        'column_company' => 'Supplier',
        'column_stock_location' => 'Receiving Location',
        'column_lines' => 'Lines',
        'column_quantity' => 'Quantity',
        'column_status' => 'Status',
        'column_completion' => 'Receipt Status',
        'column_actions' => 'Action',
        'column_sku' => 'SKU',
        'column_product' => 'Product',
        'column_unit_cost' => 'Unit Cost',
        'column_total_cost' => 'Total Cost',
    ],
    'form' => [
        'create_title' => 'Create Goods Received Note',
        'edit_title' => 'Edit Goods Received Note',
        'search_placeholder' => 'Search GRN, purchase order, supplier, SKU, or product',
        'purchase_invoice' => 'Purchase Order',
        'purchase_invoice_hint' => 'Select an approved or partially received purchase order.',
        'received_date' => 'Received Date',
        'stock_location' => 'Receiving Branch / Warehouse',
        'product' => 'Physical Product',
        'ordered_quantity' => 'Ordered',
        'previously_received' => 'Previously Received',
        'remaining_quantity' => 'Remaining',
        'available_quantity' => 'Available',
        'quantity' => 'Receive Now',
        'unit_cost' => 'Purchase Order Unit Cost',
        'attachments' => 'Delivery Order / Supporting Documents',
        'attachments_hint' => 'Upload up to 10 PDF, Office, text, or image files. Each file may be up to 5 MB.',
        'existing_attachments' => 'Existing Documents',
        'notes' => 'Receiving Notes',
    ],
    'actions' => [
        'remove_attachment' => 'Remove',
        'place_stock' => 'Place Stock',
        'save_placement' => 'Save Placement',
        'update_placement' => 'Update Location',
    ],
    'placements' => [
        'title' => 'Stock Placement',
        'subtitle' => 'Assign received GRN items to their Floor, Line, and Bin/Pallet.',
        'empty' => 'All received stock has a storage location.',
    ],
    'placement' => [
        'title' => 'Place Received Stock',
        'subtitle' => 'Assign every received item to its Floor, Line, and Bin/Pallet before posting the GRN.',
    ],
    'completion' => [
        'label' => 'Filter by receipt status',
        'all' => 'All receipt statuses',
        'complete' => 'Complete',
        'partial' => 'Partial',
        'none' => 'Not received',
    ],

    'adjustment' => [
        'title' => 'Adjust Received Quantity',
        'subtitle' => 'Correct the quantities on this posted receipt. The difference is booked as a separate movement.',
        'column_current' => 'Currently Received',
        'column_max' => 'Maximum',
        'column_headroom' => 'Headroom',
        'column_corrected' => 'Corrected Quantity',
        'column_difference' => 'Difference',
        'column_action' => 'Action',
        'recorded' => 'Recorded corrections',
        'no_corrections' => 'No corrections have been booked against this receipt yet.',
        'post_corrections' => 'Post corrections',
        'discard' => 'Discard',
        'default_note' => 'Quantity correction for a posted goods receipt.',
        'no_change' => 'Enter a corrected quantity that differs from the current one.',
        'validation' => [
            'title' => 'Correction validation',
            'not_posted' => 'Only a posted goods receipt can be adjusted.',
            'insufficient_stock' => 'The correction would take :sku below the available stock of :available.',
            'exceeds_ordered' => 'The corrected quantity cannot exceed the remaining orderable quantity of :quantity.',
        ],
    ],

    'line_state' => [
        'complete' => 'Complete',
        'partial' => 'Partial',
        'none' => 'Not received',
        'column_ordered' => 'Ordered',
        'column_received' => 'Received',
        'column_shortfall' => 'Outstanding',
        'column_state' => 'Status',
        'pending_correction' => 'Correction pending',
    ],

    'messages' => [
        'delete_confirmation' => 'Delete this pending goods receipt? Its received quantities will be discarded.',
        'saved' => 'Corrections recorded. Post them to change stock.',
        'posted' => 'Corrections posted and the purchase order recalculated.',
        'discarded' => 'Correction discarded.',
        'empty' => 'No goods received notes were found.',
        'select_invoice' => 'Select a purchase order to load its outstanding quantities.',
        'no_physical_lines' => 'This purchase order has no linked physical product lines.',
        'manual_product_required' => 'This purchase order uses description lines. Select the physical product received below.',
        'no_stock_location' => 'You are not assigned to an active branch or warehouse. A location assignment is required before receiving stock.',
        'approve_confirmation' => 'Confirm this stock receipt? The received quantities will become active inventory and the GRN can no longer be edited.',
    ],
    'validation' => [
        'line_required' => 'Enter a receiving quantity for at least one product.',
        'product_required' => 'Select a product for this receiving line.',
        'quantity_required' => 'Enter the received quantity.',
        'stock_product_required' => 'GRN accepts physical products only. Service items do not have stock movement.',
        'no_physical_lines' => 'The selected purchase order has no physical product lines.',
        'product_not_on_invoice' => 'This product does not belong to the selected purchase order.',
        'quantity_exceeds_remaining' => 'The received quantity cannot exceed the remaining quantity of :remaining.',
        'posted_delete_forbidden' => 'A posted goods receipt cannot be deleted because its quantities have already moved stock. Adjust the receipt instead.',
        'invoice_posted_lock' => 'This Goods Receipt cannot be changed because a linked Purchase Invoice has already been posted. Use a GRN Adjustment for stock corrections.',
        'stock_location_required' => 'Select a branch or warehouse assigned to you.',
        'attachment_limit' => 'A Goods Received Note may contain no more than 10 documents.',
        'invalid_placement' => 'The selected Floor, Line, and Bin/Pallet combination is invalid.',
        'placement_required' => 'Assign all received items to a Bin/Pallet before posting the GRN.',
        'attachment_upload_failed' => 'A supporting document could not be uploaded. Please try again.',
    ],
];
