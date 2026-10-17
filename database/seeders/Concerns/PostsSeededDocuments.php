<?php

/**
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace Database\Seeders\Concerns;

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Models\Bank\PaymentVoucher;
use App\Models\Bank\PaymentVoucherChart;
use App\Models\Bank\Receipt;
use App\Models\Bank\ReceiptChart;
use App\Models\Bank\Refund\CustomerRefund;
use App\Models\Bank\Refund\CustomerRefundChart;
use App\Models\Bank\Refund\SupplierRefund;
use App\Models\Bank\Refund\SupplierRefundChart;
use App\Support\Posting\PaymentLedgerPoster;
use App\Support\Posting\PostingLeg;
use Illuminate\Support\Facades\DB;

/**
 * Post seeded documents through the same poster the controllers use.
 *
 * A seeded document marked posted has to appear in the general ledger and in the
 * money and sub ledgers, exactly as it would if a user had approved it. Writing
 * those rows by hand in the seeders meant the seed and the running application
 * had two separate definitions of what posting a document means, and the two
 * drifted: a seeded receipt would show on the history grid with nothing behind it
 * in the ledgers, and the trial balance would not agree with its own detail.
 *
 * So seeding posts rather than writes. The legs come from the document's own
 * chart rows, which is the same source the controllers read, and the routing to the
 * receivable or payable ledger is decided by PaymentLedgerPoster from the accounts
 * named, so a seed cannot disagree with what a real approval would produce.
 */
trait PostsSeededDocuments
{
    /**
     * Post one seeded document if it is marked posted.
     *
     * Anything not posted is left alone. A pending or cancelled seed is a
     * document waiting for a decision, and posting it here would put it in the
     * ledgers before anyone approved it.
     *
     * The legs come from the document's own chart rows, which is the same
     * source the controllers read, and the routing to the receivable or
     * payable ledger is decided by PaymentLedgerPoster from the accounts
     * named, so a seed cannot disagree with what a real approval would
     * produce.
     *
     * @param  Receipt|PaymentVoucher|CustomerRefund|SupplierRefund  $document  The document just created.
     * @param  LedgerSubType  $subtype  What kind of document this is.
     * @param  string  $numberColumn  The column on the document holding its number.
     * @param  PaymentLedgerPoster  $poster  The poster to write the entry with, passed in by
     *                                       the seeder so this trait does not reach for the
     *                                       container itself.
     */
    /**
     * Remove every ledger row a seeded document has already written.
     *
     * A seeder replaces a document by deleting it and raising it again, but
     * deleting the document does not delete what posting it wrote. The old rows
     * stay behind and the replacement posts a second set on top of them, so a
     * re-seed quietly doubles every balance instead of replacing it.
     *
     * The tables are cleared together because a movement left standing in the
     * general ledger after its money ledger row is gone has become a figure no
     * account can be reconciled against, which is worse than a duplicate row.
     *
     * @param  string  $number  The seeded document's number.
     */
    protected function purgePostedRows(string $number): void
    {
        foreach (['ledgers', 'ar_ledgers', 'ap_ledgers', 'cash_ledgers', 'bank_ledgers', 'e_wallet_ledgers'] as $table) {
            DB::table($table)->where('document_number', $number)->delete();
        }
    }

    protected function postSeededDocument(
        Receipt|PaymentVoucher|CustomerRefund|SupplierRefund $document,
        LedgerSubType $subtype,
        string $numberColumn,
        PaymentLedgerPoster $poster
    ): void {
        if ($document->status !== LedgerStatus::POSTED) {
            return;
        }

        /** @var list<PostingLeg> $legs */
        $legs = [];
        foreach ($document->charts()->get() as $chart) {
            /** @var ReceiptChart|PaymentVoucherChart|CustomerRefundChart|SupplierRefundChart $chart */
            if ($chart->debit_id !== null) {
                $legs[] = PostingLeg::debit($chart->debit_id, (string) $chart->debit_amount, $chart->description);
            } elseif ($chart->credit_id !== null) {
                $legs[] = PostingLeg::credit($chart->credit_id, (string) $chart->credit_amount, $chart->description);
            }
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
