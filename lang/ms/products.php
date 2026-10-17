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
    'title' => 'Katalog Produk',
    'subtitle' => 'Urus item stok, gambar, kos asas, dan harga jualan.',
    'tabs' => [
        'details' => 'Umum',
        'variants' => 'Varian',
        'attachments' => 'Lampiran',
        'movement' => 'Pergerakan',
    ],
    'attachments' => [
        'title' => 'Gambar Produk',
        'hint' => 'Muat naik sehingga 10 gambar JPG, PNG, atau WebP. Setiap gambar boleh bersaiz sehingga 2 MB.',
        'existing' => 'Gambar Semasa',
        'new' => 'Tambah Gambar',
        'remove' => 'Buang',
        'empty' => 'Tiada gambar dimuat naik untuk produk ini.',
        'shared_hint' => 'Varian menggunakan gambar yang dilampirkan pada produk utama.',
    ],
    'variants' => [
        'title' => 'Varian Produk',
        'hint' => 'Setiap baris yang lengkap menjadi item stok berasingan dengan SKU sendiri. Biarkan baris yang tidak digunakan kosong.',
        'row' => 'Varian :number',
        'optional' => 'Pilihan',
        'name' => 'Nama Varian',
        'name_example' => 'Contoh: Merah / XL',
        'details' => 'Butiran Varian',
        'details_example' => 'Contoh: Warna: Merah, Saiz: XL',
        'use_main_selling_price' => 'Guna harga jualan utama',
        'total_in' => 'Jumlah Masuk',
        'total_out' => 'Jumlah Keluar',
        'total' => 'Jumlah',
        'same_price' => 'Harga utama',
        'own_price' => 'Harga sendiri',
        'empty' => 'Tiada varian dicipta untuk produk ini.',
        'count' => ':count varian',
    ],
    'validation' => [
        'invalid_variant' => 'Satu atau lebih varian bukan milik produk ini.',
        'variant_sku_exists' => 'Satu atau lebih SKU varian telah digunakan.',
        'variant_sku_matches_product' => 'SKU varian tidak boleh sama dengan SKU produk utama.',
        'variant_selling_price_required' => 'Masukkan harga jualan atau pilih Guna harga jualan utama.',
        'attachment_upload_failed' => 'Satu atau lebih gambar produk tidak dapat dimuat naik.',
        'invalid_attachment' => 'Satu atau lebih gambar yang dipilih bukan milik produk ini.',
        'maximum_attachments' => 'Sesuatu produk tidak boleh mempunyai lebih daripada :max gambar.',
        'in_use' => 'Produk ini tidak boleh dipadam kerana sudah digunakan oleh: :references.',
    ],
    'usage' => [
        'stock_movements' => 'pergerakan stok',
        'ledger_entries' => 'entri lejar',
        'customer_ledger_entries' => 'entri lejar pelanggan',
        'supplier_ledger_entries' => 'entri lejar pembekal',
        'purchase_order_lines' => 'baris pesanan belian',
        'sales_order_lines' => 'baris pesanan jualan',
        'delivery_order_lines' => 'baris nota penghantaran',
        'quotation_lines' => 'baris sebut harga',
    ],
    'form' => [
        'brand' => 'Jenama',
        'brand_hint' => 'Pilihan untuk pembaikan dan item perkhidmatan lain.',
        'supplier' => 'Pembekal Tetapan',
        'no_supplier' => 'Tiada pembekal dipilih',
        'supplier_hint' => 'Creditor ini digunakan sebagai pembekal tetapan semasa membeli produk ini.',
        'category' => 'Kategori',
        'product_type' => 'Jenis Produk',
        'select_category' => 'Pilih kategori',
        'select_type' => 'Pilih jenis',
        'classification_hint' => 'Jenis ditapis mengikut kategori peringkat pertama yang dipilih.',
        'manage_categories' => 'Urus kategori',
        'manage_types' => 'Urus jenis',
        'default_tax' => 'Cukai Tetapan',
        'default_discount' => 'Diskaun Tetapan',
        'commercial_defaults_hint' => 'Nilai Tetapan ini disalin ke baris dokumen baharu. Pengguna boleh mengubah baris tersebut tanpa mengubah produk ini.',
        'warranty' => 'Waranti Tetapan',
        'warranty_hint' => 'Disalin ke baris Pesanan Jualan. Tiada waranti menyekat RMA; tanpa had tidak mempunyai tarikh tamat.',
        'create_title' => 'Cipta Produk atau Perkhidmatan',
        'edit_title' => 'Sunting Produk atau Perkhidmatan',
        'is_service' => 'Item ini ialah perkhidmatan',
        'is_service_hint' => 'Item perkhidmatan tidak menghasilkan pergerakan produk atau entri Kos Barang Dijual.',
        'image_preview' => 'Gambar',
        'default_acquisition_type' => 'Tujuan Belian Tetapan',
        'reorder_level' => 'Paras Pesanan Semula',
        'reorder_quantity' => 'Kuantiti Pesanan Semula',
        'reorder_hint' => 'Pesanan semula hanya untuk stok jualan. Belian kegunaan dalaman dan aset tetap tidak mencetuskan pesanan semula stok.',
        'barcode' => 'Barcode',
        'mpn' => 'Nombor Bahagian Pengilang (MPN)',
    ],
    'warranty' => ['none' => 'Tiada waranti', 'unlimited' => 'Waranti tanpa had', 'months' => ':count bulan'],
    'types' => [
        'physical' => 'Produk Fizikal',
        'service' => 'Perkhidmatan',
    ],
    'table' => [
        'column_image' => 'Gambar',
        'column_sku' => 'SKU',
        'column_name' => 'Nama',
        'column_brand' => 'Jenama',
        'column_category' => 'Kategori',
        'column_product_type' => 'Jenis Produk',
        'column_type' => 'Jenis Item',
        'column_base_price' => 'Kos',
        'column_selling_price' => 'Jualan',
        'column_description' => 'Keterangan',
        'column_variants' => 'Varian',
    ],
    'movement' => [
        'title' => 'Pergerakan Produk',
        'subtitle' => 'Jejak penerimaan disahkan, keluaran invois, pergerakan belum selesai, dan baki stok.',
        'current_balance' => 'Stok Boleh Dijual Semasa',
        'total_received' => 'Jumlah Diterima',
        'total_issued' => 'Jumlah Dikeluarkan',
        'pending' => 'Belum Selesai Masuk / Keluar',
        'date' => 'Tarikh',
        'document' => 'Dokumen',
        'item' => 'Produk / Varian',
        'source' => 'Sumber Pergerakan',
        'location' => 'Cawangan / Gudang',
        'company' => 'Syarikat',
        'status' => 'Status',
        'bucket' => 'Kategori Stok',
        'quantity_in' => 'Kuantiti Masuk',
        'quantity_out' => 'Kuantiti Keluar',
        'unit_cost' => 'Kos Seunit',
        'running_balance' => 'Baki Stok',
        'empty' => 'Tiada pergerakan direkodkan untuk produk ini.',
        'location_title' => 'Stok Mengikut Lokasi',
        'location_subtitle' => 'Stok jualan tersedia di setiap cawangan atau gudang. Jumlah termasuk varian produk.',
        'location_code' => 'Kod Lokasi',
        'location_name' => 'Cawangan / Gudang',
        'location_type' => 'Jenis',
        'available' => 'Stok Tersedia',
        'pending_in' => 'Belum Selesai Masuk',
        'pending_out' => 'Belum Selesai Keluar',
        'location_empty' => 'Tiada lokasi stok yang boleh diakses ditemui.',
        'sources' => [
            'sales_order' => 'Tempahan Pesanan Jualan',
            'delivery_order' => 'Pesanan Penghantaran',
            'stock_transfer' => 'Pemindahan Stok',
            'stock_take' => 'Kiraan Stok',
            'purchase_return' => 'Pulangan Belian',
            'goods_receipt' => 'Nota Penerimaan Barang',
            'sales_invoice' => 'Invois Jualan',
            'sales_return' => 'Pemulangan Jualan',
            'rma_receipt' => 'Penerimaan RMA',
            'rma_replacement' => 'Tukar Ganti RMA',
            'rma_return_to_stock' => 'RMA Kembali ke Stok',
            'rma_write_off' => 'Hapus Kira RMA',
        ],
        'buckets' => [
            'saleable' => 'Boleh Dijual',
            'quarantine' => 'Kuarantin',
            'repair' => 'Pembaikan',
            'damaged' => 'Rosak',
        ],
        'statuses' => [
            'pending' => 'Belum Selesai',
            'active' => 'Aktif',
            'cancelled' => 'Dibatalkan',
        ],
    ],
];
