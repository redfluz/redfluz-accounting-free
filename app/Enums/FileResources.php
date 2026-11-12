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

namespace app\Enums;

/**
 * Please refer to route_name field in the table pages
 *
 * @noinspection PhpUnused
 */
enum FileResources: string
{
    // Chart
    case CHART_DASHBOARD = 'chart-of-account.dashboard.index';
    case BALANCE_SHEET = 'chart-of-account.balance-sheet.index';
    case BUDGET_ACTUAL = 'chart-of-account.budget-actual.index';
    case CASH_FLOW = 'chart-of-account.cash-flow.index';
    case CHARTS = 'chart-of-account.charts.index';
    case CHART_BUDGETS = 'chart-of-account.chart-budgets.index';
    case CHART_REPORTS = 'chart-of-account.chart-reports.index';
    case CHART_SETTINGS = 'chart-of-account.chart-settings.index';
    case CHART_TYPES = 'chart-of-account.chart-types.index';
    case PROFIT_LOSS = 'chart-of-account.profit-loss.index';
    case TRIAL_BALANCE = 'chart-of-account.trial-balance.index';

    // Claim
    case CLAIM_DASHBOARD = 'claim.dashboard.index';
    case CLAIM_BATCH = 'claim.batch.index';
    case CLAIM_MANAGEMENTS = 'claim.claim-managements.index';
    case CLAIM_SETTINGS = 'claim.claim-settings.index';

    // CRM
    case CRM_DASHBOARD = 'crm.dashboard.index';
    case APPOINTMENTS = 'crm.appointments.index';
    case COMPANIES = 'crm.companies.index';
    case COMPANY_FILES = 'crm.company-files.index';
    case CONTACTS = 'crm.contacts.index';
    case CUSTOMER_LETTERS = 'crm.customer-letters.index';
    case PROJECTS = 'crm.projects.index';

    // Inventory
    case INVENTORY_DASHBOARD = 'inventory.dashboard.index';
    case BRANDS = 'inventory.brands.index';
    case PRODUCT_CATEGORIES = 'inventory.product-categories.index';
    case PRODUCT_TYPES = 'inventory.product-types.index';
    case PRODUCTS = 'inventory.products.index';
    case STOCK_LOCATIONS = 'inventory.stock-locations.index';
    case STOCK_STRUCTURE = 'inventory.stock-structure.index';
    case STOCK_TRANSFERS = 'inventory.stock-transfers.index';
    case STOCK_TAKES = 'inventory.stock-takes.index';

    // YEAR
    case PERIODS = 'year.periods.index';
    case PERIOD_CLOSING = 'year.period-closing.index';
    case YEARS = 'year.years.index';
    case YEAR_CLOSING = 'year.year-closing.index';

    // Finance
    case FINANCE_DASHBOARD = 'finance.dashboard.index';
    case JOURNAL_ENTRIES = 'finance.journal-entries.index';
    case JOURNAL_ENTRIES_CANCELLED = 'finance.journal-entries.cancelled.index';
    case JOURNAL_ENTRIES_HISTORY = 'finance.journal-entries.history.index';
    case LEDGERS = 'finance.ledgers.index';
    case AR_LEDGERS = 'finance.ar-ledgers.index';
    case AP_LEDGERS = 'finance.ap-ledgers.index';
    case PRODUCT_LEDGERS = 'finance.product-ledgers.index';
    case BANK_RECONCILIATIONS = 'bank.reconciliations.index';
    case TAXES = 'finance.taxes.index';
    case DISCOUNTS = 'finance.discounts.index';
    case CURRENCIES = 'finance.currencies.index';

