# Senarai Perubahan — Redfluz Accounting Free

Versi 1 (main) → Versi 2 (new-update) · 7 commit · 892 fail berubah · +51,821 / −35,476 baris

## 1. Pelarasan Manual

- **[Baharu] Pelarasan Jualan Manual** — Modul baharu manual-sales-adjustments: cipta, edit, lulus, reverse, lulus/batal kelompok, halaman History dan Cancelled (dengan uncancel), PDF per dokumen, dialog pemilih COA dan pautan ke invois jualan.
  - Bukti: `routes/modules/sales.php`, `app/Http/Controllers/Sales/ManualSalesAdjustmentController.php`, `resources/views/sales/manual-adjustments/`, `database/migrations/2026_11_09_150000_create_manual_adjustment_tables.php`
- **[Baharu] Pelarasan Belian Manual** — Modul manual-purchase-adjustments dengan keupayaan sama: CRUD lengkap, lulus/reverse, tindakan kelompok, History, Cancelled + uncancel, PDF, pemilih COA dan pilihan invois belian.
  - Bukti: `routes/modules/purchase.php`, `app/Http/Controllers/Purchase/ManualPurchaseAdjustmentController.php`, `resources/views/purchase/manual-adjustments/`
- **[Penggantian] Modul pelarasan lama digantikan** — SalesAdjustment dan PurchaseAdjustment (controller, model, line, migration dan seeder chart) dibuang sepenuhnya; digantikan oleh pelarasan manual berasaskan akaun carta pilihan pengguna.
  - Bukti: `app/Http/Controllers/Sales/SalesAdjustmentController.php (dibuang)`, `database/migrations/2026_01_01_00046_create_sales_adjustments_tables.php (dibuang)`, `database/migrations/2026_01_01_00049_create_purchase_adjustments_tables.php (dibuang)`

## 2. Pemindahan Bank

- **[Baharu] History & Cancelled untuk pemindahan wang** — Halaman history dan cancelled baharu untuk bank-transfers, termasuk paparan detail dan uncancel — melengkapkan kitaran hayat dokumen pemindahan antara akaun bank, tunai dan e-dompet.
  - Bukti: `app/Http/Controllers/Bank/Transfer/HistoryController.php`, `app/Http/Controllers/Bank/Transfer/CancelledController.php`, `resources/views/bank/bank-transfers/history/`, `resources/views/bank/bank-transfers/cancelled/`

## 3. Enjin Posting & Ledger

- **[Penggantian] Enjin posting bersepadu (Posting)** — Kelas Posting tunggal menggantikan InvoiceLedgerPoster, PaymentLedgerPoster dan PostingLeg; semua dokumen kini membina DTO dan menulis ledger melalui satu aliran, dengan trait ResolvesLedgerSources untuk sumber ledger.
  - Bukti: `app/Support/Posting/Posting.php`, `app/Support/Traits/ResolvesLedgerSources.php`, `app/Support/Posting/InvoiceLedgerPoster.php (dibuang)`
- **[Penggantian] Skema dokumen wang diringkaskan** — Jadual line dan chart per dokumen wang dibuang (resit, baucar, refund, chart invois); resit kini menyimpan kolom akaun wang terus pada header; pautan reversal digantikan dengan document_original; label ledger tolakan inventori disemak semula.
  - Bukti: `2026_11_08_103643_drop_bank_document_line_tables.php`, `2026_11_12_000001_add_money_account_columns_to_receipts_table.php`, `2026_11_12_000003_replace_reversal_links_with_document_original.php`, `2026_11_12_000004_relabel_inventory_deduction_ledgers.php`
- **[Baharu] Ledger wang berasingan mengikut jenis akaun** — Model BankLedger, CashLedger dan EWalletLedger berasingan — grid ledger Debit/Kredit sendiri untuk bank, tunai dan e-dompet, tanpa satu umbrella money ledgers.
  - Bukti: `app/Models/Bank/BankLedger.php`, `app/Models/Bank/CashLedger.php`, `app/Models/Bank/EWalletLedger.php`

## 4. Tahun & Tempoh Kewangan

- **[Baharu] Kawalan tarikh posting mengikut tahun/tempoh** — Model Year dan Period plus trait ChecksFinancialPostingDate: posting ditolak pada tahun/tempoh tertutup; mod strict_journal_posting menghadkan jurnal kepada tempoh semasa; picker tarikh borang automatik terhad kepada julat terbuka.
  - Bukti: `app/Models/Year/Year.php`, `app/Models/Year/Period.php`, `app/Traits/ChecksFinancialPostingDate.php`

## 5. PDF & Eksport

- **[Baharu] PDF per dokumen pada show/history/cancelled** — Route dan template PDF berasingan bagi setiap dokumen: resit, baucar pembayaran, jurnal, invois jualan, invois belian, pelarasan GRN dan pelarasan manual — setiap jenis dokumen mempunyai fail print sendiri.
  - Bukti: `routes/modules/bank.php`, `resources/views/purchase/goods-receipt-adjustments/pdf.blade.php`, `resources/views/sales/manual-adjustments/pdf.blade.php`, `resources/views/purchase/manual-adjustments/pdf.blade.php`
- **[Penggantian] Eksport pukal senarai digantikan** — Route export-excel/export-pdf pada halaman senarai (history, cancelled, carta, P&L, kunci kira-kira, trial balance, ledger, outstanding) dibuang; eksport kini melalui PDF per dokumen.
  - Bukti: `routes/modules/bank.php`, `routes/modules/chart.php`, `routes/modules/finance.php`

