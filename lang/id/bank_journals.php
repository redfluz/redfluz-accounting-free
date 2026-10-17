<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

return [
    'title' => 'Jurnal Bank',
    'subtitle' => 'Khusus pengaturan awal (saldo pembukaan, data awal). Untuk transaksi harian, gunakan kuitansi atau voucher pembayaran.',
    'tabs' => [
        'pending' => 'Tertunda',
        'posted' => 'Diposting',
        'cancelled' => 'Dibatalkan',
    ],
    'form' => [
        'create_title' => 'Buat Jurnal Bank',
        'edit_title' => 'Ubah Jurnal Bank',
        'account' => 'Akun',
        'account_placeholder' => 'Pilih akun',
        'direction' => 'Arah',
        'direction_in' => 'Uang Masuk',
        'direction_out' => 'Uang Keluar',
        'date' => 'Tanggal Dokumen',
        'amount' => 'Jumlah',
        'offset_chart' => 'Akun Offset',
        'offset_chart_placeholder' => 'Pilih akun offset',
        'offset_chart_hint' => 'Akun chart di sisi lain pergerakan. Harus berbeda dari akun kas itu sendiri.',
        'description' => 'Deskripsi',
        'notes' => 'Catatan',
    ],
    'table' => [
        'column_direction' => 'Arah',
        'column_account' => 'Akun',
        'column_offset' => 'Akun Offset',
    ],
    'show' => [
        'posting_title' => 'Pratinjau Posting',
        'posting_hint' => 'Yang akan ditulis saat persetujuan: sisi kas terhadap sisi offset.',
        'column_side' => 'Sisi',
        'column_account' => 'Akun',
        'money_in' => 'Uang masuk',
        'money_out' => 'Uang keluar',
        'restore_button' => 'Kembalikan',
    ],
    'validation' => [
        'offset_same_as_account' => 'Akun offset harus berbeda dari akun kas itu sendiri.',
        'offset_invalid' => 'Akun offset yang dipilih tidak valid.',
        'account_inactive' => 'Akun kas yang dipilih sudah tidak aktif.',
        'crypto_account' => 'Akun kripto tidak dapat dipindahkan dengan jurnal bank.',
        'insufficient_balance' => 'Saldo akun tidak mencukupi jumlah tersebut.',
        'account_already_posted' => 'Akun ini sudah memiliki jurnal yang diposting. Saldo awal adalah pengaturan satu kali.',
    ],
];
