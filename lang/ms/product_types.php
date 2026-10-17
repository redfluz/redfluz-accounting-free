<?php

return [
    'title' => 'Jenis Produk',
    'subtitle' => 'Urus jenis produk peringkat kedua dan kategori induknya.',
    'confirm_delete' => 'Padam jenis produk yang tidak digunakan ini?',
    'form' => [
        'create_title' => 'Cipta Jenis Produk',
        'edit_title' => 'Sunting Jenis Produk',
        'category' => 'Kategori Induk',
        'select_category' => 'Pilih kategori',
        'code' => 'Kod Jenis',
        'name' => 'Nama Jenis',
        'is_active' => 'Aktif',
    ],
    'table' => [
        'code' => 'Kod',
        'name' => 'Jenis Produk',
        'category' => 'Kategori',
        'products' => 'Produk',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'],
    'validation' => [
        'in_use' => 'Jenis produk ini digunakan oleh satu atau lebih produk. Nyahaktifkannya dan jangan padam.',
    ],
];
