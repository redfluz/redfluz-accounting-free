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
    'title' => 'Penyedia Dompet Digital',
    'subtitle' => 'Kelola penerbit dompet digital yang ditetapkan pada rekening bank.',
    'accounts_title' => 'Rekening yang menggunakan penyedia ini',
    'accounts_empty' => 'Tidak ada rekening yang menggunakan penyedia ini.',
    'form' => [
        'create_title' => 'Buat Penyedia Dompet Digital',
        'edit_title' => 'Ubah Penyedia Dompet Digital',
        'name' => 'Nama Penyedia',
        'country' => 'Negara',
        'identifier_label' => 'Label Identifikator',
        'chart' => 'Bagan Akun',
        'chart_placeholder' => 'Pilih bagan akun',
        'is_active' => 'Aktif',
    ],
    'table' => [
        'name' => 'Penyedia',
        'country' => 'Negara',
        'identifier_label' => 'Identifikator',
        'chart' => 'Bagan Akun',
        'accounts' => 'Rekening',
        'status' => 'Status',
    ],
    'accounts_table' => [
        'account' => 'Rekening',
        'number' => 'Nomor Rekening',
        'chart' => 'Bagan Akun',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'],
    'validation' => [
        'provider_in_use' => 'Penyedia ini digunakan oleh satu atau lebih rekening. Nonaktifkan saja daripada menghapusnya.',
    ],
];
