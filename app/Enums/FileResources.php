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

/**
 * Please refer to route_name field in the table pages
 *
 * @noinspection PhpUnused
 */
enum FileResources: string
{
    // Chart of Accounts
    case CHARTS = 'chart-of-account.charts.index';
    case CHART_REPORTS = 'chart-of-account.chart-reports.index';
    case CHART_SETTINGS = 'chart-of-account.chart-settings.index';
    case CHART_TYPES = 'chart-of-account.chart-types.index';
    case PROFIT_LOSS = 'chart-of-account.profit-loss.index';
    case BALANCE_SHEET = 'chart-of-account.balance-sheet.index';
    case TRIAL_BALANCE = 'chart-of-account.trial-balance.index';

    // CRM
    case COMPANIES = 'crm.companies.index';
    case COMPANY_FILES = 'crm.company-files.index';
    case CONTACTS = 'crm.contacts.index';

    // Inventory
    case BRANDS = 'inventory.brands.index';
    case PRODUCT_CATEGORIES = 'inventory.product-categories.index';
    case PRODUCT_TYPES = 'inventory.product-types.index';
    case PRODUCTS = 'inventory.products.index';

    // Finance
    case JOURNAL_ENTRIES = 'finance.journal-entries.index';
    case JOURNAL_ENTRIES_CANCELLED = 'finance.journal-entries.cancelled.index';
    case JOURNAL_ENTRIES_HISTORY = 'finance.journal-entries.history.index';
    case LEDGERS = 'finance.ledgers.index';
    case TAXES = 'finance.taxes.index';
    case DISCOUNTS = 'finance.discounts.index';
    case CURRENCIES = 'finance.currencies.index';

    // Bank
    case BANKS = 'bank.banks.index';
    case CASH_ACCOUNTS = 'bank.cash-accounts.index';
    case E_WALLET_ACCOUNTS = 'bank.e-wallet-accounts.index';
    case PAYMENTS = 'bank.receipts.index';
    case RECEIPTS_HISTORY = 'bank.receipts.history.index';
    case RECEIPTS_CANCELLED = 'bank.receipts.cancelled.index';
    case PAYMENT_VOUCHERS = 'bank.payment-vouchers.index';
    case PAYMENT_VOUCHERS_HISTORY = 'bank.payment-vouchers.history.index';
    case PAYMENT_VOUCHERS_CANCELLED = 'bank.payment-vouchers.cancelled.index';
    case REFUNDS = 'bank.customer-refunds.index';
    case REFUNDS_HISTORY = 'bank.customer-refunds.history.index';
    case REFUNDS_CANCELLED = 'bank.customer-refunds.cancelled.index';
    case SUPPLIER_REFUNDS = 'bank.supplier-refunds.index';
    case SUPPLIER_REFUNDS_HISTORY = 'bank.supplier-refunds.history.index';
    case SUPPLIER_REFUNDS_CANCELLED = 'bank.supplier-refunds.cancelled.index';
    case CASH_LEDGERS = 'bank.cash-ledger.index';
    case BANK_LEDGERS = 'bank.bank-ledger.index';
    case E_WALLET_LEDGERS = 'bank.e-wallet-ledger.index';
    case E_WALLET_PROVIDERS = 'bank.e-wallet-providers.index';
    case BANK_JOURNALS = 'bank.bank-journals.index';
    case CASH_TRANSFERS = 'bank.cash-transfers.index';
    case BANK_TRANSFERS = 'bank.bank-transfers.index';

