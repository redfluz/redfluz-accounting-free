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
    'title' => 'Penyesuaian Penjualan',
    'subtitle' => 'Kelola dan cetak nota debit dan nota kredit yang terhubung ke faktur penjualan.',
    'table' => [
        'column_entry' => 'Entri',
    ],
    'form' => [
        'linked_invoice' => 'Faktur Penjualan Terkait',
    ],
    'sub_type' => [
        'increase_adjustment' => 'Nota Debit',
        'decrease_adjustment' => 'Nota Kredit',
    ],
    'messages' => [
        'design_required' => 'Buat desain PDF faktur penjualan sebelum mencetak penyesuaian ini.',
    ],
    'validation' => [
    ],
    'pdf' => [
        'debit_note_title' => 'NOTA DEBIT',
        'credit_note_title' => 'NOTA KREDIT',
        'issued_to' => 'Diterbitkan Kepada',
        'linked_invoice' => 'Faktur Terkait',
        'date' => 'Tanggal',
        'description' => 'Deskripsi',
        'quantity' => 'Kuantitas',
        'unit_price' => 'Harga Satuan',
        'amount' => 'Jumlah',
        'total' => 'Total',
        'company_placeholder' => 'PERUSAHAAN ANDA',
    ],

    'cancelled_title' => 'Penyesuaian yang Dibatalkan',
    'cancelled_subtitle' => 'Lihat semua penyesuaian penjualan yang dibatalkan.',
    'history_title' => 'Penyesuaian Penjualan Diposting',
    'history_subtitle' => 'Lihat semua penyesuaian penjualan yang telah diposting.',
];
