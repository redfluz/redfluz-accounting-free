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

namespace Database\Seeders;

use App\Models\ChartOfAccount\Chart;
use App\Models\Sales\SalesInvoiceChart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed the sales invoice chart configuration.
 *
 * Maps the default accounts for posting sales invoices to the general ledger:
 * - Receivable: Accounts Receivable (1200)
 * - Revenue: Sales Revenue (4000)
 * - Tax: Tax Payable (2500)
 * - COGS: Purchases (5000) - used as cost of goods sold
 * - Inventory: Inventory (1300)
 */
class SalesInvoiceChartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $receivable = Chart::query()->where('code', '1200')->first();
        $revenue = Chart::query()->where('code', '4000')->first();
        $tax = Chart::query()->where('code', '2500')->first();
        $discount = Chart::query()->where('code', '4050')->first();
        $cogs = Chart::query()->where('code', '5000')->first();
        $inventory = Chart::query()->where('code', '1300')->first();

        if (! $receivable || ! $revenue || ! $tax || ! $cogs || ! $inventory) {
            $this->command->warn('Chart accounts not found. Skipping sales invoice chart seeder.');

            return;
        }

        SalesInvoiceChart::query()->updateOrCreate(
            ['company_id' => null],
            [
                'id' => (string) Str::uuid7(),
                'receivable_id' => $receivable->id,
                'revenue_id' => $revenue->id,
                'tax_id' => $tax->id,
                'discount_id' => $discount?->id,
                'cogs_id' => $cogs->id,
                'inventory_id' => $inventory->id,
                'description' => 'Default sales invoice chart configuration',
            ]
        );

        $this->command->info('Sales invoice chart configuration seeded.');
    }
}
