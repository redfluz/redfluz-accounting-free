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
    'title' => 'Pengurusan Syarikat',
    'subtitle' => 'Urus syarikat pelanggan yang berdaftar dan maklumat perniagaan.',
    'table' => [
        'column_name' => 'Nama',
        'column_email' => 'E-mel',
        'column_phone' => 'Telefon',
        'column_address' => 'Alamat',
    ],
    'logo' => [
        'label' => 'Logo syarikat',
        'preview' => 'Pratonton imej',
        'upload_hint' => 'JPG, PNG atau WebP, maksimum 2 MB.',
        'replace_hint' => 'Muat naik imej baharu untuk menggantikan logo semasa. Maksimum 2 MB.',
        'alt' => 'Pratonton logo :company',
    ],
    'roles' => [
        'title' => 'Peranan Syarikat',
        'is_debtor' => 'Penghutang / Pelanggan',
        'is_creditor' => 'Pemiutang / Pembekal',
        'is_courier' => 'Kurier / Pengangkut',
    ],
    'tabs' => [
        'ar_statement' => 'Akaun Belum Terima',
        'ap_statement' => 'Akaun Belum Bayar',
    ],
    'statement' => [
        'document_number' => 'No. Dokumen',
        'applied_to' => 'Dipadankan Dengan',
        'product' => 'Produk',
        'unknown_product' => 'Produk tidak diketahui',
        'type' => 'Jenis',
        'date' => 'Tarikh',
        'debit' => 'Debit',
        'credit' => 'Kredit',
        'balance' => 'Baki',
        'closing_balance' => 'Baki Akhir',
    ],
    'messages' => [
        'no_ar_transactions' => 'Tiada transaksi akaun belum terima ditemui untuk syarikat ini.',
        'no_ap_transactions' => 'Tiada transaksi akaun belum bayar ditemui untuk syarikat ini.',
    ],
    'show' => [
        'tabs' => [
            'calendar' => 'Kalendar',
            'contacts' => 'Kenalan',
            'addresses' => 'Alamat',
            'files' => 'Fail',
        ],
        'calendar' => [
            'previous' => 'Sebelumnya',
            'next' => 'Seterusnya',
            'sunday' => 'Ahad',
            'monday' => 'Isnin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Khamis',
            'friday' => 'Jumaat',
            'saturday' => 'Sabtu',
            'event_details' => 'Butiran :type',
            'date' => 'Tarikh',
            'description' => 'Keterangan',
            'close' => 'Tutup',
        ],
        'event_types' => [
            'appointment' => 'Temujanji',
            'transaction' => 'Transaksi',
        ],
        'messages' => [
            'no_contacts' => 'Tiada kenalan dikaitkan dengan syarikat ini.',
            'no_appointments' => 'Tiada temujanji dijadualkan untuk syarikat ini.',
            'no_files' => 'Tiada fail dikaitkan dengan syarikat ini.',
        ],
    ],
    'addresses' => [
        'title' => 'Cawangan / Alamat Syarikat',
        'page_title' => 'Alamat Syarikat',
        'subtitle' => 'Urus pelbagai alamat bil dan penghantaran bagi setiap syarikat.',
        'main_address' => 'Alamat Utama',
        'company_placeholder' => 'Syarikat',
        'all_companies' => 'Semua syarikat',
        'search_placeholder' => 'Cari cawangan, telefon atau alamat',
        'create_title' => 'Tambah Alamat untuk :company',
        'edit_title' => 'Sunting Alamat untuk :company',
        'fields' => [
            'company' => 'Syarikat',
            'name' => 'Nama Cawangan / Alamat',
            'phone' => 'Nombor Telefon',
            'address' => 'Alamat',
            'is_default' => 'Gunakan sebagai alamat penghantaran lalai',
            'default' => 'Tetapan',
        ],
        'actions' => ['add' => 'Tambah Alamat', 'manage' => 'Urus Alamat'],
        'messages' => [
            'created' => 'Alamat syarikat berjaya dicipta.',
            'updated' => 'Alamat syarikat berjaya dikemas kini.',
            'deleted' => 'Alamat syarikat berjaya dipadam.',
            'delete_confirm' => 'Padam alamat syarikat ini?',
            'empty' => 'Tiada cawangan atau alamat tambahan disimpan.',
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
