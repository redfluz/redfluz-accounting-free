<?php

return [
    'title' => 'Retur Pembelian',
    'subtitle' => 'Kembalikan stok yang diterima ke pemasok. Nota kredit dan pengembalian dana dicatat secara terpisah.',
    'company' => 'Pemasok',
    'grn_document' => 'Dokumen GRN',
    'receipt_line' => 'Baris Penerimaan',
    'receipt' => 'Baris GRN Asli',
    'quantity' => 'Jumlah Retur',
    'date' => 'Tanggal Retur',
    'notes' => 'Catatan',
    'pending' => 'Tertunda',
    'active' => 'Terposting',
    'next' => 'Setelah posting, catat nota kredit pemasok di Penyesuaian Pembelian. Catat pengembalian dana pemasok hanya saat uang diterima.',
    'adjustment' => 'Penyesuaian Pembelian',
    'refund' => 'Pengembalian Dana Pemasok',
    'validation' => [
        'before_receipt' => 'Tanggal retur tidak boleh lebih awal dari tanggal penerimaan.',
        'exceeds_received' => 'Retur melebihi sisa jumlah yang diterima (:quantity).',
        'insufficient_stock' => 'Stok yang dapat dijual tidak cukup untuk memposting retur ini.',
    ],
];
