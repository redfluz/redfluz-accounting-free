<?php

declare(strict_types=1);

namespace App\Support\Posting;

use App\Enums\Finance\GeneralLedgerEntryType;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Support\Posting\Concerns\WritesLedgerTables;
use Illuminate\Support\Collection;

/**
 * Writes rows to the general ledger.
 *
 * One service, one table. The controller passes the invoice and its lines.
 * This service queries charts, builds GeneralLedgerData DTOs, and writes them
 * to ledgers. Nothing else.
 */
class Ledger
{
    use WritesLedgerTables;

    /**
     * Post a sales invoice's general ledger rows to ledgers.
     *
     * Creates the revenue-side GL entries for a sales invoice:
     * DR AR, CR Revenue, CR Tax Payable, DR Discounts.
     *
     * Cost is deliberately NOT posted here: the stock movement itself
     * (deduct-inventory button, delivery order) writes the single
     * DR COGS / CR Inventory journal, so posting it here too would
     * double-count inventory relief.
     *
     * @param  SalesInvoice  $invoice  The invoice being posted.
     * @param  Collection<int, SalesInvoiceLine>  $lines  The invoice's lines.
     * @return int How many rows were written.
     */
    public function postSalesInvoice(SalesInvoice $invoice, Collection $lines): int
    {
        if ($lines->isEmpty()) {
            return 0;
        }

        // Query the chart settings for default accounts
        $chartConfig = ChartSetting::query()->first();

        if ($chartConfig === null) {
            return 0;
        }

        // Get all chart account details needed for the posting
        $chartIds = array_filter([
            $chartConfig->client_chart_uuid,
            $chartConfig->income_chart_uuid,
            $chartConfig->output_tax_chart_uuid,
        ]);

        /** @var Collection<string, Chart> $charts */
        $charts = Chart::query()
            ->whereIn('id', $chartIds)
            ->get()
            ->keyBy(fn (Chart $chart): string => (string) $chart->id);

        // Calculate totals from all lines
        $grossTotal = '0.00';
        $taxTotal = '0.00';
        $discountTotal = '0.00';

        foreach ($lines as $line) {
            // Gross total is the subtotal before tax and discount
            $grossTotal = bcadd($grossTotal, $line->subtotal_amount ?? '0.00', 2);
            $taxTotal = bcadd($taxTotal, $line->tax_amount ?? '0.00', 2);
            $discountTotal = bcadd($discountTotal, $line->discount_amount ?? '0.00', 2);
        }

        // Invoice total = gross + tax - discount
        $invoiceTotal = bcsub(bcadd($grossTotal, $taxTotal, 2), $discountTotal, 2);

        /** @var list<GeneralLedgerData> $rows */
        $rows = [];

        // Revenue side entries
        // DR Accounts Receivable (total invoice amount)
        if ($chartConfig->client_chart_uuid !== null) {
            $receivableChart = $charts->get($chartConfig->client_chart_uuid);
            $rows[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $lines->first(), // Use first line for header-level entries
                $chartConfig,
                GeneralLedgerEntryType::RECEIVABLE,
                $receivableChart,
                $invoiceTotal,
                '0.00'
            );
        }

        // CR Sales Revenue (gross sales)
        if ($chartConfig->income_chart_uuid !== null && bccomp($grossTotal, '0.00', 2) !== 0) {
            $revenueChart = $charts->get($chartConfig->income_chart_uuid);
            $rows[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                GeneralLedgerEntryType::REVENUE,
                $revenueChart,
                '0.00',
                $grossTotal
            );
        }

        // CR Tax Payable (tax amount)
        if ($chartConfig->output_tax_chart_uuid !== null && bccomp($taxTotal, '0.00', 2) !== 0) {
            $taxChart = $charts->get($chartConfig->output_tax_chart_uuid);
            $rows[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                GeneralLedgerEntryType::TAX,
                $taxChart,
                '0.00',
                $taxTotal
            );
        }

        // DR Sales Discounts (discount amount)
        if (bccomp($discountTotal, '0.00', 2) !== 0) {
            $rows[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                GeneralLedgerEntryType::DISCOUNT,
                null,
                $discountTotal,
                '0.00'
            );
        }

        $currency = $this->defaultCurrency();

        $rowArrays = array_map(
            fn (GeneralLedgerData $row): array => ['currency_id' => $currency['id']] + $row->toRow(),
            $rows
        );

        return $this->writeMany('ledgers', $rowArrays);
    }

