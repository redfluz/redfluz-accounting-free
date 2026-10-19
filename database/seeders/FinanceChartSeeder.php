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

use App\Enums\ChartReportGroupEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seed finance data for its dedicated responsibility.
 */
class FinanceChartSeeder extends Seeder
{
    /**
     * Seed chart types and chart accounts.
     */
    public function run(): void
    {
        $this->seedCharts(now());
    }

    private function seedCharts(CarbonInterface $now): void
    {
        $chartTypes = [
            1 => ['name' => 'DEFAULT', 'isDelete' => 0, 'isDefault' => 1, 'isAsset' => 0, 'isLiability' => 0, 'isEquity' => 0, 'isIncome' => 0, 'isExpenses' => 0],
            2 => ['name' => 'ASSET', 'isDelete' => 0, 'isDefault' => 0, 'isAsset' => 1, 'isLiability' => 0, 'isEquity' => 0, 'isIncome' => 0, 'isExpenses' => 0],
            3 => ['name' => 'LIABILITY', 'isDelete' => 0, 'isDefault' => 0, 'isAsset' => 0, 'isLiability' => 1, 'isEquity' => 0, 'isIncome' => 0, 'isExpenses' => 0],
            4 => ['name' => 'EQUITY', 'isDelete' => 0, 'isDefault' => 0, 'isAsset' => 0, 'isLiability' => 0, 'isEquity' => 1, 'isIncome' => 0, 'isExpenses' => 0],
            5 => ['name' => 'INCOME', 'isDelete' => 0, 'isDefault' => 0, 'isAsset' => 0, 'isLiability' => 0, 'isEquity' => 0, 'isIncome' => 1, 'isExpenses' => 0],
            6 => ['name' => 'EXPENSES', 'isDelete' => 0, 'isDefault' => 0, 'isAsset' => 0, 'isLiability' => 0, 'isEquity' => 0, 'isIncome' => 0, 'isExpenses' => 1],
            7 => ['name' => 'BANK', 'isDelete' => 0, 'isDefault' => 0, 'isAsset' => 0, 'isLiability' => 0, 'isEquity' => 0, 'isIncome' => 0, 'isExpenses' => 0],
        ];

        $chartTypeUuidMap = [];

        foreach ($chartTypes as $intId => $type) {
            $uuid = DB::table('chart_types')->where('name', $type['name'])->value('id') ?? (string) Str::uuid7();
            $chartTypeUuidMap[$intId] = $uuid;

            DB::table('chart_types')->updateOrInsert(
                ['name' => $type['name']],
                array_merge($type, [
                    'id' => $uuid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $charts = [
            1 => ['chart_type_id' => 2, 'code' => '1000', 'name' => 'Cash in Hand', 'isBank' => true],
            2 => ['chart_type_id' => 2, 'code' => '1100', 'name' => 'Maybank Current Account', 'isBank' => true],
            3 => ['chart_type_id' => 2, 'code' => '1110', 'name' => 'CIMB Bank Savings Account', 'isBank' => true],
            4 => ['chart_type_id' => 2, 'code' => '1120', 'name' => 'Public Bank Fixed Deposit', 'isBank' => true],
            5 => ['chart_type_id' => 2, 'code' => '1130', 'name' => 'Petty Cash', 'isBank' => true],
            // One chart per drawer and per wallet, because a money account owns
            // its chart outright (banks.chart_id is unique) and that is how the
            // poster knows which drawer a movement belongs to. Sharing one cash
            // chart between two drawers would leave the balance of whichever was
            // resolved last standing in for both.
            71 => ['chart_type_id' => 2, 'code' => '1140', 'name' => 'Cash Drawer 1 - Counter', 'isBank' => true],
            72 => ['chart_type_id' => 2, 'code' => '1141', 'name' => 'Cash Drawer 2 - Warehouse', 'isBank' => true],
            73 => ['chart_type_id' => 2, 'code' => '1150', 'name' => 'Touch n Go Wallet', 'isBank' => true],
            74 => ['chart_type_id' => 2, 'code' => '1151', 'name' => 'GrabPay Wallet', 'isBank' => true],
            // Cash between the drawer and the bank. Deliberately given no row in
            // banks, cash_accounts or e_wallet_accounts: the poster recognises a
            // money account by exactly that, so an account with one behind it
            // would file transit movements into a money ledger and count the same
            // cash a second time.
            76 => ['chart_type_id' => 2, 'code' => '1190', 'name' => 'Cash in Transit', 'isBank' => false],
            6 => ['chart_type_id' => 2, 'code' => '1200', 'name' => 'Accounts Receivable', 'isBank' => false, 'isDebtor' => true],
            66 => ['chart_type_id' => 2, 'code' => '1250', 'name' => 'Employee Advance', 'isBank' => false],
            7 => ['chart_type_id' => 2, 'code' => '1300', 'name' => 'Inventory', 'isBank' => false],
            8 => ['chart_type_id' => 2, 'code' => '1400', 'name' => 'Prepaid Expenses', 'isBank' => false],
            9 => ['chart_type_id' => 2, 'code' => '1500', 'name' => 'Office Equipment', 'isBank' => false],
            10 => ['chart_type_id' => 2, 'code' => '1600', 'name' => 'Motor Vehicles', 'isBank' => false],
            11 => ['chart_type_id' => 2, 'code' => '1700', 'name' => 'Buildings', 'isBank' => false],
            12 => ['chart_type_id' => 2, 'code' => '1800', 'name' => 'Land', 'isBank' => false],
            13 => ['chart_type_id' => 2, 'code' => '1900', 'name' => 'Accumulated Depreciation', 'isBank' => false],
            14 => ['chart_type_id' => 3, 'code' => '2000', 'name' => 'Accounts Payable', 'isBank' => false, 'isCreditor' => true],
            15 => ['chart_type_id' => 3, 'code' => '2100', 'name' => 'Accrued Expenses', 'isBank' => false],
            16 => ['chart_type_id' => 3, 'code' => '2200', 'name' => 'Loan Payable', 'isBank' => false],
            17 => ['chart_type_id' => 3, 'code' => '2300', 'name' => 'Hire Purchase Payable', 'isBank' => false],
            18 => ['chart_type_id' => 3, 'code' => '2400', 'name' => 'Interest Payable', 'isBank' => false],
            19 => ['chart_type_id' => 3, 'code' => '2500', 'name' => 'Tax Payable', 'isBank' => false],
            20 => ['chart_type_id' => 3, 'code' => '2600', 'name' => 'EPF Payable', 'isBank' => false],
            21 => ['chart_type_id' => 3, 'code' => '2700', 'name' => 'SOCSO Payable', 'isBank' => false],
            22 => ['chart_type_id' => 3, 'code' => '2800', 'name' => 'EIS Payable', 'isBank' => false],
            23 => ['chart_type_id' => 3, 'code' => '2900', 'name' => 'SST Payable', 'isBank' => false],
            24 => ['chart_type_id' => 4, 'code' => '3000', 'name' => 'Share Capital', 'isBank' => false],
            25 => ['chart_type_id' => 4, 'code' => '3100', 'name' => 'Retained Earnings', 'isBank' => false],
            26 => ['chart_type_id' => 4, 'code' => '3200', 'name' => "Owner's Capital", 'isBank' => false],
            27 => ['chart_type_id' => 4, 'code' => '3300', 'name' => "Owner's Drawings", 'isBank' => false],
            28 => ['chart_type_id' => 5, 'code' => '4000', 'name' => 'Sales Revenue', 'isBank' => false],
            29 => ['chart_type_id' => 5, 'code' => '4100', 'name' => 'Service Revenue', 'isBank' => false],
            30 => ['chart_type_id' => 5, 'code' => '4200', 'name' => 'Interest Income', 'isBank' => false],
            31 => ['chart_type_id' => 5, 'code' => '4300', 'name' => 'Rental Income', 'isBank' => false],
            32 => ['chart_type_id' => 5, 'code' => '4400', 'name' => 'Other Income', 'isBank' => false],
            33 => ['chart_type_id' => 6, 'code' => '5000', 'name' => 'Purchases', 'isBank' => false],
            75 => ['chart_type_id' => 6, 'code' => '5050', 'name' => 'Cost of Goods Sold', 'isBank' => false],
            34 => ['chart_type_id' => 6, 'code' => '5100', 'name' => 'Salaries and Wages', 'isBank' => false],
            35 => ['chart_type_id' => 6, 'code' => '5200', 'name' => 'EPF Contribution', 'isBank' => false],
            36 => ['chart_type_id' => 6, 'code' => '5300', 'name' => 'SOCSO Contribution', 'isBank' => false],
            37 => ['chart_type_id' => 6, 'code' => '5400', 'name' => 'EIS Contribution', 'isBank' => false],
            38 => ['chart_type_id' => 6, 'code' => '5500', 'name' => 'Office Rental', 'isBank' => false],
            39 => ['chart_type_id' => 6, 'code' => '5600', 'name' => 'Utilities', 'isBank' => false],
            40 => ['chart_type_id' => 6, 'code' => '5700', 'name' => 'Telephone and Internet', 'isBank' => false],
            41 => ['chart_type_id' => 6, 'code' => '5800', 'name' => 'Insurance', 'isBank' => false],
            42 => ['chart_type_id' => 6, 'code' => '5900', 'name' => 'Advertising and Marketing', 'isBank' => false],
            43 => ['chart_type_id' => 6, 'code' => '6000', 'name' => 'Office Supplies', 'isBank' => false],
            44 => ['chart_type_id' => 6, 'code' => '6100', 'name' => 'Transportation and Travelling', 'isBank' => false],
            45 => ['chart_type_id' => 6, 'code' => '6200', 'name' => 'Professional Fees', 'isBank' => false],
            46 => ['chart_type_id' => 6, 'code' => '6300', 'name' => 'Bank Charges', 'isBank' => false],
            47 => ['chart_type_id' => 6, 'code' => '6400', 'name' => 'Depreciation Expense', 'isBank' => false],
            48 => ['chart_type_id' => 3, 'code' => '2910', 'name' => 'Salary Payable', 'isBank' => false],
            49 => ['chart_type_id' => 3, 'code' => '2920', 'name' => 'Employee Claim Payable', 'isBank' => false],
            50 => ['chart_type_id' => 6, 'code' => '6500', 'name' => 'Staff Claim Expense', 'isBank' => false],
            51 => ['chart_type_id' => 6, 'code' => '6510', 'name' => 'Project Claim Expense', 'isBank' => false],
            52 => ['chart_type_id' => 6, 'code' => '6520', 'name' => 'Medical / Clinic Expense', 'isBank' => false],
            53 => ['chart_type_id' => 6, 'code' => '6530', 'name' => 'Customer Entertainment Expense', 'isBank' => false],
            54 => ['chart_type_id' => 6, 'code' => '6540', 'name' => 'Vehicle / Mileage Expense', 'isBank' => false],
            55 => ['chart_type_id' => 2, 'code' => '1350', 'name' => 'Input Tax Recoverable', 'isBank' => false],
            56 => ['chart_type_id' => 4, 'code' => '3400', 'name' => 'Revaluation Surplus', 'isBank' => false],
            57 => ['chart_type_id' => 6, 'code' => '6550', 'name' => 'Impairment Loss', 'isBank' => false],
            58 => ['chart_type_id' => 6, 'code' => '6560', 'name' => 'Loss on Asset Write-off', 'isBank' => false],
            59 => ['chart_type_id' => 6, 'code' => '6570', 'name' => 'Inventory Shortage Expense', 'isBank' => false],
            60 => ['chart_type_id' => 5, 'code' => '4450', 'name' => 'Inventory Surplus Gain', 'isBank' => false],
            61 => ['chart_type_id' => 5, 'code' => '4460', 'name' => 'Gain on Disposal', 'isBank' => false],
            62 => ['chart_type_id' => 6, 'code' => '6580', 'name' => 'Loss on Disposal', 'isBank' => false],
            63 => ['chart_type_id' => 3, 'code' => '2930', 'name' => 'Zakat Payable', 'isBank' => false],
            64 => ['chart_type_id' => 3, 'code' => '2940', 'name' => 'HRDF Payable', 'isBank' => false],
            65 => ['chart_type_id' => 6, 'code' => '5510', 'name' => 'HRDF Contribution', 'isBank' => false],
            67 => ['chart_type_id' => 5, 'code' => '4050', 'name' => 'Sales Discounts', 'isBank' => false],
        ];

        $reportGroups = [
            '1000' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1100' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1110' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1120' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1130' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1140' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1141' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1150' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1151' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1190' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS,
            '1200' => ChartReportGroupEnum::RECEIVABLE,
            '1250' => ChartReportGroupEnum::OTHER_CURRENT_ASSETS,
            '1300' => ChartReportGroupEnum::INVENTORIES,
            '1350' => ChartReportGroupEnum::OTHER_CURRENT_ASSETS,
            '1400' => ChartReportGroupEnum::OTHER_CURRENT_ASSETS,
            '1500' => ChartReportGroupEnum::PROPERTY_PLANT_EQUIPMENT,
            '1600' => ChartReportGroupEnum::PROPERTY_PLANT_EQUIPMENT,
            '1700' => ChartReportGroupEnum::PROPERTY_PLANT_EQUIPMENT,
            '1800' => ChartReportGroupEnum::PROPERTY_PLANT_EQUIPMENT,
            '1900' => ChartReportGroupEnum::PROPERTY_PLANT_EQUIPMENT,
            '2000' => ChartReportGroupEnum::PAYABLE,
            '2100' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '2200' => ChartReportGroupEnum::BORROWINGS,
            '2300' => ChartReportGroupEnum::BORROWINGS,
            '2400' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '2500' => ChartReportGroupEnum::CURRENT_TAX_LIABILITIES,
            '2600' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '2700' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '2800' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '2900' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '2910' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '2920' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '2930' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '2940' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES,
            '3000' => ChartReportGroupEnum::ISSUED_CAPITAL,
            '3100' => ChartReportGroupEnum::RETAINED_EARNINGS,
            '3200' => ChartReportGroupEnum::ISSUED_CAPITAL,
            '3300' => ChartReportGroupEnum::RETAINED_EARNINGS,
            '3400' => ChartReportGroupEnum::OTHER_RESERVES,
            '4000' => ChartReportGroupEnum::REVENUE,
            '4050' => ChartReportGroupEnum::REVENUE,
            '4100' => ChartReportGroupEnum::REVENUE,
            '4200' => ChartReportGroupEnum::OTHER_INCOME,
            '4300' => ChartReportGroupEnum::OTHER_INCOME,
            '4400' => ChartReportGroupEnum::OTHER_INCOME,
            '5000' => ChartReportGroupEnum::COST_OF_SALES,
            '5050' => ChartReportGroupEnum::COST_OF_SALES,
            '5100' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '5200' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '5300' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '5400' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '5500' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '5510' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '5600' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '5700' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '5800' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '5900' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6000' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6100' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6200' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6300' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6400' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6500' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6510' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6520' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6530' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6540' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6550' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6560' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES,
            '6570' => ChartReportGroupEnum::OTHER_EXPENSES,
            '4450' => ChartReportGroupEnum::OTHER_INCOME,
            '4460' => ChartReportGroupEnum::OTHER_INCOME,
            '6580' => ChartReportGroupEnum::OTHER_EXPENSES,
        ];

        $chartUuidMap = [];

        foreach ($charts as $intId => $chart) {
            $uuid = DB::table('charts')->where('code', $chart['code'])->value('id') ?? (string) Str::uuid7();
            $chartUuidMap[$intId] = $uuid;

            DB::table('charts')->updateOrInsert(
                ['code' => $chart['code']],
                [
                    'id' => $uuid,
                    'chart_type_id' => $chartTypeUuidMap[$chart['chart_type_id']],
                    'report_group' => $reportGroups[$chart['code']]->value,
                    'name' => $chart['name'],
                    'isBank' => $chart['isBank'],
                    'isDebtor' => $chart['isDebtor'] ?? false,
                    'isCreditor' => $chart['isCreditor'] ?? false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

    }
}
