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
    'title' => 'Desain Faktur',
    'subtitle' => 'Konfigurasi tata letak PDF untuk faktur penjualan dan pembelian.',
    'table' => [
        'column_name' => 'Nama Desain',
        'column_document_type' => 'Jenis Dokumen',
        'column_layout' => 'Tata Letak',
        'column_is_default' => 'Bawaan',
    ],
    'form' => [
        'create_title' => 'Buat Desain Faktur',
        'edit_title' => 'Ubah Desain Faktur',
        'name' => 'Nama Desain',
        'document_type' => 'Jenis Dokumen',
        'sales_invoice' => 'Faktur Penjualan',
        'purchase_invoice' => 'Faktur Pembelian',
        'choose_layout' => 'Pilih Tata Letak',
        'primary_color' => 'Warna Utama',
        'secondary_color' => 'Warna Sekunder',
        'accent_color' => 'Warna Aksen',
        'font_family' => 'Font',
        'footer_text' => 'Teks Footer',
        'show_logo' => 'Tampilkan logo perusahaan',
        'show_bank_details' => 'Tampilkan detail bank',
        'show_tax_details' => 'Tampilkan detail pajak',
        'set_default' => 'Jadikan bawaan',
    ],
    'status' => [
        'default' => 'Bawaan',
    ],
    'layouts' => [
        'classic' => ['label' => 'Biru Klasik', 'description' => 'Header terbagi, tabel bergaris'],
        'modern' => ['label' => 'Toska Modern', 'description' => 'Header spanduk, tabel zebra'],
        'minimal' => ['label' => 'Slate Minimal', 'description' => 'Header ringkas, tabel minimal'],
    ],
    'actions' => [
        'preview_pdf' => 'Pratinjau PDF',
    ],
    'messages' => [
        'created' => 'Desain faktur dibuat.',
        'updated' => 'Desain faktur diperbarui.',
    ],
    'pdf' => [
        'sales_title' => 'FAKTUR',
        'purchase_title' => 'FAKTUR PEMBELIAN',
        'bill_to' => 'Tagihkan Ke',
        'date' => 'Tanggal',
        'due_date' => 'Jatuh Tempo',
        'description' => 'Keterangan',
        'quantity' => 'Jml',
        'unit_price' => 'Harga Satuan',
        'amount' => 'Jumlah',
        'total' => 'Total',
        'payment_details' => 'Detail Pembayaran',
        'tax_details_placeholder' => 'Detail pajak akan ditampilkan di area ini.',
        'bank_details_placeholder' => 'Detail rekening bank akan ditampilkan di area ini.',
        'company_placeholder' => 'PERUSAHAAN ANDA',
        'sample_customer' => 'Pelanggan Contoh Sdn. Bhd.',
        'sample_address' => 'Kuala Lumpur, Malaysia',
        'sample_line_one' => 'Paket layanan profesional',
        'sample_line_two' => 'Layanan dukungan bulanan',
    ],
];
