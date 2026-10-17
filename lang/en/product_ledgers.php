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
    'status' => [
        'pending' => 'Pending',
        'active' => 'Active',
        'cancelled' => 'Cancelled',
    ],
    'entries' => [
        'cogs_posting' => 'Cost of goods sold for :document',
    ],
    'validation' => [
        'stock_location_required' => 'Assign an active branch or warehouse before reserving or issuing stock.',
        'cogs_account_required' => 'Configure the Cost of Goods Sold account before posting a physical-product invoice.',
        'inventory_account_required' => 'Configure the Inventory account before posting a physical-product invoice.',
        'insufficient_stock' => 'Insufficient stock for :product. Requested: :requested. Available: :available.',
        'insufficient_stock_reservation' => 'Insufficient stock for :product. Requested: :requested. Available after other reservations: :available.',
    ],
];
