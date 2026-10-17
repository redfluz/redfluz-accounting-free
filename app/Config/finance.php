<?php

/**
 * Invoice and purchase-invoice PDF layout catalogue.
 * Database configurations select a key below and may override its colors.
 */

return [
    'invoice_designs' => [
        'classic' => [
            'label' => 'Classic Blue',
            'header' => 'split',
            'table' => 'lined',
            'primary_color' => '#1F4E79',
            'secondary_color' => '#F4F7FA',
            'accent_color' => '#00A6A6',
        ],
        'modern' => [
            'label' => 'Modern Teal',
            'header' => 'banner',
            'table' => 'zebra',
            'primary_color' => '#0F766E',
            'secondary_color' => '#ECFDF5',
            'accent_color' => '#F59E0B',
        ],
        'minimal' => [
            'label' => 'Minimal Slate',
            'header' => 'compact',
            'table' => 'minimal',
            'primary_color' => '#334155',
            'secondary_color' => '#F8FAFC',
            'accent_color' => '#475569',
        ],
    ],
];
