<?php

return [
    'title' => 'Pulangan Belian',
    'subtitle' => 'Pulangkan stok diterima kepada pembekal. Nota kredit dan bayaran balik direkodkan berasingan.',
    'company' => 'Pembekal',
    'grn_document' => 'Dokumen GRN',
    'receipt_line' => 'Baris Penerimaan',
    'receipt' => 'Baris GRN Asal',
    'quantity' => 'Kuantiti Pulangan',
    'date' => 'Tarikh Pulangan',
    'notes' => 'Catatan',
    'pending' => 'Belum Dipos',
    'active' => 'Telah Dipos',
    'next' => 'Selepas dipos, rekod nota kredit pembekal dalam Pelarasan Belian. Rekod bayaran balik pembekal hanya apabila wang diterima.',
    'adjustment' => 'Pelarasan Belian',
    'refund' => 'Bayaran Balik Pembekal',
    'validation' => [
        'before_receipt' => 'Tarikh pulangan tidak boleh sebelum tarikh penerimaan.',
        'exceeds_received' => 'Pulangan melebihi baki kuantiti diterima (:quantity).',
        'insufficient_stock' => 'Stok boleh jual tidak mencukupi untuk mengepos pulangan ini.',
    ],
];
