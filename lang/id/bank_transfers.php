<?php

declare(strict_types=1);

return [
    'title' => 'Pemindahan Bank',
    'subtitle' => 'Catat kas dalam perjalanan yang tiba di akun bank',

    'tabs' => [
        'pending' => 'Menunggu',
        'posted' => 'Disetujui',
        'cancelled' => 'Dibatalkan',
    ],

    'table' => [
        'column_from' => 'Dari Dalam Perjalanan',
        'column_to' => 'Ke Akun Bank',
    ],

    'form' => [
        'create_title' => 'Pemindahan Bank Baru',
        'edit_title' => 'Sunting Pemindahan Bank',
        'bank_account' => 'Akun Bank',
        'bank_account_placeholder' => 'Pilih akun bank',
        'amount' => 'Jumlah',
        'date' => 'Tanggal',
        'reference_number' => 'Referensi Bank',
        'reference_hint' => 'Nomor slip setoran atau surat kredit, untuk dicocokkan dengan rekening koran bank.',
        'description' => 'Keterangan',
        'notes' => 'Catatan',
        'from' => 'Dari',
        'available' => 'Tersedia dalam perjalanan:',
        'attachment' => 'Bukti Setoran',
        'attachment_hint' => 'Slip setoran, surat kredit bank, atau foto baris rekening koran yang menunjukkan bank sudah mengkreditkan ini. Biarkan kosong saat menyunting untuk mempertahankan berkas yang ada.',
        'attachment_current' => 'Saat ini terlampir:',
    ],

    'show' => [
        'posting' => 'Pencatatan',
        'posted_explanation' => 'Akun bank telah didebet dan kas dalam perjalanan dikreditkan, mengosongkan uang itu dari dalam perjalanan ke dalam bank.',
        'not_posted' => 'Belum dimasukkan ke buku besar. Bank belum mengkreditkan.',
        'attachment' => 'Bukti Setoran',
        'debit' => 'Debet',
        'credit' => 'Kredit',
    ],

    'validation' => [
        'account_inactive' => 'Akun bank tersebut tidak tersedia.',
        'transit_empty' => 'Tidak ada apa-apa dalam kas dalam perjalanan, jadi tidak ada apa-apa untuk dipindahkan.',
        'exceeds_transit' => 'Kas dalam perjalanan hanya memegang :available.',
    ],
];
