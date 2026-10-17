<?php

return [
    'title' => 'Currency Management',
    'subtitle' => 'Manage currencies for financial transactions.',
    'table' => [
        'column_code' => 'Code',
        'column_name' => 'Name',
        'column_symbol' => 'Symbol',
        'column_default' => 'Default',
        'column_active' => 'Active',
    ],
    // Deletion is refused while these records still reference the entry.
    'validation' => [
        'in_use' => 'This currency cannot be deleted because it is already used by: :references.',
        'default_in_use' => 'The default currency cannot be deleted. Set another currency as default first.',
    ],
    'usage' => [
        'system_settings' => 'the system settings',
    ],
];
