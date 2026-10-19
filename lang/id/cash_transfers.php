<?php

declare(strict_types=1);

return [
    'title' => 'Pemindahan Tunai',
    'subtitle' => 'Pindahkan kelebihan melebihi modal minimum laci ke akaun kas dalam perjalanan',

    'tabs' => [
        'pending' => 'Menunggu',
        'posted' => 'Diluluskan',
        'cancelled' => 'Dibatalkan',
    ],

    'table' => [
        'column_cash_account' => 'Akaun Tunai',
        'column_to' => 'Ke Dalam Perjalanan',
    ],

    'form' => [
        'create_title' => 'Pemindahan Tunai Baharu',
        'edit_title' => 'Sunting Pemindahan Tunai',
        'cash_account' => 'Akaun Tunai',
        'cash_account_placeholder' => 'Pilih akaun tunai',
        'amount' => 'Jumlah',
        'date' => 'Tarikh',
        'reference_number' => 'Rujukan',
        'description' => 'Penerangan',
        'notes' => 'Nota',
        'to' => 'Ke',
        'holding' => 'memegang',
        'surplus_hint' => 'Mengikut nilai dalam laci melebihi modal minimumnya. Boleh kurang, tidak boleh lebih.',
        'surplus_available' => 'Kelebihan tersedia: :amount',
        'no_surplus' => 'Laci ini tiada kelebihan melebihi modal minimumnya untuk dipindahkan.',
        'transit_balance' => 'Sedang dalam perjalanan:',
    ],

    'show' => [
        'posting' => 'Catatan',
        'posted_explanation' => 'Laci telah dikreditkan dan kas dalam perjalanan didebitan. Tiada lejar bank bergerak: wang itu berada antara laci dan bank sehingga pemindahan bank merekodkan bank mengreditkannya.',
        'not_posted' => 'Belum dimasukkan ke lejar. Tiada apa-apa yang bergerak.',
        'debit' => 'Debit',
        'credit' => 'Kredit',
    ],

    'validation' => [
        'account_inactive' => 'Akaun tunai tersebut tidak tersedia.',
        'no_surplus' => 'Laci ini tiada apa-apa melebihi modal minimumnya, jadi tiada apa-apa untuk dipindahkan.',
        'exceeds_surplus' => 'Laci ini hanya ada :surplus melebihi modal minimumnya.',
    ],
];
