# Change List — Redfluz Accounting Free

Version 1 (main) → Version 2 (new-update) · 7 commits · 892 files changed · +51,821 / −35,476 lines

## 1. Manual Adjustments

- **[New] Manual Sales Adjustments** — New manual-sales-adjustments module: create, edit, approve, reverse, bulk approve/cancel, History and Cancelled pages (with uncancel), per-document PDF, COA picker dialog and links to sales invoices.
  - Evidence: `routes/modules/sales.php`, `app/Http/Controllers/Sales/ManualSalesAdjustmentController.php`, `resources/views/sales/manual-adjustments/`, `database/migrations/2026_11_09_150000_create_manual_adjustment_tables.php`
- **[New] Manual Purchase Adjustments** — manual-purchase-adjustments module with the same capabilities: full CRUD, approve/reverse, bulk actions, History, Cancelled + uncancel, PDF, COA picker and purchase-invoice options.
  - Evidence: `routes/modules/purchase.php`, `app/Http/Controllers/Purchase/ManualPurchaseAdjustmentController.php`, `resources/views/purchase/manual-adjustments/`
- **[Replacement] Legacy adjustment modules replaced** — SalesAdjustment and PurchaseAdjustment (controllers, models, lines, migrations and chart seeders) removed entirely; replaced by manual adjustments based on user-selected chart accounts.
  - Evidence: `app/Http/Controllers/Sales/SalesAdjustmentController.php (dibuang)`, `database/migrations/2026_01_01_00046_create_sales_adjustments_tables.php (dibuang)`, `database/migrations/2026_01_01_00049_create_purchase_adjustments_tables.php (dibuang)`

## 2. Bank Transfers

- **[New] History & Cancelled for money transfers** — New history and cancelled pages for bank-transfers, including detail views and uncancel — completing the lifecycle of transfer documents between bank, cash and e-wallet accounts.
  - Evidence: `app/Http/Controllers/Bank/Transfer/HistoryController.php`, `app/Http/Controllers/Bank/Transfer/CancelledController.php`, `resources/views/bank/bank-transfers/history/`, `resources/views/bank/bank-transfers/cancelled/`

## 3. Posting Engine & Ledgers

- **[Replacement] Unified posting engine (Posting)** — A single Posting class replaces InvoiceLedgerPoster, PaymentLedgerPoster and PostingLeg; every document now builds DTOs and writes ledgers through one flow, with the ResolvesLedgerSources trait for ledger sources.
  - Evidence: `app/Support/Posting/Posting.php`, `app/Support/Traits/ResolvesLedgerSources.php`, `app/Support/Posting/InvoiceLedgerPoster.php (dibuang)`
- **[Replacement] Money document schema simplified** — Per-document line and chart tables for money documents removed (receipts, vouchers, refunds, invoice charts); receipts now store money account columns on the header; reversal links replaced by document_original; inventory deduction ledger labels revised.
  - Evidence: `2026_11_08_103643_drop_bank_document_line_tables.php`, `2026_11_12_000001_add_money_account_columns_to_receipts_table.php`, `2026_11_12_000003_replace_reversal_links_with_document_original.php`, `2026_11_12_000004_relabel_inventory_deduction_ledgers.php`
- **[New] Separate money ledgers per account kind** — Distinct BankLedger, CashLedger and EWalletLedger models — each with its own Debit/Credit ledger grid for bank, cash and e-wallet, without a single money-ledgers umbrella.
  - Evidence: `app/Models/Bank/BankLedger.php`, `app/Models/Bank/CashLedger.php`, `app/Models/Bank/EWalletLedger.php`

## 4. Financial Year & Periods

- **[New] Posting-date control by year/period** — Year and Period models plus the ChecksFinancialPostingDate trait: postings rejected in closed years/periods; strict_journal_posting mode limits journals to the current period; form date pickers automatically bounded to the open range.
  - Evidence: `app/Models/Year/Year.php`, `app/Models/Year/Period.php`, `app/Traits/ChecksFinancialPostingDate.php`

## 5. PDF & Exports

- **[New] Per-document PDF on show/history/cancelled** — Separate PDF routes and templates per document: receipts, payment vouchers, journals, sales invoices, purchase invoices, GRN adjustments and manual adjustments — each document type has its own print file.
  - Evidence: `routes/modules/bank.php`, `resources/views/purchase/goods-receipt-adjustments/pdf.blade.php`, `resources/views/sales/manual-adjustments/pdf.blade.php`, `resources/views/purchase/manual-adjustments/pdf.blade.php`
- **[Replacement] Bulk list exports replaced** — export-excel/export-pdf routes on list pages (history, cancelled, charts, P&L, balance sheet, trial balance, ledgers, outstanding) removed; exports now go through per-document PDFs.
  - Evidence: `routes/modules/bank.php`, `routes/modules/chart.php`, `routes/modules/finance.php`

