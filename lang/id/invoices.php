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
        'confirm' => 'Kurangi stok untuk faktur ini? Tindakan ini hanya dapat dilakukan sekali dan tidak dapat dibatalkan dengan mengedit faktur.',
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
