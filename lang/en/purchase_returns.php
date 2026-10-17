<?php

return [
    'title' => 'Purchase Returns',
    'subtitle' => 'Return received stock to the supplier. Credit notes and refunds are recorded separately.',
    'company' => 'Supplier',
    'grn_document' => 'GRN Document',
    'receipt_line' => 'Receipt Line',
    'receipt' => 'Original GRN Line',
    'quantity' => 'Return Quantity',
    'date' => 'Return Date',
    'notes' => 'Notes',
    'pending' => 'Pending',
    'active' => 'Posted',
    'next' => 'After posting, record the supplier credit note under Purchase Adjustments. Record a supplier refund only when money is received.',
    'adjustment' => 'Purchase Adjustments',
    'refund' => 'Supplier Refund',
    'validation' => [
        'before_receipt' => 'The return date cannot be earlier than the received date.',
        'exceeds_received' => 'The return exceeds the remaining received quantity (:quantity).',
        'insufficient_stock' => 'There is not enough saleable stock to post this return.',
    ],
];
