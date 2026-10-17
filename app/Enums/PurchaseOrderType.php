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

namespace App\Enums;

enum PurchaseOrderType: string
{
    case BILL = 'bill';
    case PURCHASE = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::BILL => __('purchase_orders.type.bill'),
            self::PURCHASE => __('purchase_orders.type.purchase'),
        };
    }
}
