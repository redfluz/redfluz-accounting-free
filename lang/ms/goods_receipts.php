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
    'subtitle' => 'Rekod produk fizikal yang diterima berdasarkan pesanan belian yang diluluskan.',
    'table' => [
        'column_document_number' => 'Nombor GRN',
        'column_purchase_invoice' => 'Pesanan Belian',
        'column_date' => 'Tarikh Diterima',
        'column_company' => 'Pembekal',
        'column_stock_location' => 'Lokasi Penerimaan',
        'column_lines' => 'Baris',
        'column_quantity' => 'Kuantiti',
        'column_status' => 'Status',
        'column_completion' => 'Status Terimaan',
        'column_actions' => 'Tindakan',
        'column_sku' => 'SKU',
        'column_product' => 'Produk',
        'column_unit_cost' => 'Kos Seunit',
        'column_total_cost' => 'Jumlah Kos',
    ],
    'form' => [
        'create_title' => 'Cipta Nota Penerimaan Barang',
        'edit_title' => 'Sunting Nota Penerimaan Barang',
        'search_placeholder' => 'Cari GRN, pesanan belian, pembekal, SKU atau produk',
        'purchase_invoice' => 'Pesanan Belian',
        'purchase_invoice_hint' => 'Pilih pesanan belian yang diluluskan atau diterima sebahagian.',
        'received_date' => 'Tarikh Diterima',
        'stock_location' => 'Cawangan / Gudang Penerimaan',
        'product' => 'Produk Fizikal',
        'ordered_quantity' => 'Dipesan',
        'previously_received' => 'Diterima Sebelum Ini',
        'remaining_quantity' => 'Baki',
        'available_quantity' => 'Tersedia',
        'quantity' => 'Terima Sekarang',
        'unit_cost' => 'Kos Seunit Pesanan Belian',
        'attachments' => 'Pesanan Penghantaran / Dokumen Sokongan',
        'attachments_hint' => 'Muat naik sehingga 10 fail PDF, Office, teks atau imej. Setiap fail boleh bersaiz sehingga 5 MB.',
        'existing_attachments' => 'Dokumen Sedia Ada',
        'notes' => 'Nota Penerimaan',
    ],
    'actions' => [
        'remove_attachment' => 'Buang',
        'place_stock' => 'Letak Stok',
        'save_placement' => 'Simpan Lokasi Stok',
        'update_placement' => 'Kemaskini Lokasi',
    ],
    'placements' => [
        'title' => 'Penempatan Stok',
        'subtitle' => 'Tetapkan item GRN yang diterima kepada Tingkat, Baris dan Bin/Pallet.',
        'empty' => 'Semua stok diterima telah mempunyai lokasi simpanan.',
    ],
    'placement' => [
        'title' => 'Letak Stok Diterima',
        'subtitle' => 'Tetapkan setiap item diterima kepada Tingkat, Baris dan Bin/Pallet sebelum GRN dipos.',
    ],
    'completion' => [
        'label' => 'Tapis mengikut status terimaan',
        'all' => 'Semua status terimaan',
        'complete' => 'Lengkap',
        'partial' => 'Sebahagian',
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
        'default_note' => 'Pembetulan kuantiti bagi dokumen terimaan yang disahkan.',
        'no_change' => 'Masukkan kuantiti pembetulan yang berbeza daripada kuantiti semasa.',
        'validation' => [
            'title' => 'Pengesahan pembetulan',
            'not_posted' => 'Hanya dokumen terimaan yang disahkan boleh dilaras.',
            'insufficient_stock' => 'Pembetulan ini akan menurunkan :sku ke bawah stok tersedia :available.',
            'exceeds_ordered' => 'Kuantiti pembetulan tidak boleh melebihi kuantiti yang masih boleh dipesan sebanyak :quantity.',
        ],
    ],

    'line_state' => [
        'complete' => 'Lengkap',
        'partial' => 'Sebahagian',
        'none' => 'Belum diterima',
        'column_ordered' => 'Dipesan',
        'column_received' => 'Diterima',
        'column_shortfall' => 'Belum diterima',
        'column_state' => 'Status',
        'pending_correction' => 'Pembetulan menunggu',
    ],

    'messages' => [
        'delete_confirmation' => 'Padam dokumen terimaan belum disahkan ini? Kuantiti penerimaannya akan dibuang.',
        'saved' => 'Pembetulan direkodkan. Sahkan untuk mengubah stok.',
        'posted' => 'Pembetulan disahkan dan pesanan belian dikira semula.',
        'discarded' => 'Pembetulan dibuang.',
        'empty' => 'Tiada nota penerimaan barang ditemui.',
        'select_invoice' => 'Pilih pesanan belian untuk memuatkan baki kuantiti.',
        'no_physical_lines' => 'Pesanan belian ini tiada baris produk fizikal yang dipautkan.',
        'manual_product_required' => 'Pesanan belian ini menggunakan baris keterangan. Pilih produk fizikal yang diterima di bawah.',
        'no_stock_location' => 'Anda belum ditugaskan ke cawangan atau gudang yang aktif. Tugasan lokasi diperlukan sebelum menerima stok.',
        'approve_confirmation' => 'Sahkan penerimaan stok ini? Kuantiti diterima akan menjadi inventori aktif dan GRN tidak boleh disunting lagi.',
    ],
    'validation' => [
        'line_required' => 'Masukkan kuantiti penerimaan untuk sekurang-kurangnya satu produk.',
        'product_required' => 'Pilih produk untuk baris penerimaan ini.',
        'quantity_required' => 'Masukkan kuantiti yang diterima.',
        'stock_product_required' => 'GRN hanya menerima produk fizikal. Item perkhidmatan tidak mempunyai pergerakan stok.',
        'no_physical_lines' => 'Pesanan belian yang dipilih tiada baris produk fizikal.',
        'product_not_on_invoice' => 'Produk ini bukan daripada pesanan belian yang dipilih.',
        'quantity_exceeds_remaining' => 'Kuantiti diterima tidak boleh melebihi baki :remaining.',
        'posted_delete_forbidden' => 'Dokumen terimaan yang telah disahkan tidak boleh dipadam kerana kuantitinya telah bergerak dalam stok. Sila laraskan rekod tersebut.',
        'invoice_posted_lock' => 'Nota Penerimaan Barang ini tidak boleh diubah kerana Invois Belian yang berkaitan telah dipos. Gunakan Pelarasan GRR untuk pembetulan stok.',
        'stock_location_required' => 'Pilih cawangan atau gudang yang ditugaskan kepada anda.',
        'attachment_limit' => 'Satu Nota Penerimaan Barang tidak boleh mengandungi lebih daripada 10 dokumen.',
        'invalid_placement' => 'Gabungan Tingkat, Baris dan Bin/Pallet yang dipilih tidak sah.',
        'placement_required' => 'Tetapkan semua item diterima kepada Bin/Pallet sebelum GRN dipos.',
        'attachment_upload_failed' => 'Dokumen sokongan tidak dapat dimuat naik. Sila cuba lagi.',
    ],
];
