<?php

return [
    'title' => 'Pajak',
    'subtitle' => 'Kelola tarif pajak yang dapat digunakan ulang dan mode perhitungan standarnya.',
    'form' => [
        'create_title' => 'Tambah Pajak',
        'edit_title' => 'Ubah Pajak',
        'code' => 'Kode',
        'name' => 'Nama',
        'rate' => 'Tarif',
        'default_mode' => 'Mode Pajak Default',
        'is_active' => 'Aktif',
        'save' => 'Simpan Pajak',
        'none' => 'Tanpa Pajak',
        'manual' => 'Tarif Pajak Manual',
        'selection' => 'Pajak',
        'manual_hint' => 'Masukkan tarif pajak dan pilih perhitungan inklusif atau eksklusif.',
    ],
    'table' => [
        'code' => 'Kode',
        'name' => 'Nama',
        'rate' => 'Tarif',
        'mode' => 'Mode Default',
        'status' => 'Status',
        'action' => 'Tindakan',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Nonaktif'],
    'messages' => [
        'empty' => 'Tidak ada pajak yang ditemukan.',
        'created' => 'Pajak berhasil dibuat.',
        'updated' => 'Pajak berhasil diperbarui.',
        'deleted' => 'Pajak berhasil dihapus.',
        'delete_confirm' => 'Hapus pajak ini? Faktur yang ada akan tetap menyimpan detail pajak yang tersimpan.',
        'inactive' => 'Pajak yang dipilih tidak aktif.',
    ],
];
