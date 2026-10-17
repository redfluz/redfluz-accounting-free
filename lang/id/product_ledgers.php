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
    'status' => [
        'pending' => 'Menunggu',
        'active' => 'Aktif',
        'cancelled' => 'Dibatalkan',
    ],
    'entries' => [
        'cogs_posting' => 'Harga pokok penjualan untuk :document',
    ],
    'validation' => [
        'stock_location_required' => 'Tetapkan cabang atau gudang yang aktif sebelum memesan atau mengeluarkan stok.',
        'cogs_account_required' => 'Konfigurasi akun Harga Pokok Penjualan sebelum memposting faktur produk fisik.',
        'inventory_account_required' => 'Konfigurasi akun Persediaan sebelum memposting faktur produk fisik.',
        'insufficient_stock' => 'Stok tidak mencukupi untuk :product. Diminta: :requested. Tersedia: :available.',
        'insufficient_stock_reservation' => 'Stok tidak mencukupi untuk :product. Diminta: :requested. Tersedia setelah reservasi lain: :available.',
    ],
];
