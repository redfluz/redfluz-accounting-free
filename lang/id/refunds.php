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
            'title' => 'Pengembalian Dana Pelanggan',
            'subtitle' => 'Refund pembayaran pelanggan yang terkait dengan faktur penjualan.',
            'create_title' => 'Buat Pengembalian Dana Pelanggan',
        ],
        'supplier_refund' => [
            'title' => 'Pengembalian Dana Pemasok',
            'subtitle' => 'Catat pengembalian dana pemasok yang terkait dengan faktur pembelian.',
            'create_title' => 'Buat Pengembalian Dana Pemasok',
        ],
    ],
    'title' => 'Pengembalian Dana',
    'subtitle' => 'Buat dan cetak pengembalian dana pelanggan dan pemasok yang terkait dengan faktur asli mereka.',
    'table' => [
        'column_entry' => 'Entri',
    ],
    'form' => [
        'create_title' => 'Buat Pengembalian Dana',
        'linked_document' => 'Faktur Asli',
        'linked_document_hint' => 'Semua faktur terposting ditampilkan. Pengembalian dana hanya dapat disimpan saat faktur memiliki saldo pembayaran terposting yang tersedia.',
        'accounting_hint' => 'Pengembalian penerimaan: debit debitur dan kredit bank. Pengembalian pemasok: debit bank dan kredit kreditur. Transfer: antar bank. Penarikan: kredit bank.',
    ],
    'sub_type' => [
        'receipt_refund' => 'Pengembalian Dana Pelanggan',
        'supplier_refund' => 'Pengembalian Dana Pemasok',
        'refund' => 'Pengembalian Dana',
        'transfer' => 'Transfer Dana',
        'withdrawal' => 'Penarikan Rekening',
    ],
    'actions' => [
        'view_and_print' => 'Lihat dan cetak pengembalian dana',
        'print_pdf' => 'Cetak PDF',
    ],
    'messages' => [
        'design_required' => 'Buat desain PDF pembayaran sebelum mencetak pengembalian dana ini.',
        'no_paid_balance' => 'Tidak ada saldo pembayaran terposting yang tersedia untuk faktur ini.',
    ],
    'validation' => [
        'linked_document_invalid' => 'Pilih faktur penjualan atau pembelian terposting yang valid milik perusahaan terpilih.',
        'debit_account_invalid' => 'Akun debit terpilih tidak valid untuk jenis pengembalian dana ini.',
        'credit_account_invalid' => 'Akun kredit terpilih tidak valid untuk jenis pengembalian dana ini.',
        'amount_exceeds_paid' => 'Pengembalian dana tidak boleh melebihi saldo terbayar yang tersedia sebesar RM :amount.',
    ],
    'pdf' => [
        'receipt_refund_title' => 'PENGEMBALIAN PENERIMAAN',
        'supplier_refund_title' => 'PENGEMBALIAN PEMASOK',
        'refund_title' => 'PENGEMBALIAN DANA',
        'transfer_title' => 'TRANSFER DANA',
        'withdrawal_title' => 'PENARIKAN REKENING',
        'refund_for' => 'Pengembalian Dana Untuk',
        'refund_amount' => 'Jumlah Pengembalian',
        'refund_date' => 'Tanggal Pengembalian',
        'linked_document' => 'Dokumen Terkait',
        'bank_account' => 'Rekening Bank',
        'authorised_signature' => 'Tanda Tangan Berwenang',
        'company_placeholder' => 'PERUSAHAAN ANDA',
    ],
];
