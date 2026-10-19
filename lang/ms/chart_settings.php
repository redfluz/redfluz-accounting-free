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
    'title' => 'Tetapan Carta Akaun',
    'subtitle' => 'Urus tetapan kewangan dan carta akaun Tetapan.',
    'default_chart_accounts' => 'Carta Akaun Tetapan',
    'finance_period' => 'Tempoh Kewangan',
    'banks_setup' => 'Persediaan Bank',
    'system_references' => 'Rujukan Sistem',
    'enable_strict_journal_posting' => 'Aktifkan catatan jurnal ketat',

    'table' => [
        'column_cogs_chart_uuid' => 'Akaun Kos Jualan',
        'column_income_chart_uuid' => 'Akaun Pendapatan',
        'column_bank_chart_uuid' => 'Akaun Bank',
        'column_cash_chart_uuid' => 'Akaun Tunai',
        'column_cash_in_transit_chart_uuid' => 'Akaun Tunai Dalam Perjalanan',
        'column_e_wallet_chart_uuid' => 'Akaun E-Wallet',
        'column_client_chart_uuid' => 'Akaun Pelanggan / Belum Terima',
        'column_vendor_chart_uuid' => 'Akaun Pembekal / Belum Bayar',
        'column_inventory_chart_uuid' => 'Akaun Inventori',
        'column_stock_shortage_chart_uuid' => 'Akaun Belanja Kekurangan Inventori',
        'column_stock_surplus_chart_uuid' => 'Akaun Keuntungan Lebihan Inventori',
        'column_deposit_chart_uuid' => 'Akaun Deposit',
        'column_input_tax_chart_uuid' => 'Akaun Cukai Input',
        'column_output_tax_chart_uuid' => 'Akaun Cukai Output',
        'column_retained_earnings_chart_uuid' => 'Akaun Pendapatan Tertahan',
        'column_disposal_gain_chart_uuid' => 'Akaun Keuntungan Pelupusan',
        'column_disposal_loss_chart_uuid' => 'Akaun Kerugian Pelupusan',
        'column_retained_earnings_formula' => 'Formula Pendapatan Tertahan',
        'column_closing_date' => 'Tarikh Tutup',
        'column_start_finance_period_month' => 'Bulan Mula Tempoh Kewangan',
        'column_strict_journal_posting' => 'Catatan Jurnal Ketat',
    ],
    'form' => [
        'retained_earnings_formula' => 'Formula Pendapatan Tertahan',
        'retained_earnings_formula_help' => 'Menentukan amaun akhir tahun yang dipindahkan ke akaun pendapatan tertahan. Tag yang disokong: CHART, TYPE, SUM_TYPE, GROUP dan SUM_GROUP.',
        'retained_earnings_formula_example' => 'Contoh: {SUM_TYPE:INCOME} - {SUM_TYPE:EXPENSES}',
        'cash_in_transit_help' => 'Menyimpan tunai yang sudah keluar dari laci tetapi belum dikreditkan oleh bank. Mesti akaun aset tanpa akaun tunai, bank atau e-wallet di sebaliknya.',
    ],
    'validation' => [
        'invalid_retained_earnings_formula' => 'Masukkan formula pendapatan tertahan yang sah menggunakan tag akaun dan operator matematik yang disokong.',
        'bank_chart_not_in_banks' => 'Akaun bank yang dipilih tidak mempunyai akaun bank.',
        'cash_chart_not_in_cash_accounts' => 'Akaun tunai yang dipilih tidak mempunyai akaun tunai.',
        'e_wallet_chart_not_in_e_wallet_accounts' => 'Akaun e-dompet yang dipilih tidak mempunyai akaun e-dompet.',
    ],
];
