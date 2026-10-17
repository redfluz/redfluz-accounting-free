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
        'close' => 'Close',
        'notes' => 'Notes',
        'add' => 'Add',
        'remove' => 'Remove',
        'edit' => 'Edit',
        'update' => 'Update',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'search' => 'Search',
        'reset' => 'Reset',
        'view' => 'View',
        'send' => 'Send',
        'back' => 'Back',
        'views' => 'Views',
        'post' => 'Post',
        'approve' => 'Approve',
        'print_pdf' => 'Print PDF',
        'download' => 'Download',
        'upload' => 'Upload',
        'choose' => 'Choose',
        'next' => 'Next',
        'previous' => 'Previous',
        'complete' => 'Complete',
        'select_option' => 'Please select',
        'check_all' => 'Check All',
        'clear_all' => 'Clear All',
        'filter' => 'Filter',
        'locked' => 'Locked',
        'markAllAsRead' => 'Mark all as read',
        'export_excel' => 'Export Excel',
        'export_pdf' => 'Export PDF',
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'batch_approve' => 'Approve Selected',
        'batch_cancel' => 'Cancel Selected',
        'undo' => 'Undo',
        'clone' => 'Clone',
        'action' => 'Action',
        'toggle_password_visibility' => 'Show or hide password',
    ],
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],
    'labels' => [
        'ledger' => 'Ledger',
        'reports' => 'Reports',
        'audit_trail' => 'Audit Trail',
        'login' => 'Login',
        'logout' => 'Log Out',
        'profile' => 'Profile',
        'all' => 'All',
        'not_found_record' => 'Record not found.',
        'leave_blank_password' => '(Leave blank to keep current)',
        'publish_on' => 'Publish on',
        'users' => 'Users',
        'welcome' => 'Welcome',
        'welcome_back' => 'Welcome Back',
        'sign_in_dashboard' => 'Sign in to access your dashboard',
        'sign_in_account' => 'Enter your details below',
        'username_or_email' => 'Username or Email',
        'email_address' => 'E-mail Address',
        'password' => 'Password',
        'created_at' => 'Created At',
        'actions' => 'Action',
        'brand' => 'Brand',
        'system_error' => 'System Error',
        'comments' => 'Comments',
        'join_comment' => 'Join the discussion...',
        'total' => 'Total',
        'choose_file' => 'Choose File',
        'no_file_chosen' => 'No file selected',
        'system' => 'System',
        'yes' => 'Yes',
        'no' => 'No',
    ],
    'alerts' => [
        'confirm_delete' => 'Confirm Delete',
        'confirm_delete_message' => 'Are you sure you want to delete this item?',
        'confirm_delete_yes' => 'Yes, delete it!',
        'confirm_batch_approve' => 'Approve all selected records and post to the general ledger?',
        'confirm_batch_cancel' => 'Cancel all selected records?',
        'no_selection' => 'Please select at least one record.',
    ],
    'messages' => [
        'batch_approved' => ':count record(s) approved and posted.',
        'batch_cancelled' => ':count record(s) cancelled.',
        'uncancelled' => 'Record has been restored.',
        'nothing_to_uncancel' => 'That record is no longer cancelled, so there is nothing to restore.',
        'no_longer_cancelled' => 'That record is no longer cancelled, so you have been taken to the live document.',
        'uncancel_confirm' => 'Are you sure you want to undo this cancellation?',
    ],
    'batch_rejected' => [
        // A batch is approved or it is not: one document failing a check rejects
        // the whole selection, so the user is told which document stopped it
        // rather than being handed a count that quietly dropped some.
        'not_found' => 'Document :number was not found.',
        'no_entries' => 'Document :number has no ledger entries to post.',
        'not_pending' => 'Document :number is no longer pending, so it cannot be approved.',
        'already_posted' => 'Document :number has already been posted.',
        'no_lines' => 'Document :number has no entry lines.',
        'unbalanced' => 'Document :number does not balance: debit and credit differ.',
        'non_positive' => 'Document :number has no positive amount to post.',
        'no_linked_invoice' => 'Document :number has no linked invoice or receipt.',
    ],
    'errors' => [
        'posting_date_settings_missing' => 'Financial posting is unavailable because chart settings have not been configured.',
        'posting_date_unavailable' => 'The posting date does not belong to an available financial period.',
        'posting_date_year_closed' => 'The posting date belongs to a closed financial year.',
        'posting_date_period_closed' => 'The posting date belongs to a closed financial period.',
        'posting_date_current_period_missing' => 'Strict journal posting requires a current financial year and period in chart settings.',
        'posting_date_not_current_period' => 'Strict journal posting allows submissions only in the current financial period.',
        'linked_invoice_invalid' => 'Select a valid posted invoice belonging to the selected company.',
    ],
    'sales_type' => [
        'label' => 'Sales Type',
        'product' => 'Product',
        'service' => 'Service',
        'recurring' => 'Recurring',
        'duration' => 'Duration',
    ],
];
