<?php

return [
    'title' => 'Kategori Produk',
    'subtitle' => 'Kelola pengelompokan tingkat pertama yang digunakan untuk mengorganisir produk.',
    'confirm_delete' => 'Hapus kategori yang tidak digunakan ini?',
    'form' => [
        'create_title' => 'Buat Kategori Produk',
        'edit_title' => 'Ubah Kategori Produk',
        'code' => 'Kode Kategori',
        'name' => 'Nama Kategori',
        'is_active' => 'Aktif',
    ],
    'table' => [
        'code' => 'Kode',
        'name' => 'Kategori',
        'types' => 'Jenis',
        'products' => 'Produk',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'],
    'validation' => [
        'in_use' => 'Kategori ini memiliki jenis produk atau produk. Nonaktifkan alih-alih menghapusnya.',
    ],
];
