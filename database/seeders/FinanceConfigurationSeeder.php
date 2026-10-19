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

use App\Enums\TaxMode;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seed finance data for its dedicated responsibility.
 */
class FinanceConfigurationSeeder extends Seeder
{
    /**
     * Seed financial periods and account settings.
     */
    public function run(): void
    {
        $now = now();

        $currencies = [
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'is_default' => true, 'is_active' => true],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_default' => false, 'is_active' => true],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'is_default' => false, 'is_active' => true],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿', 'is_default' => false, 'is_active' => true],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'is_default' => false, 'is_active' => true],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'is_default' => false, 'is_active' => true],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'is_default' => false, 'is_active' => true],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => false, 'is_active' => true],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'is_default' => false, 'is_active' => true],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'is_default' => false, 'is_active' => true],
        ];

        foreach ($currencies as $currency) {
            $existing = DB::table('currencies')->where('code', $currency['code'])->first();
            DB::table('currencies')->updateOrInsert(
                ['code' => $currency['code']],
                [
                    'id' => $existing->id ?? (string) Str::uuid7(),
                    'name' => $currency['name'],
                    'symbol' => $currency['symbol'],
                    'is_default' => $currency['is_default'],
                    'is_active' => $currency['is_active'],
                    'created_at' => $existing->created_at ?? $now,
                    'updated_at' => $now,
                ]
            );
        }

        $yearIds = $this->seedYears($now);
        $periodIds = $this->seedPeriods($yearIds, $now);
        $chartIds = $this->chartIds();

        $this->seedChartSettings($chartIds, $now);
        $this->seedTaxes($now);
        $this->seedDiscounts($now);
    }

    /**
     * Seed reusable tax choices for sales and purchase invoices.
     */
    private function seedTaxes(CarbonInterface $now): void
    {
        foreach ([
            ['code' => 'SST-6', 'name' => 'Sales and Service Tax 6%', 'rate' => 6.0000],
            ['code' => 'SST-8', 'name' => 'Sales and Service Tax 8%', 'rate' => 8.0000],
        ] as $tax) {
            $taxId = DB::table('taxes')->where('code', $tax['code'])->value('id');
            DB::table('taxes')->updateOrInsert(
                ['code' => $tax['code']],
                [
                    'id' => $taxId ?? (string) Str::uuid7(),
                    'name' => $tax['name'],
                    'rate' => $tax['rate'],
                    'default_mode' => TaxMode::EXCLUSIVE->value,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );
        }
    }

    /**
     * Seed reusable percentage and fixed-amount discount choices.
     */
    private function seedDiscounts(CarbonInterface $now): void
    {
        foreach ([
            ['code' => 'LOYALTY-5', 'name' => 'Loyalty Discount 5%', 'type' => 'percent', 'value' => 5.0000],
            ['code' => 'WELCOME-50', 'name' => 'Welcome Discount RM 50', 'type' => 'amount', 'value' => 50.0000],
        ] as $discount) {
            $discountId = DB::table('discounts')->where('code', $discount['code'])->value('id');
            DB::table('discounts')->updateOrInsert(
                ['code' => $discount['code']],
                [
                    'id' => $discountId ?? (string) Str::uuid7(),
                    'name' => $discount['name'],
                    'type' => $discount['type'],
                    'value' => $discount['value'],
                    'starts_at' => null,
                    'ends_at' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );
        }
    }

    /**
     * Load chart UUIDs using the legacy numeric keys used by the seed data.
     *
     * @return array<int, string>
     */
    private function chartIds(): array
    {
        $codes = [
            1 => '1000',
            2 => '1100',
            4 => '1120',
            76 => '1190',
            6 => '1200',
            66 => '1250',
            7 => '1300',
            14 => '2000',
            19 => '2500',
            20 => '2600',
            21 => '2700',
            22 => '2800',
            25 => '3100',
            28 => '4000',
            33 => '5000',
            75 => '5050',
            34 => '5100',
            35 => '5200',
            36 => '5300',
            37 => '5400',
            48 => '2910',
            50 => '6500',
            51 => '6510',
            52 => '6520',
            53 => '6530',
            54 => '6540',
            55 => '1350',
            23 => '2900',
            59 => '6570',
            60 => '4450',
            63 => '2930',
            64 => '2940',
            65 => '5510',
        ];
        $idsByCode = DB::table('charts')
            ->whereIn('code', array_values($codes))
            ->pluck('id', 'code');

        return collect($codes)
            ->mapWithKeys(fn (string $code, int $key): array => [$key => $idsByCode[$code]])
            ->all();
    }

    /**
     * @param  CarbonInterface  $now  The current timestamp for seeding records.
     * @return array<int, string>
     */
    private function seedYears(CarbonInterface $now): array
    {
        $years = [
            1 => [
                'name' => 'FY 2024',
                'year' => 2024,
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'is_closed' => 1,
            ],
            2 => [
                'name' => 'FY 2025',
                'year' => 2025,
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'is_closed' => 1,
            ],
            3 => [
                'name' => 'FY 2026',
                'year' => 2026,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_closed' => 0,
            ],
        ];

        $fyUuidMap = [];

        foreach ($years as $intId => $year) {
            $uuid = DB::table('years')->where('name', $year['name'])->value('id') ?? (string) Str::uuid7();
            $fyUuidMap[$intId] = $uuid;

            DB::table('years')->updateOrInsert(
                ['name' => $year['name']],
                array_merge($year, [
                    'id' => $uuid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        return $fyUuidMap;
    }

    /**
     * @param  array<int, string>  $fyUuidMap
     * @param  CarbonInterface  $now  The current timestamp for seeding records.
     * @return array<int, array<int, string>>
     */
    private function seedPeriods(array $fyUuidMap, CarbonInterface $now): array
    {
        $periodUuidMap = [];
        $yearMap = [
            1 => 2024,
            2 => 2025,
            3 => 2026,
        ];

        foreach ($yearMap as $fyIntId => $year) {
            for ($month = 1; $month <= 12; $month++) {
                $startDate = Carbon::parse(sprintf('%04d-%02d-01', $year, $month))->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                $uuid = DB::table('periods')
                    ->where('year_id', $fyUuidMap[$fyIntId])
                    ->where('period', $month)
                    ->value('id') ?? (string) Str::uuid7();

                $periodUuidMap[$fyIntId][$month] = $uuid;

                DB::table('periods')->updateOrInsert(
                    [
                        'year_id' => $fyUuidMap[$fyIntId],
                        'period' => $month,
                    ],
                    [
                        'id' => $uuid,
                        'name' => $startDate->format('F Y'),
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'is_closed' => $year < 2026 || $month < 8,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        return $periodUuidMap;
    }

    /**
     * @param  array<int, string>  $chartUuidMap
     * @param  CarbonInterface  $now  The current timestamp for seeding records.
     */
    private function seedChartSettings(array $chartUuidMap, CarbonInterface $now): void
    {
        $chartSettingId = DB::table('chart_settings')->value('id') ?? (string) Str::uuid7();

        DB::table('chart_settings')->updateOrInsert(
            ['id' => $chartSettingId],
            [
                'id' => $chartSettingId,
                'cogs_chart_uuid' => $chartUuidMap[75],
                'income_chart_uuid' => $chartUuidMap[28],
                'bank_chart_uuid' => $chartUuidMap[2],
                'cash_chart_uuid' => $chartUuidMap[1],
                'cash_in_transit_chart_uuid' => $chartUuidMap[76],
                // No dedicated e-wallet chart exists yet, so the default stays empty
                // until one is created and chosen in the chart settings form.
                'e_wallet_chart_uuid' => null,
                'client_chart_uuid' => $chartUuidMap[6],
                'employee_advance_chart_uuid' => $chartUuidMap[66],
                'vendor_chart_uuid' => $chartUuidMap[14],
                'inventory_chart_uuid' => $chartUuidMap[7],
                'stock_shortage_chart_uuid' => $chartUuidMap[59],
                'stock_surplus_chart_uuid' => $chartUuidMap[60],
                'deposit_chart_uuid' => $chartUuidMap[4],
                'input_tax_chart_uuid' => $chartUuidMap[55],
                'output_tax_chart_uuid' => $chartUuidMap[23],
                'retained_earnings_chart_uuid' => $chartUuidMap[25],
                'retained_earnings_formula' => '{BEGINNING:RETAINED_EARNINGS} + {SUM_TYPE:INCOME} - {SUM_TYPE:EXPENSES} + {MOVEMENT:RETAINED_EARNINGS}',
                'closing_date' => '2025-12-31',
                'start_finance_period_month' => 1,
                'strict_journal_posting' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
