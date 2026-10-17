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
    'title' => 'Jenama',
    'subtitle' => 'Urus pengeluar dan jenama yang ditetapkan kepada produk.',
    'products_title' => 'Produk yang menggunakan jenama ini',
    'products_empty' => 'Tiada produk menggunakan jenama ini.',
    'form' => [
        'create_title' => 'Cipta Jenama',
        'edit_title' => 'Sunting Jenama',
        'name' => 'Nama Jenama',
        'description' => 'Keterangan',
        'is_active' => 'Aktif',
        'none' => 'Tiada Jenama',
    ],
    'table' => [
        'name' => 'Jenama',
        'description' => 'Keterangan',
        'products' => 'Produk',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'],
    'validation' => [
        'brand_in_use' => 'Jenama ini digunakan oleh satu atau lebih produk. Nyahaktifkannya dan jangan padam.',
        'name_already_exists' => 'Jenama dengan nama ini telah wujud.',
    ],
];
