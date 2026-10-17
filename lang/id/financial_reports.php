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
        'title' => 'Neraca',
        'subtitle' => 'Per :period menggunakan formula laporan yang dikonfigurasi.',
    ],
    'profit_loss' => [
        'title' => 'Laba Rugi',
        'subtitle' => ':period menggunakan formula laporan yang dikonfigurasi.',
        'formula_info' => 'Formula Laba / Rugi Bersih: Total Pendapatan - Total Beban.',
    ],
    'trial_balance' => [
        'title' => 'Neraca Saldo',
        'subtitle' => 'Saldo dan mutasi akun untuk :period.',
        'balanced' => 'Neraca saldo seimbang.',
        'not_balanced' => 'Neraca saldo tidak seimbang. Selisih: :amount.',
    ],
    'budget_actual' => [
        'title' => 'Anggaran vs Aktual',
        'subtitle' => 'Anggaran dan kinerja aktual yang diposting untuk :period.',
        'all_periods' => 'Tahun Keuangan Penuh',
        'comparison_title' => 'Kinerja Aktual per Tahun Keuangan',
        'comparison_help' => 'Periode keuangan yang dipilih dibandingkan dengan nomor periode yang sama pada setiap tahun sebelumnya.',
        'favourable' => 'Menguntungkan',
        'unfavourable' => 'Tidak Menguntungkan',
    ],
    'cash_flow' => [
        'title' => 'Arus Kas',
        'subtitle' => 'Pergerakan kas dan bank langsung untuk :period.',
        'method_info' => 'Hanya dokumen yang diposting yang mengandung pergerakan kas atau bank yang termasuk. Klasifikasi, urutan baris, dan total mengikuti templat Arus Kas yang dapat diedit di Desain Laporan Keuangan.',
        'section' => [
            'operating' => 'Aktivitas Operasi',
            'investing' => 'Aktivitas Investasi',
            'financing' => 'Aktivitas Pendanaan',
        ],
        'net_section' => 'Kas Bersih dari :section',
        'opening_balance' => 'Saldo Awal Kas dan Bank',
        'net_cash_flow' => 'Kenaikan / (Penurunan) Bersih Kas',
        'closing_balance' => 'Saldo Akhir Kas dan Bank',
        'reconciled' => 'Arus kas cocok dengan pergerakan kas dan bank yang diposting.',
        'not_reconciled' => 'Arus kas tidak cocok. Selisih: :amount.',
    ],
    'summary' => [
        'total_assets' => 'Aset',
        'total_liabilities' => 'Liabilitas',
        'total_equity' => 'Ekuitas',
        'total_income' => 'Pendapatan',
        'total_expenses' => 'Beban',
        'net_profit_loss' => 'Laba Bersih',
        'budget_income' => 'Pendapatan Anggaran',
        'actual_income' => 'Pendapatan Aktual',
        'budget_expenses' => 'Beban Anggaran',
        'actual_expenses' => 'Beban Aktual',
        'net_budget' => 'Laba Bersih Anggaran',
        'net_actual' => 'Laba Bersih Aktual',
        'net_variance' => 'Selisih Bersih',
    ],
    'table' => [
        'column_account_code' => 'Kode Akun',
        'column_account' => 'Akun',
        'column_amount' => 'Jumlah',
        'opening_debit' => 'Debit Awal',
        'opening_credit' => 'Kredit Awal',
        'period_debit' => 'Debit Periode',
        'period_credit' => 'Kredit Periode',
        'closing_debit' => 'Debit Akhir',
        'closing_credit' => 'Kredit Akhir',
        'total' => 'Total',
        'column_type' => 'Jenis',
        'budget' => 'Anggaran',
        'actual' => 'Aktual',
        'variance' => 'Selisih',
        'variance_percent' => 'Selisih %',
        'actual_for_year' => 'Aktual :year',
        'change_from_year' => 'Perubahan vs :year',
        'cash_movement' => 'Pergerakan Kas',
    ],
    'form' => [
        'filters' => 'Filter',
        'main_view' => 'Tampilan Utama',
        'report_period' => 'Periode Laporan',
        'year' => 'Tahun',
        'month' => 'Bulan',
        'quarter' => 'Kuartal',
        'as_of_date' => 'Per Tanggal',
        'start_date' => 'Tanggal Mulai',
        'end_date' => 'Tanggal Akhir',
        'financial_year' => 'Tahun Keuangan',
        'financial_period' => 'Periode Keuangan',
        'compare_with' => 'Bandingkan Dengan',
        'no_comparison' => 'Tanpa Tahun Sebelumnya',
        'previous_one_year' => '1 Tahun Sebelumnya',
        'previous_two_years' => '2 Tahun Sebelumnya',
    ],
    'report_view' => [
        'normal' => 'Normal',
        'q1' => 'Kuartal 1 (Jan–Mar)',
        'q2' => 'Kuartal 2 (Apr–Jun)',
        'q3' => 'Kuartal 3 (Jul–Sep)',
        'q4' => 'Kuartal 4 (Okt–Des)',
        'whole_year' => 'Setahun Penuh (Jan–Des)',
        'whole_year_compare' => 'Setahun Penuh vs Tahun Lalu',
    ],
    'period' => [
        'year' => 'Tahun',
        'year_end' => 'Akhir Tahun',
        'month' => 'Bulan',
        'month_end' => 'Akhir Bulan',
        'quarter' => 'Kuartal',
        'quarter_end' => 'Akhir Kuartal',
        'custom_range' => 'Rentang Khusus',
        'specific_date' => 'Tanggal Tertentu',
    ],
    'actions' => [
        'view_ledger' => 'Lihat entri buku besar pendukung',
        'apply' => 'Terapkan',
        'reset' => 'Atur Ulang',
        'excel' => 'Excel',
        'pdf' => 'PDF',
        'clean_pdf' => 'PDF Bersih',
    ],
    'labels' => [
        'horizontal_scroll' => 'Geser bulan-bulan laporan',
        'period' => 'Periode',
        'as_of' => 'Per',
        'generated_at' => 'Dibuat',
        'formula' => 'Formula',
    ],
    'messages' => [
        'no_accounts' => 'Tidak ada akun yang dikonfigurasi untuk grup ini.',
        'unmatched_accounts' => 'Referensi akun buku besar tidak cocok',
        'unmatched_accounts_help' => 'Referensi buku besar ini tidak dapat dicocokkan dengan Bagan Akun.',
        'no_trial_balance_rows' => 'Tidak ada saldo atau mutasi buku besar yang diposting untuk periode ini.',
        'no_budget_actual_rows' => 'Tidak ada catatan anggaran atau aktual yang diposting untuk periode ini.',
        'no_cash_flow_rows' => 'Tidak ada pergerakan kas atau bank yang diposting untuk bagian ini.',
    ],
    'errors' => [
        'template_missing' => 'Templat laporan keuangan :slug belum dikonfigurasi.',
        'category_not_supported' => 'Baris laporan :code menggunakan kategori bagan, tetapi kategori bagan tidak dikonfigurasi dalam sistem ini.',
        'category_token_not_supported' => 'Kategori formula :category tidak dapat diselesaikan karena kategori bagan tidak dikonfigurasi.',
        'invalid_formula' => 'Baris laporan :code berisi formula yang tidak valid.',
        'unknown_formula_group' => 'Grup formula tidak dikenal: :group.',
        'invalid_resolved_formula' => 'Formula yang diselesaikan tidak valid: :formula.',
        'formula_execution_failed' => 'Formula tidak dapat dihitung: :formula.',
        'formula_not_numeric' => 'Formula tidak mengembalikan angka: :formula.',
        'chart_token_not_found' => 'Akun bagan :chart dalam formula tidak ditemukan.',
        'group_token_not_found' => 'Grup laporan :group dalam formula tidak ditemukan.',
        'chart_type_token_not_found' => 'Jenis bagan :type dalam formula tidak ditemukan.',
        'period_year_mismatch' => 'Periode keuangan yang dipilih tidak termasuk dalam tahun keuangan yang dipilih.',
    ],
    'account_type' => [
        'income' => 'Pendapatan',
        'expenses' => 'Beban',
    ],
];
