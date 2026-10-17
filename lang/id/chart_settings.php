<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
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
    'title' => 'Pengaturan Chart',
    'subtitle' => 'Konfigurasi pengaturan default keuangan dan bagan akun.',
    'default_chart_accounts' => 'Bagan Akun Bawaan',
    'finance_period' => 'Periode Keuangan',
    'banks_setup' => 'Pengaturan Bank',
    'system_references' => 'Referensi Sistem',
    'enable_strict_journal_posting' => 'Aktifkan posting jurnal ketat',

    'table' => [
        'column_cogs_chart_uuid' => 'Akun HPP',
        'column_income_chart_uuid' => 'Akun Pendapatan',
        'column_bank_chart_uuid' => 'Akun Bank',
        'column_cash_chart_uuid' => 'Akun Kas',
        'column_e_wallet_chart_uuid' => 'Akun E-Wallet',
        'column_client_chart_uuid' => 'Akun Klien / Piutang',
        'column_vendor_chart_uuid' => 'Akun Pemasok / Utang',
        'column_inventory_chart_uuid' => 'Akun Persediaan',
        'column_stock_shortage_chart_uuid' => 'Akun Beban Selisih Persediaan Kurang',
        'column_stock_surplus_chart_uuid' => 'Akun Keuntungan Surplus Persediaan',
        'column_deposit_chart_uuid' => 'Akun Deposit',
        'column_input_tax_chart_uuid' => 'Akun Pajak Masukan',
        'column_output_tax_chart_uuid' => 'Akun Pajak Keluaran',
        'column_retained_earnings_chart_uuid' => 'Akun Laba Ditahan',
        'column_disposal_gain_chart_uuid' => 'Akun Keuntungan Pelepasan',
        'column_disposal_loss_chart_uuid' => 'Akun Kerugian Pelepasan',
        'column_retained_earnings_formula' => 'Formula Laba Ditahan',
        'column_closing_date' => 'Tanggal Penutupan',
        'column_start_finance_period_month' => 'Bulan Awal Periode Keuangan',
        'column_strict_journal_posting' => 'Posting Jurnal Ketat',
    ],
    'form' => [
        'retained_earnings_formula' => 'Formula Laba Ditahan',
        'retained_earnings_formula_help' => 'Mengontrol jumlah akhir tahun yang dialihkan ke akun laba ditahan. Tag yang didukung: CHART, TYPE, SUM_TYPE, GROUP, dan SUM_GROUP.',
        'retained_earnings_formula_example' => 'Contoh: {SUM_TYPE:INCOME} - {SUM_TYPE:EXPENSES}',
    ],
    'validation' => [
        'invalid_retained_earnings_formula' => 'Masukkan formula laba ditahan yang valid menggunakan tag akun dan operator matematis yang didukung.',
        'bank_chart_not_in_banks' => 'Bagan akun bank yang dipilih tidak memiliki akun bank.',
        'cash_chart_not_in_cash_accounts' => 'Bagan akun kas yang dipilih tidak memiliki akun kas.',
        'e_wallet_chart_not_in_e_wallet_accounts' => 'Bagan akun e-wallet yang dipilih tidak memiliki akun e-wallet.',
    ],
];