    // Purchase
    case PURCHASE_ORDER_SETTINGS = 'purchase.purchase-order-settings.index';
    case PURCHASE_ORDERS = 'purchase.purchase-orders.index';
    case GOODS_RECEIPTS = 'purchase.goods-receipts.index';
    case GOODS_RECEIPT_ADJUSTMENTS = 'purchase.goods-receipt-adjustments.index';
    case GOODS_RECEIPT_ADJUSTMENTS_HISTORY = 'purchase.goods-receipt-adjustments.history.index';
    case GOODS_RECEIPT_ADJUSTMENTS_CANCELLED = 'purchase.goods-receipt-adjustments.cancelled.index';
    case PURCHASE_INVOICES = 'purchase.invoices.index';
    case PURCHASE_INVOICES_HISTORY = 'purchase.invoices.history.index';
    case PURCHASE_INVOICES_CANCELLED = 'purchase.invoices.cancelled.index';
    case PURCHASE_RETURNS = 'purchase.returns.index';
    case PURCHASE_ADJUSTMENTS = 'purchase.adjustments.index';
    case PURCHASE_ADJUSTMENTS_HISTORY = 'purchase.adjustments.history.index';
    case PURCHASE_ADJUSTMENTS_CANCELLED = 'purchase.adjustments.cancelled.index';
    case SUPPLIER_OUTSTANDING = 'purchase.supplier-outstanding.index';

    // Sales
    case QUOTATIONS = 'sales.quotations.index';
    case SALES_ORDERS = 'sales.sales-orders.index';
    case SALES_DELIVERY_ORDERS = 'sales.delivery-orders.index';
    case SALES_INVOICES = 'sales.invoices.index';
    case SALES_INVOICES_HISTORY = 'sales.invoices.history.index';
    case SALES_INVOICES_CANCELLED = 'sales.invoices.cancelled.index';
    case SALES_ADJUSTMENTS = 'sales.adjustments.index';
    case SALES_ADJUSTMENTS_HISTORY = 'sales.adjustments.history.index';
    case SALES_ADJUSTMENTS_CANCELLED = 'sales.adjustments.cancelled.index';
    case SALES_CUSTOMER_OUTSTANDING = 'sales.customer-outstanding.index';

    // Menu / Navigation
    case FOLDER_PERMISSIONS = 'menu.folder-permissions.index';
    case PAGE_PERMISSIONS = 'menu.page-permissions.index';
    case PAGE_GROUPING_PERMISSIONS = 'menu.page-grouping-permissions.index';

    // System & Users
    case ROLES = 'system.roles.index';
    case USERS = 'system.users.index';
    case SETTINGS = 'system.settings.index';

