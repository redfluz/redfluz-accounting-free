<?php

return [
    'title' => 'Purchase Order Settings',
    'subtitle' => 'Set the default credit term and the maximum Purchase Request, supplier quotation award, or Purchase Order amount each role may approve.',
    'default_credit_term_days' => 'Default Credit Term',
    'default_required_days' => 'Default Required Date',
    'default_required_days_help' => 'Used when a Purchase Request required date is empty.',
    'default_rfq_response_days' => 'Default RFQ Response Period',
    'default_rfq_response_days_help' => 'Sent supplier quotations expire after this number of days when no response is received.',
    'days' => 'days',
    'role' => 'Role',
    'approval_limit' => 'Maximum Approval Amount',
    'unlimited' => 'Unlimited',
    'tabs' => [
        'general' => 'General',
        'approval_limits' => 'Approval Limits',
    ],
    'messages' => ['updated' => 'Purchase Order settings updated successfully.'],
    'validation' => [
        'role_not_configured' => 'Your role has no purchase approval limit configured.',
        'limit_exceeded' => 'This document total is RM :amount. Your approval limit is RM :limit.',
    ],
];