    // Fixed Assets
    case FIXED_ASSET_DASHBOARD = 'fixed-asset.dashboard.index';
    case FIXED_ASSETS = 'fixed-asset.fixed-assets.index';
    case FIXED_ASSET_CATEGORIES = 'fixed-asset.categories.index';
    case FIXED_ASSET_DEPRECIATION = 'fixed-asset.depreciation.index';
    case FIXED_ASSET_VALUATIONS = 'fixed-asset.valuations.index';
    case FIXED_ASSET_WRITE_OFFS = 'fixed-asset.write-offs.index';
    case FIXED_ASSET_DISPOSALS = 'fixed-asset.disposals.index';
    case FIXED_ASSET_DISPOSALS_APPROVED = 'fixed-asset.disposals.approved.index';
    case FIXED_ASSET_DISPOSALS_CANCELLED = 'fixed-asset.disposals.cancelled.index';
    case FIXED_ASSET_LOANS = 'fixed-asset.loans.index';
    case FIXED_ASSET_LOANS_APPROVED = 'fixed-asset.loans.approved.index';
    case FIXED_ASSET_LOANS_CANCELLED = 'fixed-asset.loans.cancelled.index';
    case FIXED_ASSET_MAINTENANCE = 'fixed-asset.maintenance.index';
    case FIXED_ASSET_SETTINGS = 'fixed-asset.settings.index';

    // Billing
    case RECURRING_SCHEDULES = 'billing.recurring-schedules.index';
    case RECURRING_POSTING = 'billing.recurring-posting.index';
    case RECURRING_POSTING_CANCELLED = 'billing.recurring-posting.cancelled.index';
    case RECURRING_POSTING_HISTORY = 'billing.recurring-posting.history.index';

    // Invoice
    case PURCHASE_REQUESTS = 'purchase.purchase-requests.index';
    case PURCHASE_QUOTATIONS = 'purchase.purchase-quotations.index';
    case PURCHASE_ORDER_SETTINGS = 'purchase.purchase-order-settings.index';
    case PURCHASE_ORDERS = 'purchase.purchase-orders.index';
    case GOODS_RECEIPTS = 'purchase.goods-receipts.index';
    case GOODS_RECEIPT_ADJUSTMENTS = 'purchase.goods-receipt-adjustments.index';
    case GOODS_RECEIPT_ADJUSTMENTS_HISTORY = 'purchase.goods-receipt-adjustments.history.index';
    case GOODS_RECEIPT_ADJUSTMENTS_CANCELLED = 'purchase.goods-receipt-adjustments.cancelled.index';
    case RMAS = 'sales.rmas.index';
    case QUOTATIONS = 'sales.quotations.index';
    case PRODUCT_PACKAGES = 'sales.product-packages.index';
    case SALES_ORDERS = 'sales.sales-orders.index';
    case SALES_DELIVERY_ORDERS = 'sales.delivery-orders.index';
    case PURCHASE_RETURNS = 'purchase.returns.index';
    case INVOICE_DESIGNS = 'design.invoice-designs.index';
    case INVOICES = 'invoice.invoices.index';
    case MULTI_INVOICES = 'invoice.multi-invoices.index';
    case SALES_INVOICES = 'sales.invoices.index';
    case SALES_INVOICES_CANCELLED = 'sales.invoices.cancelled.index';
    case SALES_INVOICES_HISTORY = 'sales.invoices.history.index';
    case MANUAL_SALES_ADJUSTMENTS = 'sales.manual-adjustments.index';
    case MANUAL_SALES_ADJUSTMENTS_CANCELLED = 'sales.manual-adjustments.cancelled.index';
    case MANUAL_SALES_ADJUSTMENTS_HISTORY = 'sales.manual-adjustments.history.index';
    case MANUAL_SALES_INVOICES = 'sales.manual-invoices.index';
    case MANUAL_SALES_INVOICES_CANCELLED = 'sales.manual-invoices.cancelled.index';
    case MANUAL_SALES_INVOICES_HISTORY = 'sales.manual-invoices.history.index';
    case POS = 'pos.index';
    case CUSTOMER_OUTSTANDING = 'sales.customer-outstanding.index';
    case CRM_CUSTOMER_OUTSTANDING = 'crm.customer-outstanding.index';
    case DELIVERY_ORDER_OUTSTANDING = 'sales.delivery-order-outstanding.index';
    case PURCHASE_INVOICES = 'purchase.invoices.index';
    case PURCHASE_INVOICES_CANCELLED = 'purchase.invoices.cancelled.index';
    case PURCHASE_INVOICES_HISTORY = 'purchase.invoices.history.index';
    case MANUAL_PURCHASE_ADJUSTMENTS = 'purchase.manual-adjustments.index';
    case MANUAL_PURCHASE_ADJUSTMENTS_CANCELLED = 'purchase.manual-adjustments.cancelled.index';
    case MANUAL_PURCHASE_ADJUSTMENTS_HISTORY = 'purchase.manual-adjustments.history.index';
    case SUPPLIER_OUTSTANDING = 'purchase.supplier-outstanding.index';
    case CRM_SUPPLIER_OUTSTANDING = 'crm.supplier-outstanding.index';
    case GRN_OUTSTANDING = 'purchase.grn-outstanding.index';

