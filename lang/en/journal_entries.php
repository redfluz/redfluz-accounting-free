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
    'title' => 'Journal Entries',
    'subtitle' => 'Manage general ledger journal entries and line item transactions.',
    'journal_entries_title' => 'Journal Entry List',
    'create_journal_title' => 'Create New Journal Entry',
    'edit_journal_title' => 'Edit Journal Entry',
    'show_title' => 'Journal Entry Details',
    'pdf_title' => 'JOURNAL ENTRY',
    'pdf_button' => 'PDF',
    'add_journal' => 'Add Journal Entry',
    'save_journal' => 'Save Journal Entry',
    'approve_button' => 'Approve',
    'reverse_button' => 'Undo',
    'reverse_title' => 'Reverse Posted Journal Entry',
    'reverse_explanation' => 'This does not delete or edit the original journal. A new posted journal will be created with all debit and credit amounts reversed.',
    'reverse_product_ledger_warning' => 'Product ledger movements (stock in / stock out) are not reversed. Only the financial journal entries are corrected.',
    'reversal_date' => 'Reversal Date',
    'reversal_reason' => 'Reason for Reversal',
    'reversal_reason_placeholder' => 'Explain the incorrect figure or reason for this correction.',
    'confirm_reverse' => 'Create the reversal journal now?',
    'confirm_approve' => 'Are you sure you want to approve this journal entry?',
    'confirm_delete' => 'Are you sure you want to delete this journal entry?',
    'approved_success' => 'Journal entry approved successfully.',
    'reversal_success' => 'The reversal journal was created and posted successfully.',
    'reversal_posted_only' => 'Only a posted journal entry can be reversed. Edit or remove a pending entry instead.',
    'reversal_cannot_reverse' => 'A reversal journal cannot be reversed using this action. Create a separate correcting journal if another correction is required.',
    'reversal_already_exists' => 'This journal entry has already been reversed.',
    'reversal_ledger_mismatch' => 'The posted ledger lines do not match this journal entry. The reversal was stopped to protect the General Ledger.',
    'reversal_source_missing' => 'The posted ledger source for this journal entry is unavailable.',
    'reversal_line_description' => 'Reversal of :document_number - :description',
    'reversed_status' => 'Reversed',
    'reversal_status' => 'Reversal',
    'reversed_by' => 'Reversed By',
    'reversal_of' => 'Reversal Of',
    'approved_locked' => 'An approved journal entry cannot be edited, removed, or approved again.',
    'already_posted' => 'One or more journal lines have already been posted to the General Ledger.',
    'account_required' => 'Select a valid Chart of Account for this journal line.',
    'amount_required' => 'Debit and credit cannot both be zero. Enter an amount greater than zero on one side.',
    'account_amount_mismatch' => 'The account and amount are on different sides. Use a debit account for a debit amount or a credit account for a credit amount.',
    'one_side_only' => 'Debit and credit cannot both contain an amount on the same journal line. Enter the amount on one side only.',
    'lines_required' => 'Enter at least one balanced debit and credit journal line.',
    'generated_at' => 'Generated at :date',
    'unbalanced_entry_error' => 'Total Debit and Credit amounts must be equal.',
    'status_pending' => 'Pending',
    'status_approved' => 'Approved',
    'table' => [
        'column_id' => 'ID',
        'column_document_number' => 'Entry Number',
        'column_reference_number' => 'Reference Number',
        'column_date' => 'Date',
        'column_notes' => 'Notes',
        'column_status' => 'Status',
        'column_line_number' => '#',
        'column_account' => 'Account',
        'column_description' => 'Description',
        'column_debit_amount' => 'Debit',
        'column_credit_amount' => 'Credit',
        'column_total_debit' => 'Total Debit',
        'column_total_credit' => 'Total Credit',
        'column_created_at' => 'Created At',
        'column_updated_at' => 'Updated At',
        'column_actions' => 'Actions',
    ],

    'cancelled_title' => 'Cancelled Journal Entries',
    'cancelled_subtitle' => 'View all cancelled journal entries.',
    'history_title' => 'Posted Journal Entries',
    'history_subtitle' => 'View all posted journal entries.',

    'audit' => [
        'role' => 'Role',
        'name' => 'Name',
        'date' => 'Date',
        'signature' => 'Signature',
        'created_by' => 'Created By',
        'approved_by' => 'Approved By',
        'cancelled_by' => 'Cancelled By',
        'undone_by' => 'Restored By',
    ],
];
