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
    'title' => 'Merek',
    'subtitle' => 'Kelola produsen dan merek yang ditetapkan pada produk.',
    'products_title' => 'Produk yang menggunakan merek ini',
    'products_empty' => 'Tidak ada produk yang menggunakan merek ini.',
    'form' => [
        'create_title' => 'Buat Merek',
        'edit_title' => 'Ubah Merek',
        'name' => 'Nama Merek',
        'description' => 'Deskripsi',
        'is_active' => 'Aktif',
        'none' => 'Tanpa Merek',
    ],
    'table' => [
        'name' => 'Merek',
        'description' => 'Deskripsi',
        'products' => 'Produk',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'],
    'validation' => [
        'brand_in_use' => 'Merek ini digunakan oleh satu atau lebih produk. Nonaktifkan saja daripada menghapusnya.',
        'name_already_exists' => 'Jenama dengan nama ini sudah wujud.',
    ],
];
