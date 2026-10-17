<?php

return [
    'title' => 'Kategori Produk',
    'subtitle' => 'Urus kumpulan peringkat pertama yang digunakan untuk menyusun produk.',
    'confirm_delete' => 'Padam kategori yang tidak digunakan ini?',
    'form' => [
        'create_title' => 'Cipta Kategori Produk',
        'edit_title' => 'Sunting Kategori Produk',
        'code' => 'Kod Kategori',
        'name' => 'Nama Kategori',
        'is_active' => 'Aktif',
    ],
    'table' => [
        'code' => 'Kod',
        'name' => 'Kategori',
        'types' => 'Jenis',
        'products' => 'Produk',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'],
    'validation' => [
        'in_use' => 'Kategori ini mempunyai jenis atau produk. Nyahaktifkannya dan jangan padam.',
    ],
];
