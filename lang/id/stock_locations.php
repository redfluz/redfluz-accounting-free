<?php

return [
    'title' => 'Lokasi Stok',
    'subtitle' => 'Kelola cabang, gudang, serta pengguna yang diizinkan mengelola stok di lokasi tersebut.',
    'form' => [
        'create_title' => 'Buat Lokasi Stok',
        'edit_title' => 'Ubah Lokasi Stok',
        'code' => 'Kode Lokasi',
        'name' => 'Nama Lokasi',
        'type' => 'Jenis Lokasi',
        'parent_branch' => 'Cabang Induk',
        'address' => 'Alamat',
        'active' => 'Aktif',
        'users' => 'Pengguna yang Diizinkan',
    ],
    'type' => ['branch' => 'Cabang', 'warehouse' => 'Gudang', 'bin' => 'Bin', 'pallet' => 'Pallet'],
    'structure' => [
        'title' => 'Struktur Gudang',
        'subtitle' => 'Buat hierarki fisik: lokasi stok → lantai → baris → bin atau pallet.',
        'floor' => 'Tambah Lantai',
        'line' => 'Tambah Baris',
        'bin_pallet' => 'Tambah Bin / Pallet',
    ],
    'table' => [
        'code' => 'Kode', 'name' => 'Lokasi', 'type' => 'Jenis',
        'branch' => 'Cabang', 'users' => 'Pengguna', 'status' => 'Status', 'action' => 'Tindakan',
    ],
    'actions' => ['save' => 'Simpan Lokasi'],
    'messages' => [
        'empty' => 'Tidak ada lokasi stok yang ditemukan.',
        'delete_confirm' => 'Hapus lokasi stok yang tidak terpakai ini?',
        'user_access' => 'Hanya pengguna terpilih yang dapat menerima atau menggunakan stok dari lokasi ini.',
    ],
    'validation' => [
        'branch_required' => 'Pilih cabang yang memiliki gudang ini.',
        'location_in_use' => 'Lokasi ini tidak dapat dihapus karena memiliki riwayat pergerakan stok atau gudang.',
    ],
];
