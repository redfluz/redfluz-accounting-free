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
        'receipt_refund' => [
            'title' => 'Bayaran Balik Pelanggan',
            'subtitle' => 'Bayar balik bayaran pelanggan yang dikaitkan dengan invois jualan.',
            'create_title' => 'Cipta Bayaran Balik Pelanggan',
        ],
        'supplier_refund' => [
            'title' => 'Bayaran Balik Pembekal',
            'subtitle' => 'Rekod bayaran balik pembekal yang dikaitkan dengan invois belian.',
            'create_title' => 'Cipta Bayaran Balik Pembekal',
        ],
    ],
    'title' => 'Bayaran Balik',
    'subtitle' => 'Cipta dan cetak bayaran balik pelanggan dan pembekal yang dipautkan kepada invois asal.',
    'table' => [
        'column_entry' => 'Entri',
    ],
    'form' => [
        'create_title' => 'Cipta Bayaran Balik',
        'linked_document' => 'Invois Asal',
        'linked_document_hint' => 'Semua invois yang telah dipos disenaraikan. Bayaran balik hanya boleh disimpan apabila invois mempunyai baki bayaran dipos yang tersedia.',
        'accounting_hint' => 'Bayaran balik resit: debit penghutang dan kredit bank. Bayaran balik pembekal: debit bank dan kredit pemiutang. Pemindahan: bank ke bank. Pengeluaran: kredit bank.',
    ],
    'sub_type' => [
        'receipt_refund' => 'Bayaran Balik Pelanggan',
        'supplier_refund' => 'Bayaran Balik Pembekal',
        'refund' => 'Bayaran Balik',
        'transfer' => 'Pemindahan Dana',
        'withdrawal' => 'Pengeluaran Akaun',
    ],
    'actions' => [
        'view_and_print' => 'Lihat dan cetak bayaran balik',
        'print_pdf' => 'Cetak PDF',
    ],
    'messages' => [
        'design_required' => 'Cipta reka bentuk PDF bayaran sebelum mencetak bayaran balik ini.',
        'no_paid_balance' => 'Tiada baki bayaran dipos yang tersedia untuk invois ini.',
    ],
    'validation' => [
        'linked_document_invalid' => 'Pilih invois jualan atau invois belian yang telah dipos dan sah bagi syarikat yang dipilih.',
        'debit_account_invalid' => 'Akaun debit yang dipilih tidak sah untuk jenis bayaran balik ini.',
        'credit_account_invalid' => 'Akaun kredit yang dipilih tidak sah untuk jenis bayaran balik ini.',
        'amount_exceeds_paid' => 'Bayaran balik tidak boleh melebihi baki bayaran tersedia sebanyak RM :amount.',
    ],
    'pdf' => [
        'receipt_refund_title' => 'BAYARAN BALIK RESIT',
        'supplier_refund_title' => 'BAYARAN BALIK PEMBEKAL',
        'refund_title' => 'BAYARAN BALIK',
        'transfer_title' => 'PEMINDAHAN DANA',
        'withdrawal_title' => 'PENGELUARAN AKAUN',
        'refund_for' => 'Bayaran Balik Untuk',
        'refund_amount' => 'Amaun Bayaran Balik',
        'refund_date' => 'Tarikh Bayaran Balik',
        'linked_document' => 'Dokumen Berkaitan',
        'bank_account' => 'Akaun Bank',
        'authorised_signature' => 'Tandatangan Dibenarkan',
        'company_placeholder' => 'SYARIKAT ANDA',
    ],
];
