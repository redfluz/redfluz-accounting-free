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

use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Models\ChartOfAccount\Chart;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceChart;
use App\Models\Sales\SalesInvoiceLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One row of the receivable ledger.
 *
 * A sub ledger row is not a document. It is the receivable side of one: the part
 * of an entry that left the customer owing more, or stopped owing. It records a
 * figure and where that figure belongs, and nothing else:
 *
 * - Who with. The company the figure belongs to, as a reference to its row
 *   and as the name it read at posting time — a report printed years later
 *   still names the customer the figure was filed under even if the company
 *   has been renamed since.
 * - Against which account. The chart account, as a reference and as the code,
 *   name and description it had at posting time — a report printed years later
 *   still names the account the figure was booked against even if the chart has
 *   been renamed since.
 * - Raised by which document. The kind of posting it was — the same type and
 *   sub-type the general ledger records — its number, and what that document
 *   settled or corrected. The number is the drill-back: every document kind
 *   that raises a sub ledger row has a unique number, so the row reaches its
 *   document through the column it already carries rather than a source pair
 *   kept in step.
 *
 * It is deliberately a separate class from {@see ApLedgerData} rather than one
 * type used for both. The two tables are separate so the database can refuse a
 * receivable filed against the payables ledger; sharing the object would share
 * the type, and a type that fits both can be used for both by accident. Naming
 * the side is what keeps a posting honest about where it went.
 *
 * @param  numeric-string  $debitAmount
 * @param  numeric-string  $creditAmount
 */
