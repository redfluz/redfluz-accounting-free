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
    'title' => 'Reka Bentuk Laporan Kewangan',
    'subtitle' => 'Urus templat laporan kewangan berasaskan formula dan baris mengikut susunan.',
    'create_title' => 'Cipta Reka Bentuk Laporan Kewangan',
    'edit_title' => 'Sunting Reka Bentuk Laporan Kewangan',
    'create_row_title' => 'Tambah Baris Laporan',
    'edit_row_title' => 'Sunting Baris Laporan',
    'rows_title' => 'Baris Laporan',
    'rows_subtitle' => 'Baris dikira dan dipaparkan mengikut nombor baris menaik.',
    'empty' => 'Tiada reka bentuk laporan kewangan ditemui.',
    'rows_empty' => 'Tiada baris laporan dikonfigurasikan untuk reka bentuk ini.',
    'table' => [
        'column_code' => 'Kod',
        'column_name' => 'Nama',
        'column_slug' => 'Slug Laporan',
        'column_basis' => 'Pengiraan',
        'column_default' => 'Tetapan',
        'column_rows' => 'Baris',
        'column_line' => 'Baris',
        'column_parent' => 'Induk',
        'column_row_type' => 'Jenis Baris',
        'column_source' => 'Sumber Akaun',
        'column_formula' => 'Formula',
    ],
    'form' => [
        'search_placeholder' => 'Cari kod, nama, atau slug laporan...',
        'report_help' => 'Cipta laporan dahulu, kemudian konfigurasi baris mengikut susunan pada skrin sunting.',
        'code' => 'Kod Laporan',
        'name' => 'Nama Laporan',
        'slug' => 'Slug Laporan',
        'slug_help' => 'Gunakan slug unik untuk laporan tersuai, atau slug standard untuk reka bentuk standard alternatif.',
        'calculation_basis' => 'Asas Pengiraan',
        'calculation_basis_help' => 'Pilih cara nilai akaun dikira untuk laporan ini.',
        'is_default' => 'Gunakan sebagai reka bentuk Tetapan untuk slug laporan ini',
        'line' => 'Nombor Baris',
        'row_code' => 'Kod Baris',
        'row_name' => 'Nama Baris',
        'parent' => 'Baris Induk',
        'no_parent' => 'Tiada baris induk',
        'row_type' => 'Jenis Baris',
        'total_type' => 'Gaya Jumlah',
        'chart' => 'Akaun Carta',
        'chart_type' => 'Jenis Carta',
        'formula' => 'Formula',
        'formula_help' => 'Bina formula menggunakan baki akaun, jumlah jenis carta, jumlah kumpulan laporan, atau baris formula terdahulu.',
        'formula_examples' => [
            'account' => 'Baki satu akaun',
            'type' => 'Jumlah semua akaun di bawah satu jenis carta',
            'group' => 'Jumlah semua akaun di bawah satu kumpulan laporan',
            'row' => 'Nilai yang dikira oleh baris formula terdahulu',
            'calculation' => 'Contoh pengiraan untung bersih',
            'aliases' => 'TYPE dan GROUP diterima sebagai nama lain bagi SUM_TYPE dan SUM_GROUP.',
            'operators' => 'Gabungkan tag menggunakan +, -, *, /, dan kurungan.',
            'available_types' => 'Nilai SUM_TYPE yang tersedia',
            'available_groups' => 'Nilai SUM_GROUP yang tersedia',
        ],
        'report_group' => 'Kumpulan Laporan',
        'report_group_help' => 'Kunci kumpulan memilih akaun. Nama baris kekal sebagai label yang boleh disunting dan dipaparkan dalam laporan.',
        'notes' => 'Catatan',
        'select' => 'Sila pilih',
    ],
    'actions' => [
        'add_report' => 'Reka Bentuk Laporan Baharu',
        'add_row' => 'Tambah Baris Laporan',
        'view_output' => 'Lihat Output',
    ],
    'output_title' => 'Output Laporan',
    'calculation_basis' => [
        'period' => 'Aktiviti Tempoh',
        'as_of' => 'Baki pada Tarikh',
        'cash_flow' => 'Aliran Tunai Langsung',
    ],
    'status' => [
        'default' => 'Tetapan',
        'alternative' => 'Pilihan Lain',
    ],
    'row_type' => [
        'charts' => 'Akaun Carta',
        'formula' => 'Formula',
        'notes' => 'Catatan',
        'none' => 'Tajuk',
        'categories' => 'Kategori',
        'type' => 'Jenis Carta',
        'group' => 'Kumpulan Laporan',
    ],
    'total_type' => [
        'sub_total' => 'Subjumlah',
        'grand_total' => 'Jumlah Besar',
        'none' => 'Baris Biasa',
    ],
    'chart_type' => [
        'asset' => 'Aset',
        'liability' => 'Liabiliti',
        'equity' => 'Ekuiti',
        'income' => 'Pendapatan',
        'expenses' => 'Perbelanjaan',
        'none' => 'Tiada',
    ],
    'report_group' => [
        'property_plant_equipment' => 'Harta, Loji dan Peralatan',
        'investment_properties' => 'Harta Pelaburan',
        'biological_assets' => 'Aset Biologi',
        'right_of_use_assets' => 'Aset Hak Guna',
        'service_concession_assets' => 'Aset Konsesi Perkhidmatan',
        'intangible_assets' => 'Aset Tidak Ketara',
        'goodwill' => 'Gudwill',
        'investment_subsidiaries' => 'Pelaburan dalam Syarikat Subsidiari',
        'investment_associates' => 'Pelaburan dalam Syarikat Bersekutu',
        'investment_joint_ventures' => 'Pelaburan dalam Usaha Sama',
        'other_investments' => 'Pelaburan Lain',
        'receivable' => 'Belum Terima',
        'contract_assets' => 'Aset Kontrak',
        'deferred_tax_assets' => 'Aset Cukai Tertangguh',
        'derivative_financial_assets' => 'Aset Kewangan Derivatif',
        'other_non_current_assets' => 'Aset Bukan Semasa Lain',
        'inventories' => 'Inventori',
        'other_current_assets' => 'Aset Semasa Lain',
        'current_tax_assets' => 'Aset Cukai Semasa',
        'derivative_financial_assets_current' => 'Aset Kewangan Derivatif (Semasa)',
        'cash_and_cash_equivalents' => 'Tunai dan Setara Tunai',
        'service_concession_liabilities' => 'Liabiliti Konsesi Perkhidmatan',
        'borrowings' => 'Pinjaman',
        'lease_liabilities' => 'Liabiliti Pajakan',
        'employee_benefits' => 'Faedah Pekerja',
        'provisions' => 'Peruntukan',
        'payable' => 'Belum Bayar',
        'contract_liabilities' => 'Liabiliti Kontrak',
        'deferred_tax_liabilities' => 'Liabiliti Cukai Tertangguh',
        'derivative_financial_liabilities' => 'Liabiliti Kewangan Derivatif',
        'other_non_current_liabilities' => 'Liabiliti Bukan Semasa Lain',
        'current_tax_liabilities' => 'Liabiliti Cukai Semasa',
        'other_current_liabilities' => 'Liabiliti Semasa Lain',
        'issued_capital' => 'Modal Dikeluarkan',
        'retained_earnings' => 'Pendapatan Ditahan',
        'treasury_shares' => 'Saham Perbendaharaan',
        'other_reserves' => 'Rizab Lain',
        'non_controlling_interest' => 'Kepentingan Tidak Mengawal',
        'revenue' => 'Hasil',
        'cost_of_sales' => 'Kos Jualan',
        'other_income' => 'Pendapatan Lain',
        'selling_distribution_expenses' => 'Perbelanjaan Jualan dan Pengedaran',
        'administrative_expenses' => 'Perbelanjaan Pentadbiran',
        'research_development_expenses' => 'Perbelanjaan Penyelidikan dan Pembangunan',
        'other_expenses' => 'Perbelanjaan Lain',
        'finance_income' => 'Pendapatan Kewangan',
        'finance_costs' => 'Kos Kewangan',
        'share_of_profit_associates_jv' => 'Bahagian Keuntungan Syarikat Bersekutu dan Usaha Sama',
        'fair_valuation_gain_loss' => 'Untung (Rugi) Penilaian Wajar',
        'tax_expense' => 'Perbelanjaan Cukai',
        'zakat' => 'Zakat',
        'discontinued_operations' => 'Operasi Dihentikan',
    ],
    'alerts' => [
        'delete_report' => 'Tindakan ini akan membuang reka bentuk laporan dan semua baris yang dikonfigurasikan.',
        'delete_row' => 'Tindakan ini akan membuang baris laporan yang dipilih.',
    ],
    'validation' => [
        'invalid_formula' => 'Formula mengandungi tag atau ungkapan matematik yang tidak sah.',
        'parent_same_report' => 'Baris induk mesti daripada reka bentuk laporan yang sama.',
        'parent_cycle' => 'Baris induk yang dipilih akan mewujudkan hierarki laporan berulang.',
    ],
];
