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
    'actions' => [
        'add' => 'Tambah',
        'remove' => 'Buang',
        'edit' => 'Kemaskini',
        'update' => 'Perbarui',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'delete' => 'Hapus',
        'search' => 'Cari',
        'reset' => 'Segar',
        'view' => 'Lihat',
        'send' => 'Kirim',
        'back' => 'Kembali',
        'views' => 'Lihat',
        'posts' => 'Hantar',
        'please_select' => 'Sila pilih',
        'post' => 'Pos',
        'approve' => 'Lulus',
        'print_pdf' => 'Cetak PDF',
        'download' => 'Muat Turun',
        'upload' => 'Muat Naik',
        'choose' => 'Pilih',
        'next' => 'Seterusnya',
        'previous' => 'Sebelumnya',
        'complete' => 'Selesai',
        'select_option' => 'Sila pilih',
        'check_all' => 'Pilih Semua',
        'clear_all' => 'Nyahpilih Semua',
        'filter' => 'Tapis',
        'locked' => 'Kunci',
        'markAllAsRead' => 'Tandakan semua sebagai dibaca',
        'export_excel' => 'Eksport Excel',
        'export_pdf' => 'Eksport PDF',
        'select_all' => 'Pilih Semua',
        'deselect_all' => 'Nyahpilih Semua',
        'batch_approve' => 'Lulus Pilihan',
        'batch_cancel' => 'Batal Pilihan',
        'undo' => 'Undo',
        'clone' => 'Klon',
        'action' => 'Tindakan',
        'toggle_password_visibility' => 'Tunjuk atau sembunyi kata laluan',
    ],
    'status' => [
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        'draft' => 'Draf',
        'published' => 'Diterbitkan',
        'archived' => 'Diarkibkan',
    ],
    'labels' => [
        'ledger' => 'Lejar',
        'reports' => 'Laporan',
        'audit_trail' => 'Jejak Audit',
        'login' => 'Masuk',
        'logout' => 'Keluar',
        'profile' => 'Profil',
        'all' => 'Semua',
        'not_found_record' => 'Rekod tidak ditemukan.',
        'leave_blank_password' => '(Sila kosongkan jika tidak mahu kemaskini)',
        'publish_on' => 'Diterbit pada',
        'users' => 'Pengguna',
        'welcome' => 'Selamat Datang',
        'welcome_back' => 'Selamat Kembali',
        'sign_in_dashboard' => 'Halaman Utama',
        'sign_in_account' => 'Masukkan butiran anda di bawah',
        'username_or_email' => 'Nama Pengguna atau E-mel',
        'email_address' => 'Alamat E-mail',
        'password' => 'Kata Laluan',
        'created_at' => 'Dicipta',
        'actions' => 'Aksi',
        'brand' => 'Jenama',
        'system_error' => 'Sistem Bermasalah',
        'comments' => 'Komentar',
        'join_comment' => 'Jemput Komentar...',
        'total' => 'Total',
        'choose_file' => 'Pilih Fail',
        'no_file_chosen' => 'Tiada fail dipilih',
        'system' => 'Sistem',
        'yes' => 'Ya',
        'no' => 'Tidak',
    ],
    'alerts' => [
        'confirm_delete' => 'Pasti Hapus',
        'confirm_delete_message' => 'Adakah and pasti Hapus data yang dipilih?',
        'confirm_delete_yes' => 'Ya, Hapus!',
        'confirm_batch_approve' => 'Luluskan semua rekod yang dipilih dan pos ke lejar am?',
        'confirm_batch_cancel' => 'Batalkan semua rekod yang dipilih?',
        'no_selection' => 'Sila pilih sekurang-kurangnya satu rekod.',
    ],
    'messages' => [
        'batch_approved' => ':count rekod telah diluluskan dan dipos.',
        'batch_cancelled' => ':count rekod telah dibatalkan.',
        'uncancelled' => 'Rekod telah dipulihkan.',
        'nothing_to_uncancel' => 'Rekod itu tidak lagi dibatalkan, jadi tiada apa-apa untuk dipulihkan.',
        'no_longer_cancelled' => 'Rekod itu tidak lagi dibatalkan, jadi anda telah dibawa ke dokumen yang aktif.',
        'uncancel_confirm' => 'Adakah anda pasti mahu membatalkan pembatalan ini?',
    ],
    'batch_rejected' => [
        'not_found' => 'Dokumen :number tidak dijumpai.',
        'no_entries' => 'Dokumen :number tiada entri lejar untuk dipos.',
        'not_pending' => 'Dokumen :number tidak lagi menunggu, jadi tidak boleh diluluskan.',
        'already_posted' => 'Dokumen :number sudah dipos.',
        'no_lines' => 'Dokumen :number tiada baris entri.',
        'unbalanced' => 'Dokumen :number tidak seimbang: debit dan kredit berbeza.',
        'non_positive' => 'Dokumen :number tiada amaun positif untuk dipos.',
        'no_linked_invoice' => 'Dokumen :number tiada invois atau resit berhubung.',
    ],
    'errors' => [
        'posting_date_settings_missing' => 'Penghantaran kewangan tidak tersedia kerana tetapan carta belum dikonfigurasikan.',
        'posting_date_unavailable' => 'Tarikh penghantaran tidak termasuk dalam tempoh kewangan yang tersedia.',
        'posting_date_year_closed' => 'Tarikh penghantaran termasuk dalam tahun kewangan yang ditutup.',
        'posting_date_period_closed' => 'Tarikh penghantaran termasuk dalam tempoh kewangan yang ditutup.',
        'posting_date_current_period_missing' => 'Penghantaran jurnal yang ketat memerlukan tahun dan tempoh kewangan semasa dalam tetapan carta.',
        'posting_date_not_current_period' => 'Penghantaran jurnal yang ketat hanya membenarkan penghantaran dalam tempoh kewangan semasa.',
        'linked_invoice_invalid' => 'Pilih invois yang telah dipos dan sah milik syarikat yang dipilih.',
    ],
    'sales_type' => [
        'label' => 'Jenis Jualan',
        'product' => 'Produk',
        'service' => 'Perkhidmatan',
        'recurring' => 'Berulang',
        'duration' => 'Tempoh',
    ],
];
