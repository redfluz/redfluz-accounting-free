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
    'title' => 'Faktur',
    'subtitle' => 'Kelola faktur penjualan dan pembelian barang tunggal.',
    'table' => [
        'column_entry' => 'Entri',
    ],
    'form' => [
        'create_title' => 'Buat Faktur',
        'edit_title' => 'Ubah Faktur Penjualan',
        'accounting_hint' => 'Biarkan akun kosong untuk menggunakan akun bagan bawaan yang telah dikonfigurasi.',
        'quick_customer_name' => 'Nama pelanggan',
        'quick_supplier_name' => 'Nama pemasok',
        'select_product' => 'Pilih Produk',
        'search_products' => 'Cari produk berdasarkan nama atau SKU...',
        'all_categories' => 'Semua Kategori',
        'all_types' => 'Semua Jenis',
        'sku' => 'SKU',
        'product_name' => 'Nama Produk',
        'category' => 'Kategori',
        'price' => 'Harga',
        'type_to_search' => 'Ketik minimal 2 karakter untuk mencari...',
        'no_products_found' => 'Tidak ada produk yang ditemukan.',
        'search_error' => 'Kesalahan mencari produk. Silakan coba lagi.',
    ],
    'sub_type' => [
        'sales_invoice' => 'Faktur Penjualan',
        'purchase_invoice' => 'Faktur Pembelian',
    ],
    'actions' => [
        'quick_customer' => 'Tambah cepat pelanggan',
        'add_customer' => 'Tambah Pelanggan',
        'quick_supplier' => 'Tambah cepat pemasok',
        'add_supplier' => 'Tambah Pemasok',
    ],
    'messages' => [
        'design_required' => 'Buat desain faktur untuk jenis dokumen ini sebelum mencetak.',
    ],
    'deduct_inventory' => [
        'label' => 'Kurangi Stok',
        'help' => 'Kurangi stok saat faktur ini diposting. Kosongkan untuk penjualan kredit di mana barang masih berada di gudang dan pesanan pengiriman akan mengurangi stok saat dikirim.',
        'title' => 'Kurangi Stok',
        'description' => 'Kurangi stok untuk semua baris atau hanya baris yang dipilih. Baris yang sudah dikurangi tidak dapat dikurangi lagi.',
        'confirm' => 'Kurangi stok untuk baris yang dipilih? Tindakan ini tidak dapat dibatalkan.',
        'deduct_all' => 'Kurangi Semua',
        'deduct_selected' => 'Kurangi yang Dipilih',
        'select_all' => 'Pilih semua',
        'select_at_least_one' => 'Pilih minimal satu baris untuk dikurangi.',
        'success' => 'Stok berhasil dikurangi.',
        'quantity' => 'Jumlah',
    ],
    'validation' => [
        'accounts_must_differ' => 'Akun debit dan kredit harus berbeda.',
        'contact_company_invalid' => 'Kontak yang dipilih tidak termasuk dalam perusahaan yang dipilih.',
        'debit_account_invalid' => 'Akun debit yang dipilih tidak valid untuk jenis faktur ini.',
        'credit_account_invalid' => 'Akun kredit yang dipilih tidak valid untuk jenis faktur ini.',
        'attachment_type_invalid' => 'Lampiran harus berupa file PNG, JPG, JPEG, PDF, DOC, atau DOCX.',
        'company_name_required' => 'Masukkan nama perusahaan.',
        'company_create_failed' => 'Perusahaan tidak dapat dibuat. Silakan coba lagi.',
    ],
    'pdf' => [
        'sales_invoice_title' => 'FAKTUR PENJUALAN',
        'purchase_invoice_title' => 'FAKTUR PEMBELIAN',
        'company' => 'Perusahaan',
        'date' => 'Tanggal',
        'description' => 'Keterangan',
        'quantity' => 'Kuantitas',
        'unit_price' => 'Harga Satuan',
        'amount' => 'Jumlah',
        'total' => 'Total',
        'tax_number' => 'Nomor Pajak',
        'payment_details' => 'Detail Pembayaran',
        'bank_details_placeholder' => 'Silakan merujuk ke instruksi pembayaran perusahaan.',
        'company_placeholder' => 'Nama Perusahaan',
    ],
];
