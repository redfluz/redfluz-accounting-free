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

declare(strict_types=1);

namespace App\Support\Data\Sales;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * The form data for a sales invoice.
 *
 * A sales invoice is not a single model row: it is a set of client-ledger entries
 * built from the form. This DTO carries the validated header fields so the
 * controller can ask for named properties instead of reaching into an array.
 *
 * The attachment field is named invoice_attachment in the form, because a page
 * may carry more than one file input and each needs a distinct name.
 */
class SalesInvoiceData extends Data
{
    public function __construct(
        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('contact_id')]
        public ?string $contactId = null,

        #[MapName('debit_id')]
        public ?string $debitId = null,

        #[MapName('credit_id')]
        public ?string $creditId = null,

        #[MapName('date')]
        public ?CarbonImmutable $date = null,

        #[MapName('amount')]
        public ?string $amount = null,

        #[MapName('payment_debit_id')]
        public ?string $paymentDebitId = null,

        #[MapName('payment_credit_id')]
        public ?string $paymentCreditId = null,

        #[MapName('invoice_attachment')]
        public mixed $invoiceAttachment = null,

        #[MapName('invoice_notes')]
        public ?string $invoiceNotes = null,

        #[MapName('payment_notes')]
        public ?string $paymentNotes = null,
    ) {}
}