## 6. Search & Pickers

- **[New] Product search inside document forms** — products/search endpoints for quotations, sales orders, sales invoices and GRNs, plus a product picker partial (_product-picker) — products chosen by search instead of a long flat list.
  - Evidence: `routes/modules/sales.php`, `routes/modules/purchase.php`, `resources/views/sales/invoices/_product-picker.blade.php`
- **[New] COA picker dialog** — Chart-of-accounts selection in journals and manual adjustments uses a searchable COA picker dialog (charts/search), replacing flat dropdowns.
  - Evidence: `resources/views/finance/journal-entries/_coa-picker.blade.php`, `resources/views/sales/manual-adjustments/_coa-picker.blade.php`, `resources/views/purchase/manual-adjustments/_coa-picker.blade.php`

## 7. Document Actions

- **[Improvement] Dedicated Form Requests for bulk & reversal actions** — Separate Request classes for batch approve/cancel and reverse on sales/purchase invoices, journals, purchase returns and manual adjustments; customer/supplier outstanding pages also get their own Requests.
  - Evidence: `app/Http/Requests/Sales/SalesInvoice/BatchApproveSalesInvoiceRequest.php`, `app/Http/Requests/Finance/JournalEntry/ReverseJournalEntryRequest.php`, `app/Http/Requests/Sales/CustomerOutstanding/`
- **[New] Revert Sales Order to draft** — Approved sales orders can be returned to draft (revert) as long as no sales invoice or delivery order has been generated — with transaction locking and status checks.
  - Evidence: `routes/modules/sales.php`, `app/Http/Controllers/Sales/SalesOrderController.php@revert`
- **[New] Document attachment downloads** — New attachment routes to download sales order and purchase invoice attachments straight from the document page.
  - Evidence: `sales-orders/{salesOrder}/attachment`, `purchase-invoices/{documentNumber}/attachment`

## 8. Forms & Detail Views

- **[Improvement] Tabbed create/edit forms across modules** — Long forms reorganised into Bootstrap-tabbed _form partials: bank, chart of accounts, CRM, finance, inventory, personnel, purchase, sales and system — one tidy edit page instead of a long scroll.
  - Evidence: `resources/views/sales/invoices/_form.blade.php`, `resources/views/bank/receipt/_form.blade.php`, `resources/views/inventory/products/_form.blade.php`
- **[New] Statement table on company page** — The _statement-table partial shows transaction statements (debtors/creditors) directly on the company show page.
  - Evidence: `resources/views/crm/companies/_statement-table.blade.php`, `resources/views/crm/companies/show.blade.php`

## 9. Code Structure & Enums

- **[Improvement] Per-module enum namespaces (PSR-4)** — Enums reorganised into module namespaces (Bank, ChartOfAccount, Crm, Finance, Inventory, Purchase, Sales) per PSR-4; legacy names such as LedgerStatus become DocumentStatus.
  - Evidence: `app/Enums/Finance/DocumentStatus.php`, `app/Enums/Purchase/PurchaseOrderStatus.php`, `app/Enums/Sales/SalesOrderStatus.php`
- **[New] New enums added** — New enums: PaymentFor, GeneralLedgerEntryType, LedgerSubType, PaymentTransferType, PurchaseOrderStatus, PurchaseQuotationStatus, PurchaseRequestStatus, AcquisitionType, FollowUpType, AppointmentStatus, NewsStatus and LikeStatus.
  - Evidence: `app/Enums/Bank/PaymentFor.php`, `app/Enums/Finance/GeneralLedgerEntryType.php`, `app/Enums/News/NewsStatus.php`

## 10. Theme, Language & Tooling

- **[Improvement] Theme & styling improvements** — SCSS updates for the glass theme, Bootstrap 6 palettes, app background and themes: glass stays legible over wallpapers, neutral card edges and consistent translucent surfaces.
  - Evidence: `resources/sass/_glass.scss`, `resources/sass/_bs6-palettes.scss`, `resources/sass/_app-background.scss`, `resources/sass/_theme.scss`
- **[New] New translations & seeders** — ms/en/id language files for manual sales/purchase adjustments and posting messages; ManualAdjustmentSeeder adds adjustment demo data; other seeders updated for the new posting engine.
  - Evidence: `lang/ms/manual_sales_adjustments.php`, `lang/ms/posting.php`, `database/seeders/ManualAdjustmentSeeder.php`
- **[Improvement] Static analysis & lockfiles** — PHPStan configuration (phpstan.neon) added for static analysis; composer.lock and package-lock.json committed for reproducible installs.
  - Evidence: `phpstan.neon`, `composer.lock`, `package-lock.json`
