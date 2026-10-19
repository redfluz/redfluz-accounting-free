<?php

declare(strict_types=1);

return [
    'title' => 'Pemindahan Bank',
    'subtitle' => 'Rekodkan kas dalam perjalanan sampai ke akaun bank',

    'tabs' => [
        'pending' => 'Menunggu',
        'posted' => 'Diluluskan',
        'cancelled' => 'Dibatalkan',
    ],

    'table' => [
        'column_from' => 'Dari Dalam Perjalanan',
        'column_to' => 'Ke Akaun Bank',
    ],

    'form' => [
        'create_title' => 'Pemindahan Bank Baharu',
        'edit_title' => 'Sunting Pemindahan Bank',
        'bank_account' => 'Akaun Bank',
        'bank_account_placeholder' => 'Pilih akaun bank',
        'amount' => 'Jumlah',
        'date' => 'Tarikh',
        'reference_number' => 'Rujukan Bank',
        'reference_hint' => 'Nombor slip deposit atau surat krediti, untuk memadankan dengan penyata bank.',
        'description' => 'Penerangan',
        'notes' => 'Nota',
        'from' => 'Dari',
        'available' => 'Tersedia dalam perjalanan:',
        'attachment' => 'Resit Deposit',
        'attachment_hint' => 'Slip deposit, surat krediti bank, atau gambar baris penyata yang menunjukkan bank telah mengreditkan ini. Biarkan kosong semasa menyunting untuk mengekalkan fail semasa.',
        'attachment_current' => 'Dilampirkan sekarang:',
    ],

    'show' => [
        'posting' => 'Catatan',
        'posted_explanation' => 'Akaun bank telah didebitan dan kas dalam perjalanan dikreditkan, mengosongkan wang itu daripada dalam perjalanan ke dalam bank.',
        'not_posted' => 'Belum dimasukkan ke lejar. Bank belum mengreditkan lagi.',
        'attachment' => 'Resit Deposit',
        'debit' => 'Debit',
        'credit' => 'Kredit',
    ],

    'validation' => [
        'account_inactive' => 'Akaun bank tersebut tidak tersedia.',
        'transit_empty' => 'Tiada apa-apa dalam kas dalam perjalanan, jadi tiada apa-apa untuk dipindahkan.',
        'exceeds_transit' => 'Kas dalam perjalanan hanya memegang :available.',
    ],
];