    /**
     * Post a purchase invoice's general ledger rows to ledgers.
     *
     * Creates all GL entries for a purchase invoice:
     * Acquisition side: DR Inventory, CR AP, DR Input Tax, CR Discounts
     *
     * @param  PurchaseInvoice  $invoice  The invoice being posted.
     * @param  Collection<int, PurchaseInvoiceLine>  $lines  The invoice's lines.
     * @return int How many rows were written.
     */
    public function postPurchaseInvoice(PurchaseInvoice $invoice, Collection $lines): int
    {
        if ($lines->isEmpty()) {
            return 0;
        }

        // Query the chart settings for default accounts
        $chartConfig = ChartSetting::query()->first();

        if ($chartConfig === null) {
            return 0;
        }

        // Get all chart account details needed for the posting
        $chartIds = array_filter([
            $chartConfig->vendor_chart_uuid,
            $chartConfig->inventory_chart_uuid,
            $chartConfig->input_tax_chart_uuid,
        ]);

        /** @var Collection<string, Chart> $charts */
        $charts = Chart::query()
            ->whereIn('id', $chartIds)
            ->get()
            ->keyBy(fn (Chart $chart): string => (string) $chart->id);

        // Calculate totals from all lines
        $grossTotal = '0.00';
        $taxTotal = '0.00';
        $discountTotal = '0.00';

        foreach ($lines as $line) {
            // Gross total is the subtotal before tax and discount
            $grossTotal = bcadd($grossTotal, $line->subtotal_amount ?? '0.00', 2);
            $taxTotal = bcadd($taxTotal, $line->tax_amount ?? '0.00', 2);
            $discountTotal = bcadd($discountTotal, $line->discount_amount ?? '0.00', 2);
        }

        // Invoice total = gross + tax - discount
        $invoiceTotal = bcsub(bcadd($grossTotal, $taxTotal, 2), $discountTotal, 2);

        /** @var list<GeneralLedgerData> $rows */
        $rows = [];

        // Acquisition side entries
        // DR Inventory / Purchases (total)
        if ($chartConfig->inventory_chart_uuid !== null && bccomp($grossTotal, '0.00', 2) !== 0) {
            $inventoryChart = $charts->get($chartConfig->inventory_chart_uuid);
            $rows[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                GeneralLedgerEntryType::INVENTORY,
                $inventoryChart,
                $grossTotal,
                '0.00'
            );
        }

        // CR Accounts Payable (total invoice amount)
        if ($chartConfig->vendor_chart_uuid !== null) {
            $payableChart = $charts->get($chartConfig->vendor_chart_uuid);
            $rows[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                GeneralLedgerEntryType::PAYABLE,
                $payableChart,
                '0.00',
                $invoiceTotal
            );
        }

        // DR Input Tax (tax amount)
        if ($chartConfig->input_tax_chart_uuid !== null && bccomp($taxTotal, '0.00', 2) !== 0) {
            $taxChart = $charts->get($chartConfig->input_tax_chart_uuid);
            $rows[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                GeneralLedgerEntryType::TAX,
                $taxChart,
                $taxTotal,
                '0.00'
            );
        }

        // CR Purchase Discounts (discount amount)
        if (bccomp($discountTotal, '0.00', 2) !== 0) {
            $rows[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                GeneralLedgerEntryType::DISCOUNT,
                null,
                '0.00',
                $discountTotal
            );
        }

        $currency = $this->defaultCurrency();

        $rowArrays = array_map(
            fn (GeneralLedgerData $row): array => ['currency_id' => $currency['id']] + $row->toRow(),
            $rows
        );

        return $this->writeMany('ledgers', $rowArrays);
    }
}
