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
    'title' => 'Pegawai Hubungan',
    'subtitle' => 'Urus maklumat pegawai hubungan yang ditetapkan untuk syarikat pelanggan.',
    'table' => [
        'column_company' => 'Syarikat',
        'column_name' => 'Kenalan',
        'column_email' => 'E-mel',
        'column_phone' => 'Telefon',
        'column_position' => 'Jawatan',
    ],
    // Deletion is refused while these records still reference the entry.
    'validation' => [
        'in_use' => 'Hubungi ini tidak boleh dipadam kerana sudah digunakan oleh: :references.',
    ],
    'usage' => [
        'ledger_entries' => 'entri lejar',
        'customer_ledger_entries' => 'entri lejar pelanggan',
        'supplier_ledger_entries' => 'entri lejar pembekal',
        'purchase_orders' => 'pesanan belian',
        'sales_orders' => 'pesanan jualan',
        'quotation_lines' => 'baris sebut harga',
    ],
];
