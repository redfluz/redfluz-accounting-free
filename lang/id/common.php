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
        'remove' => 'Hapus',
        'edit' => 'Edit',
        'update' => 'Perbarui',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'delete' => 'Hapus',
        'search' => 'Cari',
        'reset' => 'Atur Ulang',
        'view' => 'Lihat',
        'send' => 'Kirim',
        'back' => 'Kembali',
        'views' => 'Tampilan',
        'post' => 'Posting',
        'approve' => 'Setujui',
        'print_pdf' => 'Cetak PDF',
        'download' => 'Unduh',
        'upload' => 'Unggah',
        'choose' => 'Pilih',
        'next' => 'Berikutnya',
        'previous' => 'Sebelumnya',
        'complete' => 'Selesai',
        'select_option' => 'Silakan pilih',
        'check_all' => 'Centang Semua',
        'clear_all' => 'Hapus Semua',
        'filter' => 'Filter',
        'locked' => 'Terkunci',
        'markAllAsRead' => 'Tandai semua telah dibaca',
        'export_excel' => 'Ekspor Excel',
        'export_pdf' => 'Ekspor PDF',
        'select_all' => 'Pilih Semua',
        'deselect_all' => 'Batalkan Pilihan Semua',
        'batch_approve' => 'Setujui yang Dipilih',
        'batch_cancel' => 'Batalkan yang Dipilih',
        'undo' => 'Urungkan',
        'clone' => 'Kloning',
        'action' => 'Tindakan',
        'toggle_password_visibility' => 'Tampilkan atau sembunyikan kata sandi',
    ],
    'status' => [
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        'draft' => 'Draf',
        'published' => 'Diterbitkan',
        'archived' => 'Diarsipkan',
    ],
    'labels' => [
        'ledger' => 'Buku Besar',
        'reports' => 'Laporan',
        'audit_trail' => 'Jejak Audit',
        'login' => 'Masuk',
        'logout' => 'Keluar',
        'profile' => 'Profil',
        'all' => 'Semua',
        'not_found_record' => 'Catatan tidak ditemukan.',
        'leave_blank_password' => '(Kosongkan untuk tetap menggunakan yang sekarang)',
        'publish_on' => 'Terbitkan pada',
        'users' => 'Pengguna',
        'welcome' => 'Selamat Datang',
        'welcome_back' => 'Selamat Datang Kembali',
        'sign_in_dashboard' => 'Masuk untuk mengakses dasbor Anda',
        'sign_in_account' => 'Masukkan detail Anda di bawah',
        'username_or_email' => 'Nama Pengguna atau Email',
        'email_address' => 'Alamat E-mail',
        'password' => 'Kata Sandi',
        'created_at' => 'Dibuat Pada',
        'actions' => 'Tindakan',
        'brand' => 'Merek',
        'system_error' => 'Kesalahan Sistem',
        'comments' => 'Komentar',
        'join_comment' => 'Ikuti diskusi...',
        'total' => 'Total',
        'choose_file' => 'Pilih Berkas',
        'no_file_chosen' => 'Tidak ada berkas yang dipilih',
        'system' => 'Sistem',
        'yes' => 'Ya',
        'no' => 'Tidak',
    ],
    'alerts' => [
        'confirm_delete' => 'Konfirmasi Hapus',
        'confirm_delete_message' => 'Apakah Anda yakin ingin menghapus item ini?',
        'confirm_delete_yes' => 'Ya, hapus!',
        'confirm_batch_approve' => 'Setujui semua catatan yang dipilih dan posting ke buku besar umum?',
        'confirm_batch_cancel' => 'Batalkan semua catatan yang dipilih?',
        'no_selection' => 'Silakan pilih setidaknya satu catatan.',
    ],
    'messages' => [
        'batch_approved' => ':count catatan disetujui dan diposting.',
        'batch_cancelled' => ':count catatan dibatalkan.',
        'uncancelled' => 'Catatan telah dikembalikan.',
        'nothing_to_uncancel' => 'Rekod itu tidak lagi dibatalkan, jadi tiada apa-apa untuk dipulihkan.',
        'no_longer_cancelled' => 'Rekod itu tidak lagi dibatalkan, jadi anda telah dibawa ke dokumen yang aktif.',
        'uncancel_confirm' => 'Apakah Anda yakin ingin mengurungkan pembatalan ini?',
    ],
    'batch_rejected' => [
        'not_found' => 'Dokumen :number tidak dijumpai.',
        'no_entries' => 'Dokumen :number tiada entri lejar untuk diposting.',
        'not_pending' => 'Dokumen :number tidak lagi menunggu, jadi tidak boleh diluluskan.',
        'already_posted' => 'Dokumen :number telah pun diposting.',
        'no_lines' => 'Dokumen :number tiada baris entri.',
        'unbalanced' => 'Dokumen :number tidak seimbang: debit dan kredit berbeza.',
        'non_positive' => 'Dokumen :number tiada amaun positif untuk diposting.',
        'no_linked_invoice' => 'Dokumen :number tiada invois atau resit berhubung.',
    ],
    'errors' => [
        'posting_date_settings_missing' => 'Posting keuangan tidak tersedia karena pengaturan bagan akun belum dikonfigurasi.',
        'posting_date_unavailable' => 'Tanggal posting tidak termasuk dalam periode keuangan yang tersedia.',
        'posting_date_year_closed' => 'Tanggal posting termasuk dalam tahun keuangan yang telah ditutup.',
        'posting_date_period_closed' => 'Tanggal posting termasuk dalam periode keuangan yang telah ditutup.',
        'posting_date_current_period_missing' => 'Posting jurnal ketat memerlukan tahun dan periode keuangan berjalan dalam pengaturan bagan akun.',
        'posting_date_not_current_period' => 'Posting jurnal ketat hanya mengizinkan pengajuan pada periode keuangan berjalan.',
        'linked_invoice_invalid' => 'Pilih faktur terposting yang sah milik perusahaan terpilih.',
    ],
    'sales_type' => [
        'label' => 'Jenis Penjualan',
        'product' => 'Produk',
        'service' => 'Layanan',
        'recurring' => 'Berulang',
        'duration' => 'Durasi',
    ],
];
