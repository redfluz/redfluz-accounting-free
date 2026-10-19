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
use App\Models\ChartOfAccount\Chart;
use App\Models\Purchase\PurchaseAdjustment;
use App\Models\Purchase\PurchaseAdjustmentLine;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceChart;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Models\Sales\SalesAdjustment;
use App\Models\Sales\SalesAdjustmentChart;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceChart;
use App\Models\Sales\SalesInvoiceLine;
use App\Support\PostingException;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One row of the general ledger.
 *
 * The general ledger is narrow: it holds only the financial posting columns,
 * matching the design of ar_ledgers and ap_ledgers. Document-level detail
 * (product, quantity, price, discount, tax, warranty) lives in the document
 * tables (sales_invoices, purchase_invoices, etc.), not here.
 *
 * This DTO exists so that posting writes named, typed columns rather than
 * copying whatever happens to exist on the source row. Adding a column to
 * the ledgers table is a deliberate act here first.
 */
class GeneralLedgerData extends Data
{
    /**
     * The client ledger sub-type to the source type that raised it.
     */
    private const array SOURCE_TYPES = [
        LedgerSubType::SALES_INVOICE->value => LedgerSourceType::SALES_INVOICE,
        LedgerSubType::MANUAL_SALES_INVOICE->value => LedgerSourceType::SALES_INVOICE,
        LedgerSubType::PURCHASE_INVOICE->value => LedgerSourceType::PURCHASE_INVOICE,
        LedgerSubType::RECEIPT->value => LedgerSourceType::RECEIPT,
        LedgerSubType::RECEIPT_REFUND->value => LedgerSourceType::RECEIPT_REFUND,
        LedgerSubType::SUPPLIER_REFUND->value => LedgerSourceType::SUPPLIER_REFUND,
        LedgerSubType::PAYMENT_VOUCHER->value => LedgerSourceType::PAYMENT_VOUCHER,
        LedgerSubType::INCREASE_ADJUSTMENT->value => LedgerSourceType::SALES_ADJUSTMENT,
        LedgerSubType::DECREASE_ADJUSTMENT->value => LedgerSourceType::SALES_ADJUSTMENT,
        LedgerSubType::PURCHASE_DEBIT_NOTE->value => LedgerSourceType::PURCHASE_ADJUSTMENT,
        LedgerSubType::PURCHASE_CREDIT_NOTE->value => LedgerSourceType::PURCHASE_ADJUSTMENT,
        LedgerSubType::BANK_JOURNAL->value => LedgerSourceType::BANK_JOURNAL,
        LedgerSubType::TRANSFER->value => LedgerSourceType::TRANSFER,
    ];

