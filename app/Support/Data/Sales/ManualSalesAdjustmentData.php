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

use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerSubType;
use App\Models\Sales\SalesInvoice;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A manual sales adjustment: a correction raised against an invoice that has
 * gone out, keyed the old way — the user picks the accounts and the sides.
 *
 * The note exists to correct an invoice, so the invoice is carried on the
 * document as `sales_invoice_id`, with the invoice's document number kept in
 * `reference_number` as the snapshot the grids print. The direction is a fact
 * about the document: an increase raises the customer's balance, a decrease
 * reduces it, and the posting follows whichever way the user keyed the
 * accounts.
 *
 * The lines are written by the controller from the same validated input; this
 * carries what is true of the header only.
 */
class ManualSalesAdjustmentData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('document_number')]
        public ?string $adjustmentNumber = null,

        #[MapName('subtype')]
        public ?LedgerSubType $subtype = null,

        // The invoice this corrects. Held twice, on purpose: the foreign key
        // joins it, the reference number prints it.
        #[MapName('sales_invoice_id')]
        public ?string $salesInvoiceId = null,

        #[MapName('reference_number')]
        public ?string $referenceNumber = null,

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('contact_id')]
        public ?string $contactId = null,

        #[MapName('company_name')]
        public ?string $companyName = null,

        #[MapName('contact_name')]
        public ?string $contactName = null,

        #[MapName('date')]
        public ?CarbonImmutable $date = null,

        #[MapName('status')]
        public ?DocumentStatus $status = null,

        #[MapName('attachment')]
        public mixed $attachment = null,

        #[MapName('attachment_path')]
        public ?string $attachmentPath = null,

        #[MapName('subtotal')]
        public ?string $subtotal = null,

        #[MapName('total_amount')]
        public ?string $totalAmount = null,

        #[MapName('notes')]
        public ?string $notes = null,
    ) {}

    /**
     * Fill in the invoice this note corrects, from the invoice itself.
     *
     * The form posts the invoice's id; the reference number is the invoice's
     * document number, taken from the invoice rather than typed, so the two
     * cannot disagree. The company name prefers the invoice's own snapshot,
     * falling back to the company it was billed to.
     */
    public function linkInvoice(SalesInvoice $invoice, ?string $companyName): void
    {
        $this->salesInvoiceId = $invoice->id;
        $this->referenceNumber = $invoice->document_number;
        $this->companyName = filled($invoice->company_name) ? (string) $invoice->company_name : $companyName;
    }
}
