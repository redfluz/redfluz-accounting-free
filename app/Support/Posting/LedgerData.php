<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

namespace App\Support\Posting;

use App\Enums\LedgerSourceType;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTime;
use DateTimeInterface;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One financial row of the general ledger, written from a posting leg.
 *
 * The general ledger is the double entry: every posting, whatever raised it,
 * reduced to a figure against an account. This is that row in its strictest
 * form — the account, the amount, the document, and nothing else.
 *
 * It is the row the poster builds when a caller hands it legs. Legs carry no
 * document line — a receipt line is an amount against an account, not a product
 * with a quantity — so the row carries no line detail, and there is nowhere for
 * detail to leak into. The rows for documents that do have lines, such as
 * invoices, are built by {@see GeneralLedgerData} and handed to the poster
 * already made; the two row types are deliberate. One table holds both shapes
 * because the line columns are nullable, but a posting never fills them from a
 * leg, so a leg can never invent a product or a quantity.
 *
 * Every financial column is snapshotted rather than left to be joined at read
 * time: the account's code and name alongside its id, the document's kind
 * alongside the source it came from. A ledger read years later must still
 * explain itself when the chart has been renamed or the document kind has moved
 * on.
 *
 * @param  numeric-string  $debitAmount
 * @param  numeric-string  $creditAmount
 * @param  numeric-string  $totalAmount
 */
class LedgerData extends Data
{
    /**
     * @param  string  $id  The row's own id, borrowed from the client ledger row
     *                      the movement came from when there is one — that is how
     *                      a second posting of the same document is caught — and
     *                      fresh otherwise.
     * @param  string  $sourceId  What to drill back to: the client ledger row when
     *                            the movement came from one, the document otherwise.
     * @param  LedgerSourceType|null  $sourceType  The kind of document that raised it.
     * @param  LedgerType|null  $type  The kind of posting, kept because a report
     *                                 filters on it: a payment and an invoice are
     *                                 different things even when they post to the
     *                                 same account.
     * @param  LedgerSubType|null  $subtype  The finer kind, for the same reason.
     * @param  string|null  $companyId  The customer or supplier, where the document names one.
     * @param  string|null  $companyName  Their name, snapshotted at posting time.
     * @param  string|null  $contactId  The person at that company, where named.
     * @param  string  $documentNumber  The document's number.
     * @param  string|null  $referenceNumber  What the movement settles or corrects.
     * @param  CarbonInterface  $documentDate  The document's date, which a period report filters on.
     * @param  CarbonInterface  $date  The date the entry is booked, kept beside the
     *                                 document date because a trial balance filters on this one.
     * @param  string|null  $debitId  The chart account, on the side this row carries.
     * @param  string|null  $creditId  Never set on the same row as debit_id: one row,
     *                                 one movement.
     * @param  string|null  $debitAcc  The account's code, snapshotted at posting time.
     * @param  string|null  $creditAcc  The code, on the credit side.
     * @param  string|null  $debitAccDesc  The account's name, snapshotted at posting time.
     * @param  string|null  $creditAccDesc  The name, on the credit side.
     * @param  string  $debitAmount  The amount debited.
     * @param  string  $creditAmount  The amount credited.
     * @param  string  $totalAmount  The movement itself: whichever side carries it.
     * @param  string|null  $description  What the movement was for.
     * @param  string|null  $notes  Anything the document carried beyond its lines.
     * @param  string  $status  Always posted: this row exists only on the way into the ledger.
     * @param  CarbonInterface|null  $createdAt  When the row was written.
     * @param  CarbonInterface|null  $updatedAt  When the row was last changed.
     */
    public function __construct(
        #[MapName('id')]
        public string $id,

        // Where this figure came from. Required: a ledger row that cannot be
        // traced to the document that raised it cannot be explained, and the
        // drill-down to the line detail starts here.
        #[MapName('source_id')]
        public string $sourceId,

        #[MapName('source_type')]
        public ?LedgerSourceType $sourceType,

        #[MapName('type')]
        public ?LedgerType $type,

        #[MapName('subtype')]
        public ?LedgerSubType $subtype,

        #[MapName('company_id')]
        public ?string $companyId,

        #[MapName('company_name')]
        public ?string $companyName,

        #[MapName('contact_id')]
        public ?string $contactId,

        #[MapName('document_number')]
        public string $documentNumber,

        #[MapName('reference_number')]
        public ?string $referenceNumber,

        // The date the entry is dated, kept beside document_date because a period
        // report filters on one and a trial balance on the other.
        #[MapName('document_date')]
        public CarbonInterface $documentDate,

        #[MapName('date')]
        public CarbonInterface $date,

        // The account, three ways: the id is the join, the code is how an
        // accountant refers to it, the description is what a printed report
        // shows, and all three are snapshotted now so a ledger read years later
        // still names the account the figure was booked against.
        #[MapName('debit_id')]
        public ?string $debitId,

        #[MapName('credit_id')]
        public ?string $creditId,

        #[MapName('debit_acc')]
        public ?string $debitAcc,

        #[MapName('credit_acc')]
        public ?string $creditAcc,

        #[MapName('debit_acc_desc')]
        public ?string $debitAccDesc,

        #[MapName('credit_acc_desc')]
        public ?string $creditAccDesc,

        #[MapName('debit_amount')]
        public string $debitAmount,

        #[MapName('credit_amount')]
        public string $creditAmount,

        #[MapName('total_amount')]
        public string $totalAmount,

        #[MapName('description')]
        public ?string $description,

        #[MapName('notes')]
        public ?string $notes,

        #[MapName('status')]
        public string $status,

        #[MapName('created_at')]
        public ?CarbonInterface $createdAt,

        #[MapName('updated_at')]
        public ?CarbonInterface $updatedAt,
    ) {}

