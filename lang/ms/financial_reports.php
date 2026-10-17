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
    'balance_sheet' => [
        'title' => 'Kunci Kira-kira',
        'subtitle' => 'Setakat :period menggunakan formula laporan yang dikonfigurasikan.',
    ],
    'profit_loss' => [
        'title' => 'Untung & Rugi',
        'subtitle' => ':period menggunakan formula laporan yang dikonfigurasikan.',
        'formula_info' => 'Formula Untung / Rugi Bersih: Jumlah Pendapatan - Jumlah Perbelanjaan.',
    ],
    'trial_balance' => [
        'title' => 'Imbangan Duga',
        'subtitle' => 'Baki dan pergerakan akaun bagi :period.',
        'balanced' => 'Imbangan duga adalah seimbang.',
        'not_balanced' => 'Imbangan duga tidak seimbang. Perbezaan: :amount.',
    ],
    'budget_actual' => [
        'title' => 'Bajet lwn Sebenar',
        'subtitle' => 'Prestasi bajet dan sebenar yang telah dipos bagi :period.',
        'all_periods' => 'Tahun Kewangan Penuh',
        'comparison_title' => 'Prestasi Sebenar mengikut Tahun Kewangan',
        'comparison_help' => 'Tempoh kewangan yang dipilih dibandingkan dengan nombor tempoh yang sama dalam setiap tahun terdahulu.',
        'favourable' => 'Memuaskan',
        'unfavourable' => 'Tidak Memuaskan',
    ],
    'cash_flow' => [
        'title' => 'Aliran Tunai',
        'subtitle' => 'Pergerakan tunai dan bank secara langsung bagi :period.',
        'method_info' => 'Hanya dokumen yang telah dipos dan mempunyai pergerakan tunai atau bank dimasukkan. Pengelasan, susunan baris dan jumlah mengikut templat Aliran Tunai yang boleh disunting dalam Reka Bentuk Laporan Kewangan.',
        'section' => [
            'operating' => 'Aktiviti Operasi',
            'investing' => 'Aktiviti Pelaburan',
            'financing' => 'Aktiviti Pembiayaan',
        ],
        'net_section' => 'Tunai Bersih daripada :section',
        'opening_balance' => 'Baki Awal Tunai dan Bank',
        'net_cash_flow' => 'Peningkatan / (Pengurangan) Bersih Tunai',
        'closing_balance' => 'Baki Akhir Tunai dan Bank',
        'reconciled' => 'Aliran tunai sepadan dengan pergerakan tunai dan bank yang telah dipos.',
        'not_reconciled' => 'Aliran tunai tidak sepadan. Perbezaan: :amount.',
    ],
    'summary' => [
        'total_assets' => 'Jumlah Aset',
        'total_liabilities' => 'Jumlah Liabiliti',
        'total_equity' => 'Jumlah Ekuiti',
        'total_income' => 'Jumlah Pendapatan',
        'total_expenses' => 'Jumlah Perbelanjaan',
        'net_profit_loss' => 'Untung / Rugi Bersih',
        'budget_income' => 'Pendapatan Bajet',
        'actual_income' => 'Pendapatan Sebenar',
        'budget_expenses' => 'Perbelanjaan Bajet',
        'actual_expenses' => 'Perbelanjaan Sebenar',
        'net_budget' => 'Untung Bersih Bajet',
        'net_actual' => 'Untung Bersih Sebenar',
        'net_variance' => 'Varians Bersih',
    ],
    'table' => [
        'column_account_code' => 'Kod Akaun',
        'column_account' => 'Akaun',
        'column_amount' => 'Amaun',
        'opening_debit' => 'Debit Awal',
        'opening_credit' => 'Kredit Awal',
        'period_debit' => 'Debit Tempoh',
        'period_credit' => 'Kredit Tempoh',
        'closing_debit' => 'Debit Akhir',
        'closing_credit' => 'Kredit Akhir',
        'total' => 'Jumlah',
        'column_type' => 'Jenis',
        'budget' => 'Bajet',
        'actual' => 'Sebenar',
        'variance' => 'Varians',
        'variance_percent' => 'Varians %',
        'actual_for_year' => 'Sebenar :year',
        'change_from_year' => 'Perubahan lwn :year',
        'cash_movement' => 'Pergerakan Tunai',
    ],
    'form' => [
        'filters' => 'Penapis',
        'main_view' => 'Paparan Utama',
        'report_period' => 'Tempoh Laporan',
        'year' => 'Tahun',
        'month' => 'Bulan',
        'quarter' => 'Suku Tahun',
        'as_of_date' => 'Tarikh Setakat',
        'start_date' => 'Tarikh Mula',
        'end_date' => 'Tarikh Tamat',
        'financial_year' => 'Tahun Kewangan',
        'financial_period' => 'Tempoh Kewangan',
        'compare_with' => 'Bandingkan Dengan',
        'no_comparison' => 'Tiada Tahun Terdahulu',
        'previous_one_year' => '1 Tahun Terdahulu',
        'previous_two_years' => '2 Tahun Terdahulu',
    ],
    'report_view' => [
        'normal' => 'Biasa',
        'q1' => 'Suku 1 (Jan–Mac)',
        'q2' => 'Suku 2 (Apr–Jun)',
        'q3' => 'Suku 3 (Jul–Sep)',
        'q4' => 'Suku 4 (Okt–Dis)',
        'whole_year' => 'Setahun Penuh (Jan–Dis)',
        'whole_year_compare' => 'Setahun Penuh lwn Tahun Lepas',
    ],
    'period' => [
        'year' => 'Tahun',
        'year_end' => 'Akhir Tahun',
        'month' => 'Bulan',
        'month_end' => 'Akhir Bulan',
        'quarter' => 'Suku Tahun',
        'quarter_end' => 'Akhir Suku Tahun',
        'custom_range' => 'Julat Tersuai',
        'specific_date' => 'Tarikh Tertentu',
    ],
    'actions' => [
        'view_ledger' => 'Lihat catatan lejar sokongan',
        'apply' => 'Terapkan',
        'reset' => 'Tetapkan Semula',
        'excel' => 'Excel',
        'pdf' => 'PDF',
        'clean_pdf' => 'PDF Bersih',
    ],
    'labels' => [
        'horizontal_scroll' => 'Tatal bulan laporan',
        'period' => 'Tempoh',
        'as_of' => 'Setakat',
        'generated_at' => 'Dijana',
        'formula' => 'Formula',
    ],
    'messages' => [
        'no_accounts' => 'Tiada akaun dikonfigurasikan untuk kumpulan ini.',
        'unmatched_accounts' => 'Rujukan akaun lejar yang tidak sepadan',
        'unmatched_accounts_help' => 'Rujukan lejar ini tidak dapat dipadankan dengan Carta Akaun.',
        'no_trial_balance_rows' => 'Tiada baki atau pergerakan lejar yang telah dipos bagi tempoh ini.',
        'no_budget_actual_rows' => 'Tiada rekod bajet atau sebenar yang telah dipos bagi tempoh ini.',
        'no_cash_flow_rows' => 'Tiada pergerakan tunai atau bank yang telah dipos bagi bahagian ini.',
    ],
    'errors' => [
        'template_missing' => 'Templat laporan kewangan :slug belum dikonfigurasikan.',
        'category_not_supported' => 'Baris laporan :code menggunakan kategori carta, tetapi kategori carta belum dikonfigurasikan dalam sistem ini.',
        'category_token_not_supported' => 'Kategori formula :category tidak dapat diselesaikan kerana kategori carta belum dikonfigurasikan.',
        'invalid_formula' => 'Baris laporan :code mengandungi formula yang tidak sah.',
        'unknown_formula_group' => 'Kumpulan formula tidak diketahui: :group.',
        'invalid_resolved_formula' => 'Formula yang diselesaikan tidak sah: :formula.',
        'formula_execution_failed' => 'Formula tidak dapat dikira: :formula.',
        'formula_not_numeric' => 'Formula tidak menghasilkan nombor: :formula.',
        'chart_token_not_found' => 'Akaun carta :chart dalam formula tidak ditemui.',
        'group_token_not_found' => 'Kumpulan laporan :group dalam formula tidak ditemui.',
        'chart_type_token_not_found' => 'Jenis carta :type dalam formula tidak ditemui.',
        'period_year_mismatch' => 'Tempoh kewangan yang dipilih bukan dalam tahun kewangan yang dipilih.',
    ],
    'account_type' => [
        'income' => 'Pendapatan',
        'expenses' => 'Perbelanjaan',
    ],
];
