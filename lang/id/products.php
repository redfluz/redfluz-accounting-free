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
    'subtitle' => 'Kelola item stok, gambar, biaya, dan harga jual.',
    'tabs' => [
        'details' => 'Umum',
        'variants' => 'Varian',
        'attachments' => 'Lampiran',
        'movement' => 'Pergerakan',
    ],
    'attachments' => [
        'title' => 'Gambar Produk',
        'hint' => 'Unggah hingga 10 gambar JPG, PNG, atau WebP. Setiap gambar dapat berukuran hingga 2 MB.',
        'existing' => 'Gambar Saat Ini',
        'new' => 'Tambah Gambar',
        'remove' => 'Hapus',
        'empty' => 'Belum ada gambar yang diunggah untuk produk ini.',
        'shared_hint' => 'Varian menggunakan gambar yang dilampirkan pada produk utama.',
    ],
    'variants' => [
        'title' => 'Varian Produk',
        'hint' => 'Setiap baris yang lengkap menjadi item stok tersendiri yang dapat dipilih dengan SKU-nya sendiri. Biarkan baris yang tidak digunakan kosong.',
        'row' => 'Varian :number',
        'optional' => 'Opsional',
        'name' => 'Nama Varian',
        'name_example' => 'Contoh: Merah / XL',
        'details' => 'Detail Varian',
        'details_example' => 'Contoh: Warna: Merah, Ukuran: XL',
        'use_main_selling_price' => 'Gunakan harga jual utama',
        'total_in' => 'Total Masuk',
        'total_out' => 'Total Keluar',
        'total' => 'Total',
        'same_price' => 'Harga utama',
        'own_price' => 'Harga sendiri',
        'empty' => 'Belum ada varian yang dibuat untuk produk ini.',
        'count' => ':count varian|:count varian',
    ],
    'validation' => [
        'invalid_variant' => 'Satu atau beberapa varian tidak termasuk dalam produk ini.',
        'variant_sku_exists' => 'Satu atau beberapa SKU varian sudah digunakan.',
        'variant_sku_matches_product' => 'SKU varian tidak boleh sama dengan SKU produk utama.',
        'variant_selling_price_required' => 'Masukkan harga jual atau pilih Gunakan harga jual utama.',
        'attachment_upload_failed' => 'Satu atau beberapa gambar produk tidak dapat diunggah.',
        'invalid_attachment' => 'Satu atau beberapa gambar yang dipilih tidak termasuk dalam produk ini.',
        'maximum_attachments' => 'Sebuah produk tidak boleh memiliki lebih dari :max gambar.',
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
        'brand' => 'Merek',
        'brand_hint' => 'Opsional untuk item perbaikan dan layanan lainnya.',
        'supplier' => 'Tetapkan Pemasok',
        'no_supplier' => 'Belum ada pemasok dipilih',
        'supplier_hint' => 'Kreditur ini digunakan sebagai pemasok tetap saat membeli produk ini.',
        'category' => 'Kategori',
        'product_type' => 'Jenis Produk',
        'select_category' => 'Pilih kategori',
        'select_type' => 'Pilih jenis',
        'classification_hint' => 'Jenis difilter berdasarkan kategori tingkat pertama yang dipilih.',
        'manage_categories' => 'Kelola kategori',
        'manage_types' => 'Kelola jenis',
        'default_tax' => 'Pajak Default',
        'default_discount' => 'Diskon Default',
        'commercial_defaults_hint' => 'Nilai default ini disalin ke baris dokumen baru. Pengguna dapat menggantinya pada baris tersebut tanpa mengubah produk ini.',
        'warranty' => 'Garansi Default',
        'warranty_hint' => 'Disalin ke baris Pesanan Penjualan. Tanpa garansi memblokir RMA; tak terbatas berarti tanpa kedaluwarsa.',
        'create_title' => 'Buat Produk atau Layanan',
        'edit_title' => 'Edit Produk atau Layanan',
        'is_service' => 'Item ini adalah layanan',
        'is_service_hint' => 'Item layanan tidak membuat pergerakan produk atau entri Harga Pokok Penjualan.',
        'image_preview' => 'Pratinjau Gambar',
        'default_acquisition_type' => 'Tujuan Pembelian Default',
        'reorder_level' => 'Level Pemesanan Ulang',
        'reorder_quantity' => 'Jumlah Pemesanan Ulang',
        'reorder_hint' => 'Pemesanan ulang hanya berlaku untuk stok yang dijual. Pembelian untuk penggunaan internal dan aset tetap tidak memicu pemesanan ulang.',
        'barcode' => 'Kode Batang',
        'mpn' => 'Nomor Komponen Produsen (MPN)',
    ],
    'warranty' => ['none' => 'Tanpa garansi', 'unlimited' => 'Garansi tanpa batas', 'months' => ':count bulan'],
    'types' => [
        'physical' => 'Produk Fisik',
        'service' => 'Layanan',
    ],
    'table' => [
        'column_image' => 'Gambar',
        'column_sku' => 'SKU',
        'column_name' => 'Nama Produk',
        'column_brand' => 'Merek',
        'column_category' => 'Kategori',
        'column_product_type' => 'Jenis Produk',
        'column_type' => 'Jenis Item',
        'column_base_price' => 'Harga Dasar',
        'column_selling_price' => 'Harga Jual',
        'column_description' => 'Deskripsi',
        'column_variants' => 'Varian',
    ],
    'movement' => [
        'title' => 'Pergerakan Produk',
        'subtitle' => 'Lacak penerimaan terkonfirmasi, penerbitan faktur, pergerakan tertunda, dan saldo stok.',
        'current_balance' => 'Stok yang Dapat Dijual Saat Ini',
        'total_received' => 'Total Diterima',
        'total_issued' => 'Total Dikeluarkan',
        'pending' => 'Masuk / Keluar Tertunda',
        'date' => 'Tanggal',
        'document' => 'Dokumen',
        'item' => 'Produk / Varian',
        'source' => 'Sumber Pergerakan',
        'location' => 'Cabang / Gudang',
        'company' => 'Perusahaan',
        'status' => 'Status',
        'bucket' => 'Kategori Stok',
        'quantity_in' => 'Masuk',
        'quantity_out' => 'Keluar',
        'unit_cost' => 'Biaya',
        'running_balance' => 'Saldo Stok',
        'empty' => 'Belum ada pergerakan yang dicatat untuk produk ini.',
        'location_title' => 'Stok per Lokasi',
        'location_subtitle' => 'Stok yang dapat dijual di setiap cabang atau gudang. Total mencakup varian produk.',
        'location_code' => 'Kode Lokasi',
        'location_name' => 'Cabang / Gudang',
        'location_type' => 'Jenis',
        'available' => 'Stok Tersedia',
        'pending_in' => 'Masuk Tertunda',
        'pending_out' => 'Keluar Tertunda',
        'location_empty' => 'Tidak ditemukan lokasi stok yang dapat diakses.',
        'sources' => [
            'sales_order' => 'Reservasi Pesanan Penjualan',
            'delivery_order' => 'Surat Jalan',
            'stock_transfer' => 'Transfer Stok',
            'stock_take' => 'Opname Stok',
            'purchase_return' => 'Retur Pembelian',
            'goods_receipt' => 'Surat Penerimaan Barang',
            'sales_invoice' => 'Faktur Penjualan',
            'sales_return' => 'Retur Penjualan',
            'rma_receipt' => 'Penerimaan RMA',
            'rma_replacement' => 'Pertukaran RMA',
            'rma_return_to_stock' => 'RMA Kembali ke Stok',
            'rma_write_off' => 'Penghapusan RMA',
        ],
        'buckets' => [
            'saleable' => 'Dapat Dijual',
            'quarantine' => 'Karantina',
            'repair' => 'Perbaikan',
            'damaged' => 'Rusak',
        ],
        'statuses' => [
            'pending' => 'Tertunda',
            'active' => 'Aktif',
            'cancelled' => 'Dibatalkan',
        ],
    ],
];
