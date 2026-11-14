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
 * PROPRIETARY AND CONFIDENTIAL
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace App\Enums\Finance;

enum LedgerSubType: string
{
    // Financial & Ledger
    case SALES_INVOICE = 'sales_invoice';
    case MANUAL_SALES_INVOICE = 'manual_sales_invoice';
    case PURCHASE_INVOICE = 'purchase_invoice';
    case RECEIPT = 'receipt';
    case PAYMENT_VOUCHER = 'payment_voucher';

    case JOURNAL = 'journal';
    case INVENTORY_DEDUCTION = 'inventory_deduction';
    case STOCK_VARIANCE = 'stock_variance';

    case INCREASE_ADJUSTMENT = 'increase_adjustment';
    case DECREASE_ADJUSTMENT = 'decrease_adjustment';
    case MANUAL_SALES_ADJUSTMENT = 'manual_sales_adjustment';
    case MANUAL_PURCHASE_ADJUSTMENT = 'manual_purchase_adjustment';

    case PURCHASE_DEBIT_NOTE = 'purchase_debit_note';
    case PURCHASE_CREDIT_NOTE = 'purchase_credit_note';
    case SALES_DEBIT_NOTE = 'sales_debit_note';
    case SALES_CREDIT_NOTE = 'sales_credit_note';

    case CUSTOMER_REFUND = 'customer_refund';
    case SUPPLIER_REFUND = 'supplier_refund';

    case REFUND = 'refund';
    case TRANSFER = 'transfer';
    case WITHDRAWAL = 'withdrawal';
    case BANK_JOURNAL = 'bank_journal';

    // Commercial & Operations
    case QUOTATION = 'quotation';
    case SALES_ORDER = 'sales_order';
    case DELIVERY_ORDER = 'delivery_order';
    case PURCHASE_ORDER = 'purchase_order';
    case PURCHASE_REQUEST = 'purchase_request';
    case REQUEST_FOR_QUOTATION = 'request_for_quotation';
    case GOODS_RECEIPT = 'goods_receipt';
    case STOCK_TRANSFER = 'stock_transfer';
    case STOCK_TAKE = 'stock_take';
    case PURCHASE_RETURN = 'purchase_return';
    case RMA = 'rma';

    // HR & Payroll
    case PAYROLL = 'payroll';
    case LEAVE = 'leave';

    public function label(): string
    {
        return match ($this) {
            self::SALES_INVOICE => 'Sales Invoice',
            self::MANUAL_SALES_INVOICE => 'Manual Sales Invoice',
            self::PURCHASE_INVOICE => 'Purchase Invoice',
            self::RECEIPT => 'Official Receipt',
            self::PAYMENT_VOUCHER => 'Payment Voucher',
            self::JOURNAL => 'Journal',
            self::INVENTORY_DEDUCTION => 'Inventory Deduction',
            self::STOCK_VARIANCE => 'Stock Variance',
            self::INCREASE_ADJUSTMENT => 'Increase Adjustment',
            self::DECREASE_ADJUSTMENT => 'Decrease Adjustment',
            self::MANUAL_SALES_ADJUSTMENT => 'Manual Sales Adjustment',
            self::MANUAL_PURCHASE_ADJUSTMENT => 'Manual Purchase Adjustment',
            self::PURCHASE_DEBIT_NOTE => 'Supplier Debit Note',
            self::PURCHASE_CREDIT_NOTE => 'Supplier Credit Note',
            self::SALES_DEBIT_NOTE => 'Customer Debit Note',
            self::SALES_CREDIT_NOTE => 'Customer Credit Note',
            self::CUSTOMER_REFUND => 'Receipt Refund',
            self::SUPPLIER_REFUND => 'Supplier Refund',
            self::REFUND => 'Refund',
            self::WITHDRAWAL => 'Withdrawal',
            self::TRANSFER => 'Transfer',
            self::BANK_JOURNAL => 'Bank Journal',

            self::QUOTATION => 'Quotation',
            self::SALES_ORDER => 'Sales Order',
            self::DELIVERY_ORDER => 'Delivery Order',
            self::PURCHASE_ORDER => 'Purchase Order',
            self::PURCHASE_REQUEST => 'Purchase Request',
            self::REQUEST_FOR_QUOTATION => 'Request for Quotation',
            self::GOODS_RECEIPT => 'Goods Receipt',
            self::STOCK_TRANSFER => 'Stock Transfer',
            self::STOCK_TAKE => 'Stock Take',
            self::PURCHASE_RETURN => 'Purchase Return',
            self::RMA => 'RMA',

            self::PAYROLL => 'Payroll',
            self::LEAVE => 'Leave',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::SALES_INVOICE => 'INV',
            self::MANUAL_SALES_INVOICE => 'MIV',
            self::PURCHASE_INVOICE => 'PIN',
            self::RECEIPT => 'RCP',
            self::PAYMENT_VOUCHER, self::PAYROLL => 'PAY',
            self::JOURNAL => 'JRN',
            self::INVENTORY_DEDUCTION => 'IDD',
            self::STOCK_VARIANCE => 'STV',
            self::INCREASE_ADJUSTMENT => 'ADJ',
            self::DECREASE_ADJUSTMENT => 'CNV',
            self::MANUAL_SALES_ADJUSTMENT => 'MSA',
            self::MANUAL_PURCHASE_ADJUSTMENT => 'MPA',
            self::PURCHASE_DEBIT_NOTE => 'PDN',
            self::PURCHASE_CREDIT_NOTE => 'PCN',
            self::SALES_DEBIT_NOTE => 'SDN',
            self::SALES_CREDIT_NOTE => 'SCN',
            self::CUSTOMER_REFUND => 'RFR',
            self::SUPPLIER_REFUND => 'SRF',
            self::REFUND => 'REF',
            self::WITHDRAWAL => 'WDR',
            self::TRANSFER => 'TRF',
            self::BANK_JOURNAL => 'BKJ',

            self::QUOTATION => 'QUO',
            self::SALES_ORDER => 'SOR',
            self::DELIVERY_ORDER => 'DOR',
            self::PURCHASE_ORDER => 'POR',
            self::PURCHASE_REQUEST => 'PRQ',
            self::REQUEST_FOR_QUOTATION => 'RFQ',
            self::GOODS_RECEIPT => 'GRN',
            self::STOCK_TRANSFER => 'STR',
            self::STOCK_TAKE => 'STK',
            self::PURCHASE_RETURN => 'PRT',
            self::RMA => 'RMA',
            self::LEAVE => 'LEV',
        };
    }
}
