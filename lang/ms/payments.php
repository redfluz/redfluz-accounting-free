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
            'title' => 'Resit',
            'subtitle' => 'Rekod penerimaan pelanggan bagi invois jualan.',
            'create_title' => 'Cipta Resit',
            'edit_title' => 'Sunting Resit',
        ],
        'payment_voucher' => [
            'title' => 'Baucar Bayaran',
            'subtitle' => 'Rekod bayaran pembekal bagi invois belian.',
            'create_title' => 'Cipta Baucar Bayaran',
            'edit_title' => 'Sunting Baucar Bayaran',
        ],
    ],
    'title' => 'Bayaran',
    'subtitle' => 'Cipta dan cetak resit serta baucar bayaran.',
    'table' => [
        'column_entry' => 'Entri',
    ],
    'form' => [
        'create_title' => 'Cipta Bayaran',
        'details_tab' => 'Kenalan & Produk',
        'linked_invoice' => 'Invois / Invois Belian Berkaitan',
        'linked_purchase_invoice' => 'Invois Belian Berkaitan',
        'linked_sales_invoice' => 'Invois Jualan Berkaitan',
        'direct_payment' => 'Bayaran terus (tiada invois berkaitan)',
        'linked_invoice_hint' => 'Pilihan. Resit menyenaraikan invois jualan; baucar bayaran menyenaraikan invois belian bagi syarikat yang dipilih.',
        'accounting_hint' => 'Biarkan akaun kosong untuk menggunakan tetapan carta. Resit: debit bank dan kredit penghutang. Baucar bayaran: debit pemiutang dan kredit bank.',
        'payment_transfer' => 'Cara Bayaran',
    ],
    'transfer_types' => [
        'cheque' => 'Cek',
        'bank_transfer' => 'Pindahan Bank',
        'e_wallet' => 'Dompet Elektronik',
        'cash' => 'Tunai',
    ],
    'sub_type' => [
        'receipt' => 'Resit',
        'payment_voucher' => 'Baucar Bayaran',
    ],
    'actions' => [
        'save' => 'Simpan Bayaran',
        'view_and_print' => 'Lihat dan cetak bayaran',
        'print_pdf' => 'Cetak PDF',
    ],
    'messages' => [
        'design_required' => 'Cipta reka bentuk PDF bayaran sebelum mencetak dokumen ini.',
    ],
    'validation' => [
        'contact_company_invalid' => 'Pilih kenalan yang dimiliki oleh syarikat yang dipilih.',
        'company_required_for_invoice' => 'Pilih syarikat sebelum mengaitkan invois.',
        'accounts_must_differ' => 'Akaun debit dan kredit mestilah berbeza.',
        'debit_account_invalid' => 'Akaun debit yang dipilih atau akaun Tetapan tidak sah untuk jenis bayaran ini.',
        'credit_account_invalid' => 'Akaun kredit yang dipilih atau akaun Tetapan tidak sah untuk jenis bayaran ini.',
    ],
    'pdf' => [
        'receipt_title' => 'RESIT RASMI',
        'payment_voucher_title' => 'BAUCAR BAYARAN',
        'received_from' => 'Diterima Daripada',
        'paid_to' => 'Dibayar Kepada',
        'amount' => 'Amaun',
        'date' => 'Tarikh',
        'linked_invoice' => 'Invois Berkaitan',
        'bank_account' => 'Akaun Bank',
        'payment_transfer' => 'Cara Bayaran',
        'authorised_signature' => 'Tandatangan Dibenarkan',
        'company_placeholder' => 'SYARIKAT ANDA',
    ],
];
