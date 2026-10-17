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
    'title' => 'Reka Bentuk Invois',
    'subtitle' => 'Konfigurasikan susun atur PDF untuk invois jualan dan belian.',
    'table' => [
        'column_name' => 'Nama Reka Bentuk',
        'column_document_type' => 'Jenis Dokumen',
        'column_layout' => 'Susun Atur',
        'column_is_default' => 'Tetapan',
    ],
    'form' => [
        'create_title' => 'Cipta Reka Bentuk Invois',
        'edit_title' => 'Sunting Reka Bentuk Invois',
        'name' => 'Nama Reka Bentuk',
        'document_type' => 'Jenis Dokumen',
        'sales_invoice' => 'Invois Jualan',
        'purchase_invoice' => 'Invois Belian',
        'choose_layout' => 'Pilih Susun Atur',
        'primary_color' => 'Warna Utama',
        'secondary_color' => 'Warna Sekunder',
        'accent_color' => 'Warna Aksen',
        'font_family' => 'Fon',
        'footer_text' => 'Teks Kaki Halaman',
        'show_logo' => 'Papar logo syarikat',
        'show_bank_details' => 'Papar maklumat bank',
        'show_tax_details' => 'Papar maklumat cukai',
        'set_default' => 'Tetapkan sebagai Tetapan',
    ],
    'status' => [
        'default' => 'Tetapan',
    ],
    'layouts' => [
        'classic' => ['label' => 'Biru Klasik', 'description' => 'Pengepala berpisah, jadual bergaris'],
        'modern' => ['label' => 'Hijau Kebiruan Moden', 'description' => 'Pengepala sepanduk, jadual berjalur'],
        'minimal' => ['label' => 'Kelabu Minimal', 'description' => 'Pengepala padat, jadual minimal'],
    ],
    'actions' => [
        'preview_pdf' => 'PDF',
    ],
    'messages' => [
        'created' => 'Reka bentuk invois berjaya dicipta.',
        'updated' => 'Reka bentuk invois berjaya dikemas kini.',
    ],
    'pdf' => [
        'sales_title' => 'INVOIS',
        'purchase_title' => 'INVOIS BELIAN',
        'bill_to' => 'Dibilkan Kepada',
        'date' => 'Tarikh',
        'due_date' => 'Tarikh Akhir',
        'description' => 'Keterangan',
        'quantity' => 'Kuantiti',
        'unit_price' => 'Harga Seunit',
        'amount' => 'Amaun',
        'total' => 'Jumlah',
        'payment_details' => 'Maklumat Bayaran',
        'tax_details_placeholder' => 'Maklumat cukai akan dipaparkan di ruang ini.',
        'bank_details_placeholder' => 'Maklumat akaun bank akan dipaparkan di ruang ini.',
        'company_placeholder' => 'SYARIKAT ANDA',
        'sample_customer' => 'Contoh Pelanggan Sdn. Bhd.',
        'sample_address' => 'Kuala Lumpur, Malaysia',
        'sample_line_one' => 'Pakej perkhidmatan profesional',
        'sample_line_two' => 'Perkhidmatan sokongan bulanan',
    ],
];
