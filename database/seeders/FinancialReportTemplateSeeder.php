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
use App\Enums\ChartReportTypeEnum;
use App\Enums\ChartTotalReportTypeEnum;
use App\Enums\ChartTypeEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancialReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFinancialReportTemplates(now());
    }

    private function seedFinancialReportTemplates(CarbonInterface $now): void
    {
        $templates = [
            [
                'code' => 'BS',
                'name' => 'Balance Sheet',
                'slug' => 'balance_sheet',
                'details' => [
                    // ── Non-Current Assets ──────────────────────────────
                    ['code' => 'NON_CURRENT_ASSETS_HEADING', 'name' => 'Non-current assets', 'chart_report_type' => ChartReportTypeEnum::NONE->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'formula' => null],
                    ['code' => 'PROPERTY_PLANT_EQUIPMENT', 'name' => 'Property, plant and equipment', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::PROPERTY_PLANT_EQUIPMENT->value, 'formula' => null],
                    ['code' => 'INVESTMENT_PROPERTIES', 'name' => 'Investment properties', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::INVESTMENT_PROPERTIES->value, 'formula' => null],
                    ['code' => 'BIOLOGICAL_ASSETS_NC', 'name' => 'Biological assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::BIOLOGICAL_ASSETS->value, 'formula' => null],
                    ['code' => 'RIGHT_OF_USE_ASSETS', 'name' => 'Right-of-use assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::RIGHT_OF_USE_ASSETS->value, 'formula' => null],
                    ['code' => 'SERVICE_CONCESSION_ASSETS', 'name' => 'Service concession assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::SERVICE_CONCESSION_ASSETS->value, 'formula' => null],
                    ['code' => 'INTANGIBLE_ASSETS', 'name' => 'Intangible assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::INTANGIBLE_ASSETS->value, 'formula' => null],
                    ['code' => 'GOODWILL', 'name' => 'Goodwill', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::GOODWILL->value, 'formula' => null],
                    ['code' => 'INVESTMENTS_SUBSIDIARIES', 'name' => 'Investments in subsidiaries', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::INVESTMENT_SUBSIDIARIES->value, 'formula' => null],
                    ['code' => 'INVESTMENTS_ASSOCIATES', 'name' => 'Investments in associates', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::INVESTMENT_ASSOCIATES->value, 'formula' => null],
                    ['code' => 'INVESTMENTS_JOINT_VENTURES', 'name' => 'Investments in joint ventures', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::INVESTMENT_JOINT_VENTURES->value, 'formula' => null],
                    ['code' => 'OTHER_INVESTMENTS_NC', 'name' => 'Other investments', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::OTHER_INVESTMENTS->value, 'formula' => null],
                    ['code' => 'TRADE_OTHER_NC_RECEIVABLES', 'name' => 'Trade and other non-current receivables', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::RECEIVABLE->value, 'formula' => null],
                    ['code' => 'CONTRACT_ASSETS_NC', 'name' => 'Contract assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::CONTRACT_ASSETS->value, 'formula' => null],
                    ['code' => 'DEFERRED_TAX_ASSETS', 'name' => 'Deferred tax assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::DEFERRED_TAX_ASSETS->value, 'formula' => null],
                    ['code' => 'DERIVATIVE_FINANCIAL_ASSETS_NC', 'name' => 'Derivative financial assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::DERIVATIVE_FINANCIAL_ASSETS->value, 'formula' => null],
                    ['code' => 'OTHER_NON_CURRENT_ASSETS', 'name' => 'Other non-current assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::OTHER_NON_CURRENT_ASSETS->value, 'formula' => null],
                    ['code' => 'TOTAL_NON_CURRENT_ASSETS', 'name' => 'Total non-current assets', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'formula' => '{SUM_GROUP:PROPERTY_PLANT_EQUIPMENT} + {SUM_GROUP:INVESTMENT_PROPERTIES} + {SUM_GROUP:BIOLOGICAL_ASSETS} + {SUM_GROUP:RIGHT_OF_USE_ASSETS} + {SUM_GROUP:SERVICE_CONCESSION_ASSETS} + {SUM_GROUP:INTANGIBLE_ASSETS} + {SUM_GROUP:GOODWILL} + {SUM_GROUP:INVESTMENT_SUBSIDIARIES} + {SUM_GROUP:INVESTMENT_ASSOCIATES} + {SUM_GROUP:INVESTMENT_JOINT_VENTURES} + {SUM_GROUP:OTHER_INVESTMENTS} + {SUM_GROUP:RECEIVABLE} + {SUM_GROUP:CONTRACT_ASSETS} + {SUM_GROUP:DEFERRED_TAX_ASSETS} + {SUM_GROUP:DERIVATIVE_FINANCIAL_ASSETS} + {SUM_GROUP:OTHER_NON_CURRENT_ASSETS}'],

                    // ── Current Assets ──────────────────────────────────
                    ['code' => 'CURRENT_ASSETS_HEADING', 'name' => 'Current assets', 'chart_report_type' => ChartReportTypeEnum::NONE->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'formula' => null],
                    ['code' => 'INVENTORIES', 'name' => 'Inventories', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::INVENTORIES->value, 'formula' => null],
                    ['code' => 'BIOLOGICAL_ASSETS_C', 'name' => 'Biological assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::BIOLOGICAL_ASSETS->value, 'formula' => null],
                    ['code' => 'OTHER_INVESTMENTS_C', 'name' => 'Other investments', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::OTHER_INVESTMENTS->value, 'formula' => null],
                    ['code' => 'CURRENT_TAX_ASSETS', 'name' => 'Current tax assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::CURRENT_TAX_ASSETS->value, 'formula' => null],
                    ['code' => 'TRADE_OTHER_C_RECEIVABLES', 'name' => 'Trade and other current receivables', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::RECEIVABLE->value, 'formula' => null],
                    ['code' => 'CONTRACT_ASSETS_C', 'name' => 'Contract assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::CONTRACT_ASSETS->value, 'formula' => null],
                    ['code' => 'DERIVATIVE_FINANCIAL_ASSETS_C', 'name' => 'Derivative financial assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::DERIVATIVE_FINANCIAL_ASSETS_CURRENT->value, 'formula' => null],
                    ['code' => 'CASH_AND_CASH_EQUIVALENTS', 'name' => 'Cash and cash equivalents', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS->value, 'formula' => null],
                    ['code' => 'OTHER_CURRENT_ASSETS', 'name' => 'Other current assets', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'chart_report_group' => ChartReportGroupEnum::OTHER_CURRENT_ASSETS->value, 'formula' => null],
                    ['code' => 'TOTAL_CURRENT_ASSETS', 'name' => 'Total current assets', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'formula' => '{SUM_GROUP:INVENTORIES} + {SUM_GROUP:BIOLOGICAL_ASSETS} + {SUM_GROUP:OTHER_INVESTMENTS} + {SUM_GROUP:CURRENT_TAX_ASSETS} + {SUM_GROUP:RECEIVABLE} + {SUM_GROUP:CONTRACT_ASSETS} + {SUM_GROUP:DERIVATIVE_FINANCIAL_ASSETS_CURRENT} + {SUM_GROUP:CASH_AND_CASH_EQUIVALENTS} + {SUM_GROUP:OTHER_CURRENT_ASSETS}'],

                    ['code' => 'TOTAL_ASSETS', 'name' => 'Total assets', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::GRAND_TOTAL->value, 'chart_type' => ChartTypeEnum::ASSET->value, 'formula' => '{ROW:TOTAL_NON_CURRENT_ASSETS} + {ROW:TOTAL_CURRENT_ASSETS}'],

                    // ── Equity ──────────────────────────────────────────
                    ['code' => 'EQUITY_LIABILITIES_HEADING', 'name' => 'Equity and liabilities', 'chart_report_type' => ChartReportTypeEnum::NONE->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => null],
                    ['code' => 'EQUITY_HEADING', 'name' => 'Equity', 'chart_report_type' => ChartReportTypeEnum::NONE->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EQUITY->value, 'formula' => null],
                    ['code' => 'ISSUED_CAPITAL', 'name' => 'Issued capital', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EQUITY->value, 'chart_report_group' => ChartReportGroupEnum::ISSUED_CAPITAL->value, 'formula' => null],
                    ['code' => 'RETAINED_EARNINGS', 'name' => 'Retained earnings', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EQUITY->value, 'chart_report_group' => ChartReportGroupEnum::RETAINED_EARNINGS->value, 'formula' => null],
                    ['code' => 'TREASURY_SHARES', 'name' => 'Treasury shares', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EQUITY->value, 'chart_report_group' => ChartReportGroupEnum::TREASURY_SHARES->value, 'formula' => null],
                    ['code' => 'OTHER_RESERVES', 'name' => 'Other reserves', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EQUITY->value, 'chart_report_group' => ChartReportGroupEnum::OTHER_RESERVES->value, 'formula' => null],
                    ['code' => 'TOTAL_EQUITY_ATTRIBUTABLE_OWNERS', 'name' => 'Total equity attributable to owners', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::EQUITY->value, 'formula' => '{ROW:ISSUED_CAPITAL} + {ROW:RETAINED_EARNINGS} + {ROW:TREASURY_SHARES} + {ROW:OTHER_RESERVES}'],
                    ['code' => 'NON_CONTROLLING_INTEREST', 'name' => 'Non-controlling interests', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EQUITY->value, 'chart_report_group' => ChartReportGroupEnum::NON_CONTROLLING_INTEREST->value, 'formula' => null],
                    ['code' => 'TOTAL_EQUITY', 'name' => 'Total equity', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::EQUITY->value, 'formula' => '{ROW:TOTAL_EQUITY_ATTRIBUTABLE_OWNERS} + {ROW:NON_CONTROLLING_INTEREST}'],

                    // ── Non-Current Liabilities ─────────────────────────
                    ['code' => 'NON_CURRENT_LIABILITIES_HEADING', 'name' => 'Non-current liabilities', 'chart_report_type' => ChartReportTypeEnum::NONE->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'formula' => null],
                    ['code' => 'SERVICE_CONCESSION_LIABILITIES_NC', 'name' => 'Service concession liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::SERVICE_CONCESSION_LIABILITIES->value, 'formula' => null],
                    ['code' => 'BORROWINGS_NC', 'name' => 'Borrowings', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::BORROWINGS->value, 'formula' => null],
                    ['code' => 'LEASE_LIABILITIES_NC', 'name' => 'Lease liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::LEASE_LIABILITIES->value, 'formula' => null],
                    ['code' => 'EMPLOYEE_BENEFITS_NC', 'name' => 'Employee benefit liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::EMPLOYEE_BENEFITS->value, 'formula' => null],
                    ['code' => 'PROVISIONS_NC', 'name' => 'Provisions', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::PROVISIONS->value, 'formula' => null],
                    ['code' => 'TRADE_OTHER_NC_PAYABLES', 'name' => 'Trade and other non-current payables', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::PAYABLE->value, 'formula' => null],
                    ['code' => 'CONTRACT_LIABILITIES_NC', 'name' => 'Contract liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::CONTRACT_LIABILITIES->value, 'formula' => null],
                    ['code' => 'DEFERRED_TAX_LIABILITIES', 'name' => 'Deferred tax liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::DEFERRED_TAX_LIABILITIES->value, 'formula' => null],
                    ['code' => 'OTHER_NON_CURRENT_LIABILITIES', 'name' => 'Other non-current liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::OTHER_NON_CURRENT_LIABILITIES->value, 'formula' => null],
                    ['code' => 'TOTAL_NON_CURRENT_LIABILITIES', 'name' => 'Total non-current liabilities', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'formula' => '{SUM_GROUP:SERVICE_CONCESSION_LIABILITIES} + {SUM_GROUP:BORROWINGS} + {SUM_GROUP:LEASE_LIABILITIES} + {SUM_GROUP:EMPLOYEE_BENEFITS} + {SUM_GROUP:PROVISIONS} + {SUM_GROUP:PAYABLE} + {SUM_GROUP:CONTRACT_LIABILITIES} + {SUM_GROUP:DEFERRED_TAX_LIABILITIES} + {SUM_GROUP:OTHER_NON_CURRENT_LIABILITIES}'],

                    // ── Current Liabilities ─────────────────────────────
                    ['code' => 'CURRENT_LIABILITIES_HEADING', 'name' => 'Current liabilities', 'chart_report_type' => ChartReportTypeEnum::NONE->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'formula' => null],
                    ['code' => 'BORROWINGS_C', 'name' => 'Borrowings', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::BORROWINGS->value, 'formula' => null],
                    ['code' => 'LEASE_LIABILITIES_C', 'name' => 'Lease liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::LEASE_LIABILITIES->value, 'formula' => null],
                    ['code' => 'EMPLOYEE_BENEFITS_C', 'name' => 'Employee benefit liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::EMPLOYEE_BENEFITS->value, 'formula' => null],
                    ['code' => 'PROVISIONS_C', 'name' => 'Provisions', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::PROVISIONS->value, 'formula' => null],
                    ['code' => 'CURRENT_TAX_LIABILITIES', 'name' => 'Current tax liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::CURRENT_TAX_LIABILITIES->value, 'formula' => null],
                    ['code' => 'TRADE_OTHER_C_PAYABLES', 'name' => 'Trade and other current payables', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::PAYABLE->value, 'formula' => null],
                    ['code' => 'CONTRACT_LIABILITIES_C', 'name' => 'Contract liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::CONTRACT_LIABILITIES->value, 'formula' => null],
                    ['code' => 'DERIVATIVE_FINANCIAL_LIABILITIES_C', 'name' => 'Derivative financial liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::DERIVATIVE_FINANCIAL_LIABILITIES->value, 'formula' => null],
                    ['code' => 'OTHER_CURRENT_LIABILITIES', 'name' => 'Other current liabilities', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'chart_report_group' => ChartReportGroupEnum::OTHER_CURRENT_LIABILITIES->value, 'formula' => null],
                    ['code' => 'TOTAL_CURRENT_LIABILITIES', 'name' => 'Total current liabilities', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'formula' => '{SUM_GROUP:BORROWINGS} + {SUM_GROUP:LEASE_LIABILITIES} + {SUM_GROUP:EMPLOYEE_BENEFITS} + {SUM_GROUP:PROVISIONS} + {SUM_GROUP:CURRENT_TAX_LIABILITIES} + {SUM_GROUP:PAYABLE} + {SUM_GROUP:CONTRACT_LIABILITIES} + {SUM_GROUP:DERIVATIVE_FINANCIAL_LIABILITIES} + {SUM_GROUP:OTHER_CURRENT_LIABILITIES}'],

                    ['code' => 'TOTAL_LIABILITIES', 'name' => 'Total liabilities', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::LIABILITY->value, 'formula' => '{ROW:TOTAL_NON_CURRENT_LIABILITIES} + {ROW:TOTAL_CURRENT_LIABILITIES}'],
                    ['code' => 'TOTAL_EQUITY_AND_LIABILITIES', 'name' => 'Total equity and liabilities', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::GRAND_TOTAL->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => '{ROW:TOTAL_EQUITY} + {ROW:TOTAL_LIABILITIES}'],
                    ['code' => 'BALANCE_CHECK', 'name' => 'Balance Check', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::GRAND_TOTAL->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => '{ROW:TOTAL_ASSETS} - {ROW:TOTAL_EQUITY_AND_LIABILITIES}'],
                ],
            ],
            [
                'code' => 'IS',
                'name' => 'Income Statement',
                'slug' => 'income_statement',
                'details' => [
                    // ── Continuing Operations ───────────────────────────
                    ['code' => 'CONTINUING_OPS_HEADING', 'name' => 'Continuing operations', 'chart_report_type' => ChartReportTypeEnum::NONE->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => null],
                    ['code' => 'REVENUE', 'name' => 'Revenue', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::INCOME->value, 'chart_report_group' => ChartReportGroupEnum::REVENUE->value, 'formula' => null],
                    ['code' => 'COST_OF_SALES', 'name' => 'Cost of sales', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EXPENSES->value, 'chart_report_group' => ChartReportGroupEnum::COST_OF_SALES->value, 'formula' => null],
                    ['code' => 'GROSS_PROFIT', 'name' => 'Gross profit', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => '{ROW:REVENUE} - {ROW:COST_OF_SALES}'],
                    ['code' => 'OTHER_INCOME', 'name' => 'Other income', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::INCOME->value, 'chart_report_group' => ChartReportGroupEnum::OTHER_INCOME->value, 'formula' => null],
                    ['code' => 'SELLING_DISTRIBUTION_EXPENSES', 'name' => 'Selling and distribution expenses', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EXPENSES->value, 'chart_report_group' => ChartReportGroupEnum::SELLING_DISTRIBUTION_EXPENSES->value, 'formula' => null],
                    ['code' => 'ADMINISTRATIVE_EXPENSES', 'name' => 'Administrative expenses', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EXPENSES->value, 'chart_report_group' => ChartReportGroupEnum::ADMINISTRATIVE_EXPENSES->value, 'formula' => null],
                    ['code' => 'RESEARCH_DEVELOPMENT_EXPENSES', 'name' => 'Research and development expenses', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EXPENSES->value, 'chart_report_group' => ChartReportGroupEnum::RESEARCH_DEVELOPMENT_EXPENSES->value, 'formula' => null],
                    ['code' => 'OTHER_EXPENSES', 'name' => 'Other expenses', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EXPENSES->value, 'chart_report_group' => ChartReportGroupEnum::OTHER_EXPENSES->value, 'formula' => null],
                    ['code' => 'PROFIT_FROM_OPERATING_ACTIVITIES', 'name' => 'Profit (loss) from operating activities', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => '{ROW:GROSS_PROFIT} + {ROW:OTHER_INCOME} - {ROW:SELLING_DISTRIBUTION_EXPENSES} - {ROW:ADMINISTRATIVE_EXPENSES} - {ROW:RESEARCH_DEVELOPMENT_EXPENSES} - {ROW:OTHER_EXPENSES}'],
                    ['code' => 'FINANCE_INCOME', 'name' => 'Finance income', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::INCOME->value, 'chart_report_group' => ChartReportGroupEnum::FINANCE_INCOME->value, 'formula' => null],
                    ['code' => 'FINANCE_COSTS', 'name' => 'Finance costs', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EXPENSES->value, 'chart_report_group' => ChartReportGroupEnum::FINANCE_COSTS->value, 'formula' => null],
                    ['code' => 'SHARE_OF_PROFIT_ASSOCIATES_JV', 'name' => 'Share of profit (loss) of associates and joint ventures', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::INCOME->value, 'chart_report_group' => ChartReportGroupEnum::SHARE_OF_PROFIT_ASSOCIATES_JV->value, 'formula' => null],
                    ['code' => 'FAIR_VALUATION_GAIN_LOSS', 'name' => 'Fair valuation gain/(loss) arising from distribution of non-cash assets to owners', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::INCOME->value, 'chart_report_group' => ChartReportGroupEnum::FAIR_VALUATION_GAIN_LOSS->value, 'formula' => null],
                    ['code' => 'PROFIT_BEFORE_TAX', 'name' => 'Profit (loss) before tax', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => '{ROW:PROFIT_FROM_OPERATING_ACTIVITIES} + {ROW:FINANCE_INCOME} - {ROW:FINANCE_COSTS} + {ROW:SHARE_OF_PROFIT_ASSOCIATES_JV} + {ROW:FAIR_VALUATION_GAIN_LOSS}'],
                    ['code' => 'TAX_EXPENSE', 'name' => 'Tax expense', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EXPENSES->value, 'chart_report_group' => ChartReportGroupEnum::TAX_EXPENSE->value, 'formula' => null],
                    ['code' => 'ZAKAT', 'name' => 'Contribution of zakat', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::EXPENSES->value, 'chart_report_group' => ChartReportGroupEnum::ZAKAT->value, 'formula' => null],
                    ['code' => 'PROFIT_FROM_CONTINUING_OPS', 'name' => 'Profit (loss) from continuing operations', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::SUB_TOTAL->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => '{ROW:PROFIT_BEFORE_TAX} - {ROW:TAX_EXPENSE} - {ROW:ZAKAT}'],

                    // ── Discontinued Operations ─────────────────────────
                    ['code' => 'DISCONTINUED_OPS_HEADING', 'name' => 'Discontinued operation', 'chart_report_type' => ChartReportTypeEnum::NONE->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => null],
                    ['code' => 'PROFIT_DISCONTINUED_OPS', 'name' => 'Profit (loss) from discontinued operation, net tax', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::INCOME->value, 'chart_report_group' => ChartReportGroupEnum::DISCONTINUED_OPERATIONS->value, 'formula' => null],

                    // ── Total Profit/Loss ───────────────────────────────
                    ['code' => 'PROFIT_LOSS', 'name' => 'Profit (loss)', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::GRAND_TOTAL->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => '{ROW:PROFIT_FROM_CONTINUING_OPS} + {ROW:PROFIT_DISCONTINUED_OPS}'],

                    // ── Profit Attributable To ──────────────────────────
                    ['code' => 'ATTRIBUTABLE_HEADING', 'name' => 'Profit (loss) attributable to', 'chart_report_type' => ChartReportTypeEnum::NONE->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => null],
                    ['code' => 'PROFIT_ATTRIBUTABLE_OWNERS', 'name' => 'Owners of parent', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::INCOME->value, 'chart_report_group' => ChartReportGroupEnum::RETAINED_EARNINGS->value, 'formula' => null],
                    ['code' => 'PROFIT_ATTRIBUTABLE_NCI', 'name' => 'Non-controlling interests', 'chart_report_type' => ChartReportTypeEnum::GROUPS->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::NONE->value, 'chart_type' => ChartTypeEnum::INCOME->value, 'chart_report_group' => ChartReportGroupEnum::NON_CONTROLLING_INTEREST->value, 'formula' => null],
                    ['code' => 'TOTAL_PROFIT_LOSS', 'name' => 'Total profit (loss)', 'chart_report_type' => ChartReportTypeEnum::FORMULA->value, 'chart_total_report_type' => ChartTotalReportTypeEnum::GRAND_TOTAL->value, 'chart_type' => ChartTypeEnum::NONE->value, 'formula' => '{ROW:PROFIT_ATTRIBUTABLE_OWNERS} + {ROW:PROFIT_ATTRIBUTABLE_NCI}'],
                ],
            ],
        ];

        foreach ($templates as $template) {
            $reportId = DB::table('chart_reports')
                ->where('slug', $template['slug'])
                ->value('id') ?? (string) Str::uuid7();

            DB::table('chart_reports')->updateOrInsert(
                [
                    'slug' => $template['slug'],
                ],
                [
                    'id' => $reportId,
                    'code' => $template['code'],
                    'name' => $template['name'],
                    'is_default' => true,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('chart_report_details')
                ->where('chart_report_id', $reportId)
                ->delete();

            foreach ($template['details'] as $index => $detail) {
                $detailId = DB::table('chart_report_details')
                    ->where('chart_report_id', $reportId)
                    ->where('code', $detail['code'])
                    ->value('id') ?? (string) Str::uuid7();

                DB::table('chart_report_details')->updateOrInsert(
                    [
                        'chart_report_id' => $reportId,
                        'code' => $detail['code'],
                    ],
                    [
                        'id' => $detailId,
                        'parent_id' => null,
                        'chart_id' => null,
                        'name' => $detail['name'],
                        'chart_report_type' => $detail['chart_report_type'],
                        'chart_total_report_type' => $detail['chart_total_report_type'],
                        'chart_type' => $detail['chart_type'],
                        'chart_report_group' => $detail['chart_report_group'] ?? null,
                        'formula' => $detail['formula'],
                        'line' => $index + 1,
                        'notes' => null,
                        'deleted_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
