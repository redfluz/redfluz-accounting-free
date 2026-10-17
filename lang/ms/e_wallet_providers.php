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
    'title' => 'Penyedia E-Dompet',
    'subtitle' => 'Urus penerbit e-dompet yang ditetapkan kepada akaun bank.',
    'accounts_title' => 'Akaun yang menggunakan penyedia ini',
    'accounts_empty' => 'Tiada akaun menggunakan penyedia ini.',
    'form' => [
        'create_title' => 'Cipta Penyedia E-Dompet',
        'edit_title' => 'Sunting Penyedia E-Dompet',
        'name' => 'Nama Penyedia',
        'country' => 'Negara',
        'identifier_label' => 'Label Pengecam',
        'chart' => 'Carta Akaun',
        'chart_placeholder' => 'Pilih carta akaun',
        'is_active' => 'Aktif',
    ],
    'table' => [
        'name' => 'Penyedia',
        'country' => 'Negara',
        'identifier_label' => 'Pengecam',
        'chart' => 'Carta',
        'accounts' => 'Akaun',
        'status' => 'Status',
    ],
    'accounts_table' => [
        'account' => 'Akaun',
        'number' => 'Nombor Akaun',
        'chart' => 'Carta',
        'status' => 'Status',
    ],
    'status' => ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'],
    'validation' => [
        'provider_in_use' => 'Penyedia ini digunakan oleh satu atau lebih akaun. Nyahaktifkannya dan jangan padam.',
    ],
];
