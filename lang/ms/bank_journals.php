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
    'subtitle' => 'Persediaan pertama sahaja (baki pembukaan, data awal). Untuk transaksi harian, gunakan resit atau baucar pembayaran.',
    'tabs' => [
        'pending' => 'Tertunda',
        'posted' => 'Diposting',
        'cancelled' => 'Dibatalkan',
    ],
    'form' => [
        'create_title' => 'Cipta Jurnal Bank',
        'edit_title' => 'Ubah Jurnal Bank',
        'account' => 'Akaun',
        'account_placeholder' => 'Pilih akaun',
        'direction' => 'Arah',
        'direction_in' => 'Wang Masuk',
        'direction_out' => 'Wang Keluar',
        'date' => 'Tarikh Dokumen',
        'amount' => 'Jumlah',
        'offset_chart' => 'Akaun Offset',
        'offset_chart_placeholder' => 'Pilih akaun offset',
        'offset_chart_hint' => 'Akaun carta di sisi lain pergerakan. Mestilah berbeza daripada akaun wang itu sendiri.',
        'description' => 'Penerangan',
        'notes' => 'Nota',
    ],
    'table' => [
        'column_direction' => 'Arah',
        'column_account' => 'Akaun',
        'column_offset' => 'Akaun Offset',
    ],
    'show' => [
        'posting_title' => 'Pratonton Posting',
        'posting_hint' => 'Yang akan ditulis semasa kelulusan: sisi wang terhadap sisi offset.',
        'column_side' => 'Sisi',
        'column_account' => 'Akaun',
        'money_in' => 'Wang masuk',
        'money_out' => 'Wang keluar',
        'restore_button' => 'Pulihkan',
    ],
    'validation' => [
        'offset_same_as_account' => 'Akaun offset mestilah berbeza daripada akaun wang itu sendiri.',
        'offset_invalid' => 'Akaun offset yang dipilih tidak sah.',
        'account_inactive' => 'Akaun wang yang dipilih sudah tidak aktif.',
        'crypto_account' => 'Akaun kripto tidak boleh dipindahkan dengan jurnal bank.',
        'insufficient_balance' => 'Baki akaun tidak mencukupi jumlah tersebut.',
        'account_already_posted' => 'Akaun ini sudah mempunyai jurnal yang diposkan. Baki awal adalah setup sekali sahaja.',
    ],
];
