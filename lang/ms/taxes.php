<?php

return [
    'title' => 'Cukai',
    'subtitle' => 'Urus kadar cukai yang boleh digunakan semula dan kaedah pengiraan Tetapannya.',
    'form' => [
        'create_title' => 'Tambah Cukai',
        'edit_title' => 'Sunting Cukai',
        'code' => 'Kod',
        'name' => 'Nama',
        'rate' => 'Kadar',
        'default_mode' => 'Kaedah Cukai Tetapan',
        'is_active' => 'Aktif',
        'save' => 'Simpan Cukai',
        'none' => 'Tanpa Cukai',
        'manual' => 'Kadar Cukai Manual',
        'selection' => 'Cukai',
        'manual_hint' => 'Masukkan kadar cukai dan pilih pengiraan inklusif atau eksklusif.',
    ],
    'table' => [
        'code' => 'Kod',
        'name' => 'Nama',
        'rate' => 'Kadar',
        'mode' => 'Kaedah Tetapan',
        'status' => 'Status',
        'action' => 'Tindakan',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'],
    'messages' => [
        'empty' => 'Tiada cukai ditemui.',
        'created' => 'Cukai berjaya dicipta.',
        'updated' => 'Cukai berjaya dikemas kini.',
        'deleted' => 'Cukai berjaya dipadam.',
        'delete_confirm' => 'Padam cukai ini? Invois sedia ada akan mengekalkan butiran cukai yang disimpan.',
        'inactive' => 'Cukai yang dipilih tidak aktif.',
    ],
];
