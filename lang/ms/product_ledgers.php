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
        'pending' => 'Belum Dipos',
        'active' => 'Aktif',
        'cancelled' => 'Dibatalkan',
    ],
    'entries' => [
        'cogs_posting' => 'Kos barang dijual untuk :document',
    ],
    'validation' => [
        'stock_location_required' => 'Tetapkan cawangan atau gudang yang aktif sebelum menempah atau mengeluarkan stok.',
        'cogs_account_required' => 'Konfigurasikan akaun Kos Barang Dijual sebelum mengepos invois produk fizikal.',
        'inventory_account_required' => 'Konfigurasikan akaun Inventori sebelum mengepos invois produk fizikal.',
        'insufficient_stock' => 'Stok tidak mencukupi untuk :product. Diminta: :requested. Tersedia: :available.',
        'insufficient_stock_reservation' => 'Stok tidak mencukupi untuk :product. Diminta: :requested. Baki selepas tempahan lain: :available.',
    ],
];