class ArLedgerData extends Data
{
    /**
     * @param  string  $id  The row's own identifier, fresh rather than borrowed.
     * @param  string  $chartId  The chart account the figure was booked against.
     * @param  string  $companyId  The customer the figure belongs to.
     * @param  string  $currencyId  The currency the figure is in.
     * @param  string  $type  The kind of posting the row was raised by — the
     *                        same values the general ledger records.
     * @param  string  $subtype  The finer kind, matching the general ledger.
     * @param  string  $documentNumber  The document's number, the row's
     *                                  drill-back to what raised it.
     * @param  string|null  $referenceNumber  What the document settles or corrects.
     * @param  string|null  $chartCode  The account's code, snapshotted at posting time.
     * @param  string|null  $chartName  The account's name, snapshotted at posting time.
     * @param  string|null  $chartDescription  The account's description, snapshotted
     *                                         at posting time.
     * @param  string|null  $companyName  The customer's name, snapshotted at posting
     *                                    time.
     * @param  string|null  $description  What the movement was for, in the words
     *                                    of the line that raised it.
     * @param  string  $debitAmount  The amount the customer owes more.
     * @param  string  $creditAmount  The amount the customer owes less.
     * @param  CarbonInterface|null  $createdAt  When the row was written.
     * @param  CarbonInterface|null  $updatedAt  When the row was last changed.
     * @param  CarbonInterface|null  $deletedAt  When the row was removed, if it was.
     */
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('chart_id')]
        public string $chartId,

        #[MapName('company_id')]
        public string $companyId,

        #[MapName('currency_id')]
        public string $currencyId,

        // The kind of posting the row was raised by, held as the enum's value
        // the way the general ledger rows hold theirs: a receipt and an
        // invoice both move a customer's balance, and the row says which it
        // was so a ledger written by both can still tell them apart.
        #[MapName('type')]
        public string $type,

        #[MapName('subtype')]
        public string $subtype,

        #[MapName('document_number')]
        public string $documentNumber,

        #[MapName('date')]
        public ?string $date,

        // What this document settles or corrects. The second half of the
        // drill-down, and the one most easily lost, because nothing on the row
        // needs it to balance and every other column does.
        #[MapName('reference_number')]
        public ?string $referenceNumber,

        #[MapName('chart_code')]
        public ?string $chartCode,

        #[MapName('chart_name')]
        public ?string $chartName,

        #[MapName('chart_description')]
        public ?string $chartDescription,

        // The customer the figure belongs to, as its name read at posting
        // time. The reference column is what a join resolves; this is what a
        // report prints, and the row keeps the name it was written under
        // after the company has been renamed.
        #[MapName('company_name')]
        public ?string $companyName,

        #[MapName('description')]
        public ?string $description,

        #[MapName('debit_amount')]
        public string $debitAmount,

        #[MapName('credit_amount')]
        public string $creditAmount,

        #[MapName('created_at')]
        public ?CarbonInterface $createdAt,

        #[MapName('updated_at')]
        public ?CarbonInterface $updatedAt,

        #[MapName('deleted_at')]
        public ?CarbonInterface $deletedAt,
    ) {}

    /**
     * Build the receivable row for one movement against a receivable account.
     *
     * The row is built from a single-sided leg, so which side carries the amount
     * is the leg's own fact rather than a guess from the amounts, and both
     * amount columns are written from the leg exactly as it stands — one of
     * them is always zero, and bcmath has already made both exact.
     *
     * @param  PostingLeg  $leg  The movement, already single-sided and exact.
     * @param  array{code: string|null, name: string|null, description: string|null}  $chart  The
     *                                                                                        chart account's code, name and description, snapshotted at posting time.
     * @param  string  $companyId  The customer the figure belongs to.
     * @param  string  $currencyId  The currency the figure is in.
     * @param  LedgerType  $type  The kind of posting the row was raised by.
     * @param  LedgerSubType  $subtype  The finer kind, recorded as its value.
     * @param  string  $documentNumber  The document's number.
     * @param  string|null  $date  The document's own date, so the statement reads
     *                             in the order the documents happened.
     * @param  string|null  $referenceNumber  What the document settles or corrects.
     * @param  string|null  $companyName  The customer's name, snapshotted at posting
     *                                    time.
     */
    public static function fromLeg(
        PostingLeg $leg,
        array $chart,
        string $companyId,
        string $currencyId,
        LedgerType $type,
        LedgerSubType $subtype,
        string $documentNumber,
        ?string $date,
        ?string $referenceNumber,
        ?string $companyName,
    ): self {
        return new self(
            id: (string) Str::uuid7(),
            chartId: $leg->chartId,
            companyId: $companyId,
            currencyId: $currencyId,
            type: $type->value,
            subtype: $subtype->value,
            documentNumber: $documentNumber,
            date: $date,
            referenceNumber: $referenceNumber,
            chartCode: $chart['code'],
            chartName: $chart['name'],
            chartDescription: $chart['description'],
            companyName: $companyName,
            description: $leg->description,
            debitAmount: $leg->debit,
            creditAmount: $leg->credit,
            createdAt: now(),
            updatedAt: now(),
            deletedAt: null,
        );
    }

    /**
     * Build the receivable row from a sales invoice line.
     *
     * A sales invoice line carries both sides of its accounting entry. The
     * receivable side is the debit to the customer's account. This method
     * extracts that side and builds an ArLedgerData for it.
     *
     * @param  SalesInvoice  $invoice  The invoice being posted.
     * @param  SalesInvoiceLine  $line  The invoice's line.
     * @param  SalesInvoiceChart  $chartConfig  The chart configuration for default accounts.
     * @param  Chart  $chart  The chart account's details.
     */
    public static function fromSalesInvoiceLine(
        SalesInvoice $invoice,
        SalesInvoiceLine $line,
        SalesInvoiceChart $chartConfig,
        Chart $chart,
    ): ?self {
        // The receivable side is the debit to the customer's account.
        // If there's no debit amount on the line, there's no receivable row.
        if (bccomp($line->total_amount ?? '0.00', '0.00', 2) === 0) {
            return null;
        }

        // Get the currency from settings
        $currencyValue = DB::table('settings')->value('currency_id');
        $currencyId = is_string($currencyValue) ? $currencyValue : '';

        return new self(
            id: (string) Str::uuid7(),
            chartId: $chartConfig->receivable_id ?? '',
            companyId: $invoice->company_id ?? '',
            currencyId: $currencyId,
            type: LedgerType::INVOICE->value,
            subtype: LedgerSubType::SALES_INVOICE->value,
            documentNumber: $invoice->document_number,
            date: $invoice->date->format('Y-m-d'),
            referenceNumber: $invoice->reference_number,
            chartCode: $chart->code,
            chartName: $chart->name,
            chartDescription: $chart->description,
            companyName: $invoice->company_name,
            description: $line->description,
            debitAmount: $line->total_amount ?? '0.00',
            creditAmount: '0.00',
            createdAt: now(),
            updatedAt: now(),
            deletedAt: null,
        );
    }

    /**
     * The row as the ar_ledgers table wants it.
     *
     * The timestamps become the strings the columns hold; nothing else is
     * reshaped.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
