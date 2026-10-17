<?php

return [
    'title' => 'Manajemen Mata Uang',
    'subtitle' => 'Kelola mata uang untuk transaksi keuangan.',
    'table' => [
        'column_code' => 'Kode',
        'column_name' => 'Nama',
        'column_symbol' => 'Simbol',
        'column_default' => 'Default',
        'column_active' => 'Aktif',
    ],
    // Deletion is refused while these records still reference the entry.
    'validation' => [
        'in_use' => 'Mata wang ini tidak boleh dipadam kerana sudah digunakan oleh: :references.',
        'default_in_use' => 'Mata wang am tidak boleh dipadam. Tetapkan mata wang lain sebagai lalai dahulu.',
    ],
    'usage' => [
        'system_settings' => 'tetapan sistem',
    ],
];
