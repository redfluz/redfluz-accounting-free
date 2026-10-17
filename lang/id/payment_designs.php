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
    'title' => 'Desain Kuitansi',
    'subtitle' => 'Konfigurasi tata letak PDF untuk kuitansi pembayaran tunggal.',
    'table' => [
        'column_name' => 'Nama Desain',
        'column_layout' => 'Tata Letak',
        'column_is_default' => 'Default',
    ],
    'form' => [
        'create_title' => 'Buat Desain Kuitansi',
        'edit_title' => 'Ubah Desain Kuitansi',
        'name' => 'Nama Desain',
        'layout' => 'Tata Letak Kuitansi',
        'standard_layout' => 'Kuitansi Standar',
        'compact_layout' => 'Kuitansi Ringkas',
        'primary_color' => 'Warna Utama',
        'secondary_color' => 'Warna Sekunder',
        'accent_color' => 'Warna Aksen',
        'font_family' => 'Font',
        'signature_label' => 'Label Tanda Tangan',
        'footer_text' => 'Teks Footer',
        'show_logo' => 'Tampilkan logo perusahaan',
        'show_bank_details' => 'Tampilkan detail bank/pembayaran',
        'show_signature' => 'Tampilkan area tanda tangan',
        'set_default' => 'Jadikan default kuitansi',
    ],
    'status' => [
        'default' => 'Default',
    ],
    'actions' => [
        'preview_pdf' => 'Pratinjau PDF',
    ],
    'messages' => [
        'created' => 'Desain kuitansi dibuat.',
        'updated' => 'Desain kuitansi diperbarui.',
    ],
    'pdf' => [
        'title' => 'KUITANSI RESMI',
        'received_from' => 'Diterima Dari',
        'received_amount' => 'Jumlah Diterima',
        'receipt_date' => 'Tanggal Kuitansi',
        'payment_method' => 'Metode Pembayaran',
        'linked_invoice' => 'Faktur Terkait',
        'bank_account' => 'Rekening Bank',
        'authorised_signature' => 'Tanda Tangan Berwenang',
        'bank_details_placeholder' => 'Detail rekening bank akan muncul di area ini.',
        'company_placeholder' => 'PERUSAHAAN ANDA',
        'sample_customer' => 'Sample Customer Sdn. Bhd.',
        'sample_payment_method' => 'Transfer Bank',
    ],
];
