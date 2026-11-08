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

use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
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
 * with a quantity — so the row carries no line detail beyond what the leg says.
 * The rows for documents that do have lines, such as invoices, are built by
 * {@see GeneralLedgerData} and handed to the poster already made.
 *
 * Every descriptive column is snapshotted rather than left to be joined at read
 * time: the account's code and name alongside its id, the company's name
 * alongside its id. A ledger read years later must still explain itself when
 * the chart has been renamed or the company has moved on.
 *
 * @param  numeric-string  $debitAmount
 * @param  numeric-string  $creditAmount
 */
class LedgerData extends Data
{
    /**
     * @param  string  $id  The row's own id, borrowed from the client ledger row
     *                      the movement came from when there is one — that is how
     *                      a second posting of the same document is caught — and
     *                      fresh otherwise.
     * @param  string|null  $chartId  The chart account, on the side this row carries.
     * @param  string|null  $companyId  The customer or supplier, where the document names one.
     * @param  string|null  $currencyId  The currency the figures are in.
     * @param  LedgerType|null  $type  The kind of posting, kept because a report
     *                                 filters on it: a payment and an invoice are
     *                                 different things even when they post to the
     *                                 same account.
     * @param  LedgerSubType|null  $subtype  The finer kind, for the same reason.
     * @param  string  $documentNumber  The document's number.
     * @param  string|null  $referenceNumber  What the movement settles or corrects.
     * @param  CarbonInterface  $date  The date the entry is booked, which a period
     *                                 report or a trial balance filters on.
     * @param  string|null  $chartCode  The account's code, snapshotted at posting time.
     * @param  string|null  $chartName  The account's name, snapshotted at posting time.
     * @param  string|null  $companyName  The company's name, snapshotted at posting time.
     * @param  string|null  $description  What the posting was for at document level —
     *                                    the master description or notes.
     * @param  string|null  $lineDescription  What the movement was for at line level —
     *                                        the detail description or notes.
     * @param  string  $debitAmount  The amount debited.
     * @param  string  $creditAmount  The amount credited.
     * @param  CarbonInterface|null  $createdAt  When the row was written.
     * @param  CarbonInterface|null  $updatedAt  When the row was last changed.
     */
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('chart_id')]
        public ?string $chartId,

        #[MapName('company_id')]
        public ?string $companyId,

        #[MapName('currency_id')]
        public ?string $currencyId,

        #[MapName('type')]
        public ?LedgerType $type,

        #[MapName('subtype')]
        public ?LedgerSubType $subtype,

        #[MapName('document_number')]
        public string $documentNumber,

        #[MapName('reference_number')]
        public ?string $referenceNumber,

        // The date the entry is booked. There is one date on a ledger row: the
        // document's own date. When it was approved is carried by the document.
        #[MapName('date')]
        public CarbonInterface $date,

        #[MapName('chart_code')]
        public ?string $chartCode,

        #[MapName('chart_name')]
        public ?string $chartName,

        #[MapName('company_name')]
        public ?string $companyName,

        #[MapName('description')]
        public ?string $description,

        #[MapName('line_description')]
        public ?string $lineDescription,

        #[MapName('debit_amount')]
        public string $debitAmount,

        #[MapName('credit_amount')]
        public string $creditAmount,

        #[MapName('created_at')]
        public ?CarbonInterface $createdAt,

        #[MapName('updated_at')]
        public ?CarbonInterface $updatedAt,
    ) {}

    /**
     * Build the general ledger row for one posting leg.
     *
     * @param  PostingLeg  $leg  The movement, already single-sided and exact.
     * @param  LedgerType  $type  The kind of posting.
     * @param  LedgerSubType  $subtype  The finer kind.
     * @param  array{code: string|null, name: string|null}  $account  The chart
     *                                                                account's code and name, snapshotted at posting time.
     * @param  string|null  $companyId  The customer or supplier, where the document names one.
     * @param  string|null  $companyName  Their name.
     * @param  string|null  $currencyId  The currency the figures are in.
     * @param  string  $documentNumber  The document's number.
     * @param  DateTimeInterface  $date  The document's date.
     * @param  string|null  $referenceNumber  What the document settles or corrects.
     * @param  string|null  $notes  The document's master description, beyond its lines.
     */
    public static function fromLeg(
        PostingLeg $leg,
        LedgerType $type,
        LedgerSubType $subtype,
        array $account,
        ?string $companyId,
        ?string $companyName,
        ?string $currencyId,
        string $documentNumber,
        DateTimeInterface $date,
        ?string $referenceNumber = null,
        ?string $notes = null,
    ): self {
        $date = $date instanceof CarbonInterface
            ? $date
            : Carbon::instance(DateTime::createFromInterface($date));

        return new self(
            id: $leg->id ?? (string) Str::uuid7(),
            chartId: $leg->chartId,
            companyId: $companyId,
            currencyId: $currencyId,
            type: $type,
            subtype: $subtype,
            documentNumber: $documentNumber,
            referenceNumber: $referenceNumber,
            date: $date,
            chartCode: $account['code'],
            chartName: $account['name'],
            companyName: $companyName,
            description: $notes,
            lineDescription: $leg->description,
            debitAmount: $leg->debit,
            creditAmount: $leg->credit,
            createdAt: now(),
            updatedAt: now(),
        );
    }

    /**
     * The row as the ledgers table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'type' => $this->type?->value,
            'subtype' => $this->subtype?->value,
            'date' => $this->date->format('Y-m-d'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
