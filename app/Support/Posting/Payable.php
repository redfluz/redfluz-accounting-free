<?php

declare(strict_types=1);

namespace App\Support\Posting;

use App\Models\ChartOfAccount\Chart;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceChart;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Support\Posting\Concerns\WritesLedgerTables;
use Illuminate\Support\Collection;

/**
 * Writes rows to the accounts payable sub-ledger.
 *
 * One service, one table. The controller passes the invoice and its lines.
 * This service queries charts, builds ApLedgerData DTOs, and writes them to
 * ap_ledgers. Nothing else.
 */
class Payable
{
    use WritesLedgerTables;

    /**
     * Post a purchase invoice's payable rows to ap_ledgers.
     *
     * @param  PurchaseInvoice  $invoice  The invoice being posted.
     * @param  Collection<int, PurchaseInvoiceLine>  $lines  The invoice's lines.
     * @return int How many rows were written.
     */
    public function post(PurchaseInvoice $invoice, Collection $lines): int
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

        if ($chartConfig === null || $chartConfig->payable_id === null) {
            return 0;
        }

        // Get the chart account details for the payable (AP) account
        $chart = Chart::query()->find($chartConfig->payable_id);

        if ($chart === null) {
            return 0;
        }

        /** @var list<ApLedgerData> $rows */
        $rows = [];
        foreach ($lines as $line) {
            $row = ApLedgerData::fromPurchaseInvoiceLine($invoice, $line, $chartConfig, $chart);
            if ($row !== null) {
                $rows[] = $row;
            }
        }

        if ($rows === []) {
            return 0;
        }

        $rowArrays = array_map(
            fn (ApLedgerData $row): array => $row->toRow(),
            $rows
        );

        return $this->writeMany('ap_ledgers', $rowArrays);
    }
}