    // Bank
    case BANK_DASHBOARD = 'bank.dashboard.index';
    case BANKS = 'bank.banks.index';
    case CASH_ACCOUNTS = 'bank.cash-accounts.index';
    case E_WALLET_ACCOUNTS = 'bank.e-wallet-accounts.index';
    case PAYMENTS = 'bank.receipts.index';
    case RECEIPTS_HISTORY = 'bank.receipts.history.index';
    case RECEIPTS_CANCELLED = 'bank.receipts.cancelled.index';
    case PAYMENT_VOUCHERS = 'bank.payment-vouchers.index';
    case PAYMENT_VOUCHERS_HISTORY = 'bank.payment-vouchers.history.index';
    case PAYMENT_VOUCHERS_CANCELLED = 'bank.payment-vouchers.cancelled.index';
    case PAYMENT_DESIGNS = 'design.payment-designs.index';
    case SALARY_DESIGNS = 'design.salary-designs.index';
    case REFUNDS = 'bank.customer-refunds.index';
    case REFUNDS_HISTORY = 'bank.customer-refunds.history.index';
    case REFUNDS_CANCELLED = 'bank.customer-refunds.cancelled.index';
    case SUPPLIER_REFUNDS = 'bank.supplier-refunds.index';
    case SUPPLIER_REFUNDS_HISTORY = 'bank.supplier-refunds.history.index';
    case SUPPLIER_REFUNDS_CANCELLED = 'bank.supplier-refunds.cancelled.index';
    case CASH_TRANSFERS = 'bank.cash-transfers.index';
    case BANK_TRANSFERS = 'bank.bank-transfers.index';
    case BANK_TRANSFERS_HISTORY = 'bank.bank-transfers.history.index';
    case BANK_TRANSFERS_CANCELLED = 'bank.bank-transfers.cancelled.index';
    case CASH_LEDGERS = 'bank.cash-ledger.index';
    case BANK_LEDGERS = 'bank.bank-ledger.index';
    case E_WALLET_LEDGERS = 'bank.e-wallet-ledger.index';
    case BANK_JOURNALS = 'bank.bank-journals.index';

    // Leave
    case LEAVE_DASHBOARD = 'leave.dashboard.index';
    case HOLIDAYS = 'leave.holidays.index';
    case LEAVE_MANAGEMENTS = 'leave.leave-managements.index';
    case LEAVE_SETTINGS = 'leave.leave-settings.index';

    // Management
    case MANAGEMENT_DASHBOARD = 'management.dashboard.index';
    case DEPARTMENTS = 'management.departments.index';
    case EMPLOYEES = 'management.employees.index';
    case ORGANIZATIONS = 'management.organizations.index';
    case LETTER_TEMPLATES = 'management.letter-templates.index';

    // Menu / Navigation
    case FOLDER = 'menu.folders.index';
    case FOLDER_PERMISSIONS = 'menu.folder-permissions.index';
    case PAGES = 'menu.pages.index';
    case PAGE_PERMISSIONS = 'menu.page-permissions.index';
    case PAGE_GROUPINGS = 'menu.page-groupings.index';
    case PAGE_GROUPING_PERMISSIONS = 'menu.page-grouping-permissions.index';

    // news
    case NEWS = 'news.news.index';
    case NEWS_CATEGORIES = 'news.news-categories.index';

