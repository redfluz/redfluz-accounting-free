<?php

return [
    'title' => 'Tetapan Pesanan Belian',
    'subtitle' => 'Tetapkan tempoh kredit Tetapan dan amaun maksimum Permintaan Belian, penganugerahan sebut harga pembekal, atau Pesanan Belian yang boleh diluluskan oleh setiap peranan.',
    'default_credit_term_days' => 'Tempoh Kredit Tetapan',
    'default_required_days' => 'Tarikh Diperlukan Tetapan',
    'default_required_days_help' => 'Digunakan apabila tarikh diperlukan bagi Permintaan Belian tidak diisi.',
    'default_rfq_response_days' => 'Tempoh Maklum Balas RFQ Tetapan',
    'default_rfq_response_days_help' => 'Sebut harga pembekal yang dihantar akan tamat tempoh selepas bilangan hari ini jika tiada maklum balas diterima.',
    'days' => 'hari',
    'role' => 'Peranan',
    'approval_limit' => 'Amaun Kelulusan Maksimum',
    'unlimited' => 'Tanpa Had',
    'tabs' => [
        'general' => 'Umum',
        'approval_limits' => 'Had Kelulusan',
    ],
    'messages' => ['updated' => 'Tetapan Pesanan Belian berjaya dikemas kini.'],
    'validation' => [
        'role_not_configured' => 'Had kelulusan pembelian belum ditetapkan untuk peranan anda.',
        'limit_exceeded' => 'Jumlah dokumen ini ialah RM :amount. Had kelulusan anda ialah RM :limit.',
    ],
];
