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
    'title' => 'Dasbor bisnis',
    'kpi' => [
        'revenue_collected' => 'Pendapatan Terkumpul',
        'pending_revenue' => 'Pendapatan Tertunda',
        'total_transactions' => 'Total Transaksi',
        'metrics_count' => 'Perusahaan / Kontak / Produk',
    ],
    'charts' => [
        'revenue' => 'Pendapatan',
        'revenue_by_month' => 'Pendapatan per Bulan',
        'ledger_status_split' => 'Pembagian Status Buku Besar',
        'revenue_by_type' => 'Pendapatan per Subjenis',
        'top_products' => 'Produk Teratas',
        'revenue_by_company' => 'Pendapatan per Perusahaan',
    ],
    'recent' => [
        'title' => 'Transaksi untuk :month',
        'entries_this_month' => 'Entri bulan ini',
        'total_for' => 'Total untuk :month',
        'no_entries_month' => 'Tidak ada transaksi yang dicatat untuk :month.',
        'no_entries' => 'Belum ada entri buku besar.',
        'table' => [
            'date' => 'Tanggal',
            'company' => 'Perusahaan',
            'product' => 'Produk',
            'contact' => 'Kontak',
            'type' => 'Jenis / Subjenis',
            'total' => 'Total',
            'status' => 'Status',
        ],
    ],
    'outstanding' => [
        'ap_title' => '5 Teratas Saldo Hutang Pemasok',
        'ar_title' => '5 Teratas Saldo Piutang Pelanggan',
        'no_ap' => 'Tidak ada saldo hutang pemasok.',
        'no_ar' => 'Tidak ada saldo piutang pelanggan.',
    ],
    'status' => [
        'pending' => 'Tertunda',
        'paid' => 'Dibayar',
        'cancelled' => 'Dibatalkan',
        'posted' => 'Diposting',
    ],
];
