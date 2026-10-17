<?php

declare(strict_types=1);

namespace App\Support\Posting;

use App\Models\ChartOfAccount\Chart;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceChart;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceChart;
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

        // Query the sales invoice charts configuration for default accounts
        $chartConfig = SalesInvoiceChart::query()
            ->where('company_id', $invoice->company_id)
            ->first();

        // If no company-specific config, try global default
        if ($chartConfig === null) {
            $chartConfig = SalesInvoiceChart::query()
                ->whereNull('company_id')
                ->first();
        }

        if ($chartConfig === null) {
            return 0;
        }

        // Get all chart account details needed for the posting
        $chartIds = array_filter([
            $chartConfig->receivable_id,
            $chartConfig->revenue_id,
            $chartConfig->tax_id,
            $chartConfig->discount_id,
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
        if ($chartConfig->receivable_id !== null) {
            $receivableChart = $charts->get($chartConfig->receivable_id);
            $rows[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $lines->first(), // Use first line for header-level entries
                $chartConfig,
                'receivable',
                $receivableChart,
                $invoiceTotal,
                '0.00'
            );
        }

        // CR Sales Revenue (gross sales)
        if ($chartConfig->revenue_id !== null && bccomp($grossTotal, '0.00', 2) !== 0) {
            $revenueChart = $charts->get($chartConfig->revenue_id);
            $rows[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                'revenue',
                $revenueChart,
                '0.00',
                $grossTotal
            );
        }

        // CR Tax Payable (tax amount)
        if ($chartConfig->tax_id !== null && bccomp($taxTotal, '0.00', 2) !== 0) {
            $taxChart = $charts->get($chartConfig->tax_id);
            $rows[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                'tax',
                $taxChart,
                '0.00',
                $taxTotal
            );
        }

        // DR Sales Discounts (discount amount)
        if ($chartConfig->discount_id !== null && bccomp($discountTotal, '0.00', 2) !== 0) {
            $discountChart = $charts->get($chartConfig->discount_id);
            $rows[] = GeneralLedgerData::fromSalesInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                'discount',
                $discountChart,
                $discountTotal,
                '0.00'
            );
        }

        $rowArrays = array_map(
            fn (GeneralLedgerData $row): array => $row->toRow(),
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

        // Query the purchase invoice charts configuration for default accounts
        $chartConfig = PurchaseInvoiceChart::query()
            ->where('company_id', $invoice->company_id)
            ->first();

        // If no company-specific config, try global default
        if ($chartConfig === null) {
            $chartConfig = PurchaseInvoiceChart::query()
                ->whereNull('company_id')
                ->first();
        }

        if ($chartConfig === null) {
            return 0;
        }

        // Get all chart account details needed for the posting
        $chartIds = array_filter([
            $chartConfig->payable_id,
            $chartConfig->inventory_id,
            $chartConfig->tax_id,
            $chartConfig->discount_id,
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
        if ($chartConfig->inventory_id !== null && bccomp($grossTotal, '0.00', 2) !== 0) {
            $inventoryChart = $charts->get($chartConfig->inventory_id);
            $rows[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                'inventory',
                $inventoryChart,
                $grossTotal,
                '0.00'
            );
        }

        // CR Accounts Payable (total invoice amount)
        if ($chartConfig->payable_id !== null) {
            $payableChart = $charts->get($chartConfig->payable_id);
            $rows[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                'payable',
                $payableChart,
                '0.00',
                $invoiceTotal
            );
        }

        // DR Input Tax (tax amount)
        if ($chartConfig->tax_id !== null && bccomp($taxTotal, '0.00', 2) !== 0) {
            $taxChart = $charts->get($chartConfig->tax_id);
            $rows[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                'tax',
                $taxChart,
                $taxTotal,
                '0.00'
            );
        }

        // CR Purchase Discounts (discount amount)
        if ($chartConfig->discount_id !== null && bccomp($discountTotal, '0.00', 2) !== 0) {
            $discountChart = $charts->get($chartConfig->discount_id);
            $rows[] = GeneralLedgerData::fromPurchaseInvoiceLine(
                $invoice,
                $lines->first(),
                $chartConfig,
                'discount',
                $discountChart,
                '0.00',
                $discountTotal
            );
        }

        $rowArrays = array_map(
            fn (GeneralLedgerData $row): array => $row->toRow(),
            $rows
        );

        return $this->writeMany('ledgers', $rowArrays);
    }
}
