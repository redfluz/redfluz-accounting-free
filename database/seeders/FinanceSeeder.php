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

/**
 * Seed essential finance reference and configuration data.
 *
 * For demo transactions (ArLedger, ApLedger, ProductLedger, Journal),
 * run DatabaseSeeder with --demo instead.
 */
class FinanceSeeder extends Seeder
{
    /**
     * Seed finance chart, configuration, and report templates.
     */
    public function run(): void
    {
        $this->call([
            FinanceChartSeeder::class,
            FinanceConfigurationSeeder::class,
            FinancialReportTemplateSeeder::class,
        ]);
    }
}
