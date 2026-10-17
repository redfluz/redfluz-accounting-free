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
    'title' => 'Penyesuaian Pembelian',
    'subtitle' => 'Kelola dan cetak nota debit dan nota kredit yang terkait dengan faktur pembelian terposting.',
    'table' => [
        'column_entry' => 'Entri',
    ],
    'form' => [
        'linked_invoice' => 'Faktur Pembelian Terkait',
    ],
    'sub_type' => [
        'purchase_debit_note' => 'Nota Debit',
        'purchase_credit_note' => 'Nota Kredit',
    ],
    'messages' => [
        'design_required' => 'Buat desain PDF faktur pembelian sebelum mencetak penyesuaian ini.',
    ],
    'validation' => [
        'debit_account_invalid' => 'Pilih akun debit pembelian atau utang yang sesuai dengan nota ini.',
        'credit_account_invalid' => 'Pilih akun kredit pembelian atau utang yang sesuai dengan nota ini.',
    ],
    'pdf' => [
        'debit_note_title' => 'NOTA DEBIT',
        'credit_note_title' => 'NOTA KREDIT',
        'issued_to' => 'Diterbitkan Untuk',
        'linked_invoice' => 'Faktur Terkait',
        'date' => 'Tanggal',
        'description' => 'Deskripsi',
        'quantity' => 'Jumlah',
        'unit_price' => 'Harga Satuan',
        'amount' => 'Jumlah',
        'total' => 'Total',
        'company_placeholder' => 'PERUSAHAAN ANDA',
    ],

    'cancelled_title' => 'Penyesuaian Pembelian Dibatalkan',
    'cancelled_subtitle' => 'Lihat semua penyesuaian pembelian yang dibatalkan.',
    'history_title' => 'Penyesuaian Pembelian Terposting',
    'history_subtitle' => 'Lihat semua penyesuaian pembelian yang terposting.',
];
