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
    'title' => 'Jurnal',
    'subtitle' => 'Kelola jurnal buku besar dan transaksi baris pos.',
    'journal_entries_title' => 'Daftar Jurnal',
    'create_journal_title' => 'Buat Jurnal Baru',
    'edit_journal_title' => 'Ubah Jurnal',
    'show_title' => 'Detail Jurnal',
    'pdf_title' => 'JURNAL',
    'pdf_button' => 'PDF',
    'add_journal' => 'Tambah Jurnal',
    'save_journal' => 'Simpan Jurnal',
    'approve_button' => 'Setujui',
    'reverse_button' => 'Batalkan',
    'reverse_title' => 'Balikkan Jurnal yang Diposting',
    'reverse_explanation' => 'Ini tidak menghapus atau mengubah jurnal asli. Jurnal terposting baru akan dibuat dengan semua jumlah debit dan kredit dibalik.',
    'reverse_product_ledger_warning' => 'Pergerakan ledger produk (stok masuk / stok keluar) tidak dibalik. Hanya entri jurnal keuangan yang dikoreksi.',
    'reversal_date' => 'Tanggal Pembalikan',
    'reversal_reason' => 'Alasan Pembalikan',
    'reversal_reason_placeholder' => 'Jelaskan angka yang salah atau alasan koreksi ini.',
    'confirm_reverse' => 'Buat jurnal pembalikan sekarang?',
    'confirm_approve' => 'Anda yakin ingin menyetujui jurnal ini?',
    'confirm_delete' => 'Anda yakin ingin menghapus jurnal ini?',
    'approved_success' => 'Jurnal berhasil disetujui.',
    'reversal_success' => 'Jurnal pembalikan berhasil dibuat dan diposting.',
    'reversal_posted_only' => 'Hanya jurnal yang sudah diposting yang dapat dibalik. Ubah atau hapus entri yang tertunda.',
    'reversal_cannot_reverse' => 'Jurnal pembalikan tidak dapat dibalik menggunakan aksi ini. Buat jurnal perbaikan terpisah jika diperlukan koreksi lain.',
    'reversal_already_exists' => 'Jurnal ini sudah dibalik.',
    'reversal_ledger_mismatch' => 'Baris buku besar yang diposting tidak cocok dengan jurnal ini. Pembalikan dihentikan untuk melindungi Buku Besar.',
    'reversal_source_missing' => 'Sumber buku besar yang diposting untuk jurnal ini tidak tersedia.',
    'reversal_line_description' => 'Pembalikan dari :document_number - :description',
    'reversed_status' => 'Dibalik',
    'reversal_status' => 'Pembalikan',
    'reversed_by' => 'Dibalik Oleh',
    'reversal_of' => 'Pembalikan Dari',
    'approved_locked' => 'Jurnal yang telah disetujui tidak dapat diubah, dihapus, atau disetujui kembali.',
    'already_posted' => 'Satu atau lebih baris jurnal sudah diposting ke Buku Besar.',
    'account_required' => 'Pilih Bagan Akun yang valid untuk baris jurnal ini.',
    'amount_required' => 'Debit dan kredit tidak boleh keduanya nol. Masukkan jumlah lebih dari nol pada salah satu sisi.',
    'account_amount_mismatch' => 'Akun dan jumlah berada di sisi berbeda. Gunakan akun debit untuk jumlah debit atau akun kredit untuk jumlah kredit.',
    'one_side_only' => 'Debit dan kredit tidak boleh berisi jumlah pada baris jurnal yang sama. Masukkan jumlah pada satu sisi saja.',
    'lines_required' => 'Masukkan setidaknya satu baris jurnal debit dan kredit yang seimbang.',
    'generated_at' => 'Dibuat pada :date',
    'unbalanced_entry_error' => 'Total Debit dan Kredit harus sama.',
    'status_pending' => 'Tertunda',
    'status_approved' => 'Disetujui',
    'table' => [
        'column_id' => 'ID',
        'column_document_number' => 'Nomor Entri',
        'column_reference_number' => 'Nomor Referensi',
        'column_date' => 'Tanggal',
        'column_notes' => 'Catatan',
        'column_status' => 'Status',
        'column_line_number' => '#',
        'column_account' => 'Akun',
        'column_description' => 'Keterangan',
        'column_debit_amount' => 'Debit',
        'column_credit_amount' => 'Kredit',
        'column_total_debit' => 'Total Debit',
        'column_total_credit' => 'Total Kredit',
        'column_created_at' => 'Dibuat Pada',
        'column_updated_at' => 'Diperbarui Pada',
        'column_actions' => 'Aksi',
    ],

    'cancelled_title' => 'Jurnal Dibatalkan',
    'cancelled_subtitle' => 'Lihat semua jurnal yang dibatalkan.',
    'history_title' => 'Jurnal yang Diposting',
    'history_subtitle' => 'Lihat semua jurnal yang diposting.',

    'audit' => [
        'role' => 'Peran',
        'name' => 'Nama',
        'date' => 'Tanggal',
        'signature' => 'Tanda Tangan',
        'created_by' => 'Dibuat Oleh',
        'approved_by' => 'Disetujui Oleh',
        'cancelled_by' => 'Dibatalkan Oleh',
        'undone_by' => 'Dipulihkan Oleh',
    ],
];
