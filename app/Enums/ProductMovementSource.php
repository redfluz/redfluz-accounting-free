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

enum ProductMovementSource: string
{
    case SALES_ORDER = 'sales_order';
    case DELIVERY_ORDER = 'delivery_order';
    case SALES_INVOICE = 'sales_invoice';
    case SALES_RETURN = 'sales_return';
    case GOODS_RECEIPT = 'goods_receipt';
    case PURCHASE_RETURN = 'purchase_return';
    case STOCK_TRANSFER = 'stock_transfer';
    case STOCK_TAKE = 'stock_take';
}
