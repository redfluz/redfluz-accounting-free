<?php

return [
    'title' => 'Berkas & Catatan Perusahaan',
    'subtitle' => 'Kelola dokumen terlampir dan catatan untuk perusahaan ini',
    'actions' => [
        'add' => 'Tambah Berkas / Catatan',
        'edit' => 'Edit Berkas / Catatan',
        'show' => 'Detail Berkas / Catatan',
        'download' => 'Unduh Lampiran',
    ],
    'table' => [
        'column_company' => 'Perusahaan',
        'column_title' => 'Judul',
        'column_notes' => 'Catatan',
        'column_attachment' => 'Lampiran',
        'column_file_name' => 'Nama Berkas',
        'column_file_size' => 'Ukuran Berkas',
        'column_mime_type' => 'Tipe Berkas',
        'column_date' => 'Tanggal',
    ],
    'form' => [
        'label_company' => 'Perusahaan',
        'label_title' => 'Judul / Subjek',
        'label_notes' => 'Catatan',
        'label_attachment' => 'Lampiran',
        'label_remove_attachment' => 'Hapus lampiran berkas saat ini',
        'attachment_size_hint' => 'Unggah hingga 10 berkas. Gambar (JPG, PNG, WebP) hingga 8000 KB; dokumen (PDF, DOC, DOCX, XLS, XLSX) hingga 20 MB.',
        'attachment_too_large' => 'Berkas berikut melebihi batas ukuran dan tidak ditambahkan:',
        'select_company' => 'Pilih Perusahaan',
        'attachment_too_large_image' => 'Gambar melebihi batas 8000 KB dan tidak ditambahkan:',
        'attachment_too_large_doc' => 'Dokumen melebihi batas 20 MB dan tidak ditambahkan:',
        'too_many_attachments' => 'Anda tidak dapat mengunggah lebih dari 10 berkas.',
    ],
    'messages' => [
        'no_attachment' => 'Tidak ada lampiran yang diunggah.',
    ],
    'tabs' => [
        'details' => 'Detail',
        'attachments' => 'Lampiran',
    ],
    'attachments' => [
        'current' => 'Lampiran Saat Ini',
        'replace' => 'Ganti Lampiran',
        'new_preview' => 'Pratinjau Lampiran Baru',
        'add_more' => 'Tambah Berkas Lain',
        'add' => 'Tambah Lampiran',
        'remove' => 'Hapus',
    ],
];
