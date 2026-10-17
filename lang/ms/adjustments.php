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
    'title' => 'Pelarasan Jualan',
    'subtitle' => 'Urus dan cetak nota debit serta nota kredit yang berkaitan dengan invois jualan.',
    'table' => [
        'column_entry' => 'Entri',
    ],
    'form' => [
        'linked_invoice' => 'Invois Jualan Berkaitan',
    ],
    'sub_type' => [
        'increase_adjustment' => 'Nota Debit',
        'decrease_adjustment' => 'Nota Kredit',
    ],
    'messages' => [
        'design_required' => 'Cipta reka bentuk PDF invois jualan sebelum mencetak pelarasan ini.',
    ],
    'validation' => [
    ],
    'pdf' => [
        'debit_note_title' => 'NOTA DEBIT',
        'credit_note_title' => 'NOTA KREDIT',
        'issued_to' => 'Dikeluarkan Kepada',
        'linked_invoice' => 'Invois Berkaitan',
        'date' => 'Tarikh',
        'description' => 'Keterangan',
        'quantity' => 'Kuantiti',
        'unit_price' => 'Harga Seunit',
        'amount' => 'Amaun',
        'total' => 'Jumlah',
        'company_placeholder' => 'SYARIKAT ANDA',
    ],

    'cancelled_title' => 'Pelarasan Dibatalkan',
    'cancelled_subtitle' => 'Lihat semua pelarasan jualan yang dibatalkan.',
    'history_title' => 'Pelarasan Jualan Disiarkan',
    'history_subtitle' => 'Lihat semua pelarasan jualan yang telah disiarkan.',
];
