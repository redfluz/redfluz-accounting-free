<?php

/**
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace Database\Seeders\Concerns;

use App\Enums\Finance\LedgerStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\Bank\PaymentVoucher;
use App\Models\Bank\Receipt;
use App\Models\Bank\Refund\CustomerRefund;
use App\Models\Bank\Refund\SupplierRefund;
use App\Support\Posting\PaymentLedgerPoster;
use App\Support\Posting\PostingLeg;
use Illuminate\Support\Facades\DB;

/**
 * Post seeded documents through the same poster the controllers use.
 *
 * The free edition has no intermediate *_charts tables for receipts, vouchers
 * or refunds. The seeder builds the PostingLeg list directly from the document's
 * debit and credit chart IDs and hands it to this trait, which passes it to the
 * poster unchanged.
 */
trait PostsSeededDocuments
{
    /**
     * Remove every ledger row a seeded document has already written.
     *
     * @param  string  $number  The seeded document's number.
     */
    protected function purgePostedRows(string $number): void
    {
        foreach (['ledgers', 'ar_ledgers', 'ap_ledgers', 'cash_ledgers', 'bank_ledgers', 'e_wallet_ledgers'] as $table) {
            DB::table($table)->where('document_number', $number)->delete();
        }
    }

    /**
     * Post one seeded document if it is marked posted.
     *
     * @param  Receipt|PaymentVoucher|CustomerRefund|SupplierRefund  $document  The document just created.
     * @param  LedgerSubType  $subtype  What kind of document this is.
     * @param  string  $numberColumn  The column on the document holding its number.
     * @param  PaymentLedgerPoster  $poster  The poster to write the entry with.
     * @param  list<PostingLeg>  $legs  The double-entry legs, built by the caller.
     */
    protected function postSeededDocument(
        Receipt|PaymentVoucher|CustomerRefund|SupplierRefund $document,
        LedgerSubType $subtype,
        string $numberColumn,
        PaymentLedgerPoster $poster,
        array $legs = [],
    ): void {
        if ($document->status !== LedgerStatus::POSTED) {
            return;
        }

        if ($legs === []) {
            return;
        }

        $poster->post(
            $document,
            (string) $document->{$numberColumn},
            $document->date,
            LedgerType::PAYMENT,
            $subtype,
            $legs,
            companyId: $document->company_id,
            contactId: $document->contact_id,
            referenceNumber: $document->reference_number,
        );
    }
}
