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
    'title' => 'Manajemen Perusahaan',
    'subtitle' => 'Kelola perusahaan klien terdaftar dan detail bisnis.',
    'table' => [
        'column_name' => 'Nama Perusahaan',
        'column_email' => 'Alamat Email',
        'column_phone' => 'Nomor Telepon',
        'column_address' => 'Alamat',
    ],
    'logo' => [
        'label' => 'Logo perusahaan',
        'preview' => 'Pratinjau gambar',
        'upload_hint' => 'JPG, PNG atau WebP, maksimum 2 MB.',
        'replace_hint' => 'Unggah gambar baru untuk mengganti logo saat ini. Maksimum 2 MB.',
        'alt' => 'pratinjau logo :company',
    ],
    'roles' => [
        'title' => 'Peran Perusahaan',
        'is_debtor' => 'Debitur / Pelanggan',
        'is_creditor' => 'Kreditur / Pemasok',
        'is_courier' => 'Kurir / Pengangkut',
    ],
    'tabs' => [
        'ar_statement' => 'Laporan AR',
        'ap_statement' => 'Laporan AP',
    ],
    'statement' => [
        'document_number' => 'No. Dokumen',
        'applied_to' => 'Diterapkan Ke',
        'product' => 'Produk',
        'unknown_product' => 'Produk tidak dikenal',
        'type' => 'Jenis',
        'date' => 'Tanggal',
        'debit' => 'Debit',
        'credit' => 'Kredit',
        'balance' => 'Saldo',
        'closing_balance' => 'Saldo Akhir',
    ],
    'messages' => [
        'no_ar_transactions' => 'Tidak ada transaksi piutang yang ditemukan untuk perusahaan ini.',
        'no_ap_transactions' => 'Tidak ada transaksi hutang yang ditemukan untuk perusahaan ini.',
    ],
    'show' => [
        'tabs' => [
            'calendar' => 'Kalender',
            'contacts' => 'Kontak',
            'addresses' => 'Alamat',
            'files' => 'Berkas',
        ],
        'calendar' => [
            'previous' => 'Sebelumnya',
            'next' => 'Berikutnya',
            'sunday' => 'Minggu',
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'event_details' => 'Detail :type',
            'date' => 'Tanggal',
            'description' => 'Deskripsi',
            'close' => 'Tutup',
        ],
        'event_types' => [
            'appointment' => 'Janji Temu',
            'transaction' => 'Transaksi',
        ],
        'messages' => [
            'no_contacts' => 'Tidak ada kontak yang terkait dengan perusahaan ini.',
            'no_appointments' => 'Tidak ada janji temu yang dijadwalkan untuk perusahaan ini.',
            'no_files' => 'Tidak ada berkas yang terkait dengan perusahaan ini.',
        ],
    ],
    'addresses' => [
        'title' => 'Cabang / Alamat Perusahaan',
        'page_title' => 'Alamat Perusahaan',
        'subtitle' => 'Kelola beberapa alamat penagihan dan pengiriman untuk setiap perusahaan.',
        'main_address' => 'Alamat Utama',
        'company_placeholder' => 'Perusahaan',
        'all_companies' => 'Semua perusahaan',
        'search_placeholder' => 'Cari cabang, telepon, atau alamat',
        'create_title' => 'Tambah Alamat untuk :company',
        'edit_title' => 'Edit Alamat untuk :company',
        'fields' => [
            'company' => 'Perusahaan',
            'name' => 'Nama Cabang / Alamat',
            'phone' => 'Nomor Telepon',
            'address' => 'Alamat',
            'is_default' => 'Gunakan sebagai alamat pengiriman default',
            'default' => 'Default',
        ],
        'actions' => ['add' => 'Tambah Alamat', 'manage' => 'Kelola Alamat'],
        'messages' => [
            'created' => 'Alamat perusahaan berhasil dibuat.',
            'updated' => 'Alamat perusahaan berhasil diperbarui.',
            'deleted' => 'Alamat perusahaan berhasil dihapus.',
            'delete_confirm' => 'Hapus alamat perusahaan ini?',
            'empty' => 'Belum ada cabang atau alamat tambahan yang disimpan.',
        ],
    ],
    // Deletion is refused while these records still reference the entry.
    'validation' => [
        'in_use' => 'Syarikat ini tidak boleh dipadam kerana sudah digunakan oleh: :references.',
    ],
    'usage' => [
        'ledger_entries' => 'entri lejar',
        'customer_ledger_entries' => 'entri lejar pelanggan',
        'supplier_ledger_entries' => 'entri lejar pembekal',
        'product_movements' => 'pergerakan stok',
        'purchase_orders' => 'pesanan belian',
        'sales_orders' => 'pesanan jualan',
        'quotation_lines' => 'baris sebut harga',
        'delivery_orders' => 'nota penghantaran',
        'courier_deliveries' => 'penghantaran kourier',
        'supplied_products' => 'produk yang dibekalkan',
    ],
];
