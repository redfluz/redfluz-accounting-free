<?php

return [
    'title' => 'Fail & Nota Syarikat',
    'subtitle' => 'Urus lampiran dokumen dan nota untuk syarikat ini',
    'actions' => [
        'add' => 'Tambah Fail / Nota',
        'edit' => 'Kemaskini Fail / Nota',
        'show' => 'Butiran Fail / Nota',
        'download' => 'Muat Turun Lampiran',
    ],
    'table' => [
        'column_company' => 'Syarikat',
        'column_title' => 'Tajuk',
        'column_notes' => 'Nota',
        'column_attachment' => 'Lampiran',
        'column_file_name' => 'Nama Fail',
        'column_file_size' => 'Saiz Fail',
        'column_mime_type' => 'Jenis Fail',
        'column_date' => 'Tarikh',
    ],
    'form' => [
        'label_company' => 'Syarikat',
        'label_title' => 'Tajuk / Subjek',
        'label_notes' => 'Nota',
        'label_attachment' => 'Lampiran',
        'label_remove_attachment' => 'Buang lampiran fail sedia ada',
        'attachment_size_hint' => 'Muat naik sehingga 10 fail. Imej (JPG, PNG, WebP) sehingga 8000 KB; dokumen (PDF, DOC, DOCX, XLS, XLSX) sehingga 20 MB.',
        'attachment_too_large' => 'Fail berikut melebihi had saiz dan tidak ditambah:',
        'select_company' => 'Pilih Syarikat',
        'attachment_too_large_image' => 'Imej melebihi had 8000 KB dan tidak ditambah:',
        'attachment_too_large_doc' => 'Dokumen melebihi had 20 MB dan tidak ditambah:',
        'too_many_attachments' => 'Anda tidak boleh memuat naik lebih daripada 10 fail.',
    ],
    'messages' => [
        'no_attachment' => 'Tiada lampiran dimuat naik.',
    ],
    'tabs' => [
        'details' => 'Butiran',
        'attachments' => 'Lampiran',
    ],
    'attachments' => [
        'current' => 'Lampiran Semasa',
        'replace' => 'Ganti Lampiran',
        'new_preview' => 'Pratonton Lampiran Baharu',
        'add_more' => 'Tambah Lebih Banyak Fail',
        'add' => 'Tambah Lampiran',
        'remove' => 'Buang',
    ],
];
