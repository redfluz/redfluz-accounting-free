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

enum LedgerType: string
{
    case INVOICE = 'invoice';
    case PAYMENT = 'payment';
    case JOURNAL = 'journal';
    case CLAIM = 'claim';
    case SALARY = 'salary';
    case STOCK_TAKE = 'stock_take';
    case FIXED_ASSET_WRITE_OFF = 'fixed_asset_write_off';
    case FIXED_ASSET_DEPRECIATION = 'fixed_asset_depreciation';
    case FIXED_ASSET_APPRECIATION = 'fixed_asset_appreciation';
    case FIXED_ASSET_IMPAIRMENT = 'fixed_asset_impairment';
    case FIXED_ASSET_DISPOSAL = 'fixed_asset_disposal';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE => 'Invoice',
            self::PAYMENT => 'Payment',
            self::JOURNAL => 'Journal',
            self::CLAIM => 'Claim',
            self::SALARY => 'Salary',
            self::STOCK_TAKE => 'Stock Take',
            self::FIXED_ASSET_WRITE_OFF => 'Fixed Asset Write-off',
            self::FIXED_ASSET_DEPRECIATION => 'Fixed Asset Depreciation',
            self::FIXED_ASSET_APPRECIATION => 'Fixed Asset Appreciation',
            self::FIXED_ASSET_IMPAIRMENT => 'Fixed Asset Impairment',
            self::FIXED_ASSET_DISPOSAL => 'Fixed Asset Disposal',
        };
    }
}
