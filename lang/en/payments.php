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

return [
    'workflow' => [
        'receipt' => [
            'title' => 'Receipts',
            'subtitle' => 'Record customer receipts against sales invoices.',
            'create_title' => 'Create Receipt',
            'edit_title' => 'Edit Receipt',
        ],
        'payment_voucher' => [
            'title' => 'Payment Vouchers',
            'subtitle' => 'Record supplier payments against purchase invoices.',
            'create_title' => 'Create Payment Voucher',
            'edit_title' => 'Edit Payment Voucher',
        ],
        'refund' => [
            'title' => 'Customer Refunds',
            'subtitle' => 'Return money to a customer against a receipt.',
            'create_title' => 'Create Customer Refund',
        ],
        'supplier_refund' => [
            'title' => 'Supplier Refunds',
            'subtitle' => 'Recover money from a supplier against a payment.',
            'create_title' => 'Create Supplier Refund',
        ],
    ],
    'title' => 'Payments',
    'subtitle' => 'Create and print receipts and payment vouchers.',
    'table' => [
        'column_entry' => 'Entry',
    ],
    'form' => [
        'create_title' => 'Create Payment',
        'details_tab' => 'Contact & Product',
        'linked_invoice' => 'Linked Invoice / Purchase Invoice',
        'linked_purchase_invoice' => 'Linked Purchase Invoice',
        'linked_sales_invoice' => 'Linked Sales Invoice',
        'direct_payment' => 'Direct payment (no linked invoice)',
        'linked_invoice_hint' => 'Optional. Receipts list sales invoices; payment vouchers list purchase invoices for the selected company.',
        'accounting_hint' => 'Leave an account blank to use chart settings. Receipt: debit bank and credit debtor. Payment voucher: debit creditor and credit bank.',
        'payment_transfer' => 'Payment Transfer',
    ],
    'transfer_types' => [
        'cheque' => 'Cheque',
        'bank_transfer' => 'Bank Transfer',
        'e_wallet' => 'E-Wallet',
        'cash' => 'Cash',
    ],
    'sub_type' => [
        'receipt' => 'Receipt',
        'payment_voucher' => 'Payment Voucher',
        'receipt_refund' => 'Customer Refund',
        'supplier_refund' => 'Supplier Refund',
    ],
    'actions' => [
        'save' => 'Save Payment',
        'view_and_print' => 'View and print payment',
        'print_pdf' => 'Print PDF',
    ],
    'messages' => [
        'design_required' => 'Create a payment PDF design before printing this document.',
    ],
    'validation' => [
        'contact_company_invalid' => 'Select a contact belonging to the selected company.',
        'company_required_for_invoice' => 'Select a company before linking an invoice.',
        'accounts_must_differ' => 'The debit and credit accounts must be different.',
        'debit_account_invalid' => 'The selected or default debit account is not valid for this payment type.',
        'credit_account_invalid' => 'The selected or default credit account is not valid for this payment type.',
    ],
    'pdf' => [
        'receipt_title' => 'OFFICIAL RECEIPT',
        'payment_voucher_title' => 'PAYMENT VOUCHER',
        'received_from' => 'Received From',
        'paid_to' => 'Paid To',
        'amount' => 'Amount',
        'date' => 'Date',
        'linked_invoice' => 'Linked Invoice',
        'bank_account' => 'Bank Account',
        'payment_transfer' => 'Payment Transfer',
        'authorised_signature' => 'Authorised Signature',
        'company_placeholder' => 'YOUR COMPANY',
    ],
];
