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
    'title' => 'Berkas & Catatan Perusahaan',
    'subtitle' => 'Kelola dokumen terlampir dan catatan untuk perusahaan ini',
    'actions' => [
        'add' => 'Tambah Berkas / Catatan',
        'edit' => 'Edit Berkas / Catatan',
        'show' => 'Detail Berkas / Catatan',
        'download' => 'Unduh Lampiran',
    ],
    'table' => [
        'column_company' => 'Perusahaan',
        'column_title' => 'Judul',
        'column_notes' => 'Catatan',
        'column_attachment' => 'Lampiran',
        'column_file_name' => 'Nama Berkas',
        'column_file_size' => 'Ukuran Berkas',
        'column_mime_type' => 'Tipe Berkas',
        'column_date' => 'Tanggal',
    ],
    'form' => [
        'label_company' => 'Perusahaan',
        'label_title' => 'Judul / Subjek',
        'label_notes' => 'Catatan',
        'label_attachment' => 'Lampiran',
        'label_remove_attachment' => 'Hapus lampiran berkas saat ini',
        'select_company' => 'Pilih Perusahaan',
    ],
    'messages' => [
        'no_attachment' => 'Tidak ada lampiran yang diunggah.',
    ],
];
