<?php

return [
    'title' => 'Pengurusan Mata Wang',
    'subtitle' => 'Urus mata wang untuk transaksi kewangan.',
    'table' => [
        'column_code' => 'Kod',
        'column_name' => 'Nama',
        'column_symbol' => 'Simbol',
        'column_default' => 'Am',
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
