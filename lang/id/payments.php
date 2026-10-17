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
            'title' => 'Kuitansi',
            'subtitle' => 'Catat kuitansi pelanggan terhadap faktur penjualan.',
            'create_title' => 'Buat Kuitansi',
            'edit_title' => 'Ubah Kuitansi',
        ],
        'payment_voucher' => [
            'title' => 'Bukti Pembayaran',
            'subtitle' => 'Catat pembayaran pemasok terhadap faktur pembelian.',
            'create_title' => 'Buat Bukti Pembayaran',
            'edit_title' => 'Ubah Bukti Pembayaran',
        ],
    ],
    'title' => 'Pembayaran',
    'subtitle' => 'Buat dan cetak kuitansi serta bukti pembayaran.',
    'table' => [
        'column_entry' => 'Entri',
    ],
    'form' => [
        'create_title' => 'Buat Pembayaran',
        'details_tab' => 'Kontak & Produk',
        'linked_invoice' => 'Faktur Terkait / Faktur Pembelian',
        'linked_purchase_invoice' => 'Faktur Pembelian Terkait',
        'linked_sales_invoice' => 'Faktur Penjualan Terkait',
        'direct_payment' => 'Pembayaran langsung (tanpa faktur terkait)',
        'linked_invoice_hint' => 'Opsional. Kuitansi mencantumkan faktur penjualan; bukti pembayaran mencantumkan faktur pembelian untuk perusahaan terpilih.',
        'accounting_hint' => 'Kosongkan akun untuk menggunakan pengaturan bagan akun. Kuitansi: debit bank dan kredit debitur. Bukti pembayaran: debit kreditur dan kredit bank.',
        'payment_transfer' => 'Transfer Pembayaran',
    ],
    'transfer_types' => [
        'cheque' => 'Cek',
        'bank_transfer' => 'Transfer Bank',
        'e_wallet' => 'Dompet Elektronik',
        'cash' => 'Tunai',
    ],
    'sub_type' => [
        'receipt' => 'Kuitansi',
        'payment_voucher' => 'Bukti Pembayaran',
    ],
    'actions' => [
        'save' => 'Simpan Pembayaran',
        'view_and_print' => 'Lihat dan cetak pembayaran',
        'print_pdf' => 'Cetak PDF',
    ],
    'messages' => [
        'design_required' => 'Buat desain PDF pembayaran sebelum mencetak dokumen ini.',
    ],
    'validation' => [
        'contact_company_invalid' => 'Pilih kontak yang termasuk dalam perusahaan terpilih.',
        'company_required_for_invoice' => 'Pilih perusahaan sebelum menautkan faktur.',
        'accounts_must_differ' => 'Akun debit dan kredit harus berbeda.',
        'debit_account_invalid' => 'Akun debit terpilih atau default tidak valid untuk jenis pembayaran ini.',
        'credit_account_invalid' => 'Akun kredit terpilih atau default tidak valid untuk jenis pembayaran ini.',
    ],
    'pdf' => [
        'receipt_title' => 'KUITANSI RESMI',
        'payment_voucher_title' => 'BUKTI PEMBAYARAN',
        'received_from' => 'Diterima Dari',
        'paid_to' => 'Dibayar Kepada',
        'amount' => 'Jumlah',
        'date' => 'Tanggal',
        'linked_invoice' => 'Faktur Terkait',
        'bank_account' => 'Rekening Bank',
        'payment_transfer' => 'Transfer Pembayaran',
        'authorised_signature' => 'Tanda Tangan Berwenang',
        'company_placeholder' => 'PERUSAHAAN ANDA',
    ],
];
