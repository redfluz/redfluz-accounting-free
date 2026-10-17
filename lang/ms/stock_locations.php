<?php

return [
    'title' => 'Lokasi Stok',
    'subtitle' => 'Urus cawangan, gudang di bawahnya dan pengguna yang dibenarkan mengendalikan stok di lokasi tersebut.',
    'form' => [
        'create_title' => 'Cipta Lokasi Stok',
        'edit_title' => 'Sunting Lokasi Stok',
        'code' => 'Kod Lokasi',
        'name' => 'Nama Lokasi',
        'type' => 'Jenis Lokasi',
        'parent_branch' => 'Cawangan Induk',
        'address' => 'Alamat',
        'active' => 'Aktif',
        'users' => 'Pengguna Dibenarkan',
    ],
    'type' => ['branch' => 'Cawangan', 'warehouse' => 'Gudang', 'bin' => 'Bin', 'pallet' => 'Pallet'],
    'structure' => [
        'title' => 'Struktur Gudang',
        'subtitle' => 'Cipta hierarki fizikal: lokasi stok → tingkat → baris → bin atau pallet.',
        'floor' => 'Tambah Tingkat',
        'line' => 'Tambah Baris',
        'bin_pallet' => 'Tambah Bin / Pallet',
    ],
    'table' => [
        'code' => 'Kod', 'name' => 'Lokasi', 'type' => 'Jenis',
        'branch' => 'Cawangan', 'users' => 'Pengguna', 'status' => 'Status', 'action' => 'Tindakan',
    ],
    'actions' => ['save' => 'Simpan Lokasi'],
    'messages' => [
        'empty' => 'Tiada lokasi stok ditemui.',
        'delete_confirm' => 'Padam lokasi stok yang belum digunakan ini?',
        'user_access' => 'Hanya pengguna yang dipilih boleh menerima atau menggunakan stok dari lokasi ini.',
    ],
    'validation' => [
        'branch_required' => 'Pilih cawangan yang memiliki gudang ini.',
        'location_in_use' => 'Lokasi ini tidak boleh dipadam kerana mempunyai pergerakan stok atau gudang.',
    ],
];