## 6. Carian & Pemilih

- **[Baharu] Carian produk dalam borang dokumen** — Endpoint products/search untuk quotation, sales order, invois jualan dan GRN, plus partial pemilih produk (_product-picker) — pilihan produk melalui carian, bukan senarai flat yang panjang.
  - Bukti: `routes/modules/sales.php`, `routes/modules/purchase.php`, `resources/views/sales/invoices/_product-picker.blade.php`
- **[Baharu] Dialog pemilih COA** — Pemilihan akaun carta dalam jurnal dan pelarasan manual menggunakan dialog pemilih COA dengan carian (charts/search), menggantikan dropdown rata.
  - Bukti: `resources/views/finance/journal-entries/_coa-picker.blade.php`, `resources/views/sales/manual-adjustments/_coa-picker.blade.php`, `resources/views/purchase/manual-adjustments/_coa-picker.blade.php`

## 7. Tindakan Dokumen

- **[Naik taraf] Form Request khusus untuk tindakan kelompok & pembalikan** — Kelas Request berasingan untuk batch approve/cancel dan reverse bagi invois jualan/belian, jurnal, return belian dan pelarasan manual; halaman outstanding pelanggan/pembekal juga mendapat Request sendiri.
  - Bukti: `app/Http/Requests/Sales/SalesInvoice/BatchApproveSalesInvoiceRequest.php`, `app/Http/Requests/Finance/JournalEntry/ReverseJournalEntryRequest.php`, `app/Http/Requests/Sales/CustomerOutstanding/`
- **[Baharu] Revert Sales Order ke draf** — Sales order yang telah diluluskan boleh dikembalikan ke draf (revert) selagi tiada invois jualan atau delivery order terjana — dengan kunci transaksi dan semakan status.
  - Bukti: `routes/modules/sales.php`, `app/Http/Controllers/Sales/SalesOrderController.php@revert`
- **[Baharu] Muat turun lampiran dokumen** — Route attachment baharu untuk memuat turun lampiran sales order dan invois belian terus dari halaman dokumen.
  - Bukti: `sales-orders/{salesOrder}/attachment`, `purchase-invoices/{documentNumber}/attachment`

## 8. Borang & Paparan Detail

- **[Naik taraf] Borang create/edit bertab merentas modul** — Borang panjang disusun semula kepada partial _form dengan tab Bootstrap: bank, carta akaun, CRM, kewangan, inventori, personel, belian, jualan dan sistem — satu halaman edit yang kemas, bukan scroll panjang.
  - Bukti: `resources/views/sales/invoices/_form.blade.php`, `resources/views/bank/receipt/_form.blade.php`, `resources/views/inventory/products/_form.blade.php`
- **[Baharu] Jadual penyata pada halaman syarikat** — Partial _statement-table memaparkan penyata urus niaga (penghutang/pemiutang) terus pada halaman show syarikat.
  - Bukti: `resources/views/crm/companies/_statement-table.blade.php`, `resources/views/crm/companies/show.blade.php`

## 9. Struktur Kod & Enum

- **[Naik taraf] Namespace enum mengikut modul (PSR-4)** — Enum disusun semula ke namespace modul (Bank, ChartOfAccount, Crm, Finance, Inventory, Purchase, Sales) selaras standard PSR-4; nama lama seperti LedgerStatus menjadi DocumentStatus.
  - Bukti: `app/Enums/Finance/DocumentStatus.php`, `app/Enums/Purchase/PurchaseOrderStatus.php`, `app/Enums/Sales/SalesOrderStatus.php`
- **[Baharu] Enum baharu disediakan** — Enum baharu ditambah: PaymentFor, GeneralLedgerEntryType, LedgerSubType, PaymentTransferType, PurchaseOrderStatus, PurchaseQuotationStatus, PurchaseRequestStatus, AcquisitionType, FollowUpType, AppointmentStatus, NewsStatus dan LikeStatus.
  - Bukti: `app/Enums/Bank/PaymentFor.php`, `app/Enums/Finance/GeneralLedgerEntryType.php`, `app/Enums/News/NewsStatus.php`

## 10. Tema, Bahasa & Alatan

- **[Naik taraf] Penambahbaikan tema & gaya** — Kemas kini SCSS untuk tema glass, palet Bootstrap 6, latar belakang app dan tema: glass kekal jelas atas wallpaper, tepi kad neutral dan permukaan telus yang konsisten.
  - Bukti: `resources/sass/_glass.scss`, `resources/sass/_bs6-palettes.scss`, `resources/sass/_app-background.scss`, `resources/sass/_theme.scss`
- **[Baharu] Terjemahan & seeder baharu** — Fail bahasa ms/en/id untuk pelarasan jualan/belian manual dan mesej posting; ManualAdjustmentSeeder menambah data demo pelarasan; seeder lain dikemas kini mengikut enjin posting baharu.
  - Bukti: `lang/ms/manual_sales_adjustments.php`, `lang/ms/posting.php`, `database/seeders/ManualAdjustmentSeeder.php`
- **[Naik taraf] Analisis statik & lockfile** — Konfigurasi PHPStan (phpstan.neon) ditambah untuk analisis statik; composer.lock dan package-lock.json dikomit untuk pemasangan berulang yang tepat.
  - Bukti: `phpstan.neon`, `composer.lock`, `package-lock.json`