    /**
     * @param  numeric-string  $debitAmount
     * @param  numeric-string  $creditAmount
     * @param  numeric-string  $totalAmount
     */
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('source_id')]
        public ?string $sourceId,

        #[MapName('source_type')]
        public ?string $sourceType,

        #[MapName('type')]
        public string $type,

        #[MapName('subtype')]
        public string $subtype,

        #[MapName('company_id')]
        public ?string $companyId,

        #[MapName('company_name')]
        public ?string $companyName,

        #[MapName('contact_id')]
        public ?string $contactId,

        #[MapName('document_number')]
        public ?string $documentNumber,

        #[MapName('reference_number')]
        public ?string $referenceNumber,

        #[MapName('date')]
        public CarbonInterface $date,

        #[MapName('document_date')]
        public ?CarbonInterface $documentDate,

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

        #[MapName('status')]
        public string $status,

        #[MapName('description')]
        public ?string $description,

        #[MapName('created_at')]
        public ?CarbonInterface $createdAt,

        #[MapName('updated_at')]
        public ?CarbonInterface $updatedAt,

        #[MapName('deleted_at')]
        public ?CarbonInterface $deletedAt,
    ) {}

    /**
     * The source type that a document sub-type raises its ledger rows under.
     */
    public static function sourceTypeFor(LedgerSubType $subtype): LedgerSourceType
    {
        return self::SOURCE_TYPES[$subtype->value]
            ?? throw PostingException::unknownLedgerSourceType($subtype->value);
    }

    /**
     * Build a general ledger row for one side of a sales invoice entry.
     *
     * @param  SalesInvoiceChart  $chartConfig  The chart configuration for default accounts.
     * @param  string  $entryType  Which entry this is: 'receivable', 'revenue', 'tax', 'discount', 'cogs', 'inventory'.
     * @param  Chart|null  $chart  The chart account's details.
     * @param  numeric-string  $debitAmount  The debit amount for this entry.
     * @param  numeric-string  $creditAmount  The credit amount for this entry.
     */
    public static function fromSalesInvoiceLine(
        SalesInvoice $invoice,
        SalesInvoiceLine $line,
        SalesInvoiceChart $chartConfig,
        string $entryType,
        ?Chart $chart,
        string $debitAmount,
        string $creditAmount,
    ): self {
        // Determine which chart ID to use based on entry type
        $chartId = match ($entryType) {
            'receivable' => $chartConfig->receivable_id,
            'revenue' => $chartConfig->revenue_id,
            'tax' => $chartConfig->tax_id,
            'discount' => $chartConfig->discount_id,
            'cogs' => $chartConfig->cogs_id,
            'inventory' => $chartConfig->inventory_id,
            default => null,
        };

        return new self(
            id: (string) Str::uuid7(),
            sourceId: $line->id,
            sourceType: self::sourceTypeFor(LedgerSubType::SALES_INVOICE)->value,
            type: LedgerType::INVOICE->value,
            subtype: LedgerSubType::SALES_INVOICE->value,
            companyId: $invoice->company_id,
            companyName: $invoice->company_name,
            contactId: $invoice->contact_id,
            documentNumber: $invoice->document_number,
            referenceNumber: $invoice->reference_number,
            date: $invoice->date,
            documentDate: $invoice->date,
            debitId: bccomp($debitAmount, '0.00', 2) !== 0 ? $chartId : null,
            creditId: bccomp($creditAmount, '0.00', 2) !== 0 ? $chartId : null,
            debitAcc: bccomp($debitAmount, '0.00', 2) !== 0 ? $chart?->code : null,
            creditAcc: bccomp($creditAmount, '0.00', 2) !== 0 ? $chart?->code : null,
            debitAccDesc: bccomp($debitAmount, '0.00', 2) !== 0 ? $chart?->name : null,
            creditAccDesc: bccomp($creditAmount, '0.00', 2) !== 0 ? $chart?->name : null,
            debitAmount: $debitAmount,
            creditAmount: $creditAmount,
            totalAmount: bcadd($debitAmount, $creditAmount, 2),
            status: LedgerStatus::POSTED->value,
            description: $line->description,
            createdAt: now(),
            updatedAt: now(),
            deletedAt: null,
        );
    }

    /**
     * Build the general ledger row from a sales adjustment chart entry.
     *
     * The entry is one side of the correction as it posts to the chart of
     * accounts: a chart row carries either a debit or a credit, never both.
     * The adjustment's product lines carry what is being corrected, not which
     * accounts move, so they are not accepted here.
     *
     * @param  Collection<string, string>  $accountDescriptions  Chart id to name.
     */
    public static function fromSalesAdjustmentLine(
        SalesAdjustment $adjustment,
        SalesAdjustmentChart $line,
        Collection $accountDescriptions,
    ): self {
        return new self(
            id: (string) Str::uuid7(),
            sourceId: $line->id,
            sourceType: self::sourceTypeFor($adjustment->subtype)->value,
            type: LedgerType::INVOICE->value,
            subtype: $adjustment->subtype->value,
            companyId: $adjustment->company_id,
            companyName: $adjustment->company_name,
            contactId: $adjustment->contact_id,
            documentNumber: $adjustment->document_number,
            referenceNumber: $adjustment->reference_number,
            date: $adjustment->date,
            documentDate: $adjustment->date,
            debitId: $line->debit_id,
            creditId: $line->credit_id,
            debitAcc: $line->debit_acc,
            creditAcc: $line->credit_acc,
            debitAccDesc: $line->debit_acc_desc ?? self::accountName($accountDescriptions, $line->debit_id),
            creditAccDesc: $line->credit_acc_desc ?? self::accountName($accountDescriptions, $line->credit_id),
            debitAmount: $line->debit_amount ?? '0.00',
            creditAmount: $line->credit_amount ?? '0.00',
            totalAmount: $line->total_amount ?? '0.00',
            status: LedgerStatus::POSTED->value,
            description: $line->description,
            createdAt: now(),
            updatedAt: now(),
            deletedAt: null,
        );
    }

    /**
     * The chart name for an account id, as recorded at posting time.
     *
     * The descriptions map is built from chart rows, so a lookup can only miss
     * when the account has since been removed; a missing name is null rather
     * than a guess.
     *
     * @param  Collection<string, string>  $accountDescriptions  Chart id to name.
     */
    private static function accountName(Collection $accountDescriptions, ?string $accountId): ?string
    {
        if ($accountId === null) {
            return null;
        }

        $name = $accountDescriptions->get($accountId);

        return is_string($name) ? $name : null;
    }

    /**
     * A numeric amount read off a loosely-typed row attribute.
     *
     * @return numeric-string
     */
    private static function numericAmount(mixed $value): string
    {
        return is_string($value) && is_numeric($value) ? $value : '0.00';
    }

    /**
     * An account reference read off a loosely-typed row attribute.
     */
    private static function accountReference(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    /**
     * Build a general ledger row for one side of a purchase invoice entry.
     *
     * @param  PurchaseInvoiceChart  $chartConfig  The chart configuration for default accounts.
     * @param  string  $entryType  Which entry this is: 'payable', 'inventory', 'tax', 'discount'.
     * @param  Chart|null  $chart  The chart account's details.
     * @param  numeric-string  $debitAmount  The debit amount for this entry.
     * @param  numeric-string  $creditAmount  The credit amount for this entry.
     */
    public static function fromPurchaseInvoiceLine(
        PurchaseInvoice $invoice,
        PurchaseInvoiceLine $line,
        PurchaseInvoiceChart $chartConfig,
        string $entryType,
        ?Chart $chart,
        string $debitAmount,
        string $creditAmount,
    ): self {
        // Determine which chart ID to use based on entry type
        $chartId = match ($entryType) {
            'payable' => $chartConfig->payable_id,
            'inventory' => $chartConfig->inventory_id,
            'tax' => $chartConfig->tax_id,
            'discount' => $chartConfig->discount_id,
            default => null,
        };

        return new self(
            id: (string) Str::uuid7(),
            sourceId: $line->id,
            sourceType: self::sourceTypeFor(LedgerSubType::PURCHASE_INVOICE)->value,
            type: LedgerType::INVOICE->value,
            subtype: LedgerSubType::PURCHASE_INVOICE->value,
            companyId: $invoice->company_id,
            companyName: $invoice->company_name,
            contactId: $invoice->contact_id,
            documentNumber: $invoice->document_number,
            referenceNumber: $invoice->reference_number,
            date: $invoice->date,
            documentDate: $invoice->date,
            debitId: bccomp($debitAmount, '0.00', 2) !== 0 ? $chartId : null,
            creditId: bccomp($creditAmount, '0.00', 2) !== 0 ? $chartId : null,
            debitAcc: bccomp($debitAmount, '0.00', 2) !== 0 ? $chart?->code : null,
            creditAcc: bccomp($creditAmount, '0.00', 2) !== 0 ? $chart?->code : null,
            debitAccDesc: bccomp($debitAmount, '0.00', 2) !== 0 ? $chart?->name : null,
            creditAccDesc: bccomp($creditAmount, '0.00', 2) !== 0 ? $chart?->name : null,
            debitAmount: $debitAmount,
            creditAmount: $creditAmount,
            totalAmount: bcadd($debitAmount, $creditAmount, 2),
            status: LedgerStatus::POSTED->value,
            description: $line->description,
            createdAt: now(),
            updatedAt: now(),
            deletedAt: null,
        );
    }

    /**
     * Build the general ledger rows from a purchase adjustment line.
     *
     * @param  Collection<string, string>  $accountDescriptions  Chart id to name.
     * @return list<self> The debit and/or credit rows.
     */
    public static function fromPurchaseAdjustmentLine(
        PurchaseAdjustment $adjustment,
        PurchaseAdjustmentLine $line,
        Collection $accountDescriptions,
    ): array {
        $rows = [];

        // A purchase adjustment line records product detail; its debit and
        // credit accounts are not declared columns, so they are read back
        // through getAttribute with a guard instead of a declared property.
        $debitId = self::accountReference($line->getAttribute('debit_id'));

        if ($debitId !== null) {
            $rows[] = new self(
                id: (string) Str::uuid7(),
                sourceId: $line->id,
                sourceType: self::sourceTypeFor($adjustment->subtype)->value,
                type: LedgerType::INVOICE->value,
                subtype: $adjustment->subtype->value,
                companyId: $adjustment->company_id,
                companyName: $adjustment->company_name,
                contactId: $adjustment->contact_id,
                documentNumber: $adjustment->document_number,
                referenceNumber: $adjustment->reference_number,
                date: $adjustment->date,
                documentDate: $adjustment->date,
                debitId: $debitId,
                creditId: null,
                debitAcc: self::accountReference($line->getAttribute('debit_acc')),
                creditAcc: null,
                debitAccDesc: self::accountReference($line->getAttribute('debit_acc_desc'))
                    ?? self::accountName($accountDescriptions, $debitId),
                creditAccDesc: null,
                debitAmount: self::numericAmount($line->getAttribute('debit_amount')),
                creditAmount: '0.00',
                totalAmount: $line->total_amount ?? '0.00',
                status: LedgerStatus::POSTED->value,
                description: $line->description,
                createdAt: now(),
                updatedAt: now(),
                deletedAt: null,
            );
        }

        $creditId = self::accountReference($line->getAttribute('credit_id'));

        if ($creditId !== null) {
            $rows[] = new self(
                id: (string) Str::uuid7(),
                sourceId: $line->id,
                sourceType: self::sourceTypeFor($adjustment->subtype)->value,
                type: LedgerType::INVOICE->value,
                subtype: $adjustment->subtype->value,
                companyId: $adjustment->company_id,
                companyName: $adjustment->company_name,
                contactId: $adjustment->contact_id,
                documentNumber: $adjustment->document_number,
                referenceNumber: $adjustment->reference_number,
                date: $adjustment->date,
                documentDate: $adjustment->date,
                debitId: null,
                creditId: $creditId,
                debitAcc: null,
                creditAcc: self::accountReference($line->getAttribute('credit_acc')),
                debitAccDesc: null,
                creditAccDesc: self::accountReference($line->getAttribute('credit_acc_desc'))
                    ?? self::accountName($accountDescriptions, $creditId),
                debitAmount: '0.00',
                creditAmount: self::numericAmount($line->getAttribute('credit_amount')),
                totalAmount: $line->total_amount ?? '0.00',
                status: LedgerStatus::POSTED->value,
                description: $line->description,
                createdAt: now(),
                updatedAt: now(),
                deletedAt: null,
            );
        }

        return $rows;
    }

    /**
     * The single-sided posting leg this row contributes to a posting.
     */
    public function toLeg(): PostingLeg
    {
        $hasDebit = bccomp($this->debitAmount, '0.00', 2) !== 0;
        $hasCredit = bccomp($this->creditAmount, '0.00', 2) !== 0;

        if ($hasDebit && $hasCredit) {
            throw PostingException::oneSidedEntryRequired($this->id);
        }

        $debitSide = $hasDebit || ! $hasCredit;
        $chartId = $debitSide ? $this->debitId : $this->creditId;

        if ($chartId === null) {
            throw PostingException::oneSidedEntryRequired($this->id);
        }

        return PostingLeg::movement(
            $this->id,
            $chartId,
            $debitSide,
            $this->debitAmount,
            $this->creditAmount,
            $this->description,
            $this->referenceNumber,
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
            'date' => $this->date->format('Y-m-d'),
            'document_date' => $this->documentDate?->format('Y-m-d'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
