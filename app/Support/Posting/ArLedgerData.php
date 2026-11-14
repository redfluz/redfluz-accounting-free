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
use App\Models\ChartOfAccount\Chart;
use App\Models\Sales\ManualSalesAdjustment;
use App\Models\Sales\ManualSalesAdjustmentChart;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use Carbon\CarbonInterface;
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
        public ?CarbonInterface $createdAt = null,

        #[MapName('updated_at')]
        public ?CarbonInterface $updatedAt = null,

        #[MapName('deleted_at')]
        public ?CarbonInterface $deletedAt = null,
    ) {}

    /**
     * Build an AR row for one sales invoice entry.
     *
     * The receivable is written per entry - gross, tax, discount and the net debt -
     * so the customer's ledger shows the same rows the general ledger shows for the
     * same invoice. Each row carries the amount its entry was built from and the
     * account that entry was booked against, so a revenue row reads as revenue and
     * a receivable row reads as the receivable, rather than every row wearing the
     * customer's account.
     *
     * @param  SalesInvoice  $invoice  The invoice being posted.
     * @param  SalesInvoiceLine  $line  The line whose description the row carries.
     * @param  Chart  $chart  The account this entry was booked against.
     * @param  string  $currencyId  The currency the amount is expressed in.
     * @param  numeric-string  $debitAmount  The amount the customer owes on this entry.
     * @param  numeric-string  $creditAmount  The amount this entry takes back from the customer.
     */
    public static function fromSalesInvoiceLine(
        SalesInvoice $invoice,
        SalesInvoiceLine $line,
        Chart $chart,
        string $currencyId,
        string $debitAmount,
        string $creditAmount,
    ): self {
        return new self(
            id: (string) Str::uuid7(),
            chartId: $chart->id,
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
            debitAmount: $debitAmount,
            creditAmount: $creditAmount,
        );
    }

    /**
     * Build an AR row directly from a manual sales adjustment chart line.
     *
     * The line's own debit and credit figures are the movement, and the row is
     * booked to the account the line names, so a manual adjustment writes the
     * receivable ledger with the same rows it writes the general ledger with.
     * The reference and the wording are read the same way the general ledger row
     * reads them, so one entry prints the same text in both tables.
     *
     * @param  ManualSalesAdjustment  $adjustment  The adjustment being posted.
     * @param  ManualSalesAdjustmentChart  $line  The chart line the row reads its figures from.
     * @param  Chart  $chart  The account the line was booked against.
     * @param  string  $currencyId  The currency the amount is expressed in.
     * @param  numeric-string  $debitAmount  What the line debits.
     * @param  numeric-string  $creditAmount  What the line credits.
     * @return self The row.
     */
    public static function fromManualSalesAdjustmentChartLine(
        ManualSalesAdjustment $adjustment,
        ManualSalesAdjustmentChart $line,
        Chart $chart,
        string $currencyId,
        string $debitAmount,
        string $creditAmount,
    ): self {
        return new self(
            id: (string) Str::uuid7(),
            chartId: $chart->id,
            companyId: $adjustment->company_id ?? '',
            currencyId: $currencyId,
            type: LedgerType::INVOICE->value,
            subtype: $line->subtype->value,
            documentNumber: $adjustment->document_number,
            date: $adjustment->date->format('Y-m-d'),
            referenceNumber: $line->reference_number ?? $adjustment->reference_number,
            chartCode: $chart->code,
            chartName: $chart->name,
            chartDescription: $chart->description,
            companyName: $adjustment->company_name,
            description: $line->line_description ?? $line->description ?? $adjustment->notes,
            debitAmount: $debitAmount,
            creditAmount: $creditAmount,
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
