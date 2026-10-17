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
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;

/**
 * @noinspection PhpUnused
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Whether the demo data is wanted.
     *
     * Set by whichever command is running the seed, because "did the user ask
     * for demo data" is a question about the install, not about the arguments
     * this seeder happens to be able to see.
     */
    public bool $withDemoData = false;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Register the --demo option at runtime so both db:seed and
        // migrate:fresh --seed recognise it without a custom command.
        if ($this->command && ! $this->command->getDefinition()->hasOption('demo')) {
            $this->command->getDefinition()->addOption(
                new InputOption(
                    'demo',
                    null,
                    InputOption::VALUE_NONE,
                    'Seed with full Malaysian demo data',
                )
            );
        }

        // Whether to seed demo data. The property is authoritative, because the
        // installer asks the question itself and answers it from a prompt
        // rather than from --demo on its own command line. Re-reading the input
        // option here would see false for an interactive install and skip every
        // demo seeder while still reporting that the demo data was seeded.
        $demo = $this->withDemoData;

        if (! $demo && $this->command && $this->command->getDefinition()->hasOption('demo')) {
            $demo = (bool) $this->command->option('demo');
        }

        // ── Essential: app cannot function without these ────────────────
        $this->call([
            SystemSeeder::class,
            MenuSeeder::class,
        ]);

        // Finance essential: chart of accounts, currencies, financial years,
        // periods, taxes, discounts, and report templates.
        $this->call([
            FinanceChartSeeder::class,
            FinanceConfigurationSeeder::class,
            SalesInvoiceChartSeeder::class,
            PurchaseInvoiceChartSeeder::class,
            FinancialReportTemplateSeeder::class,
        ]);

        // Set currency on settings after FinanceSeeder has seeded currencies
        $myrCurrencyId = DB::table('currencies')->where('code', 'MYR')->value('id');
        if ($myrCurrencyId) {
            DB::table('settings')->update(['currency_id' => $myrCurrencyId]);
        }

        // ── Demo: realistic Malaysian business sample data ──────────────
        if (! $demo) {
            return;
        }

        $this->call([
            CrmSeeder::class,
            InventorySeeder::class,
            BankSeeder::class,
            SubLedgerSeeder::class,
            ProductLedgerSeeder::class,
            JournalSeeder::class,
            ReceiptSeeder::class,
            MoneyLedgerSeeder::class,
            PaymentVoucherSeeder::class,
            RefundSeeder::class,
            PurchaseOrderSeeder::class,
            GoodsReceiptSeeder::class,
            // Corrections hang off the seeded goods receipt lines, and the
            // purchase order statuses depend on them, so this must run last.
            GoodsReceiptAdjustmentSeeder::class,
            QuotationSeeder::class,
            SalesOrderSeeder::class,
            DeliveryOrderSeeder::class,
            // Last: needs the purchase orders, receipts and products to exist.
            AttachmentSeeder::class,
            PurchaseDocumentSeeder::class,
        ]);
    }
}
