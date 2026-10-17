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
use App\Models\Purchase\PurchaseInvoiceChart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed the purchase invoice chart configuration.
 *
 * Maps the default accounts for posting purchase invoices to the general ledger:
 * - Payable: Accounts Payable (2000)
 * - Inventory: Inventory (1300) / Purchases (5000)
 * - Tax: Input Tax Recoverable (1350)
 * - Discount: Purchases (5000) - used as contra account
 */
class PurchaseInvoiceChartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payable = Chart::query()->where('code', '2000')->first();
        $inventory = Chart::query()->where('code', '1300')->first();
        $tax = Chart::query()->where('code', '1350')->first();
        $discount = Chart::query()->where('code', '5000')->first();

        if (! $payable || ! $inventory || ! $tax || ! $discount) {
            $this->command->warn('Chart accounts not found. Skipping purchase invoice chart seeder.');

            return;
        }

        PurchaseInvoiceChart::query()->updateOrCreate(
            ['company_id' => null],
            [
                'id' => (string) Str::uuid7(),
                'payable_id' => $payable->id,
                'inventory_id' => $inventory->id,
                'tax_id' => $tax->id,
                'discount_id' => $discount->id,
                'description' => 'Default purchase invoice chart configuration',
            ]
        );

        $this->command->info('Purchase invoice chart configuration seeded.');
    }
}
