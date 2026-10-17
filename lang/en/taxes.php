<?php

return [
    'title' => 'Taxes',
    'subtitle' => 'Maintain reusable tax rates and their default calculation mode.',
    'form' => [
        'create_title' => 'Add Tax',
        'edit_title' => 'Edit Tax',
        'code' => 'Code',
        'name' => 'Name',
        'rate' => 'Rate',
        'default_mode' => 'Default Tax Mode',
        'is_active' => 'Active',
        'save' => 'Save Tax',
        'none' => 'No Tax',
        'manual' => 'Manual Tax Rate',
        'selection' => 'Tax',
        'manual_hint' => 'Enter the tax rate and choose inclusive or exclusive calculation.',
    ],
    'table' => [
        'code' => 'Code',
        'name' => 'Name',
        'rate' => 'Rate',
        'mode' => 'Default Mode',
        'status' => 'Status',
        'action' => 'Action',
    ],
    'status' => ['active' => 'Active', 'inactive' => 'Inactive'],
    'messages' => [
        'empty' => 'No taxes were found.',
        'created' => 'Tax created successfully.',
        'updated' => 'Tax updated successfully.',
        'deleted' => 'Tax deleted successfully.',
        'delete_confirm' => 'Delete this tax? Existing invoices will keep their saved tax details.',
        'inactive' => 'The selected tax is inactive.',
    ],
];
