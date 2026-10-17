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
    'tabs' => ['general' => 'Umum', 'attachment' => 'Lampiran', 'payment' => 'Pembayaran', 'shipping' => 'Pengiriman', 'accounting' => 'Akuntansi'],
    'title' => 'Penawaran',
    'subtitle' => 'Siapkan penawaran pelanggan dan lanjutkan penawaran yang diterima ke pemenuhan atau penagihan layanan.',
    'table' => [
        'column_quotation_number' => 'Nomor Penawaran',
        'column_date' => 'Tanggal',
        'column_expiry_date' => 'Berlaku Hingga',
        'column_company' => 'Perusahaan',
        'column_reference_number' => 'Nomor PO Pelanggan',
        'column_status' => 'Status',
        'column_total_amount' => 'Jumlah Total',
        'column_actions' => 'Aksi',
        'column_entry' => 'Entri',
        'converted_to' => 'Dikonversi ke',
    ],
    'form' => [
        'create_title' => 'Buat Penawaran',
        'edit_title' => 'Edit Penawaran',
        'search_placeholder' => 'Cari nomor penawaran, nomor PO pelanggan, atau perusahaan',
        'company' => 'Perusahaan',
        'contact' => 'Kontak',
        'product' => 'Produk',
        'description' => 'Deskripsi',
        'attachment' => 'Lampiran Penawaran',
        'reference_number' => 'Nomor PO Pelanggan',
        'date' => 'Tanggal Penawaran',
        'expiry_date' => 'Berlaku Hingga',
        'status' => 'Status',
        'quantity' => 'Jumlah',
        'unit_price' => 'Harga Satuan',
        'total_amount' => 'Jumlah Total',
        'debit_account' => 'Akun Debit',
        'credit_account' => 'Akun Kredit',
        'debit_amount' => 'Jumlah Debit',
        'credit_amount' => 'Jumlah Kredit',
        'use_default_account' => 'Gunakan akun default yang dikonfigurasi',
        'notes' => 'Deskripsi / Catatan',
        'quick_company_name' => 'Nama pelanggan',
    ],
    'status' => [
        'draft' => 'Draf',
        'sent' => 'Terkirim',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'expired' => 'Kedaluwarsa',
        'converted' => 'Dikonversi',
    ],
    'actions' => [
        'convert' => 'Konversi ke Faktur',
        'convert_sales_order' => 'Buat Pesanan Penjualan',
        'open_invoice' => 'Buka Faktur',
        'quick_company' => 'Tambah cepat pelanggan',
        'add_company' => 'Tambah Pelanggan',
    ],
    'messages' => [
        'empty' => 'Tidak ada penawaran ditemukan.',
        'accepted_required' => 'Hanya penawaran yang diterima yang dapat dikonversi menjadi faktur penjualan.',
        'convert_confirmation' => 'Konversi penawaran ini menjadi faktur penjualan tertunda?',
        'convert_sales_order_confirmation' => 'Buat Pesanan Penjualan draf dari penawaran ini?',
        'design_required' => 'Buat desain faktur penjualan sebelum mencetak penawaran ini.',
        'lines_required' => 'Tambahkan minimal satu baris produk atau deskripsi.',
        'line_values_required' => 'Masukkan jumlah dan harga satuan untuk setiap baris yang lengkap.',
    ],
    'validation' => [
        'company_name_required' => 'Masukkan nama pelanggan.',
        'company_create_failed' => 'Pelanggan tidak dapat dibuat. Silakan coba lagi.',
    ],
    'pdf' => [
        'title' => 'PENAWARAN',
        'quoted_to' => 'Penawaran Untuk',
        'date' => 'Tanggal Penawaran',
        'valid_until' => 'Berlaku Hingga',
        'reference_number' => 'Nomor PO Pelanggan',
        'description' => 'Deskripsi',
        'quantity' => 'Jumlah',
        'unit_price' => 'Harga Satuan',
        'amount' => 'Jumlah',
        'total' => 'Total',
        'tax_number' => 'Nomor Pajak',
        'notes' => 'Syarat / Catatan',
        'company_placeholder' => 'Nama Perusahaan',
    ],
];