    /**
     * Build the general ledger row for one posting leg.
     *
     * @param  PostingLeg  $leg  The movement, already single-sided and exact.
     * @param  string  $documentKey  The posting document's key, used as the drill-back
     *                               target when the leg came from no client ledger row.
     * @param  LedgerType  $type  The kind of posting.
     * @param  LedgerSubType  $subtype  The finer kind.
     * @param  LedgerSourceType|null  $sourceType  The kind of document that raised it.
     * @param  array{code: string|null, name: string|null}  $account  The chart
     *                                                                account's code and name, snapshotted at posting time.
     * @param  string|null  $companyId  The customer or supplier, where the document names one.
     * @param  string|null  $companyName  Their name.
     * @param  string|null  $contactId  The person at that company, where named.
     * @param  string  $documentNumber  The document's number.
     * @param  DateTimeInterface  $documentDate  The document's date.
     * @param  string|null  $referenceNumber  What the document settles or corrects.
     * @param  string|null  $notes  Anything the document carried beyond its lines.
     */
    public static function fromLeg(
        PostingLeg $leg,
        string $documentKey,
        LedgerType $type,
        LedgerSubType $subtype,
        ?LedgerSourceType $sourceType,
        array $account,
        ?string $companyId,
        ?string $companyName,
        ?string $contactId,
        string $documentNumber,
        DateTimeInterface $documentDate,
        ?string $referenceNumber = null,
        ?string $notes = null,
    ): self {
        $documentDate = $documentDate instanceof CarbonInterface
            ? $documentDate
            : Carbon::instance(DateTime::createFromInterface($documentDate));
        $isDebit = $leg->isDebit;

        return new self(
            id: $leg->id ?? (string) Str::uuid7(),
            sourceId: $leg->id ?? $documentKey,
            sourceType: $sourceType,
            type: $type,
            subtype: $subtype,
            companyId: $companyId,
            companyName: $companyName,
            contactId: $contactId,
            documentNumber: $documentNumber,
            referenceNumber: $referenceNumber,
            documentDate: $documentDate,
            date: $documentDate,
            debitId: $isDebit ? $leg->chartId : null,
            creditId: $isDebit ? null : $leg->chartId,
            debitAcc: $isDebit ? $account['code'] : null,
            creditAcc: $isDebit ? null : $account['code'],
            debitAccDesc: $isDebit ? $account['name'] : null,
            creditAccDesc: $isDebit ? null : $account['name'],
            debitAmount: $leg->debit,
            creditAmount: $leg->credit,
            totalAmount: bcadd($leg->debit, $leg->credit, 2),
            description: $leg->description,
            notes: $notes,
            // Always POSTED. A row is only built on the way into the general
            // ledger, and there is no pending state to carry across.
            status: LedgerStatus::POSTED->value,
            createdAt: now(),
            updatedAt: now(),
        );
    }

    /**
     * The row as the ledgers table wants it.
     *
     * Only the financial columns are written. The line columns the table still
     * carries are left alone, so a leg cannot put a product or a quantity on a
     * ledger row.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'source_type' => $this->sourceType?->value,
            'type' => $this->type?->value,
            'subtype' => $this->subtype?->value,
            'document_date' => $this->documentDate->format('Y-m-d'),
            'date' => $this->date->format('Y-m-d'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