    // Personnel
    case PERSONNEL_DASHBOARD = 'personnel.dashboard.index';
    case CLAIMS = 'personnel.claims.index';
    case LEAVES = 'personnel.leaves.index';
    case ATTENDANCE = 'attendance.index';
    case PERSONNEL_HOLIDAYS = 'personnel.leaves.holidays';
    case MESSAGES = 'personnel.messages.index';
    case NEWS_COMMENTS = 'personnel.comments.index';
    case SALARIES = 'personnel.salaries.index';
    case TASKS = 'personnel.tasks.index';
    case PERSONNEL_TASK_HISTORY = 'personnel.tasks.history';
    case PERSONNEL_LOAN_REQUESTS = 'personnel.loan-requests.index';
    case PERSONNEL_RETURN_ASSETS = 'personnel.return-assets.index';
    case NOTIFICATIONS = 'personnel.notifications.index';

    // Salary / Payroll
    case SALARY_DASHBOARD = 'salary.dashboard.index';
    case SALARY_ANNUAL_REPORTS = 'salary.annual-reports.index';
    case SALARY_BATCH = 'salary.salary-batches.index';
    case SALARY_MANAGEMENTS = 'salary.salary-managements.index';
    case SALARY_SETTINGS = 'salary.salary-settings.index';
    case SALARY_TYPES = 'salary.salary-types.index';
    case SALARY_ATTENDANCE = 'salary.attendance.index';

    // System & Users
    case SYSTEM_DASHBOARD = 'system.dashboard.index';
    case ACTIVITY_LOGS = 'system.activity-logs.index';
    case ROLES = 'system.roles.index';
    case SETTINGS = 'system.settings.index';
    case SYSTEM_LOGS = 'system.system-logs.index';
    case SYSTEM_NOTIFICATIONS = 'system.notifications.index';
    case SYSTEM_QUEUES = 'system.queues.index';
    case SYSTEM_ROUTES = 'system.routes.index';
    case USERS = 'system.users.index';
    case SYSTEM_BACKUPS = 'system.backups.index';
    case TENANTS = 'system.tenants.index';
    case TIMEZONES = 'system.timezones.index';
    case DOCUMENT_SEQUENCES = 'system.document-sequences.index';

    // Onboarding
    case ONBOARDING_DASHBOARD = 'onboarding.dashboard.index';
    case CANDIDATES = 'onboarding.candidates.index';
    case CANDIDATE_LETTERS = 'onboarding.candidate-letters.index';
    case VACANCIES = 'onboarding.vacancies.index';
    case JOBS = 'onboarding.jobs.index';
    case JOB_CATEGORIES = 'onboarding.job-categories.index';
    case BRANCHES = 'onboarding.branches.index';

    // Tasks
    case TASK_DASHBOARD = 'task.dashboard.index';
    case TASK_MANAGEMENTS = 'task.task-managements.index';
    case TASK_HISTORY = 'task.task-managements.history';
    case TASK_MAINTENANCE = 'task.task-managements.maintenance';
    case TASK_MAINTENANCE_HISTORY = 'task.task-managements.maintenance-history';
    case TASK_WORKFLOWS = 'task.task-workflows.index';

