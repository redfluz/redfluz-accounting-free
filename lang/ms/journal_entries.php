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
    'title' => 'Jurnal Entri',
    'subtitle' => 'Urus entri jurnal lejar am dan transaksi item barisan.',
    'journal_entries_title' => 'Senarai Jurnal Entri',
    'create_journal_title' => 'Cipta Jurnal Entri Baru',
    'edit_journal_title' => 'Kemaskini Jurnal Entri',
    'show_title' => 'Butiran Entri Jurnal',
    'pdf_title' => 'ENTRI JURNAL',
    'pdf_button' => 'PDF',
    'add_journal' => 'Tambah Jurnal Entri',
    'save_journal' => 'Simpan Jurnal Entri',
    'approve_button' => 'Luluskan',
    'reverse_button' => 'Buat Asal',
    'reverse_title' => 'Balikkan Entri Jurnal yang Telah Diposkan',
    'reverse_explanation' => 'Tindakan ini tidak memadam atau mengubah jurnal asal. Jurnal baharu yang telah diposkan akan dicipta dengan semua amaun debit dan kredit diterbalikkan.',
    'reverse_product_ledger_warning' => 'Pergerakan lejar produk (stok masuk / stok keluar) tidak diterbalikkan. Hanya entri jurnal kewangan yang dibetulkan.',
    'reversal_date' => 'Tarikh Pembalikan',
    'reversal_reason' => 'Sebab Pembalikan',
    'reversal_reason_placeholder' => 'Terangkan angka yang salah atau sebab pembetulan ini dibuat.',
    'confirm_reverse' => 'Cipta jurnal pembalikan sekarang?',
    'confirm_approve' => 'Adakah anda pasti mahu meluluskan jurnal entri ini?',
    'confirm_delete' => 'Adakah anda pasti mahu memadam jurnal entri ini?',
    'approved_success' => 'Jurnal entri berjaya diluluskan.',
    'reversal_success' => 'Jurnal pembalikan berjaya dicipta dan diposkan.',
    'reversal_posted_only' => 'Hanya entri jurnal yang telah diposkan boleh dibalikkan. Sunting atau padam entri yang masih belum diluluskan.',
    'reversal_cannot_reverse' => 'Jurnal pembalikan tidak boleh dibalikkan melalui tindakan ini. Cipta jurnal pembetulan berasingan jika pembetulan lain diperlukan.',
    'reversal_already_exists' => 'Entri jurnal ini telah pun dibalikkan.',
    'reversal_ledger_mismatch' => 'Baris lejar yang telah diposkan tidak sepadan dengan entri jurnal ini. Pembalikan dihentikan untuk melindungi Lejar Am.',
    'reversal_source_missing' => 'Sumber lejar yang telah diposkan untuk entri jurnal ini tidak tersedia.',
    'reversal_line_description' => 'Pembalikan bagi :document_number - :description',
    'reversed_status' => 'Telah Dibalikkan',
    'reversal_status' => 'Pembalikan',
    'reversed_by' => 'Dibalikkan Oleh',
    'reversal_of' => 'Pembalikan Bagi',
    'approved_locked' => 'Entri jurnal yang telah diluluskan tidak boleh disunting, dipadam atau diluluskan semula.',
    'already_posted' => 'Satu atau lebih baris jurnal telah diposkan ke Lejar Am.',
    'account_required' => 'Pilih Carta Akaun yang sah untuk baris jurnal ini.',
    'amount_required' => 'Debit dan kredit tidak boleh kedua-duanya sifar. Masukkan amaun melebihi sifar pada satu bahagian.',
    'account_amount_mismatch' => 'Akaun dan amaun berada pada bahagian yang berbeza. Gunakan akaun debit untuk amaun debit atau akaun kredit untuk amaun kredit.',
    'one_side_only' => 'Debit dan kredit tidak boleh kedua-duanya mempunyai amaun pada baris jurnal yang sama. Masukkan amaun pada satu bahagian sahaja.',
    'lines_required' => 'Masukkan sekurang-kurangnya satu baris debit dan kredit yang seimbang.',
    'generated_at' => 'Dijana pada :date',
    'unbalanced_entry_error' => 'Jumlah amaun Debit dan Kredit mestilah seimbang.',
    'status_pending' => 'Belum Diluluskan',
    'status_approved' => 'Diluluskan',
    'table' => [
        'column_id' => 'ID',
        'column_document_number' => 'Nombor Entri',
        'column_reference_number' => 'Nombor Rujukan',
        'column_date' => 'Tarikh',
        'column_notes' => 'Nota',
        'column_status' => 'Status',
        'column_line_number' => '#',
        'column_account' => 'Akaun',
        'column_description' => 'Keterangan',
        'column_debit_amount' => 'Amaun Debit',
        'column_credit_amount' => 'Amaun Kredit',
        'column_total_debit' => 'Jumlah Debit',
        'column_total_credit' => 'Jumlah Kredit',
        'column_created_at' => 'Dicipta Pada',
        'column_updated_at' => 'Dikemaskini Pada',
        'column_actions' => 'Tindakan',
    ],

    'cancelled_title' => 'Entri Jurnal Dibatalkan',
    'cancelled_subtitle' => 'Lihat semua entri jurnal yang dibatalkan.',
    'history_title' => 'Entri Jurnal Diposkan',
    'history_subtitle' => 'Lihat semua entri jurnal yang telah diposkan.',

    'audit' => [
        'role' => 'Peranan',
        'name' => 'Nama',
        'date' => 'Tarikh',
        'signature' => 'Tandatangan',
        'created_by' => 'Dicipta Oleh',
        'approved_by' => 'Diluluskan Oleh',
        'cancelled_by' => 'Dibatalkan Oleh',
        'undone_by' => 'Dipulihkan Oleh',
    ],
];
