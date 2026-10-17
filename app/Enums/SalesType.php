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

enum SalesType: string
{
    case PRODUCT = 'product';
    case SERVICE = 'service';
    case RECURRING = 'recurring';
    case DURATION = 'duration';

    public function label(): string
    {
        return match ($this) {
            self::PRODUCT => __('common.sales_type.product'),
            self::SERVICE => __('common.sales_type.service'),
            self::RECURRING => __('common.sales_type.recurring'),
            self::DURATION => __('common.sales_type.duration'),
        };
    }
}
