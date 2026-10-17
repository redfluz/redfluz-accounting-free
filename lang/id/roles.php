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
    'title' => 'Manajemen Peran',
    'subtitle' => 'Tentukan peran pengguna dan tingkat kontrol akses di seluruh intranet.',
    'table' => [
        'column_id' => 'ID',
        'column_name' => 'Nama',
        'column_dashboard' => 'Dasbor',
        'column_description' => 'Deskripsi',
        'column_user_name' => 'Nama Pengguna',
        'column_email' => 'Email',
        'column_department' => 'Departemen',
        'column_created_at' => 'Dibuat Pada',
        'column_updated_at' => 'Diperbarui Pada',
        'column_is_default' => 'Apakah Default',
    ],
    'grid' => [
        'assigned_user' => 'Pengguna',
    ],
    'show' => [
        'title' => 'Detail Peran',
        'subtitle' => 'Lihat setiap pengguna dan departemen yang ditetapkan ke peran ini.',
        'total_users' => 'Total Pengguna',
        'users_title' => 'Pengguna yang Ditetapkan',
        'empty_users' => 'Tidak ada pengguna yang ditetapkan ke peran ini.',
    ],
];
