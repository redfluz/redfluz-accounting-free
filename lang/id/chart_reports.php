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
    'title' => 'Desain Laporan Keuangan',
    'subtitle' => 'Kelola templat laporan keuangan berbasis formula dan barisnya.',
    'create_title' => 'Buat Desain Laporan Keuangan',
    'edit_title' => 'Ubah Desain Laporan Keuangan',
    'create_row_title' => 'Tambah Baris Laporan',
    'edit_row_title' => 'Ubah Baris Laporan',
    'rows_title' => 'Baris Laporan',
    'rows_subtitle' => 'Baris dihitung dan ditampilkan dalam urutan baris menaik.',
    'empty' => 'Tidak ada desain laporan keuangan ditemukan.',
    'rows_empty' => 'Tidak ada baris laporan yang dikonfigurasi untuk desain ini.',
    'table' => [
        'column_code' => 'Kode',
        'column_name' => 'Nama',
        'column_slug' => 'Slug Laporan',
        'column_basis' => 'Perhitungan',
        'column_default' => 'Bawaan',
        'column_rows' => 'Baris',
        'column_line' => 'No. Baris',
        'column_parent' => 'Induk',
        'column_row_type' => 'Jenis Baris',
        'column_source' => 'Sumber Akun',
        'column_formula' => 'Formula',
    ],
    'form' => [
        'search_placeholder' => 'Cari kode, nama, atau slug laporan...',
        'report_help' => 'Buat laporan terlebih dahulu, lalu konfigurasi barisnya pada layar ubah.',
        'code' => 'Kode Laporan',
        'name' => 'Nama Laporan',
        'slug' => 'Slug Laporan',
        'slug_help' => 'Gunakan slug unik untuk laporan kustom, atau slug standar untuk desain standar alternatif.',
        'calculation_basis' => 'Dasar Perhitungan',
        'calculation_basis_help' => 'Pilih cara nilai akun dihitung untuk laporan ini.',
        'is_default' => 'Gunakan sebagai desain bawaan untuk slug laporan ini',
        'line' => 'Nomor Baris',
        'row_code' => 'Kode Baris',
        'row_name' => 'Nama Baris',
        'parent' => 'Baris Induk',
        'no_parent' => 'Tanpa baris induk',
        'row_type' => 'Jenis Baris',
        'total_type' => 'Gaya Total',
        'chart' => 'Akun Chart',
        'chart_type' => 'Jenis Chart',
        'formula' => 'Formula',
        'formula_help' => 'Susun formula menggunakan saldo akun, total jenis chart, total grup laporan, atau baris formula sebelumnya.',
        'formula_examples' => [
            'account' => 'Saldo satu akun',
            'type' => 'Total semua akun dalam satu jenis chart',
            'group' => 'Total semua akun dalam satu grup laporan',
            'row' => 'Nilai yang dihitung dari baris formula sebelumnya',
            'calculation' => 'Contoh perhitungan laba bersih',
            'aliases' => 'TYPE dan GROUP diterima sebagai alias untuk SUM_TYPE dan SUM_GROUP.',
            'operators' => 'Gabungkan tag menggunakan +, -, *, /, dan tanda kurung.',
            'available_types' => 'Nilai SUM_TYPE yang tersedia',
            'available_groups' => 'Nilai SUM_GROUP yang tersedia',
        ],
        'report_group' => 'Grup Laporan',
        'report_group_help' => 'Kunci grup memilih akun. Nama baris tetap menjadi label yang dapat diubah dalam laporan.',
        'notes' => 'Catatan',
        'select' => 'Silakan pilih',
    ],
    'actions' => [
        'add_report' => 'Desain Laporan Baru',
        'add_row' => 'Tambah Baris Laporan',
        'view_output' => 'Lihat Hasil',
    ],
    'output_title' => 'Hasil Laporan',
    'calculation_basis' => [
        'period' => 'Aktivitas Periode',
        'as_of' => 'Saldo hingga Tanggal',
        'cash_flow' => 'Arus Kas Langsung',
    ],
    'status' => [
        'default' => 'Bawaan',
        'alternative' => 'Alternatif',
    ],
    'row_type' => [
        'charts' => 'Akun Chart',
        'formula' => 'Formula',
        'notes' => 'Catatan',
        'none' => 'Judul',
        'categories' => 'Kategori',
        'type' => 'Jenis Chart',
        'group' => 'Grup Laporan',
    ],
    'total_type' => [
        'sub_total' => 'Subtotal',
        'grand_total' => 'Total Keseluruhan',
        'none' => 'Baris Biasa',
    ],
    'chart_type' => [
        'asset' => 'Aset',
        'liability' => 'Liabilitas',
        'equity' => 'Ekuitas',
        'income' => 'Pendapatan',
        'expenses' => 'Beban',
        'none' => 'Tidak Ada',
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
    'alerts' => [
        'delete_report' => 'Ini akan menghapus desain laporan dan semua barisnya.',
        'delete_row' => 'Ini akan menghapus baris laporan yang dipilih.',
    ],
    'validation' => [
        'invalid_formula' => 'Formula mengandung tag atau ekspresi matematis yang tidak valid.',
        'parent_same_report' => 'Baris induk harus termasuk desain laporan yang sama.',
        'parent_cycle' => 'Induk yang dipilih akan membuat hierarki laporan melingkar.',
    ],
];
