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

enum LedgerSubType: string
{
    case SALES_INVOICE = 'sales_invoice';
    case MANUAL_SALES_INVOICE = 'manual_sales_invoice';
    case PURCHASE_INVOICE = 'purchase_invoice';
    case RECEIPT = 'receipt';
    case PAYMENT_VOUCHER = 'payment_voucher';

    case JOURNAL = 'journal';
    case STOCK_VARIANCE = 'stock_variance';

    case INCREASE_ADJUSTMENT = 'increase_adjustment';
    case DECREASE_ADJUSTMENT = 'decrease_adjustment';
    case PURCHASE_DEBIT_NOTE = 'purchase_debit_note';
    case PURCHASE_CREDIT_NOTE = 'purchase_credit_note';

    case RECEIPT_REFUND = 'receipt_refund';
    case SUPPLIER_REFUND = 'supplier_refund';

    case REFUND = 'refund';
    case TRANSFER = 'transfer';
    case WITHDRAWAL = 'withdrawal';
    case BANK_JOURNAL = 'bank_journal';

    public function label(): string
    {
        return match ($this) {
            self::SALES_INVOICE => 'Sales Invoice',
            self::MANUAL_SALES_INVOICE => 'Manual Sales Invoice',
            self::PURCHASE_INVOICE => 'Purchase Invoice',
            self::RECEIPT => 'Official Receipt',
            self::PAYMENT_VOUCHER => 'Payment Voucher',
            self::JOURNAL => 'Journal',
            self::STOCK_VARIANCE => 'Stock Variance',
            self::INCREASE_ADJUSTMENT => 'Increase Adjustment',
            self::DECREASE_ADJUSTMENT => 'Decrease Adjustment',
            self::PURCHASE_DEBIT_NOTE => __('purchase_adjustments.sub_type.purchase_debit_note'),
            self::PURCHASE_CREDIT_NOTE => __('purchase_adjustments.sub_type.purchase_credit_note'),
            self::RECEIPT_REFUND => 'Receipt Refund',
            self::SUPPLIER_REFUND => 'Supplier Refund',
            self::REFUND => 'Refund',
            self::WITHDRAWAL => 'Withdrawal',
            self::TRANSFER => 'Transfer',
            self::BANK_JOURNAL => 'Bank Journal',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::SALES_INVOICE => 'IV',
            self::MANUAL_SALES_INVOICE => 'MIV',
            self::PURCHASE_INVOICE => 'PI',
            self::RECEIPT => 'RCP',
            self::PAYMENT_VOUCHER => 'PV',
            self::JOURNAL => 'JV',
            self::STOCK_VARIANCE => 'STK',
            self::INCREASE_ADJUSTMENT => 'DN',
            self::DECREASE_ADJUSTMENT => 'CN',
            self::PURCHASE_DEBIT_NOTE => 'PDN',
            self::PURCHASE_CREDIT_NOTE => 'PCN',
            self::RECEIPT_REFUND => 'RR',
            self::SUPPLIER_REFUND => 'SR',
            self::REFUND => 'RF',
            self::WITHDRAWAL => 'WD',
            self::TRANSFER => 'TR',
            self::BANK_JOURNAL => 'BJ',

        };
    }
}
