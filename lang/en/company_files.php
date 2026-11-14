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
    'title' => 'Company Files & Notes',
    'subtitle' => 'Manage attached documents and notes for this company',
    'tabs' => [
        'details' => 'Details',
        'attachments' => 'Attachment',
    ],
    'attachments' => [
        'current' => 'Current Attachments',
        'replace' => 'Replace Attachment',
        'new_preview' => 'New Attachment Preview',
        'add_more' => 'Add More Files',
        'add' => 'Add Attachments',
        'remove' => 'Remove',
    ],
    'actions' => [
        'add' => 'Add File / Note',
        'edit' => 'Edit File / Note',
        'show' => 'File / Note Details',
        'download' => 'Download Attachment',
    ],
    'table' => [
        'column_company' => 'Company',
        'column_title' => 'Title',
        'column_notes' => 'Notes',
        'column_attachment' => 'Attachment',
        'column_file_name' => 'File Name',
        'column_file_size' => 'File Size',
        'column_mime_type' => 'File Type',
        'column_date' => 'Date',
    ],
    'form' => [
        'label_company' => 'Company',
        'label_title' => 'Title / Subject',
        'label_notes' => 'Notes',
        'label_attachment' => 'Attachment',
        'label_remove_attachment' => 'Remove current file attachment',
        'attachment_size_hint' => 'Upload up to 10 files. Images (JPG, PNG, WebP) up to 8000 KB; documents (PDF, DOC, DOCX, XLS, XLSX) up to 20 MB.',
        'attachment_too_large' => 'The following files exceed the size limit and were not added:',
        'attachment_too_large_image' => 'Image exceeds the 8000 KB limit and was not added:',
        'attachment_too_large_doc' => 'Document exceeds the 20 MB limit and was not added:',
        'too_many_attachments' => 'You may upload no more than 10 files.',
        'select_company' => 'Select a Company',
    ],
    'messages' => [
        'no_attachment' => 'No attachment uploaded.',
    ],
];
