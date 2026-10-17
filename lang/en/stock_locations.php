<?php

return [
    'title' => 'Stock Locations',
    'subtitle' => 'Manage branches, their warehouses, and the users permitted to handle stock there.',
    'form' => [
        'create_title' => 'Create Stock Location',
        'edit_title' => 'Edit Stock Location',
        'code' => 'Location Code',
        'name' => 'Location Name',
        'type' => 'Location Type',
        'parent_branch' => 'Parent Branch',
        'address' => 'Address',
        'active' => 'Active',
        'users' => 'Permitted Users',
    ],
    'type' => ['branch' => 'Branch', 'warehouse' => 'Warehouse', 'bin' => 'Bin', 'pallet' => 'Pallet'],
    'structure' => [
        'title' => 'Warehouse Structure',
        'subtitle' => 'Create the physical hierarchy: stock location → floor → line → bin or pallet.',
        'floor' => 'Add Floor',
        'line' => 'Add Line',
        'bin_pallet' => 'Add Bin / Pallet',
    ],
    'table' => [
        'code' => 'Code', 'name' => 'Location', 'type' => 'Type',
        'branch' => 'Branch', 'users' => 'Users', 'status' => 'Status', 'action' => 'Action',
    ],
    'actions' => ['save' => 'Save Location'],
    'messages' => [
        'empty' => 'No stock locations were found.',
        'delete_confirm' => 'Delete this unused stock location?',
        'user_access' => 'Only selected users can receive or use stock from this location.',
    ],
    'validation' => [
        'branch_required' => 'Select the branch that owns this warehouse.',
        'location_in_use' => 'This location cannot be deleted because it has stock movements or warehouses.',
    ],
];
