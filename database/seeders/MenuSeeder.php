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

/**
 * @noinspection ALL
 *
 * @phpstan-ignore-file
 */

namespace Database\Seeders;

use App\Enums\FileResources;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['Super Admin', 'Manager', 'Editor', 'User', 'Finance'];

        $structure = [
            'SYSTEM' => [
                'groupings' => [
                    'ACCESS CONTROL' => ['icon' => 'shield-check', 'pages' => ['ROLE', 'USER']],
                    'CONFIGURATION' => ['icon' => 'sliders-horizontal', 'pages' => ['SETTING']],
                ],
                'icon' => 'settings',
                'access' => $roles,
                'pages' => [
                    [
                        'name' => 'ROLE',
                        'route_name' => FileResources::ROLES->value,
                        'icon' => 'badge-check',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'USER',
                        'route_name' => FileResources::USERS->value,
                        'icon' => 'users-round',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'SETTING',
                        'route_name' => FileResources::SETTINGS->value,
                        'icon' => 'building-2',
                        'access' => $this->allAccess($roles, ['read', 'update']),
                    ],
                ],
            ],
            'MENU' => [
                'groupings' => [
                    'PERMISSIONS' => ['icon' => 'shield-check', 'pages' => ['FOLDER PERMISSION', 'PAGE PERMISSION', 'PAGE GROUPING PERMISSION']],
                ],
                'icon' => 'menu',
                'access' => $roles,
                'pages' => [
                    [
                        'name' => 'FOLDER PERMISSION',
                        'route_name' => FileResources::FOLDER_PERMISSIONS->value,
                        'icon' => 'folder-cog',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'PAGE PERMISSION',
                        'route_name' => FileResources::PAGE_PERMISSIONS->value,
                        'icon' => 'file-check-2',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'PAGE GROUPING PERMISSION',
                        'route_name' => FileResources::PAGE_GROUPING_PERMISSIONS->value,
                        'icon' => 'shield-check',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                ],
            ],
            'CHART OF ACCOUNTS' => [
                'groupings' => [
                    'STRUCTURE' => ['icon' => 'chart-column', 'pages' => ['CHART', 'CHART TYPE', 'CHART SETTING']],
                    'REPORTS' => ['icon' => 'bar-chart-3', 'pages' => ['PROFIT & LOSS', 'BALANCE SHEET', 'TRIAL BALANCE']],
                ],
                'icon' => 'bar-chart-3',
                'access' => $roles,
                'pages' => [
                    [
                        'name' => 'CHART',
                        'route_name' => FileResources::CHARTS->value,
                        'icon' => 'chart-column',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'CHART TYPE',
                        'route_name' => FileResources::CHART_TYPES->value,
                        'icon' => 'pie-chart',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'CHART SETTING',
                        'route_name' => FileResources::CHART_SETTINGS->value,
                        'icon' => 'settings-2',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'PROFIT & LOSS',
                        'route_name' => FileResources::PROFIT_LOSS->value,
                        'icon' => 'arrow-up-right',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'BALANCE SHEET',
                        'route_name' => FileResources::BALANCE_SHEET->value,
                        'icon' => 'scale',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'TRIAL BALANCE',
                        'route_name' => FileResources::TRIAL_BALANCE->value,
                        'icon' => 'columns-3',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                ],
            ],
            'FINANCE' => [
                'groupings' => [
                    'OPERATIONS' => ['icon' => 'notebook-pen', 'pages' => ['JOURNAL', 'CANCELLED JOURNAL ENTRIES', 'JOURNAL ENTRIES HISTORY']],
                    'ACCOUNTS' => ['icon' => 'book-open', 'pages' => ['LEDGERS', 'TAXES', 'DISCOUNTS', 'CURRENCIES']],
                ],
                'icon' => 'landmark',
                'access' => $roles,
                'pages' => [
                    [
                        'name' => 'JOURNAL',
                        'route_name' => FileResources::JOURNAL_ENTRIES->value,
                        'icon' => 'notebook-pen',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'CANCELLED JOURNAL ENTRIES',
                        'route_name' => FileResources::JOURNAL_ENTRIES_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'JOURNAL ENTRIES HISTORY',
                        'route_name' => FileResources::JOURNAL_ENTRIES_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'LEDGERS',
                        'route_name' => FileResources::LEDGERS->value,
                        'icon' => 'book-open',
                        'access' => $this->allAccess($roles, ['read', 'update']),
                    ],
                    [
                        'name' => 'TAXES',
                        'route_name' => FileResources::TAXES->value,
                        'icon' => 'percent',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'DISCOUNTS',
                        'route_name' => FileResources::DISCOUNTS->value,
                        'icon' => 'badge-percent',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'CURRENCIES',
                        'route_name' => FileResources::CURRENCIES->value,
                        'icon' => 'coins',
                        'access' => $this->allAccess($roles, ['read', 'remove']),
                    ],
                ],
            ],
            'BANK' => [
                'groupings' => [
                    'BANKING' => [
                        'icon' => 'landmark',
                        'pages' => ['BANK ACCOUNTS', 'CASH ACCOUNTS', 'E-WALLET ACCOUNTS', 'CASH LEDGER', 'BANK LEDGER', 'E-WALLET LEDGER', 'CASH TRANSFERS', 'BANK TRANSFERS', 'BANK JOURNALS'],
                    ],
                    'RECEIPTS' => ['icon' => 'arrow-down-to-line', 'pages' => ['RECEIPTS', 'CANCELLED RECEIPTS', 'RECEIPTS HISTORY']],
                    'PAYMENT VOUCHERS' => ['icon' => 'receipt-text', 'pages' => ['PAYMENT VOUCHERS', 'CANCELLED PAYMENT VOUCHERS', 'PAYMENT VOUCHERS HISTORY']],
                    'CUSTOMER REFUNDING' => ['icon' => 'user-x', 'pages' => ['CUSTOMER REFUNDS', 'CANCELLED CUSTOMER REFUNDS', 'CUSTOMER REFUNDS HISTORY']],
                    'SUPPLIER REFUNDING' => ['icon' => 'truck-x', 'pages' => ['SUPPLIER REFUNDS', 'CANCELLED SUPPLIER REFUNDS', 'SUPPLIER REFUNDS HISTORY']],
                ],
                'icon' => 'landmark',
                'access' => $roles,
                'pages' => [
                    [
                        'name' => 'BANK ACCOUNTS',
                        'route_name' => FileResources::BANKS->value,
                        'icon' => 'building-2',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'CASH ACCOUNTS',
                        'route_name' => FileResources::CASH_ACCOUNTS->value,
                        'icon' => 'wallet',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'E-WALLET ACCOUNTS',
                        'route_name' => FileResources::E_WALLET_ACCOUNTS->value,
                        'icon' => 'smartphone',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'CASH LEDGER',
                        'route_name' => FileResources::CASH_LEDGERS->value,
                        'icon' => 'wallet',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'BANK LEDGER',
                        'route_name' => FileResources::BANK_LEDGERS->value,
                        'icon' => 'landmark',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'E-WALLET LEDGER',
                        'route_name' => FileResources::E_WALLET_LEDGERS->value,
                        'icon' => 'smartphone',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'BANK JOURNALS',
                        'route_name' => FileResources::BANK_JOURNALS->value,
                        'icon' => 'arrow-left-right',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        // Drawer to transit: the first half of a banker's deposit.
                        'name' => 'CASH TRANSFERS',
                        'route_name' => FileResources::CASH_TRANSFERS->value,
                        'icon' => 'banknote',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        // Transit to bank: the second half, once the bank credits it.
                        'name' => 'BANK TRANSFERS',
                        'route_name' => FileResources::BANK_TRANSFERS->value,
                        'icon' => 'landmark',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'RECEIPTS',
                        'route_name' => FileResources::PAYMENTS->value,
                        'icon' => 'hand-coins',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'CANCELLED RECEIPTS',
                        'route_name' => FileResources::RECEIPTS_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'RECEIPTS HISTORY',
                        'route_name' => FileResources::RECEIPTS_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'PAYMENT VOUCHERS',
                        'route_name' => FileResources::PAYMENT_VOUCHERS->value,
                        'icon' => 'credit-card',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'CANCELLED PAYMENT VOUCHERS',
                        'route_name' => FileResources::PAYMENT_VOUCHERS_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'PAYMENT VOUCHERS HISTORY',
                        'route_name' => FileResources::PAYMENT_VOUCHERS_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'CUSTOMER REFUNDS',
                        'route_name' => FileResources::REFUNDS->value,
                        'icon' => 'undo-2',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'CANCELLED CUSTOMER REFUNDS',
                        'route_name' => FileResources::REFUNDS_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'CUSTOMER REFUNDS HISTORY',
                        'route_name' => FileResources::REFUNDS_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'SUPPLIER REFUNDS',
                        'route_name' => FileResources::SUPPLIER_REFUNDS->value,
                        'icon' => 'undo-2',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'CANCELLED SUPPLIER REFUNDS',
                        'route_name' => FileResources::SUPPLIER_REFUNDS_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'SUPPLIER REFUNDS HISTORY',
                        'route_name' => FileResources::SUPPLIER_REFUNDS_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                ],
            ],
            'INVENTORY' => [
                'groupings' => [
                    'GROUP_PRODUCTS' => ['icon' => 'package', 'pages' => ['BRANDS', 'PRODUCTS', 'PRODUCT CATEGORIES', 'PRODUCT TYPES']],
                ],
                'icon' => 'boxes',
                'access' => $roles,
                'pages' => [
                    [
                        'name' => 'BRANDS',
                        'route_name' => FileResources::BRANDS->value,
                        'icon' => 'tags',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'PRODUCTS',
                        'route_name' => FileResources::PRODUCTS->value,
                        'icon' => 'package',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'PRODUCT CATEGORIES',
                        'route_name' => FileResources::PRODUCT_CATEGORIES->value,
                        'icon' => 'folders',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'PRODUCT TYPES',
                        'route_name' => FileResources::PRODUCT_TYPES->value,
                        'icon' => 'list-tree',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                ],
            ],
            'CRM' => [
                'groupings' => [
                    'PARTIES' => ['icon' => 'users', 'pages' => ['COMPANIES', 'CONTACTS']],
                    'ACTIVITIES' => ['icon' => 'calendar-check', 'pages' => ['FILES']],
                    'OUTSTANDING' => ['icon' => 'alert-circle', 'pages' => ['SUPPLIER OUTSTANDING', 'CUSTOMER OUTSTANDING']],
                ],
                'icon' => 'handshake',
                'access' => $roles,
                'pages' => [
                    [
                        'name' => 'COMPANIES',
                        'route_name' => FileResources::COMPANIES->value,
                        'icon' => 'building-2',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'CONTACTS',
                        'route_name' => FileResources::CONTACTS->value,
                        'icon' => 'contact',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'FILES',
                        'route_name' => FileResources::COMPANY_FILES->value,
                        'icon' => 'file-text',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove']),
                    ],
                    [
                        'name' => 'SUPPLIER OUTSTANDING',
                        'route_name' => FileResources::SUPPLIER_OUTSTANDING->value,
                        'icon' => 'wallet-cards',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'CUSTOMER OUTSTANDING',
                        'route_name' => FileResources::SALES_CUSTOMER_OUTSTANDING->value,
                        'icon' => 'wallet-cards',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                ],
            ],
            'PURCHASES' => [
                'groupings' => [
                    'PROCUREMENT' => ['icon' => 'shopping-cart', 'pages' => ['PURCHASE ORDER', 'PURCHASE ORDER SETTINGS']],
                    'RECEIVING' => ['icon' => 'package-check', 'pages' => ['GOODS RECEIVED NOTE', 'GOODS RECEIPT ADJUSTMENTS', 'CANCELLED GOODS RECEIPT ADJUSTMENTS', 'GOODS RECEIPT ADJUSTMENT HISTORY']],
                    'PURCHASE INVOICING' => ['icon' => 'file-digit', 'pages' => ['PURCHASE INVOICES', 'CANCELLED PURCHASE INVOICES', 'PURCHASE INVOICES HISTORY']],
                    'PURCHASE RETURNING' => ['icon' => 'rotate-ccw', 'pages' => ['PURCHASE RETURNS']],
                    'PURCHASE ADJUSTMENTING' => ['icon' => 'file-cog', 'pages' => ['PURCHASE ADJUSTMENTS', 'CANCELLED PURCHASE ADJUSTMENTS', 'PURCHASE ADJUSTMENTS HISTORY']],
                ],
                'icon' => 'shopping-cart',
                'access' => $roles,
                'pages' => [
                    [
                        'name' => 'PURCHASE ORDER',
                        'route_name' => FileResources::PURCHASE_ORDERS->value,
                        'icon' => 'clipboard-list',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve']),
                    ],
                    [
                        'name' => 'GOODS RECEIVED NOTE',
                        'route_name' => FileResources::GOODS_RECEIPTS->value,
                        'icon' => 'package-check',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve']),
                    ],
                    [
                        'name' => 'GOODS RECEIPT ADJUSTMENTS',
                        'route_name' => FileResources::GOODS_RECEIPT_ADJUSTMENTS->value,
                        'icon' => 'sliders-horizontal',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'GOODS RECEIPT ADJUSTMENT HISTORY',
                        'route_name' => FileResources::GOODS_RECEIPT_ADJUSTMENTS_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'CANCELLED GOODS RECEIPT ADJUSTMENTS',
                        'route_name' => FileResources::GOODS_RECEIPT_ADJUSTMENTS_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'PURCHASE INVOICES',
                        'route_name' => FileResources::PURCHASE_INVOICES->value,
                        'icon' => 'receipt-text',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'CANCELLED PURCHASE INVOICES',
                        'route_name' => FileResources::PURCHASE_INVOICES_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'PURCHASE INVOICES HISTORY',
                        'route_name' => FileResources::PURCHASE_INVOICES_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'PURCHASE RETURNS',
                        'route_name' => FileResources::PURCHASE_RETURNS->value,
                        'icon' => 'package-minus',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'PURCHASE ADJUSTMENTS',
                        'route_name' => FileResources::PURCHASE_ADJUSTMENTS->value,
                        'icon' => 'file-minus',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'CANCELLED PURCHASE ADJUSTMENTS',
                        'route_name' => FileResources::PURCHASE_ADJUSTMENTS_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'PURCHASE ADJUSTMENTS HISTORY',
                        'route_name' => FileResources::PURCHASE_ADJUSTMENTS_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'PURCHASE ORDER SETTINGS',
                        'route_name' => FileResources::PURCHASE_ORDER_SETTINGS->value,
                        'icon' => 'settings-2',
                        'access' => $this->allAccess($roles, ['read', 'update']),
                    ],
                ],
            ],
            'SALES' => [
                'groupings' => [
                    'PRE-SALES' => ['icon' => 'file-text', 'pages' => ['QUOTATIONS']],
                    'ORDER FULFILLMENT' => ['icon' => 'truck', 'pages' => ['SALES ORDERS', 'DELIVERY ORDERS']],
                    'SALES INVOICING' => ['icon' => 'file-digit', 'pages' => ['SALES INVOICES', 'CANCELLED SALES INVOICES', 'SALES INVOICES HISTORY']],
                    'SALES ADJUSTMENTING' => ['icon' => 'file-cog', 'pages' => ['SALES ADJUSTMENTS', 'CANCELLED SALES ADJUSTMENTS', 'SALES ADJUSTMENTS HISTORY']],
                ],
                'icon' => 'badge-dollar-sign',
                'access' => $roles,
                'pages' => [
                    [
                        'name' => 'QUOTATIONS',
                        'route_name' => FileResources::QUOTATIONS->value,
                        'icon' => 'file-signature',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve']),
                    ],
                    [
                        'name' => 'SALES ORDERS',
                        'route_name' => FileResources::SALES_ORDERS->value,
                        'icon' => 'clipboard-list',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve']),
                    ],
                    [
                        'name' => 'DELIVERY ORDERS',
                        'route_name' => FileResources::SALES_DELIVERY_ORDERS->value,
                        'icon' => 'truck',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve']),
                    ],
                    [
                        'name' => 'SALES INVOICES',
                        'route_name' => FileResources::SALES_INVOICES->value,
                        'icon' => 'receipt-text',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'remove', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'CANCELLED SALES INVOICES',
                        'route_name' => FileResources::SALES_INVOICES_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'SALES INVOICES HISTORY',
                        'route_name' => FileResources::SALES_INVOICES_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'SALES ADJUSTMENTS',
                        'route_name' => FileResources::SALES_ADJUSTMENTS->value,
                        'icon' => 'file-minus',
                        'access' => $this->allAccess($roles, ['read', 'add', 'update', 'approve', 'cancel']),
                    ],
                    [
                        'name' => 'CANCELLED SALES ADJUSTMENTS',
                        'route_name' => FileResources::SALES_ADJUSTMENTS_CANCELLED->value,
                        'icon' => 'x-circle',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                    [
                        'name' => 'SALES ADJUSTMENTS HISTORY',
                        'route_name' => FileResources::SALES_ADJUSTMENTS_HISTORY->value,
                        'icon' => 'history',
                        'access' => $this->allAccess($roles, ['read']),
                    ],
                ],
            ],
        ];

        $this->seedStructure($structure);
    }

    /**
     * Generate access array for all roles with same permissions.
     *
     * @param  array<string>  $roles  List of role names.
     * @param  array<string>  $permissions  List of permission keys.
     * @return array<string, array<string, array<string>>> Access configuration array.
     */
    private function allAccess(array $roles, array $permissions): array
    {
        $access = [];
        foreach ($roles as $role) {
            $access[$role] = ['can' => $permissions];
        }

        return $access;
    }

    /**
     * Seed the folder, page, and page_permission structure.
     *
     * Pages that no longer exist in the route table are pruned so stale menu
     * entries cannot produce dead links in the sidebar.
     *
     * @param  array<string, array<string, mixed>>  $structure  Menu structure configuration.
     */
    private function seedStructure(array $structure): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Manager', 'Editor', 'User', 'Finance'])
            ->pluck('id', 'name');

        $this->pruneStalePages($structure);

        foreach ($structure as $folderName => $folderConfig) {
            $existingFolder = DB::table('folders')->where('name', $folderName)->first();
            $folderId = $existingFolder->id ?? (string) Str::uuid7();

            DB::table('folders')->updateOrInsert(
                ['name' => $folderName],
                [
                    'id' => $folderId,
                    'icon' => $folderConfig['icon'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            foreach ($folderConfig['access'] as $roleName) {
                $roleId = $roleIds[$roleName] ?? null;
                if (! $roleId) {
                    continue;
                }

                $existingPermission = DB::table('folder_permissions')
                    ->where('role_id', $roleId)
                    ->where('folder_id', $folderId)
                    ->first();

                DB::table('folder_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'folder_id' => $folderId],
                    [
                        'id' => $existingPermission->id ?? (string) Str::uuid7(),
                        'can_read' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            foreach ($folderConfig['pages'] as $pageConfig) {
                $existingPage = DB::table('pages')
                    ->where('route_name', $pageConfig['route_name'])
                    ->first();
                $pageId = $existingPage->id ?? (string) Str::uuid7();

                DB::table('pages')->updateOrInsert(
                    ['id' => $pageId],
                    [
                        'folder_id' => $folderId,
                        'name' => $pageConfig['name'],
                        'route_name' => $pageConfig['route_name'],
                        'icon' => $pageConfig['icon'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                foreach ($pageConfig['access'] as $roleName => $rolePermissions) {
                    $roleId = $roleIds[$roleName] ?? null;
                    if (! $roleId) {
                        continue;
                    }

                    // Every role is seeded exactly what the menu structure asks
                    // for. No role is granted a wider set than its siblings, so
                    // the stored matrix is the only thing that grants access.
                    $granted = $rolePermissions['can'] ?? [];

                    $existingPermission = DB::table('page_permissions')
                        ->where('role_id', $roleId)
                        ->where('page_id', $pageId)
                        ->first();

                    DB::table('page_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'page_id' => $pageId],
                        [
                            'id' => $existingPermission->id ?? (string) Str::uuid7(),
                            'can_read' => in_array('read', $granted),
                            'can_add' => in_array('add', $granted),
                            'can_update' => in_array('update', $granted),
                            'can_remove' => in_array('remove', $granted),
                            'can_approve' => in_array('approve', $granted),
                            'can_cancel' => in_array('cancel', $granted),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            $this->seedGroupings($folderId, $folderConfig['groupings'] ?? [], $roleIds, $folderConfig['access']);
        }
    }

    /**
     * Seed the third menu level (page groupings) and grant every role read access.
     *
     * @param  string  $folderId  The parent folder identifier.
     * @param  array<string, array<string, mixed>>  $groupings  Grouping name => definition.
     * @param  Collection<string, string>  $roleIds  Role identifiers keyed by name.
     * @param  array<string>  $roles  Role names that receive read access.
     */
    private function seedGroupings(string $folderId, array $groupings, Collection $roleIds, array $roles): void
    {
        if ($groupings === []) {
            return;
        }

        $validGroupingNames = array_keys($groupings);

        $staleGroupings = DB::table('page_groupings')
            ->where('folder_id', $folderId)
            ->whereNotIn('name', $validGroupingNames)
            ->get(['id']);

        DB::table('pages')->whereIn('page_grouping_id', $staleGroupings->pluck('id'))->update(['page_grouping_id' => null]);
        DB::table('page_grouping_permissions')->whereIn('page_grouping_id', $staleGroupings->pluck('id'))->delete();
        DB::table('page_groupings')->whereIn('id', $staleGroupings->pluck('id'))->delete();

        foreach ($groupings as $groupingName => $grouping) {
            $existing = DB::table('page_groupings')
                ->where('folder_id', $folderId)
                ->where('name', $groupingName)
                ->first();
            $groupingId = $existing->id ?? (string) Str::uuid7();

            DB::table('page_groupings')->updateOrInsert(
                ['id' => $groupingId],
                [
                    'folder_id' => $folderId,
                    'name' => $groupingName,
                    'icon' => $grouping['icon'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            foreach ($roles as $roleName) {
                $roleId = $roleIds[$roleName] ?? null;

                if (! $roleId) {
                    continue;
                }

                $existingPermission = DB::table('page_grouping_permissions')
                    ->where('role_id', $roleId)
                    ->where('page_grouping_id', $groupingId)
                    ->first();

                DB::table('page_grouping_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'page_grouping_id' => $groupingId],
                    [
                        'id' => $existingPermission->id ?? (string) Str::uuid7(),
                        'can_read' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::table('pages')
                ->where('folder_id', $folderId)
                ->whereIn('name', $grouping['pages'])
                ->update([
                    'page_grouping_id' => $groupingId,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Remove menu pages whose routes are no longer registered, together with
     * their permission rows.
     *
     * @param  array<string, array<string, mixed>>  $structure  Menu structure configuration.
     */
    private function pruneStalePages(array $structure): void
    {
        $validRoutes = collect($structure)
            ->flatMap(fn (array $folder): array => collect($folder['pages'])->pluck('route_name')->all());

        $stalePages = DB::table('pages')
            ->whereNotIn('route_name', $validRoutes)
            ->get(['id']);

        if ($stalePages->isEmpty()) {
            return;
        }

        DB::table('page_permissions')->whereIn('page_id', $stalePages->pluck('id'))->delete();
        DB::table('pages')->whereIn('id', $stalePages->pluck('id'))->delete();
    }
}
