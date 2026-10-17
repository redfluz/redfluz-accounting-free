<?php

return [
    'title' => 'Jenis Produk',
    'subtitle' => 'Kelola jenis produk tingkat kedua dan kategori induknya.',
    'confirm_delete' => 'Hapus jenis produk yang tidak digunakan ini?',
    'form' => [
        'create_title' => 'Buat Jenis Produk',
        'edit_title' => 'Ubah Jenis Produk',
        'category' => 'Kategori Induk',
        'select_category' => 'Pilih kategori',
        'code' => 'Kode Jenis',
        'name' => 'Nama Jenis',
        'is_active' => 'Aktif',
    ],
    'table' => [
        'code' => 'Kode',
        'name' => 'Jenis Produk',
        'category' => 'Kategori',
        'products' => 'Produk',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'],
    'validation' => [
        'in_use' => 'Jenis produk ini digunakan oleh satu atau lebih produk. Nonaktifkan alih-alih menghapusnya.',
    ],
];
