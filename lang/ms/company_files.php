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
    'title' => 'Fail & Nota Syarikat',
    'subtitle' => 'Urus lampiran dokumen dan nota untuk syarikat ini',
    'actions' => [
        'add' => 'Tambah Fail / Nota',
        'edit' => 'Kemaskini Fail / Nota',
        'show' => 'Butiran Fail / Nota',
        'download' => 'Muat Turun Lampiran',
    ],
    'table' => [
        'column_company' => 'Syarikat',
        'column_title' => 'Tajuk',
        'column_notes' => 'Nota',
        'column_attachment' => 'Lampiran',
        'column_file_name' => 'Nama Fail',
        'column_file_size' => 'Saiz Fail',
        'column_mime_type' => 'Jenis Fail',
        'column_date' => 'Tarikh',
    ],
    'form' => [
        'label_company' => 'Syarikat',
        'label_title' => 'Tajuk / Subjek',
        'label_notes' => 'Nota',
        'label_attachment' => 'Lampiran',
        'label_remove_attachment' => 'Buang lampiran fail sedia ada',
        'select_company' => 'Pilih Syarikat',
    ],
    'messages' => [
        'no_attachment' => 'Tiada lampiran dimuat naik.',
    ],
];
