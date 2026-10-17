<?php

return [
    'title' => 'Pengaturan Pesanan Pembelian',
    'subtitle' => 'Atur termin kredit default dan jumlah maksimum Permintaan Pembelian, penetapan penawaran pemasok, atau Pesanan Pembelian yang dapat disetujui setiap peran.',
    'default_credit_term_days' => 'Termin Kredit Default',
    'default_required_days' => 'Tanggal Dibutuhkan Default',
    'default_required_days_help' => 'Digunakan saat tanggal dibutuhkan Permintaan Pembelian kosong.',
    'default_rfq_response_days' => 'Periode Respons RFQ Default',
    'default_rfq_response_days_help' => 'Penawaran pemasok yang dikirim kedaluwarsa setelah jumlah hari ini tanpa respons.',
    'days' => 'Hari',
    'role' => 'Peran',
    'approval_limit' => 'Jumlah Persetujuan Maksimum',
    'unlimited' => 'Tanpa Batas',
    'tabs' => [
        'general' => 'Umum',
        'approval_limits' => 'Batas Persetujuan',
    ],
    'messages' => ['updated' => 'Pengaturan Pesanan Pembelian berhasil diperbarui.'],
    'validation' => [
        'role_not_configured' => 'Peran Anda belum memiliki batas persetujuan pembelian yang dikonfigurasi.',
        'limit_exceeded' => 'Total dokumen ini adalah RM :amount. Batas persetujuan Anda adalah RM :limit.',
    ],
];