    /**
     * Get the human-readable resource name.
     *
     * @return string Display name shown to end users.
     */
    public function displayName(): string
    {
        return match ($this) {
            // Chart
            self::CHART_DASHBOARD => 'Chart Dashboard',
            self::BALANCE_SHEET => 'Balance Sheet',
            self::BUDGET_ACTUAL => 'Budget vs Actual',
            self::CASH_FLOW => 'Cash Flow',
            self::CHARTS => 'Chart',
            self::CHART_BUDGETS => 'Chart Budget',
            self::CHART_REPORTS => 'Chart Report',
            self::CHART_SETTINGS => 'Chart Setting',
            self::CHART_TYPES => 'Chart Type',
            self::PROFIT_LOSS => 'Profit & Loss',
            self::TRIAL_BALANCE => 'Trial Balance',

            // Claim
            self::CLAIM_DASHBOARD => 'Claim Dashboard',
            self::CLAIM_BATCH => 'Claim Batch',
            self::CLAIM_MANAGEMENTS => 'Claim Management',
            self::CLAIM_SETTINGS => 'Claim Setting',

            // CRM
            self::CRM_DASHBOARD => 'CRM Dashboard',
            self::APPOINTMENTS => 'Appointment',
            self::COMPANIES => 'Company',
            self::COMPANY_FILES => 'Company File',
            self::CONTACTS => 'Contact',
            self::CUSTOMER_LETTERS => 'Customer Letter',
            self::PROJECTS => 'Project',
            self::INVOICES => 'Invoice',
            self::MULTI_INVOICES => 'Multi-Invoice',
            self::SALES_INVOICES => 'Sales Invoice',
            self::SALES_INVOICES_CANCELLED => 'Cancelled Sales Invoice',
            self::SALES_INVOICES_HISTORY => 'Sales Invoice History',
            self::MANUAL_SALES_ADJUSTMENTS => 'Manual Sales Adjustment',
            self::MANUAL_SALES_ADJUSTMENTS_CANCELLED => 'Cancelled Manual Sales Adjustment',
            self::MANUAL_SALES_ADJUSTMENTS_HISTORY => 'Manual Sales Adjustment History',
            self::MANUAL_SALES_INVOICES => 'Manual Sales Invoice',
            self::MANUAL_SALES_INVOICES_CANCELLED => 'Cancelled Manual Sales Invoice',
            self::MANUAL_SALES_INVOICES_HISTORY => 'Manual Sales Invoice History',
            self::POS => 'Point of Sale',
            self::CUSTOMER_OUTSTANDING, self::CRM_CUSTOMER_OUTSTANDING => 'Customer Outstanding',
            self::SUPPLIER_OUTSTANDING, self::CRM_SUPPLIER_OUTSTANDING => 'Supplier Outstanding',
            self::GRN_OUTSTANDING => 'GRN Outstanding',
            self::DELIVERY_ORDER_OUTSTANDING => 'Delivery Order Outstanding',
            self::INVENTORY_DASHBOARD => 'Inventory Dashboard',
            self::BRANDS => 'Brand',
            self::PRODUCT_CATEGORIES => 'Product Category',
            self::PRODUCT_TYPES => 'Product Type',
            self::PRODUCTS => 'Product',
            self::STOCK_LOCATIONS => 'Stock Location',
            self::STOCK_STRUCTURE => 'Stock Structure',
            self::STOCK_TRANSFERS => 'Stock Transfer',
            self::STOCK_TAKES => 'Stock Take',

            // YEAR
            self::PERIODS => 'Financial Period',
            self::PERIOD_CLOSING => 'Period Closing',
            self::YEARS => 'Financial Year',
            self::YEAR_CLOSING => 'Year Closing',

            // Finance
            self::FINANCE_DASHBOARD => 'Finance Dashboard',

            self::JOURNAL_ENTRIES => 'Journal Entry',
            self::JOURNAL_ENTRIES_CANCELLED => 'Cancelled Journal Entry',
            self::JOURNAL_ENTRIES_HISTORY => 'Journal Entry History',
            self::LEDGERS => 'Ledger',
            self::AR_LEDGERS => 'AR Ledger',
            self::AP_LEDGERS => 'AP Ledger',
            self::PRODUCT_LEDGERS => 'Product Ledger',
            self::BANK_RECONCILIATIONS => 'Bank Reconciliation',
            self::TAXES => 'Tax',
            self::DISCOUNTS => 'Discount',
            self::CURRENCIES => 'Currency',

            // Fixed Assets
            self::FIXED_ASSET_DASHBOARD => 'Fixed Asset Dashboard',
            self::FIXED_ASSETS => 'Fixed Asset',
            self::FIXED_ASSET_CATEGORIES => 'Fixed Asset Category',
            self::FIXED_ASSET_DEPRECIATION => 'Fixed Asset Depreciation',
            self::FIXED_ASSET_VALUATIONS => 'Fixed Asset Valuation',
            self::FIXED_ASSET_WRITE_OFFS => 'Fixed Asset Write-off',
            self::FIXED_ASSET_DISPOSALS => 'Fixed Asset Disposal',
            self::FIXED_ASSET_DISPOSALS_APPROVED => 'Approved Fixed Asset Disposal',
            self::FIXED_ASSET_DISPOSALS_CANCELLED => 'Cancelled Fixed Asset Disposal',
            self::FIXED_ASSET_LOANS => 'Fixed Asset Loan',
            self::FIXED_ASSET_LOANS_APPROVED => 'Approved Fixed Asset Loan',
            self::FIXED_ASSET_LOANS_CANCELLED => 'Cancelled Fixed Asset Loan',
            self::FIXED_ASSET_MAINTENANCE => 'Fixed Asset Maintenance',
            self::FIXED_ASSET_SETTINGS => 'Fixed Asset Setting',

            // Billing
            self::RECURRING_SCHEDULES => 'Recurring Schedule',
            self::RECURRING_POSTING => 'Recurring Posting',
            self::RECURRING_POSTING_CANCELLED => 'Cancelled Recurring Posting',
            self::RECURRING_POSTING_HISTORY => 'Recurring Posting History',

            // Invoice
            self::PURCHASE_REQUESTS => 'Purchase Request',
            self::PURCHASE_QUOTATIONS => 'Supplier Quotation',
            self::PURCHASE_ORDER_SETTINGS => 'Purchase Order Setting',
            self::PURCHASE_ORDERS => 'Purchase Order',
            self::GOODS_RECEIPTS => 'Goods Received Note',
            self::GOODS_RECEIPT_ADJUSTMENTS => 'Goods Receipt Adjustment',
            self::GOODS_RECEIPT_ADJUSTMENTS_HISTORY => 'Goods Receipt Adjustment History',
            self::GOODS_RECEIPT_ADJUSTMENTS_CANCELLED => 'Cancelled Goods Receipt Adjustment',
            self::RMAS => 'RMA',
            self::QUOTATIONS => 'Quotation',
            self::PRODUCT_PACKAGES => 'Product Package',
            self::SALES_ORDERS => 'Sales Order',
            self::SALES_DELIVERY_ORDERS => 'Delivery Order',
            self::PURCHASE_RETURNS => 'Purchase Return',
            self::INVOICE_DESIGNS => 'Invoice Design',
            self::PURCHASE_INVOICES => 'Purchase Invoice',
            self::PURCHASE_INVOICES_CANCELLED => 'Cancelled Purchase Invoice',
            self::PURCHASE_INVOICES_HISTORY => 'Purchase Invoice History',
            self::MANUAL_PURCHASE_ADJUSTMENTS => 'Manual Purchase Adjustment',
            self::MANUAL_PURCHASE_ADJUSTMENTS_CANCELLED => 'Cancelled Manual Purchase Adjustment',
            self::MANUAL_PURCHASE_ADJUSTMENTS_HISTORY => 'Manual Purchase Adjustment History',

            // Bank
            self::BANK_DASHBOARD => 'Bank Dashboard',
            self::BANKS => 'Bank Account',
            self::CASH_ACCOUNTS => 'Cash Account',
            self::E_WALLET_ACCOUNTS => 'E-Wallet Account',
            self::PAYMENTS => 'Receipt',
            self::RECEIPTS_HISTORY => 'Receipt History',
            self::RECEIPTS_CANCELLED => 'Cancelled Receipt',
            self::PAYMENT_VOUCHERS => 'Payment Voucher',
            self::PAYMENT_VOUCHERS_HISTORY => 'Payment Voucher History',
            self::PAYMENT_VOUCHERS_CANCELLED => 'Cancelled Payment Voucher',
            self::PAYMENT_DESIGNS => 'Payment Design',
            self::SALARY_DESIGNS => 'Salary Design',
            self::REFUNDS => 'Refund',
            self::REFUNDS_HISTORY => 'Refund History',
            self::REFUNDS_CANCELLED => 'Cancelled Refund',
            self::SUPPLIER_REFUNDS => 'Supplier Refund',
            self::SUPPLIER_REFUNDS_HISTORY => 'Supplier Refund History',
            self::SUPPLIER_REFUNDS_CANCELLED => 'Cancelled Supplier Refund',
            self::CASH_TRANSFERS => 'Cash Transfer',
            self::BANK_TRANSFERS => 'Bank Transfer',
            self::BANK_TRANSFERS_HISTORY => 'Bank Transfer History',
            self::BANK_TRANSFERS_CANCELLED => 'Cancelled Bank Transfer',
            self::CASH_LEDGERS => 'Cash Ledger',
            self::BANK_LEDGERS => 'Bank Ledger',
            self::E_WALLET_LEDGERS => 'E-Wallet Ledger',
            self::BANK_JOURNALS => 'Bank Journal',

            // Leave
            self::LEAVE_DASHBOARD => 'Leave Dashboard',
            self::HOLIDAYS => 'Holiday',
            self::LEAVE_MANAGEMENTS => 'Leave Management',
            self::LEAVE_SETTINGS => 'Leave Setting',

            // Management
            self::MANAGEMENT_DASHBOARD => 'Management Dashboard',
            self::DEPARTMENTS => 'Department',
            self::EMPLOYEES => 'Employee',
            self::ORGANIZATIONS => 'Organisation',
            self::LETTER_TEMPLATES => 'Letter Template',

            // Menu / Navigation
            self::FOLDER => 'Folder',
            self::FOLDER_PERMISSIONS => 'Folder Permission',
            self::PAGES => 'Page',
            self::PAGE_PERMISSIONS => 'Page Permission',
            self::PAGE_GROUPINGS => 'Page Grouping',
            self::PAGE_GROUPING_PERMISSIONS => 'Page Grouping Permission',

            // news
            self::NEWS => 'news',
            self::NEWS_CATEGORIES => 'Category',

            // Personnel
            self::PERSONNEL_DASHBOARD => 'Personnel Dashboard',
            self::CLAIMS => 'Claim',
            self::LEAVES => 'Leave',
            self::ATTENDANCE => 'Attendance',
            self::PERSONNEL_HOLIDAYS => 'Holiday Calendar',
            self::MESSAGES => 'Chat',
            self::NEWS_COMMENTS => 'news Comment',
            self::SALARIES => 'Salary',
            self::TASKS => 'Task',
            self::PERSONNEL_TASK_HISTORY => 'My Task History',
            self::PERSONNEL_LOAN_REQUESTS => 'Loan Request',
            self::PERSONNEL_RETURN_ASSETS => 'Return Asset',
            self::NOTIFICATIONS => 'User Notification',

            // Salary / Payroll
            self::SALARY_DASHBOARD => 'Payroll Dashboard',
            self::SALARY_ANNUAL_REPORTS => 'Company Tax Information',
            self::SALARY_BATCH => 'Payroll Batch',
            self::SALARY_MANAGEMENTS => 'Salary Management',
            self::SALARY_SETTINGS => 'Salary Setting',
            self::SALARY_TYPES => 'Salary Type',
            self::SALARY_ATTENDANCE => 'Salary Attendance',

            // System & Users
            self::SYSTEM_DASHBOARD => 'System Dashboard',
            self::ACTIVITY_LOGS => 'Activity Logs',
            self::ROLES => 'Role',
            self::SETTINGS => 'Setting',
            self::SYSTEM_LOGS => 'System Logs',
            self::SYSTEM_NOTIFICATIONS => 'Notification',
            self::SYSTEM_QUEUES => 'System Queues',
            self::SYSTEM_ROUTES => 'System Routes',
            self::USERS => 'User',
            self::SYSTEM_BACKUPS => 'Backup & Restore',
            self::TENANTS => 'Tenant',
            self::TIMEZONES => 'Timezone',
            self::DOCUMENT_SEQUENCES => 'Document Sequence',

            // Onboarding
            self::ONBOARDING_DASHBOARD => 'Onboarding Dashboard',
            self::CANDIDATES => 'Candidate',
            self::CANDIDATE_LETTERS => 'Candidate Letter',
            self::VACANCIES => 'Vacancy',
            self::JOBS => 'Job',
            self::JOB_CATEGORIES => 'Job Category',
            self::BRANCHES => 'Branch',

            // Tasks
            self::TASK_DASHBOARD => 'Task Dashboard',
            self::TASK_MANAGEMENTS => 'Task RMA',
            self::TASK_HISTORY => 'Task RMA History',
            self::TASK_MAINTENANCE => 'Maintenance Task',
            self::TASK_MAINTENANCE_HISTORY => 'Maintenance Task History',
            self::TASK_WORKFLOWS => 'Task Workflow',
        };
    }
}
