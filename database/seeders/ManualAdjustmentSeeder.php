<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
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

/**
 * @noinspection ALL
 *
 * @phpstan-ignore-file
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use stdClass;
use Throwable;

/**
 * Seed posted manual sales and purchase adjustments so their history pages can
 * be exercised with real posted documents and ledger rows.
 *
 * The rows mirror what approving an adjustment writes: the header and its chart
 * lines, the general ledger lines, and the same lines in the receivable or
 * payable sub-ledger. Nothing else moves — no stock, no money ledger.
 */
class ManualAdjustmentSeeder extends Seeder
{
    /**
     * Seed one posted manual sales adjustment and one posted purchase adjustment.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        $charts = DB::table('charts')
            ->whereIn('code', ['1200', '2000', '4100', '5000'])
            ->get(['id', 'code', 'name', 'description'])
            ->keyBy('code');

        foreach (['1200', '2000', '4100', '5000'] as $code) {
            if (! $charts->has($code)) {
                throw new LogicException("Chart account $code is required for manual adjustment seeding.");
            }
        }

        $currencyId = DB::table('settings')->value('currency_id')
            ?? DB::table('currencies')->where('is_default', true)->value('id');

        if (! is_string($currencyId) || $currencyId === '') {
            throw new LogicException('A default currency is required for manual adjustment seeding.');
        }

        $salesInvoice = DB::table('sales_invoices')
            ->where('document_number', 'IV-RMA-DEMO-0001')
            ->first(['id', 'document_number', 'company_id', 'contact_id', 'company_name', 'contact_name']);

        $purchaseInvoice = DB::table('purchase_invoices')
            ->where('document_number', 'PI-DEMO-0001')
            ->first(['id', 'document_number', 'company_id', 'contact_id', 'company_name', 'contact_name']);

        if ($salesInvoice === null) {
            throw new LogicException('Manual sales adjustment seeding requires the IV-RMA-DEMO-0001 invoice.');
        }

        if ($purchaseInvoice === null) {
            throw new LogicException('Manual purchase adjustment seeding requires the PI-DEMO-0001 invoice.');
        }

        DB::transaction(function () use ($charts, $currencyId, $salesInvoice, $purchaseInvoice): void {
            $this->seedSalesAdjustment($charts, $currencyId, $salesInvoice);
            $this->seedPurchaseAdjustment($charts, $currencyId, $purchaseInvoice);
        });
    }

    /**
     * Seed a posted customer credit note against the demo sales invoice.
     *
     * @param  Collection<string, stdClass>  $charts  Chart accounts keyed by code.
     * @param  string  $currencyId  The default currency UUID.
     * @param  stdClass  $invoice  The posted sales invoice the note corrects.
     */
    private function seedSalesAdjustment(Collection $charts, string $currencyId, stdClass $invoice): void
    {
        $documentNumber = 'MSA-DEMO-0001';
        $date = '2026-10-15';
        $amount = '500.00';
        $description = 'Demo posted customer credit note — service fee correction.';

        $adjustmentId = DB::table('manual_sales_adjustments')
            ->where('document_number', $documentNumber)
            ->value('id') ?? (string) Str::uuid7();

        DB::table('manual_sales_adjustments')->updateOrInsert(
            ['document_number' => $documentNumber],
            [
                'id' => $adjustmentId,
                'company_id' => $invoice->company_id,
                'contact_id' => $invoice->contact_id,
                'subtype' => 'sales_credit_note',
                'reference_number' => $invoice->document_number,
                'document_original' => null,
                'sales_invoice_id' => $invoice->id,
                'company_name' => $invoice->company_name,
                'contact_name' => $invoice->contact_name,
                'date' => $date,
                'status' => 'posted',
                'subtotal' => $amount,
                'total_amount' => $amount,
                'notes' => $description,
                'approved_name' => 'System Seeder',
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        );

        $lines = [
            [
                'chart' => $charts->get('4100'),
                'line_description' => 'Reduce service revenue for the corrected fee.',
                'debit_amount' => $amount,
                'credit_amount' => '0.00',
            ],
            [
                'chart' => $charts->get('1200'),
                'line_description' => 'Reduce the customer receivable for the corrected fee.',
                'debit_amount' => '0.00',
                'credit_amount' => $amount,
            ],
        ];

        $this->replaceChartsAndLedgers(
            'manual_sales_adjustment_charts',
            'manual_sales_adjustment_id',
            $adjustmentId,
            'ar_ledgers',
            $documentNumber,
            $invoice,
            $lines,
            $currencyId,
            'sales_credit_note',
            $date,
        );
    }

    /**
     * Seed a posted supplier debit note against the demo purchase invoice.
     *
     * @param  Collection<string, stdClass>  $charts  Chart accounts keyed by code.
     * @param  string  $currencyId  The default currency UUID.
     * @param  stdClass  $invoice  The posted purchase invoice the note corrects.
     */
    private function seedPurchaseAdjustment(Collection $charts, string $currencyId, stdClass $invoice): void
    {
        $documentNumber = 'MPA-DEMO-0001';
        $date = '2026-10-16';
        $amount = '300.00';
        $description = 'Demo posted supplier debit note — purchase price correction.';

        $adjustmentId = DB::table('manual_purchase_adjustments')
            ->where('document_number', $documentNumber)
            ->value('id') ?? (string) Str::uuid7();

        DB::table('manual_purchase_adjustments')->updateOrInsert(
            ['document_number' => $documentNumber],
            [
                'id' => $adjustmentId,
                'company_id' => $invoice->company_id,
                'contact_id' => $invoice->contact_id,
                'subtype' => 'purchase_debit_note',
                'reference_number' => $invoice->document_number,
                'document_original' => null,
                'purchase_invoice_id' => $invoice->id,
                'company_name' => $invoice->company_name,
                'contact_name' => $invoice->contact_name,
                'date' => $date,
                'status' => 'posted',
                'subtotal' => $amount,
                'total_amount' => $amount,
                'notes' => $description,
                'approved_name' => 'System Seeder',
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        );

        $lines = [
            [
                'chart' => $charts->get('2000'),
                'line_description' => 'Reduce the supplier payable for the corrected price.',
                'debit_amount' => $amount,
                'credit_amount' => '0.00',
            ],
            [
                'chart' => $charts->get('5000'),
                'line_description' => 'Reduce purchases for the corrected price.',
                'debit_amount' => '0.00',
                'credit_amount' => $amount,
            ],
        ];

        $this->replaceChartsAndLedgers(
            'manual_purchase_adjustment_charts',
            'manual_purchase_adjustment_id',
            $adjustmentId,
            'ap_ledgers',
            $documentNumber,
            $invoice,
            $lines,
            $currencyId,
            'purchase_debit_note',
            $date,
        );
    }

    /**
     * Replace an adjustment's chart lines and the ledger rows they produce.
     *
     * Kept idempotent so re-seeding does not stack duplicate ledger rows on a
     * document that should still hold exactly the two sides of one entry.
     *
     * @param  string  $chartTable  The manual adjustment chart-lines table.
     * @param  string  $foreignKey  The chart-line foreign key back to its header.
     * @param  string  $adjustmentId  The header UUID.
     * @param  string  $subLedgerTable  ar_ledgers or ap_ledgers.
     * @param  string  $documentNumber  The adjustment document number.
     * @param  stdClass  $invoice  The invoice the adjustment corrects.
     * @param  list<array{chart: stdClass, line_description: string, debit_amount: string, credit_amount: string}>  $lines  The balanced entry lines.
     * @param  string  $currencyId  The default currency UUID.
     * @param  string  $subtype  The adjustment subtype value.
     * @param  string  $date  The posting date.
     */
    private function replaceChartsAndLedgers(
        string $chartTable,
        string $foreignKey,
        string $adjustmentId,
        string $subLedgerTable,
        string $documentNumber,
        stdClass $invoice,
        array $lines,
        string $currencyId,
        string $subtype,
        string $date,
    ): void {
        DB::table($chartTable)->where($foreignKey, $adjustmentId)->delete();
        DB::table('ledgers')->where('document_number', $documentNumber)->delete();
        DB::table($subLedgerTable)->where('document_number', $documentNumber)->delete();

        $now = now();

        foreach ($lines as $line) {
            $chart = $line['chart'];
            if (! $chart instanceof stdClass) {
                throw new LogicException("Manual adjustment seeding requires every referenced chart for $documentNumber.");
            }

            $lineDescription = $line['line_description'];

            DB::table($chartTable)->insert([
                'id' => (string) Str::uuid7(),
                $foreignKey => $adjustmentId,
                'chart_id' => $chart->id,
                'company_id' => null,
                'currency_id' => $currencyId,
                'type' => 'invoice',
                'subtype' => $subtype,
                'document_number' => $documentNumber,
                'reference_number' => $invoice->document_number,
                'date' => $date,
                'chart_code' => $chart->code,
                'chart_name' => $chart->name,
                'company_name' => null,
                'description' => null,
                'line_description' => $lineDescription,
                'debit_amount' => $line['debit_amount'],
                'credit_amount' => $line['credit_amount'],
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);

            DB::table('ledgers')->insert([
                'id' => (string) Str::uuid7(),
                'chart_id' => $chart->id,
                'company_id' => $invoice->company_id,
                'currency_id' => null,
                'type' => 'invoice',
                'subtype' => $subtype,
                'document_number' => $documentNumber,
                'reference_number' => $invoice->document_number,
                'date' => $date,
                'chart_code' => $chart->code,
                'chart_name' => $chart->name,
                'company_name' => $invoice->company_name,
                'description' => $lineDescription,
                'line_description' => $lineDescription,
                'debit_amount' => $line['debit_amount'],
                'credit_amount' => $line['credit_amount'],
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);

            DB::table($subLedgerTable)->insert([
                'id' => (string) Str::uuid7(),
                'chart_id' => $chart->id,
                'company_id' => $invoice->company_id,
                'currency_id' => $currencyId,
                'type' => 'invoice',
                'subtype' => $subtype,
                'document_number' => $documentNumber,
                'date' => $date,
                'reference_number' => $invoice->document_number,
                'chart_code' => $chart->code,
                'chart_name' => $chart->name,
                'chart_description' => $chart->description,
                'company_name' => $invoice->company_name,
                'description' => $lineDescription,
                'debit_amount' => $line['debit_amount'],
                'credit_amount' => $line['credit_amount'],
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }
    }
}