    /**
     * Get the human-readable resource name.
     *
     * @return string Display name shown to end users.
     */
    public function displayName(): string
    {
        return match ($this) {
            // Chart of Accounts
            self::CHARTS => 'Chart',
            self::CHART_REPORTS => 'Chart Report',
            self::CHART_SETTINGS => 'Chart Setting',
            self::CHART_TYPES => 'Chart Type',
            self::PROFIT_LOSS => 'Profit & Loss',
            self::BALANCE_SHEET => 'Balance Sheet',
            self::TRIAL_BALANCE => 'Trial Balance',

            // CRM
            self::COMPANIES => 'Company',
            self::COMPANY_FILES => 'Company File',
            self::CONTACTS => 'Contact',

            // Inventory
            self::BRANDS => 'Brand',
            self::PRODUCT_CATEGORIES => 'Product Category',
            self::PRODUCT_TYPES => 'Product Type',
            self::PRODUCTS => 'Product',

            // Finance
            self::JOURNAL_ENTRIES => 'Journal Entry',
            self::JOURNAL_ENTRIES_CANCELLED => 'Cancelled Journal Entry',
            self::JOURNAL_ENTRIES_HISTORY => 'Journal Entry History',
            self::LEDGERS => 'Ledger',
            self::TAXES => 'Tax',
            self::DISCOUNTS => 'Discount',
            self::CURRENCIES => 'Currency',

            // Bank
            self::BANKS => 'Bank Account',
            self::CASH_ACCOUNTS => 'Cash Account',
            self::E_WALLET_ACCOUNTS => 'E-Wallet Account',
            self::PAYMENTS => 'Receipt',
            self::RECEIPTS_HISTORY => 'Receipt History',
            self::RECEIPTS_CANCELLED => 'Cancelled Receipt',
            self::PAYMENT_VOUCHERS => 'Payment Voucher',
            self::PAYMENT_VOUCHERS_HISTORY => 'Payment Voucher History',
            self::PAYMENT_VOUCHERS_CANCELLED => 'Cancelled Payment Voucher',
            self::PURCHASE_INVOICES => 'Purchase Invoice',
            self::PURCHASE_INVOICES_CANCELLED => 'Cancelled Purchase Invoice',
            self::PURCHASE_INVOICES_HISTORY => 'Purchase Invoice History',
            self::REFUNDS => 'Refund',
            self::REFUNDS_HISTORY => 'Refund History',
            self::REFUNDS_CANCELLED => 'Cancelled Refund',
            self::SUPPLIER_REFUNDS => 'Supplier Refund',
            self::SUPPLIER_REFUNDS_HISTORY => 'Supplier Refund History',
            self::SUPPLIER_REFUNDS_CANCELLED => 'Cancelled Supplier Refund',
            self::CASH_LEDGERS => 'Cash Ledger',
            self::BANK_LEDGERS => 'Bank Ledger',
            self::E_WALLET_LEDGERS => 'E-Wallet Ledger',
            self::E_WALLET_PROVIDERS => 'E-Wallet Provider',
            self::BANK_JOURNALS => 'Bank Journal',
            self::CASH_TRANSFERS => 'Cash Transfer',
            self::BANK_TRANSFERS => 'Bank Transfer',

            // Purchase
            self::PURCHASE_ORDER_SETTINGS => 'Purchase Order Setting',
            self::PURCHASE_ORDERS => 'Purchase Order',
            self::GOODS_RECEIPTS => 'Goods Receipt',
            self::GOODS_RECEIPT_ADJUSTMENTS => 'Goods Receipt Adjustment',
            self::GOODS_RECEIPT_ADJUSTMENTS_HISTORY => 'Goods Receipt Adjustment History',
            self::GOODS_RECEIPT_ADJUSTMENTS_CANCELLED => 'Cancelled Goods Receipt Adjustment',
            self::PURCHASE_RETURNS => 'Purchase Return',
            self::PURCHASE_ADJUSTMENTS => 'Purchase Adjustment',
            self::PURCHASE_ADJUSTMENTS_HISTORY => 'Purchase Adjustment History',
            self::PURCHASE_ADJUSTMENTS_CANCELLED => 'Cancelled Purchase Adjustment',
            self::SUPPLIER_OUTSTANDING => 'Supplier Outstanding',

            // Sales
            self::QUOTATIONS => 'Quotation',
            self::SALES_ORDERS => 'Sales Order',
            self::SALES_DELIVERY_ORDERS => 'Delivery Order',
            self::SALES_INVOICES => 'Sales Invoice',
            self::SALES_INVOICES_HISTORY => 'Sales Invoice History',
            self::SALES_INVOICES_CANCELLED => 'Cancelled Sales Invoice',
            self::SALES_ADJUSTMENTS => 'Sales Adjustment',
            self::SALES_ADJUSTMENTS_HISTORY => 'Sales Adjustment History',
            self::SALES_ADJUSTMENTS_CANCELLED => 'Cancelled Sales Adjustment',
            self::SALES_CUSTOMER_OUTSTANDING => 'Customer Outstanding',

            // Menu / Navigation
            self::FOLDER_PERMISSIONS => 'Folder Permission',
            self::PAGE_PERMISSIONS => 'Page Permission',
            self::PAGE_GROUPING_PERMISSIONS => 'Page Grouping Permission',

            // System & Users
            self::ROLES => 'Role',
            self::USERS => 'User',
            self::SETTINGS => 'System Setting',
        };
    }
}
