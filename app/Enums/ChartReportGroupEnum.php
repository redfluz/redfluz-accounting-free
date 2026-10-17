<?php

declare(strict_types=1);

namespace App\Enums;

enum ChartReportGroupEnum: string
{
    case PROPERTY_PLANT_EQUIPMENT = 'property_plant_equipment';
    case INVESTMENT_PROPERTIES = 'investment_properties';
    case BIOLOGICAL_ASSETS = 'biological_assets';
    case RIGHT_OF_USE_ASSETS = 'right_of_use_assets';
    case SERVICE_CONCESSION_ASSETS = 'service_concession_assets';
    case INTANGIBLE_ASSETS = 'intangible_assets';
    case GOODWILL = 'goodwill';
    case INVESTMENT_SUBSIDIARIES = 'investment_subsidiaries';
    case INVESTMENT_ASSOCIATES = 'investment_associates';
    case INVESTMENT_JOINT_VENTURES = 'investment_joint_ventures';
    case OTHER_INVESTMENTS = 'other_investments';
    case RECEIVABLE = 'receivable';
    case CONTRACT_ASSETS = 'contract_assets';
    case DEFERRED_TAX_ASSETS = 'deferred_tax_assets';
    case DERIVATIVE_FINANCIAL_ASSETS = 'derivative_financial_assets';
    case OTHER_NON_CURRENT_ASSETS = 'other_non_current_assets';

    case INVENTORIES = 'inventories';
    case OTHER_CURRENT_ASSETS = 'other_current_assets';
    case CURRENT_TAX_ASSETS = 'current_tax_assets';
    case DERIVATIVE_FINANCIAL_ASSETS_CURRENT = 'derivative_financial_assets_current';
    case CASH_AND_CASH_EQUIVALENTS = 'cash_and_cash_equivalents';

    case SERVICE_CONCESSION_LIABILITIES = 'service_concession_liabilities';
    case BORROWINGS = 'borrowings';
    case LEASE_LIABILITIES = 'lease_liabilities';
    case EMPLOYEE_BENEFITS = 'employee_benefits';
    case PROVISIONS = 'provisions';
    case PAYABLE = 'payable';
    case CONTRACT_LIABILITIES = 'contract_liabilities';
    case DEFERRED_TAX_LIABILITIES = 'deferred_tax_liabilities';
    case DERIVATIVE_FINANCIAL_LIABILITIES = 'derivative_financial_liabilities';
    case OTHER_NON_CURRENT_LIABILITIES = 'other_non_current_liabilities';

    case CURRENT_TAX_LIABILITIES = 'current_tax_liabilities';
    case OTHER_CURRENT_LIABILITIES = 'other_current_liabilities';

    case ISSUED_CAPITAL = 'issued_capital';
    case RETAINED_EARNINGS = 'retained_earnings';
    case TREASURY_SHARES = 'treasury_shares';
    case OTHER_RESERVES = 'other_reserves';
    case NON_CONTROLLING_INTEREST = 'non_controlling_interest';

    case REVENUE = 'revenue';
    case COST_OF_SALES = 'cost_of_sales';
    case OTHER_INCOME = 'other_income';
    case SELLING_DISTRIBUTION_EXPENSES = 'selling_distribution_expenses';
    case ADMINISTRATIVE_EXPENSES = 'administrative_expenses';
    case RESEARCH_DEVELOPMENT_EXPENSES = 'research_development_expenses';
    case OTHER_EXPENSES = 'other_expenses';
    case FINANCE_INCOME = 'finance_income';
    case FINANCE_COSTS = 'finance_costs';
    case SHARE_OF_PROFIT_ASSOCIATES_JV = 'share_of_profit_associates_jv';
    case FAIR_VALUATION_GAIN_LOSS = 'fair_valuation_gain_loss';
    case TAX_EXPENSE = 'tax_expense';
    case ZAKAT = 'zakat';
    case DISCONTINUED_OPERATIONS = 'discontinued_operations';

    public function chartType(): ChartTypeEnum
    {
        return match ($this) {
            self::PROPERTY_PLANT_EQUIPMENT,
            self::INVESTMENT_PROPERTIES,
            self::BIOLOGICAL_ASSETS,
            self::RIGHT_OF_USE_ASSETS,
            self::SERVICE_CONCESSION_ASSETS,
            self::INTANGIBLE_ASSETS,
            self::GOODWILL,
            self::INVESTMENT_SUBSIDIARIES,
            self::INVESTMENT_ASSOCIATES,
            self::INVESTMENT_JOINT_VENTURES,
            self::OTHER_INVESTMENTS,
            self::RECEIVABLE,
            self::CONTRACT_ASSETS,
            self::DEFERRED_TAX_ASSETS,
            self::DERIVATIVE_FINANCIAL_ASSETS,
            self::OTHER_NON_CURRENT_ASSETS,
            self::INVENTORIES,
            self::OTHER_CURRENT_ASSETS,
            self::CURRENT_TAX_ASSETS,
            self::DERIVATIVE_FINANCIAL_ASSETS_CURRENT,
            self::CASH_AND_CASH_EQUIVALENTS => ChartTypeEnum::ASSET,

            self::SERVICE_CONCESSION_LIABILITIES,
            self::BORROWINGS,
            self::LEASE_LIABILITIES,
            self::EMPLOYEE_BENEFITS,
            self::PROVISIONS,
            self::PAYABLE,
            self::CONTRACT_LIABILITIES,
            self::DEFERRED_TAX_LIABILITIES,
            self::DERIVATIVE_FINANCIAL_LIABILITIES,
            self::OTHER_NON_CURRENT_LIABILITIES,
            self::CURRENT_TAX_LIABILITIES,
            self::OTHER_CURRENT_LIABILITIES => ChartTypeEnum::LIABILITY,

            self::ISSUED_CAPITAL,
            self::RETAINED_EARNINGS,
            self::TREASURY_SHARES,
            self::OTHER_RESERVES,
            self::NON_CONTROLLING_INTEREST => ChartTypeEnum::EQUITY,

            self::REVENUE,
            self::OTHER_INCOME,
            self::FINANCE_INCOME,
            self::SHARE_OF_PROFIT_ASSOCIATES_JV,
            self::FAIR_VALUATION_GAIN_LOSS,
            self::DISCONTINUED_OPERATIONS => ChartTypeEnum::INCOME,

            self::COST_OF_SALES,
            self::SELLING_DISTRIBUTION_EXPENSES,
            self::ADMINISTRATIVE_EXPENSES,
            self::RESEARCH_DEVELOPMENT_EXPENSES,
            self::OTHER_EXPENSES,
            self::FINANCE_COSTS,
            self::TAX_EXPENSE,
            self::ZAKAT => ChartTypeEnum::EXPENSES,
        };
    }
}
