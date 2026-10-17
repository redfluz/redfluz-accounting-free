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
    'tabs' => ['general' => 'Umum', 'attachment' => 'Lampiran', 'payment' => 'Bayaran', 'shipping' => 'Penghantaran', 'accounting' => 'Perakaunan'],
    'title' => 'Sebut Harga',
    'subtitle' => 'Sediakan sebut harga pelanggan dan teruskan sebut harga yang diterima kepada penghantaran atau invois perkhidmatan.',
    'table' => [
        'column_quotation_number' => 'Nombor Sebut Harga',
        'column_date' => 'Tarikh',
        'column_expiry_date' => 'Sah Sehingga',
        'column_company' => 'Syarikat',
        'column_reference_number' => 'Nombor PO Pelanggan',
        'column_status' => 'Status',
        'column_total_amount' => 'Jumlah Amaun',
        'column_actions' => 'Tindakan',
        'column_entry' => 'Entri',
        'converted_to' => 'Ditukar kepada',
    ],
    'form' => [
        'create_title' => 'Cipta Sebut Harga',
        'edit_title' => 'Sunting Sebut Harga',
        'search_placeholder' => 'Cari nombor sebut harga, nombor PO pelanggan atau syarikat',
        'company' => 'Syarikat',
        'contact' => 'Kenalan',
        'product' => 'Produk',
        'description' => 'Penerangan',
        'attachment' => 'Lampiran Sebut Harga',
        'reference_number' => 'Nombor PO Pelanggan',
        'date' => 'Tarikh Sebut Harga',
        'expiry_date' => 'Sah Sehingga',
        'status' => 'Status',
        'quantity' => 'Kuantiti',
        'unit_price' => 'Harga Seunit',
        'total_amount' => 'Jumlah Amaun',
        'debit_account' => 'Akaun Debit',
        'credit_account' => 'Akaun Kredit',
        'debit_amount' => 'Amaun Debit',
        'credit_amount' => 'Amaun Kredit',
        'use_default_account' => 'Gunakan akaun Tetapan yang dikonfigurasikan',
        'notes' => 'Penerangan / Nota',
        'quick_company_name' => 'Nama pelanggan',
    ],
    'status' => [
        'draft' => 'Draf',
        'sent' => 'Dihantar',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'expired' => 'Tamat Tempoh',
        'converted' => 'Ditukar',
    ],
    'actions' => [
        'convert' => 'Tukar kepada Invois',
        'convert_sales_order' => 'Cipta Pesanan Jualan',
        'open_invoice' => 'Buka Invois',
        'quick_company' => 'Tambah pelanggan segera',
        'add_company' => 'Tambah Pelanggan',
    ],
    'messages' => [
        'empty' => 'Tiada sebut harga ditemui.',
        'accepted_required' => 'Hanya sebut harga yang diterima boleh ditukar kepada invois jualan.',
        'convert_confirmation' => 'Tukar sebut harga ini kepada invois jualan yang belum dipos?',
        'convert_sales_order_confirmation' => 'Cipta Pesanan Jualan draf daripada sebut harga ini?',
        'design_required' => 'Cipta reka bentuk invois jualan sebelum mencetak sebut harga ini.',
        'lines_required' => 'Tambah sekurang-kurangnya satu baris produk atau penerangan.',
        'line_values_required' => 'Masukkan kuantiti dan harga seunit bagi setiap baris yang lengkap.',
    ],
    'validation' => [
        'company_name_required' => 'Masukkan nama pelanggan.',
        'company_create_failed' => 'Pelanggan tidak dapat dicipta. Sila cuba lagi.',
    ],
    'pdf' => [
        'title' => 'SEBUT HARGA',
        'quoted_to' => 'Sebut Harga Kepada',
        'date' => 'Tarikh Sebut Harga',
        'valid_until' => 'Sah Sehingga',
        'reference_number' => 'Nombor PO Pelanggan',
        'description' => 'Penerangan',
        'quantity' => 'Kuantiti',
        'unit_price' => 'Harga Seunit',
        'amount' => 'Amaun',
        'total' => 'Jumlah',
        'tax_number' => 'Nombor Cukai',
        'notes' => 'Terma / Nota',
        'company_placeholder' => 'Nama Syarikat',
    ],
];
