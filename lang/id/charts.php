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
    'title' => 'Bagan Akun',
    'subtitle' => 'Kelola kode akun dan klasifikasi sistem akuntansi.',
    'export_excel' => 'Ekspor Excel',
    'yes' => 'Ya',
    'no' => 'Tidak',
    'all_types' => 'Semua Jenis',
    'all_report_groups' => 'Semua Grup Laporan',
    'table' => [
        'column_code' => 'Kode Akun',
        'column_name' => 'Nama Akun',
        'column_type' => 'Jenis Chart',
        'column_report_group' => 'Grup Laporan',
        'column_bank' => 'Bank',
        'column_debtor' => 'Debitur',
        'column_creditor' => 'Kreditur',
    ],
    'form' => [
        'report_group' => 'Grup Laporan Keuangan',
        'report_group_help' => 'Mengatur di mana akun ini muncul dalam laporan keuangan. Grup yang tersedia disaring berdasarkan jenis chart terpilih.',
        'report_group_select_type' => 'Pilih jenis chart terlebih dahulu',
        'report_group_empty' => 'Tidak ada grup laporan untuk jenis chart ini',
    ],
    'report_group' => [
        'property_plant_equipment' => 'Properti, Pabrik dan Peralatan',
        'investment_properties' => 'Properti Investasi',
        'biological_assets' => 'Aset Biologis',
        'right_of_use_assets' => 'Aset Hak Guna',
        'service_concession_assets' => 'Aset Koncesi Jasa',
        'intangible_assets' => 'Aset Tak Berwujud',
        'goodwill' => 'Goodwill',
        'investment_subsidiaries' => 'Investasi pada Entitas Anak',
        'investment_associates' => 'Investasi pada Entitas Asosiasi',
        'investment_joint_ventures' => 'Investasi pada Ventura Bersama',
        'other_investments' => 'Investasi Lainnya',
        'receivable' => 'Piutang',
        'contract_assets' => 'Aset Kontrak',
        'deferred_tax_assets' => 'Aset Pajak Tangguhan',
        'derivative_financial_assets' => 'Aset Keuangan Derivatif',
        'other_non_current_assets' => 'Aset Tidak Lancar Lainnya',
        'inventories' => 'Persediaan',
        'other_current_assets' => 'Aset Lancar Lainnya',
        'current_tax_assets' => 'Aset Pajak Kini',
        'derivative_financial_assets_current' => 'Aset Keuangan Derivatif (Kini)',
        'cash_and_cash_equivalents' => 'Kas dan Setara Kas',
        'service_concession_liabilities' => 'Liabilitas Koncesi Jasa',
        'borrowings' => 'Pinjaman',
        'lease_liabilities' => 'Liabilitas Sewa',
        'employee_benefits' => 'Imbalan Karyawan',
        'provisions' => 'Provisi',
        'payable' => 'Utang',
        'contract_liabilities' => 'Liabilitas Kontrak',
        'deferred_tax_liabilities' => 'Liabilitas Pajak Tangguhan',
        'derivative_financial_liabilities' => 'Liabilitas Keuangan Derivatif',
        'other_non_current_liabilities' => 'Liabilitas Jangka Panjang Lainnya',
        'current_tax_liabilities' => 'Liabilitas Pajak Kini',
        'other_current_liabilities' => 'Liabilitas Jangka Pendek Lainnya',
        'issued_capital' => 'Modal Ditempatkan',
        'retained_earnings' => 'Laba Ditahan',
        'treasury_shares' => 'Saham Treasuri',
        'other_reserves' => 'Cadangan Lainnya',
        'non_controlling_interest' => 'Kepentingan Non-pengendali',
        'revenue' => 'Pendapatan',
        'cost_of_sales' => 'Beban Pokok Penjualan',
        'other_income' => 'Pendapatan Lainnya',
        'selling_distribution_expenses' => 'Beban Penjualan dan Distribusi',
        'administrative_expenses' => 'Beban Administrasi',
        'research_development_expenses' => 'Beban Riset dan Pengembangan',
        'other_expenses' => 'Beban Lainnya',
        'finance_income' => 'Pendapatan Keuangan',
        'finance_costs' => 'Biaya Keuangan',
        'share_of_profit_associates_jv' => 'Bagian Laba Entitas Asosiasi dan Ventura Bersama',
        'fair_valuation_gain_loss' => 'Keuntungan (Kerugian) Penilaian Wajar',
        'tax_expense' => 'Beban Pajak',
        'zakat' => 'Zakat',
        'discontinued_operations' => 'Operasi yang Dihentikan',
    ],
    'validation' => [
        'report_group_type_mismatch' => 'Grup laporan yang dipilih tidak valid untuk jenis chart :type.',
        'linked_bank' => 'Akun ini tidak dapat dihapus karena tertaut ke :count akun uang. Tetapkan ulang akun tersebut terlebih dahulu.',
    ],
];
