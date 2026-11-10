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

use App\Enums\Finance\GeneralLedgerEntryType;
use App\Enums\Finance\LedgerSourceType;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Finance\ManualPurchaseAdjustment;
use App\Models\Finance\ManualPurchaseAdjustmentChart;
use App\Models\Finance\ManualSalesAdjustment;
use App\Models\Finance\ManualSalesAdjustmentChart;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Models\Sales\SalesInvoice;
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
        LedgerSubType::CUSTOMER_REFUND->value => LedgerSourceType::CUSTOMER_REFUND,
        LedgerSubType::SUPPLIER_REFUND->value => LedgerSourceType::SUPPLIER_REFUND,
        LedgerSubType::PAYMENT_VOUCHER->value => LedgerSourceType::PAYMENT_VOUCHER,
        LedgerSubType::INCREASE_ADJUSTMENT->value => LedgerSourceType::SALES_ADJUSTMENT,
        LedgerSubType::DECREASE_ADJUSTMENT->value => LedgerSourceType::SALES_ADJUSTMENT,
        LedgerSubType::MANUAL_SALES_ADJUSTMENT->value => LedgerSourceType::SALES_ADJUSTMENT,
        LedgerSubType::MANUAL_PURCHASE_ADJUSTMENT->value => LedgerSourceType::PURCHASE_ADJUSTMENT,
        LedgerSubType::BANK_JOURNAL->value => LedgerSourceType::BANK_JOURNAL,
    ];

    /**
     * @param  numeric-string  $debitAmount
     * @param  numeric-string  $creditAmount
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
        public string $type,

        #[MapName('subtype')]
        public string $subtype,

        #[MapName('document_number')]
        public ?string $documentNumber,

        #[MapName('reference_number')]
        public ?string $referenceNumber,

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
     * @param  ChartSetting  $chartConfig  The chart configuration for default accounts.
     * @param  Chart|null  $chart  The chart account's details.
     * @param  numeric-string  $debitAmount  The debit amount for this entry.
     * @param  numeric-string  $creditAmount  The credit amount for this entry.
     */
    public static function fromSalesInvoiceLine(
        SalesInvoice $invoice,
        SalesInvoiceLine $line,
        ChartSetting $chartConfig,
        GeneralLedgerEntryType $entryType,
        ?Chart $chart,
        string $debitAmount,
        string $creditAmount,
    ): self {
        $chartId = match ($entryType) {
            GeneralLedgerEntryType::RECEIVABLE => $chartConfig->client_chart_uuid,
            GeneralLedgerEntryType::REVENUE => $chartConfig->income_chart_uuid,
            GeneralLedgerEntryType::TAX => $chartConfig->output_tax_chart_uuid,
            GeneralLedgerEntryType::DISCOUNT => $chartConfig->discount_chart_uuid,
            GeneralLedgerEntryType::COGS => $chartConfig->cogs_chart_uuid,
            GeneralLedgerEntryType::INVENTORY => $chartConfig->inventory_chart_uuid,
            GeneralLedgerEntryType::PAYABLE => $chartConfig->vendor_chart_uuid,
        };

        return new self(
            id: (string) Str::uuid7(),
            chartId: $chartId,
            companyId: $invoice->company_id,
            currencyId: null,
            type: LedgerType::INVOICE->value,
            subtype: LedgerSubType::SALES_INVOICE->value,
            documentNumber: $invoice->document_number,
            referenceNumber: $invoice->reference_number,
            date: $invoice->date,
            chartCode: $chart?->code,
            chartName: $chart?->name,
            companyName: $invoice->company_name,
            description: $invoice->notes,
            lineDescription: $line->description,
            debitAmount: $debitAmount,
            creditAmount: $creditAmount,
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
     * Build a general ledger row for one side of a purchase invoice entry.
     *
     * @param  ChartSetting  $chartConfig  The chart configuration for default accounts.
     * @param  Chart|null  $chart  The chart account's details.
     * @param  numeric-string  $debitAmount  The debit amount for this entry.
     * @param  numeric-string  $creditAmount  The credit amount for this entry.
     */
    public static function fromPurchaseInvoiceLine(
        PurchaseInvoice $invoice,
        PurchaseInvoiceLine $line,
        ChartSetting $chartConfig,
        GeneralLedgerEntryType $entryType,
        ?Chart $chart,
        string $debitAmount,
        string $creditAmount,
    ): self {
        $chartId = match ($entryType) {
            GeneralLedgerEntryType::PAYABLE => $chartConfig->vendor_chart_uuid,
            GeneralLedgerEntryType::INVENTORY => $chartConfig->inventory_chart_uuid,
            GeneralLedgerEntryType::TAX => $chartConfig->input_tax_chart_uuid,
            GeneralLedgerEntryType::DISCOUNT => null,
            GeneralLedgerEntryType::RECEIVABLE => $chartConfig->client_chart_uuid,
            GeneralLedgerEntryType::REVENUE => $chartConfig->income_chart_uuid,
            GeneralLedgerEntryType::COGS => $chartConfig->cogs_chart_uuid,
        };

        return new self(
            id: (string) Str::uuid7(),
            chartId: $chartId,
            companyId: $invoice->company_id,
            currencyId: null,
            type: LedgerType::INVOICE->value,
            subtype: LedgerSubType::PURCHASE_INVOICE->value,
            documentNumber: $invoice->document_number,
            referenceNumber: $invoice->reference_number,
            date: $invoice->date,
            chartCode: $chart?->code,
            chartName: $chart?->name,
            companyName: $invoice->company_name,
            description: $invoice->notes,
            lineDescription: $line->description,
            debitAmount: $debitAmount,
            creditAmount: $creditAmount,
            createdAt: now(),
            updatedAt: now(),
            deletedAt: null,
        );
    }

    /**
     * Build the general ledger row that mirrors one payable sub-ledger row.
     *
     * The sub-ledger row is the source. What is owed to the supplier is written to
     * the payable ledger and, unchanged, to the same account in the general
     * ledger, so the two agree line for line instead of relying on two totals that
     * happen to come out the same.
     *
     * @param  ApLedgerData  $row  The payable row being mirrored.
     * @param  PurchaseInvoice  $invoice  The invoice the row was raised by.
     */
    public static function fromApLedgerRow(ApLedgerData $row, PurchaseInvoice $invoice): self
    {
        return new self(
            id: (string) Str::uuid7(),
            chartId: $row->chartId,
            companyId: $row->companyId,
            currencyId: $row->currencyId,
            type: $row->type,
            subtype: $row->subtype,
            documentNumber: $row->documentNumber,
            referenceNumber: $row->referenceNumber,
            date: $invoice->date,
            chartCode: $row->chartCode,
            chartName: $row->chartName,
            companyName: $row->companyName,
            description: $invoice->notes,
            lineDescription: $row->description,
            debitAmount: $row->debitAmount,
            creditAmount: $row->creditAmount,
            createdAt: now(),
            updatedAt: now(),
            deletedAt: null,
        );
    }

    /**
     * Build the general ledger row from a manual sales adjustment chart line.
     *
     * The line is one single-sided entry as the user keyed it: one account,
     * one amount, debit or credit, never both.
     *
     * @param  Collection<string, string>  $accountDescriptions  Chart id to name.
     */
    public static function fromManualSalesAdjustmentLine(
        ManualSalesAdjustment $adjustment,
        ManualSalesAdjustmentChart $line,
        Collection $accountDescriptions,
    ): self {
        return new self(
            id: (string) Str::uuid7(),
            chartId: $line->chart_id,
            companyId: $line->company_id ?? $adjustment->company_id,
            currencyId: null,
            type: $line->type->value,
            subtype: $line->subtype->value,
            documentNumber: $line->document_number,
            referenceNumber: $line->reference_number,
            date: $line->date,
            chartCode: $line->chart_code,
            chartName: $line->chart_name ?? self::accountName($accountDescriptions, $line->chart_id),
            companyName: $line->company_name ?? $adjustment->company_name,
            description: $line->description ?? $adjustment->notes,
            lineDescription: $line->line_description,
            debitAmount: $line->debit_amount ?? '0.00',
            creditAmount: $line->credit_amount ?? '0.00',
            createdAt: now(),
            updatedAt: now(),
            deletedAt: null,
        );
    }

    /**
     * Build the general ledger row from a manual purchase adjustment chart line.
     *
     * The line is one single-sided entry as the user keyed it: one account,
     * one amount, debit or credit, never both.
     *
     * @param  Collection<string, string>  $accountDescriptions  Chart id to name.
     */
    public static function fromManualPurchaseAdjustmentLine(
        ManualPurchaseAdjustment $adjustment,
        ManualPurchaseAdjustmentChart $line,
        Collection $accountDescriptions,
    ): self {
        return new self(
            id: (string) Str::uuid7(),
            chartId: $line->chart_id,
            companyId: $line->company_id ?? $adjustment->company_id,
            currencyId: null,
            type: $line->type->value,
            subtype: $line->subtype->value,
            documentNumber: $line->document_number,
            referenceNumber: $line->reference_number,
            date: $line->date,
            chartCode: $line->chart_code,
            chartName: $line->chart_name ?? self::accountName($accountDescriptions, $line->chart_id),
            companyName: $line->company_name ?? $adjustment->company_name,
            description: $line->description ?? $adjustment->notes,
            lineDescription: $line->line_description,
            debitAmount: $line->debit_amount ?? '0.00',
            creditAmount: $line->credit_amount ?? '0.00',
            createdAt: now(),
            updatedAt: now(),
            deletedAt: null,
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
            'type' => $this->type,
            'subtype' => $this->subtype,
            'date' => $this->date->format('Y-m-d'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
