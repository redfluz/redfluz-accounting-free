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
    'title' => 'Nota Penerimaan Barang',
    'subtitle' => 'Catat produk fisik yang diterima sesuai pesanan pembelian yang telah disetujui.',
    'table' => [
        'column_document_number' => 'Nomor GRN',
        'column_purchase_invoice' => 'Pesanan Pembelian',
        'column_date' => 'Tanggal Diterima',
        'column_company' => 'Pemasok',
        'column_stock_location' => 'Lokasi Penerimaan',
        'column_lines' => 'Baris',
        'column_quantity' => 'Kuantitas',
        'column_status' => 'Status',
        'column_completion' => 'Status Penerimaan',
        'column_actions' => 'Aksi',
        'column_sku' => 'SKU',
        'column_product' => 'Produk',
        'column_unit_cost' => 'Harga Satuan',
        'column_total_cost' => 'Total Biaya',
    ],
    'form' => [
        'create_title' => 'Buat Nota Penerimaan Barang',
        'edit_title' => 'Ubah Nota Penerimaan Barang',
        'search_placeholder' => 'Cari GRN, pesanan pembelian, pemasok, SKU, atau produk',
        'purchase_invoice' => 'Pesanan Pembelian',
        'purchase_invoice_hint' => 'Pilih pesanan pembelian yang telah disetujui atau sebagian diterima.',
        'received_date' => 'Tanggal Diterima',
        'stock_location' => 'Cabang / Gudang Penerima',
        'product' => 'Produk Fisik',
        'ordered_quantity' => 'Dipesan',
        'previously_received' => 'Sebelumnya Diterima',
        'remaining_quantity' => 'Sisa',
        'available_quantity' => 'Tersedia',
        'quantity' => 'Terima Sekarang',
        'unit_cost' => 'Harga Satuan Pesanan Pembelian',
        'attachments' => 'Surat Jalan / Dokumen Pendukung',
        'attachments_hint' => 'Unggah hingga 10 file PDF, Office, teks, atau gambar. Setiap file maksimal 5 MB.',
        'existing_attachments' => 'Dokumen yang Ada',
        'notes' => 'Catatan Penerimaan',
    ],
    'actions' => [
        'remove_attachment' => 'Hapus',
        'place_stock' => 'Tempatkan Stok',
        'save_placement' => 'Simpan Penempatan',
        'update_placement' => 'Perbarui Lokasi',
    ],
    'placements' => [
        'title' => 'Penempatan Stok',
        'subtitle' => 'Tetapkan item GRN yang diterima ke Lantai, Baris, dan Bin/Pallet.',
        'empty' => 'Semua stok yang diterima sudah memiliki lokasi penyimpanan.',
    ],
    'placement' => [
        'title' => 'Tempatkan Stok yang Diterima',
        'subtitle' => 'Tetapkan setiap item yang diterima ke Lantai, Baris, dan Bin/Pallet sebelum memposting GRN.',
    ],
    'completion' => [
        'label' => 'Saring berdasarkan status penerimaan',
        'all' => 'Semua status penerimaan',
        'complete' => 'Lengkap',
        'partial' => 'Sebagian',
        'none' => 'Belum diterima',
    ],

    'adjustment' => [
        'title' => 'Laras Kuantiti Diterima',
        'subtitle' => 'Betulkan kuantiti pada rekod ini. Perbezaan akan dicatat sebagai pergerakan berasingan.',
        'column_current' => 'Kuantiti Semasa',
        'column_max' => 'Maksimum',
        'column_headroom' => 'Baki',
        'column_corrected' => 'Kuantiti Dibetulkan',
        'column_difference' => 'Perbezaan',
        'column_action' => 'Tindakan',
        'recorded' => 'Pembetulan yang direkodkan',
        'no_corrections' => 'Tiada pembetulan direkodkan bagi rekod ini lagi.',
        'post_corrections' => 'Sahkan pembetulan',
        'discard' => 'Buang',
        'default_note' => 'Pembetulan kuantiti bagi barang diterima yang disahkan.',
        'no_change' => 'Masukkan kuantiti pembetulan yang berbeza daripada kuantiti semasa.',
        'validation' => [
            'title' => 'Pengesahan pembetulan',
            'not_posted' => 'Hanya barang diterima yang disahkan boleh dilaras.',
            'insufficient_stock' => 'Pembetulan ini akan menurunkan :sku ke bawah stok tersedia :available.',
            'exceeds_ordered' => 'Kuantiti pembetulan tidak boleh melebihi kuantiti yang masih boleh dipesan sebanyak :quantity.',
        ],
    ],

    'line_state' => [
        'complete' => 'Lengkap',
        'partial' => 'Sebagian',
        'none' => 'Belum diterima',
        'column_ordered' => 'Dipesan',
        'column_received' => 'Diterima',
        'column_shortfall' => 'Belum diterima',
        'column_state' => 'Status',
        'pending_correction' => 'Pembetulan menunggu',
    ],

    'messages' => [
        'delete_confirmation' => 'Hapus barang diterima yang belum disahkan ini? Kuantiti penerimaannya akan dibuang.',
        'saved' => 'Pembetulan direkodkan. Sahkan untuk mengubah stok.',
        'posted' => 'Pembetulan disahkan dan pesanan belian dikira semula.',
        'discarded' => 'Pembetulan dibuang.',
        'empty' => 'Tidak ada nota penerimaan barang yang ditemukan.',
        'select_invoice' => 'Pilih pesanan pembelian untuk memuat kuantitas terutang.',
        'no_physical_lines' => 'Pesanan pembelian ini tidak memiliki baris produk fisik yang terkait.',
        'manual_product_required' => 'Pesanan pembelian ini menggunakan baris deskripsi. Pilih produk fisik yang diterima di bawah ini.',
        'no_stock_location' => 'Anda tidak ditugaskan ke cabang atau gudang aktif. Penugasan lokasi diperlukan sebelum menerima stok.',
        'approve_confirmation' => 'Konfirmasi penerimaan stok ini? Kuantitas yang diterima akan menjadi persediaan aktif dan GRN tidak dapat diubah lagi.',
    ],
    'validation' => [
        'line_required' => 'Masukkan kuantitas penerimaan untuk setidaknya satu produk.',
        'product_required' => 'Pilih produk untuk baris penerimaan ini.',
        'quantity_required' => 'Masukkan kuantitas yang diterima.',
        'stock_product_required' => 'GRN hanya menerima produk fisik. Item layanan tidak memiliki pergerakan stok.',
        'no_physical_lines' => 'Pesanan pembelian yang dipilih tidak memiliki baris produk fisik.',
        'product_not_on_invoice' => 'Produk ini tidak termasuk dalam pesanan pembelian yang dipilih.',
        'quantity_exceeds_remaining' => 'Kuantitas yang diterima tidak boleh melebihi kuantitas tersisa :remaining.',
        'posted_delete_forbidden' => 'Barang diterima yang telah disahkan tidak boleh dipadam kerana kuantitinya sudah bergerak dalam stok. Sila laraskan rekod tersebut.',
        'invoice_posted_lock' => 'Penerimaan Barang ini tidak dapat diubah karena Faktur Pembelian terkait sudah dipos. Gunakan Penyesuaian GRN untuk koreksi stok.',
        'stock_location_required' => 'Pilih cabang atau gudang yang ditugaskan kepada Anda.',
        'attachment_limit' => 'Nota Penerimaan Barang tidak boleh berisi lebih dari 10 dokumen.',
        'invalid_placement' => 'Kombinasi Lantai, Baris, dan Bin/Pallet yang dipilih tidak valid.',
        'placement_required' => 'Tetapkan semua item yang diterima ke Bin/Pallet sebelum memposting GRN.',
        'attachment_upload_failed' => 'Dokumen pendukung gagal diunggah. Silakan coba lagi.',
    ],
];
