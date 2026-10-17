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
    'title' => 'Carta Akaun',
    'subtitle' => 'Urus kod akaun dan pengelasan sistem perakaunan.',
    'export_excel' => 'Eksport Excel',
    'yes' => 'Ya',
    'no' => 'Tidak',
    'all_types' => 'Semua Jenis',
    'all_report_groups' => 'Semua Kumpulan Laporan',
    'table' => [
        'column_code' => 'Kod Akaun',
        'column_name' => 'Nama Akaun',
        'column_type' => 'Jenis Carta',
        'column_report_group' => 'Kumpulan Laporan',
        'column_bank' => 'Bank',
        'column_debtor' => 'Penghutang',
        'column_creditor' => 'Pemuitang',
    ],
    'form' => [
        'report_group' => 'Kumpulan Penyata Kewangan',
        'report_group_help' => 'Mengawal lokasi akaun ini dipaparkan dalam laporan kewangan. Kumpulan yang tersedia ditapis mengikut jenis carta yang dipilih.',
        'report_group_select_type' => 'Pilih jenis carta dahulu',
        'report_group_empty' => 'Tiada kumpulan laporan tersedia untuk jenis carta ini',
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
    'validation' => [
        'report_group_type_mismatch' => 'Kumpulan laporan yang dipilih tidak sah untuk jenis carta :type.',
        'linked_bank' => 'Akaun ini tidak boleh dipadam kerana terpaut kepada :count akaun wang. Tetapkan semula akaun tersebut dahulu.',
    ],
];
