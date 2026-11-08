<?php

declare(strict_types=1);

namespace App\Support\Posting;

use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Support\Posting\Concerns\WritesLedgerTables;
use Illuminate\Support\Collection;

/**
 * Writes rows to the accounts receivable sub-ledger.
 *
 * One service, one table. The controller passes the invoice and its lines.
 * This service queries charts, builds ArLedgerData DTOs, and writes them to
 * ar_ledgers. Nothing else.
 */
class Receivable
{
    use WritesLedgerTables;

    /**
     * Post a sales invoice's receivable rows to ar_ledgers.
     *
     * @param  SalesInvoice  $invoice  The invoice being posted.
     * @param  Collection<int, SalesInvoiceLine>  $lines  The invoice's lines.
     * @return int How many rows were written.
     */
    public function post(SalesInvoice $invoice, Collection $lines): int
    {
        if ($lines->isEmpty()) {
            return 0;
        }

        // Query the chart settings for default accounts
        $chartConfig = ChartSetting::query()->first();

        if ($chartConfig === null || $chartConfig->client_chart_uuid === null) {
            return 0;
        }

        // Get the chart account details for the receivable (AR) account
        $chart = Chart::query()->find($chartConfig->client_chart_uuid);

        if ($chart === null) {
            return 0;
        }

        /** @var list<ArLedgerData> $rows */
        $rows = [];
        foreach ($lines as $line) {
            $row = ArLedgerData::fromSalesInvoiceLine($invoice, $line, $chartConfig, $chart);
            if ($row !== null) {
                $rows[] = $row;
            }
        }

        if ($rows === []) {
            return 0;
        }

        $rowArrays = array_map(
            fn (ArLedgerData $row): array => $row->toRow(),
            $rows
        );

        return $this->writeMany('ar_ledgers', $rowArrays);
    }

}
