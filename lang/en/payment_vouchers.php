<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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
    'title' => 'Payment Voucher',

    'validation' => [
        'lines_required' => 'A payment voucher must carry at least one accounting line before it can be posted.',
    ],

    /*
     * The audit trail printed at the foot of a receipt.
     *
     * These labels head the same table on screen, in the printed copy and in the
     * spreadsheet, so a receipt says the same thing wherever it is read.
     */
    'audit' => [
        'role' => 'Role',
        'name' => 'Name',
        'date' => 'Date',
        'signature' => 'Signature',

        'created_by' => 'Created By',
        'created_at' => 'Created On',
        'approved_at' => 'Approved On',
        'cancelled_at' => 'Cancelled On',
        'undone_at' => 'Restored On',
        'approved_by' => 'Approved By',
        'cancelled_by' => 'Cancelled By',
        'undone_by' => 'Restored By',
    ],
];
