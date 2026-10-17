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
        'sales_invoice' => [
            'title' => 'Invois Jualan',
            'subtitle' => 'Urus invois pelanggan dan dokumen akaun belum terima.',
            'create_title' => 'Cipta Invois Jualan',
        ],
        'purchase_invoice' => [
            'title' => 'Invois Belian',
            'subtitle' => 'Urus invois pembekal dan dokumen akaun belum bayar.',
            'create_title' => 'Cipta Invois Belian',
        ],
    ],
    'title' => 'Lejar Am',
    'subtitle' => 'Lihat semua transaksi lejar yang telah dipos dari semua modul.',
    'invoices_title' => 'Senarai Invois',
    'payments_title' => 'Senarai Bayaran',
    'invoice_designs_title' => 'Reka Bentuk Invois',
    'invoice_designs_subtitle' => 'Konfigurasikan susun atur PDF untuk invois jualan dan belian.',
    'create_invoice_design_title' => 'Cipta Reka Bentuk Invois',
    'add_invoice_design' => 'Reka Bentuk Baharu',
    'no_invoice_designs' => 'Tiada reka bentuk invois dikonfigurasikan.',
    'design_name' => 'Nama',
    'document_type' => 'Jenis Dokumen',
    'choose_layout' => 'Pilih Susun Atur',
    'primary_color' => 'Warna Utama',
    'secondary_color' => 'Warna Sekunder',
    'accent_color' => 'Warna Aksen',
    'font_family' => 'Fon',
    'footer_text' => 'Teks Kaki Halaman',
    'show_logo' => 'Papar logo',
    'show_bank_details' => 'Papar maklumat bank',
    'show_tax_details' => 'Papar maklumat cukai',
    'set_as_default' => 'Tetapkan sebagai Tetapan',
    'save_design' => 'Simpan Reka Bentuk',
    'default' => 'Tetapan',
    'multi_invoices_title' => 'Pelbagai Invois',
    'create_multi_invoices_title' => 'Cipta Entri Pelbagai Invois',
    'multi_invoices_hint' => 'Cipta invois dengan sehingga 10 baris dan rekod resit atau baucar bayaran secara pilihan.',
    'optional_payment_hint' => 'Pilihan. Biarkan amaun kosong jika tiada bayaran direkodkan.',
    'delete_invoice_confirmation' => 'Buang invois belum dipos ini bersama bayaran berkaitan dan pergerakan stok belum selesai?',
    'invoice_lines' => 'Baris Invois',
    'complete_rows_needed' => 'Lengkapkan hanya baris yang diperlukan.',
    'tax_line_description' => 'Cukai invois',
    'linked_document' => 'Invois atau Pesanan Belian Berkaitan',
    'direct_payment' => 'Bayaran terus (tiada invois berkaitan)',
    'linked_document_hint' => 'Pilihan. Resit memaparkan invois jualan; baucar memaparkan invois belian.',
    'default_account_hint' => 'Biarkan akaun kosong untuk menggunakan akaun Tetapan dalam tetapan carta.',
    'receipt_debit_hint' => 'Akaun bank',
    'voucher_debit_hint' => 'Akaun liabiliti atau hutang pembekal',
    'receipt_credit_hint' => 'Akaun aset atau penghutang pelanggan',
    'voucher_credit_hint' => 'Akaun bank',
    'create_adjustment_title' => 'Pelarasan',
    'create_refund_title' => 'Bayaran Balik',
    'create_invoice_title' => 'Cipta Invois Baharu',
    'create_payment_title' => 'Cipta Bayaran Baharu',
    'edit_ledger_title' => 'Sunting Rekod Lejar',
    'add_entry' => 'Entri Baharu',
    'add_invoice' => 'Tambah Invois',
    'add_payment' => 'Tambah Bayaran',
    'save_invoice' => 'Simpan Invois',
    'save_payment' => 'Simpan Bayaran',
    'back' => 'Kembali',
    'filter' => 'Tapis',
    'reset' => 'Tetap Semula',
    'search_placeholder' => 'Cari Syarikat, Produk, Kenalan atau Akaun...',
    'no_invoice_records' => 'Tiada rekod invois ditemui.',
    'no_payment_records' => 'Tiada rekod bayaran ditemui.',
    'no_records' => 'Tiada rekod lejar ditemui.',
    'general' => 'Umum',
    'crm' => 'Syarikat',
    'attachment' => 'Lampiran',
    'accounting' => 'Perakaunan',
    'attachment_info' => 'Anda boleh melampirkan sehingga 1 fail. Format yang dibenarkan: PNG, JPG, JPEG, PDF, DOC, DOCX.',
    'current_saved_file' => 'Fail Semasa Disimpan',
    'accounting_info' => 'Pilihan untuk syarikat ringkas. Tentukan lejar secara manual jika penjejakan debit/kredit tersuai diperlukan.',
    'export_pdf' => 'Eksport PDF',
    'export_excel' => 'Eksport Excel',
    'all_types' => 'Jenis',
    'fixed_assets' => [
        'title' => 'Pembelian Aset Tetap',
        'subtitle' => 'Lihat aset tetap yang diiktiraf daripada invois belian yang diluluskan.',
        'action' => 'Aset Tetap',
        'posted_only' => 'Hanya pembelian aset tetap yang telah diluluskan dan dipos dipaparkan di sini.',
        'asset' => 'Aset',
        'asset_account' => 'Akaun Aset Tetap',
        'acquisition_cost' => 'Kos Pemerolehan',
        'total_acquisition_cost' => 'Jumlah Kos Pemerolehan',
        'no_records' => 'Tiada pembelian aset tetap yang telah dipos ditemui.',
    ],
    'actions' => [
        'view_source' => 'Buka dokumen sumber',
    ],
    'filters' => [
        'title' => 'Penapis',
        'account' => 'Akaun perincian',
    ],
    'table' => [
        'column_id' => 'ID', 'column_company' => 'Syarikat', 'column_document_number' => 'Dokumen', 'column_reference_number' => 'Rujukan',
        'column_product' => 'Produk', 'column_contact' => 'Kenalan', 'column_debit_acc' => 'Akaun Debit', 'column_credit_acc' => 'Akaun Kredit',
        'column_type' => 'Jenis', 'column_subtype' => 'Jenis Invois', 'column_date' => 'Tarikh', 'column_quantity' => 'Kuantiti',
        'column_unit_price' => 'Harga Seunit', 'column_amount' => 'Amaun', 'column_debit_amount' => 'Debit', 'column_credit_amount' => 'Kredit',
        'column_total_amount' => 'Jumlah', 'column_attachment_path' => 'Lampiran', 'column_status' => 'Status', 'column_notes' => 'Nota',
        'column_subtotal_amount' => 'Jumlah Kecil', 'column_discount_rate' => 'Kadar Diskaun', 'column_discount_amount' => 'Diskaun',
        'column_tax_rate' => 'Kadar Cukai', 'column_tax_amount' => 'Cukai', 'column_tax_mode' => 'Kaedah Cukai', 'column_line_total_amount' => 'Jumlah Baris',
        'column_description' => 'Keterangan', 'column_created_at' => 'Dicipta Pada', 'column_updated_at' => 'Dikemas Kini Pada', 'column_actions' => 'Tindakan',
        'column_due_date' => 'Tarikh Akhir', 'column_lines' => 'Baris', 'column_payment_status' => 'Status Bayaran',
    ],
    'payment_status' => [
        'paid' => 'Dibayar',
        'partial' => 'Separa',
        'unpaid' => 'Belum Dibayar',
    ],
    'tax_mode' => [
        'exclusive' => 'Eksklusif (cukai ditambah pada harga)',
        'inclusive' => 'Inklusif (cukai termasuk dalam harga)',
    ],
    'type' => [
        'invoice' => 'Invois',
        'payment' => 'Bayaran',
        'journal' => 'Entri Jurnal',
        'claim' => 'Tuntutan',
        'salary' => 'Gaji',
        'stock_take' => 'Kiraan Stok',
        'fixed_asset_write_off' => 'Hapus Kira Aset Tetap',
        'fixed_asset_depreciation' => 'Susut Nilai Aset Tetap',
        'fixed_asset_appreciation' => 'Peningkatan Nilai Aset Tetap',
        'fixed_asset_impairment' => 'Pengurangan Nilai Aset Tetap',
        'fixed_asset_disposal' => 'Pelupusan Aset Tetap',
    ],
    'sub_type' => [
        'purchase_debit_note' => 'Nota Debit Pembekal (Tambah Hutang)',
        'purchase_credit_note' => 'Nota Kredit Pembekal (Kurangkan Hutang)',
        'sales_invoice' => 'Invois Jualan', 'purchase_invoice' => 'Invois Belian', 'receipt' => 'Resit', 'payment_voucher' => 'Baucar Bayaran',
        'adjustment' => 'Pelarasan', 'increase_adjustment' => 'Tambah Invois', 'decrease_adjustment' => 'Kurangkan Invois',
        'receipt_refund' => 'Bayaran Balik Pelanggan', 'supplier_refund' => 'Bayaran Balik Pembekal',
        'refund' => 'Bayaran Balik Pelanggan', 'transfer' => 'Pindahan Dana', 'withdrawal' => 'Pengeluaran Akaun', 'journal' => 'Entri Jurnal',
        'stock_variance' => 'Perbezaan Stok',
        'manual_sales_invoice' => 'Invois Jualan Manual',
    ],
    'status' => ['pending' => 'Belum Selesai', 'paid' => 'Dibayar', 'cancelled' => 'Dibatalkan', 'posted' => 'Dipost'],
    'validation' => [
        'debit_sales_must_be_asset' => 'Akaun debit untuk invois jualan mestilah akaun aset.',
        'debit_purchase_must_be_expense_or_asset' => 'Akaun debit untuk invois belian mestilah akaun belanja atau aset.',
        'credit_sales_must_be_income' => 'Akaun kredit untuk invois jualan mestilah akaun pendapatan.',
        'credit_purchase_must_be_liability' => 'Akaun kredit untuk invois belian mestilah akaun liabiliti.',
        'debit_account_or_default_required' => 'Pilih akaun debit atau tetapkan akaun carta Tetapan.',
        'credit_account_or_default_required' => 'Pilih akaun kredit atau tetapkan akaun carta Tetapan.',
        'invoice_line_required' => 'Masukkan sekurang-kurangnya satu baris invois.',
        'invoice_line_product_or_description_required' => 'Pilih produk atau masukkan keterangan untuk baris invois ini.',
        'invoice_line_description_required' => 'Masukkan keterangan untuk baris invois pembelian ini.',
        'invoice_line_quantity_required' => 'Masukkan kuantiti untuk baris invois ini.',
        'invoice_line_unit_price_required' => 'Masukkan harga seunit untuk baris invois ini.',
        'debit_receipt_must_be_bank' => 'Akaun debit untuk resit mestilah akaun bank.',
        'debit_payment_must_be_liability' => 'Akaun debit untuk baucar pembayaran mestilah akaun liabiliti.',
        'credit_receipt_must_be_asset' => 'Akaun kredit untuk resit mestilah akaun aset.',
        'credit_payment_must_be_bank' => 'Akaun kredit untuk baucar pembayaran mestilah akaun bank.',
        'posted_invoice_delete_forbidden' => 'Invois yang telah dipos tidak boleh dibuang.',
        'active_stock_delete_forbidden' => 'Invois ini mempunyai pergerakan stok aktif dan tidak boleh dibuang.',
        'input_tax_account_required' => 'Tetapkan akaun Cukai Input dalam Tetapan Carta sebelum menyimpan invois belian bercukai.',
        'output_tax_account_required' => 'Tetapkan akaun Cukai Output dalam Tetapan Carta sebelum menyimpan invois jualan bercukai.',
        'input_tax_must_be_asset' => 'Akaun Cukai Input yang ditetapkan mestilah akaun aset.',
        'output_tax_must_be_liability' => 'Akaun Cukai Output yang ditetapkan mestilah akaun liabiliti.',
        'mixed_purchase_purposes' => 'Invois Belian terus hanya boleh mengandungi satu tujuan belian. Gunakan aliran Permintaan Belian dan Pesanan Belian untuk gabungan stok, kegunaan dalaman, dan aset tetap.',
        'purchase_purpose_account_mismatch' => 'Akaun debit tidak sepadan dengan tujuan belian. Stok mesti menggunakan akaun stok yang ditetapkan, kegunaan dalaman mesti menggunakan akaun perbelanjaan, dan aset tetap mesti menggunakan akaun aset tetap.',
    ],

    'cancelled_title' => 'Dokumen Dibatalkan',
    'cancelled_subtitle' => 'Lihat semua invois dan pelarasan yang dibatalkan.',
    'history_title' => 'Invois Disiarkan',
    'history_subtitle' => 'Lihat semua invois yang telah disiarkan.',
    'generated_at' => 'Dijana pada :date',
    'status_cancelled' => 'Dibatalkan',
];
