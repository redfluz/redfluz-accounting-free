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

enum DashboardRole: string
{
    case ADMIN = 'admin';
    case ALL = 'all';
    case FINANCE = 'finance';
    case HOME = 'home';
    case MANAGER = 'manager';
    case PERSONNEL = 'personnel';
    case INVENTORY = 'inventory';
    case PURCHASE = 'purchase';
    case SALES = 'sales';
    case BANK = 'bank';
    case EDITOR = 'editor';
    case USER = 'user';
    case CRM = 'crm';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::ALL => 'All Dashboards',
            self::FINANCE => 'Finance',
            self::HOME => 'Home',
            self::MANAGER => 'Manager',
            self::PERSONNEL => 'Personnel',
            self::INVENTORY => 'Inventory',
            self::PURCHASE => 'Purchase',
            self::SALES => 'Sales',
            self::BANK => 'Bank',
            self::EDITOR => 'Editor',
            self::USER => 'User',
            self::CRM => 'CRM',
        };
    }
}
