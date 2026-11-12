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

use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use Database\Seeders\Concerns\SeedsDocumentSequences;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use stdClass;
use Throwable;

/**
 * Seed traceable document flows to the general, receivable and payable ledgers.
 *
 * Sales documents write their rows to ar_ledgers and purchase documents to
 * ap_ledgers, and they write the same rows the general ledger holds: every side
 * of a posted entry appears in both ledgers, against the account that side was
 * booked to. A statement read from the sub-ledger therefore holds exactly what
 * the same document holds in the general ledger.
 */
class SubLedgerSeeder extends Seeder
{
    use SeedsDocumentSequences;

    /**
     * Seed sales, purchase, payment, and refund document flows.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        $this->seedDocumentSequences();

        $company = DB::table('companies')
            ->where('is_debtor', true)
            ->orderBy('created_at')
            ->first(['id', 'name']);
        $creditorCompanies = DB::table('companies')
            ->where('is_creditor', true)
            ->orderBy('created_at')
            ->get(['id', 'name'])
            ->keyBy('name');
        $product = DB::table('products')
            ->where('sku', 'HP-PAV-15')
            ->whereNull('deleted_at')
            ->first(['id', 'name', 'warranty_months']);

        if ($company === null || $product === null || $creditorCompanies->isEmpty()) {
            throw new LogicException('Client ledger flow seeding requires seeded companies and products.');
        }

        $contactId = DB::table('contacts')
            ->where('company_id', $company->id)
            ->orderBy('created_at')
            ->value('id');
        $charts = DB::table('charts')
            ->whereIn('code', ['1100', '1110', '1120', '1200', '2000', '4100', '5000'])
            ->get(['id', 'code', 'name'])
            ->keyBy('code');

        foreach (['1100', '1110', '1120', '1200', '2000', '4100', '5000'] as $requiredCode) {
            if (! $charts->has($requiredCode)) {
                throw new LogicException("Chart account $requiredCode is required for client ledger flow seeding.");
            }
        }

        $currencyId = DB::table('currencies')->orderBy('created_at')->value('id');

        DB::transaction(function () use ($charts, $company, $product, $creditorCompanies, $currencyId): void {
            foreach ($this->documents() as $document) {
                $party = $company;
                if (isset($document['party_name'])) {
                    $party = $creditorCompanies->get($document['party_name']);
                    if ($party === null) {
                        throw new LogicException("Creditor {$document['party_name']} is required for client ledger flow seeding.");
                    }
                }

                $this->seedDocument(
                    $document,
                    $charts,
                    $party,
                    $product,
                    $currencyId
                );
            }
        });

        // Create cancelled sales invoices in the sales_invoices table for testing
        $this->seedCancelledSalesInvoices($company, $contactId, $product);

        // Pending and posted headers for the same demo invoices whose ledger
        // rows are seeded above (IV-DEMO-0001 posted, IV-DEMO-0002 pending).
        // The sales invoice lists read the sales_invoices table, so without
        // these headers the pending/history grids stay empty.
        $this->seedDemoSalesInvoiceHeaders($company, $contactId);
    }

    /**
     * Seed cancelled sales invoices for testing the cancelled list.
     */
    private function seedCancelledSalesInvoices(stdClass $company, ?string $contactId, stdClass $product): void
    {
        $now = now();

        foreach (
            [
                [
                    'document_number' => 'IV-CANCEL-0001',
                    'date' => '2026-07-10',
                    'subtotal' => '3600.00',
                    'total_amount' => '3600.00',
                    'notes' => 'Cancelled sales invoice — customer changed mind before approval.',
                ],
                [
                    'document_number' => 'IV-CANCEL-0002',
                    'date' => '2026-08-15',
                    'subtotal' => '5200.00',
                    'total_amount' => '5200.00',
                    'notes' => 'Cancelled sales invoice — duplicate entry voided.',
                ],
            ] as $invoice
        ) {
            $existingId = DB::table('sales_invoices')
                ->where('document_number', $invoice['document_number'])
                ->value('id');

            DB::table('sales_invoices')->updateOrInsert(
                ['document_number' => $invoice['document_number']],
                [
                    'id' => $existingId ?? (string) Str::uuid7(),
                    'company_id' => $company->id,
                    'contact_id' => $contactId,
                    'sales_type' => 'product',
                    'reference_number' => $invoice['document_number'],
                    'company_name' => $company->name,
                    'contact_name' => null,
                    'date' => $invoice['date'],
                    'due_date' => null,
                    'status' => 'cancelled',
                    'attachment_path' => null,
                    'subtotal' => $invoice['subtotal'],
                    'total_amount' => $invoice['total_amount'],
                    'notes' => $invoice['notes'],
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );
        }
    }

    /**
     * Seed pending/posted sales invoice headers for the demo invoices.
     *
     * The ledger rows for IV-DEMO-0001/IV-DEMO-0002 are seeded in
     * seedDocument(), but the pending/history grids read the
     * sales_invoices headers table, which otherwise holds only the
     * cancelled rows. Amounts mirror the seeded ledger rows.
     */
    private function seedDemoSalesInvoiceHeaders(stdClass $company, ?string $contactId): void
    {
        $now = now();
        $product = DB::table('products')->where('sku', 'HP-PAV-15')->first(['id', 'name', 'warranty_months']);
        if ($product === null) {
            throw new LogicException('Demo sales invoices require a seeded product.');
        }

        foreach (
            [
                [
                    'document_number' => 'IV-DEMO-0001',
                    'date' => '2026-08-01',
                    'status' => 'posted',
                    'subtotal' => '4500.00',
                    'total_amount' => '4500.00',
                    'notes' => 'Demo posted sales invoice — HP Pavilion 15 Laptop.',
                ],
                [
                    'document_number' => 'IV-DEMO-0002',
                    'date' => '2026-08-25',
                    'status' => 'pending',
                    'subtotal' => '3200.00',
                    'total_amount' => '3200.00',
                    'notes' => 'Demo pending sales invoice — awaiting approval.',
                ],
                [
                    'document_number' => 'IV-RMA-DEMO-0001',
                    'date' => now()->subDays(45)->toDateString(),
                    'status' => 'posted',
                    'subtotal' => '4500.00',
                    'total_amount' => '4500.00',
                    'notes' => 'Posted hardware invoice reserved for the repeatable RMA inspection and repair demo.',
                ],
            ] as $invoice
        ) {
            $existingId = DB::table('sales_invoices')
                ->where('document_number', $invoice['document_number'])
                ->value('id');

            DB::table('sales_invoices')->updateOrInsert(
                ['document_number' => $invoice['document_number']],
                [
                    'id' => $existingId ?? (string) Str::uuid7(),
                    'company_id' => $company->id,
                    'contact_id' => $contactId,
                    'sales_type' => 'product',
                    'reference_number' => $invoice['document_number'],
                    'company_name' => $company->name,
                    'contact_name' => null,
                    'date' => $invoice['date'],
                    'due_date' => null,
                    'status' => $invoice['status'],
                    'attachment_path' => null,
                    'subtotal' => $invoice['subtotal'],
                    'total_amount' => $invoice['total_amount'],
                    'notes' => $invoice['notes'],
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );

            $invoiceId = DB::table('sales_invoices')
                ->where('document_number', $invoice['document_number'])
                ->value('id');
            $lineId = DB::table('sales_invoice_lines')
                ->where('sales_invoice_id', $invoiceId)
                ->where('product_id', $product->id)
                ->value('id') ?? (string) Str::uuid7();
            $quantity = 1;

            DB::table('sales_invoice_lines')->updateOrInsert(
                ['id' => $lineId],
                [
                    'sales_invoice_id' => $invoiceId,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $invoice['total_amount'],
                    'subtotal_amount' => $invoice['total_amount'],
                    'line_total_amount' => $invoice['total_amount'],
                    'total_amount' => $invoice['total_amount'],
                    'warranty_months' => $product->warranty_months,
                    'deduct_inventory' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    /**
     * Seed a strict sequence for the document subtypes still written here.
     */
    private function seedDocumentSequences(): void
    {
        $startingNumbers = [
            LedgerSubType::PURCHASE_INVOICE->value => 10007,
            LedgerSubType::SALES_INVOICE->value => 10009,
            LedgerSubType::JOURNAL->value => 26004,
        ];

        // Only the subtypes still written to ledgers. Receipts, payment
        // vouchers and refunds moved to their own tables, and their counters are
        // seeded from those tables by their own seeders. Reading them from
        // ledgers here would rewind them.
        $owned = [
            LedgerSubType::SALES_INVOICE,
            LedgerSubType::PURCHASE_INVOICE,
            LedgerSubType::JOURNAL,
        ];

        foreach ($owned as $subtype) {
            $this->seedDocumentSequence(
                $subtype->value,
                $subtype->prefix(),
                $startingNumbers[$subtype->value] ?? 10001,
                'ledgers',
                'document_number'
            );
        }
    }

    /**
     * Return linked documents covering each operational finance flow.
     *
     * @return array<int, array<string, mixed>>
     */
    private function documents(): array
    {
        return [
            [
                'document_number' => 'IV-DEMO-0001',
                'reference_number' => 'IV-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::SALES_INVOICE,
                'date' => '2026-08-01',
                'debit_code' => '1200',
                'credit_code' => '4100',
                'quantity' => 1,
                'unit_price' => '4500.00',
                'amount' => '4500.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'notes' => 'Posted sales invoice used to demonstrate client-ledger to GL drill-down.',
            ],
            [
                'document_number' => 'PI-DEMO-0001',
                'reference_number' => 'PI-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-08-08',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 2,
                'unit_price' => '900.00',
                'amount' => '1800.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'party_name' => 'Ingram Micro Malaysia Sdn Bhd',
                'notes' => 'Posted purchase invoice used to demonstrate supplier document flow.',
            ],
            [
                'document_number' => 'IV-DEMO-0002',
                'reference_number' => 'IV-DEMO-0002',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::SALES_INVOICE,
                'date' => '2026-08-25',
                'debit_code' => '1200',
                'credit_code' => '4100',
                'quantity' => 1,
                'unit_price' => '2200.00',
                'amount' => '2200.00',
                'status' => DocumentStatus::PENDING,
                'use_product' => true,
                'notes' => 'Pending sales invoice intentionally kept outside the General Ledger.',
            ],
            [
                'document_number' => 'IV-RMA-DEMO-0001',
                'reference_number' => 'IV-RMA-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::SALES_INVOICE,
                'date' => now()->subDays(45)->toDateString(),
                'debit_code' => '1200',
                'credit_code' => '4100',
                'quantity' => 1,
                'unit_price' => '4500.00',
                'amount' => '4500.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'notes' => 'Posted hardware invoice reserved for the repeatable RMA inspection and repair demo.',
            ],
            [
                'document_number' => 'PI-KWSP-DEMO-0001',
                'reference_number' => 'PI-KWSP-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-09-05',
                'due_date' => '2026-10-05',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 1,
                'unit_price' => '3850.00',
                'amount' => '3850.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'party_name' => 'Kumpulan Wang Simpanan Pekerja (KWSP)',
                'notes' => 'Outstanding statutory contribution payable to KWSP.',
            ],
            [
                'document_number' => 'PI-INGRAM-DEMO-0002',
                'reference_number' => 'PI-INGRAM-DEMO-0002',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-09-01',
                'due_date' => '2026-09-28',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 2,
                'unit_price' => '2300.00',
                'amount' => '4600.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'party_name' => 'Ingram Micro Malaysia Sdn Bhd',
                'notes' => 'Outstanding inventory purchase payable to Ingram Micro Malaysia.',
            ],
            [
                'document_number' => 'PI-VSTECS-DEMO-0001',
                'reference_number' => 'PI-VSTECS-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-09-02',
                'due_date' => '2026-09-29',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 3,
                'unit_price' => '1450.00',
                'amount' => '4350.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'party_name' => 'VSTECS Berhad',
                'notes' => 'Outstanding inventory purchase payable to VSTECS Berhad.',
            ],
            [
                'document_number' => 'PI-SNS-DEMO-0001',
                'reference_number' => 'PI-SNS-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-09-03',
                'due_date' => '2026-09-30',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 4,
                'unit_price' => '330.00',
                'amount' => '1320.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'party_name' => 'SNS Network (M) Sdn Bhd',
                'notes' => 'Outstanding inventory purchase payable to SNS Network.',
            ],
            [
                'document_number' => 'PI-PERKESO-DEMO-0001',
                'reference_number' => 'PI-PERKESO-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-09-08',
                'due_date' => '2026-10-08',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 1,
                'unit_price' => '1275.00',
                'amount' => '1275.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'party_name' => 'Pertubuhan Keselamatan Sosial (PERKESO / SOCSO)',
                'notes' => 'Outstanding statutory contribution payable to PERKESO / SOCSO.',
            ],
            [
                'document_number' => 'PI-LHDN-DEMO-0001',
                'reference_number' => 'PI-LHDN-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-09-10',
                'due_date' => '2026-10-10',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 1,
                'unit_price' => '4650.00',
                'amount' => '4650.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'party_name' => 'Lembaga Hasil Dalam Negeri Malaysia (LHDN)',
                'notes' => 'Outstanding PCB remittance payable to LHDN.',
            ],
            [
                'document_number' => 'PI-HRDCORP-DEMO-0001',
                'reference_number' => 'PI-HRDCORP-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-09-12',
                'due_date' => '2026-10-12',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 1,
                'unit_price' => '980.00',
                'amount' => '980.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'party_name' => 'Human Resources Development Corporation (HRD Corp)',
                'notes' => 'Outstanding statutory levy payable to HRD Corp.',
            ],
            [
                'document_number' => 'PI-PTPTN-DEMO-0001',
                'reference_number' => 'PI-PTPTN-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-09-15',
                'due_date' => '2026-10-15',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 1,
                'unit_price' => '620.00',
                'amount' => '620.00',
                'status' => DocumentStatus::POSTED,
                'use_product' => true,
                'party_name' => 'Perbadanan Tabung Pendidikan Tinggi Nasional (PTPTN)',
                'notes' => 'Outstanding payroll deduction payable to PTPTN.',
            ],
            [
                'document_number' => 'IV-CANCEL-0001',
                'reference_number' => 'IV-CANCEL-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::SALES_INVOICE,
                'date' => '2026-07-10',
                'debit_code' => '1200',
                'credit_code' => '4100',
                'quantity' => 2,
                'unit_price' => '1800.00',
                'amount' => '3600.00',
                'status' => DocumentStatus::CANCELLED,
                'use_product' => true,
                'notes' => 'Cancelled sales invoice — customer changed mind before approval.',
            ],
            [
                'document_number' => 'IV-CANCEL-0002',
                'reference_number' => 'IV-CANCEL-0002',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::SALES_INVOICE,
                'date' => '2026-08-15',
                'debit_code' => '1200',
                'credit_code' => '4100',
                'quantity' => 1,
                'unit_price' => '5200.00',
                'amount' => '5200.00',
                'status' => DocumentStatus::CANCELLED,
                'use_product' => true,
                'notes' => 'Cancelled sales invoice — duplicate entry voided.',
            ],
            [
                'document_number' => 'PI-CANCEL-0001',
                'reference_number' => 'PI-CANCEL-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-07-20',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 1,
                'unit_price' => '2750.00',
                'amount' => '2750.00',
                'status' => DocumentStatus::CANCELLED,
                'use_product' => true,
                'party_name' => 'Ingram Micro Malaysia Sdn Bhd',
                'notes' => 'Cancelled purchase invoice — wrong supplier selected.',
            ],
            [
                'document_number' => 'PI-CANCEL-0002',
                'reference_number' => 'PI-CANCEL-0002',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_INVOICE,
                'date' => '2026-09-01',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 3,
                'unit_price' => '680.00',
                'amount' => '2040.00',
                'status' => DocumentStatus::CANCELLED,
                'use_product' => true,
                'party_name' => 'VSTECS Berhad',
                'notes' => 'Cancelled purchase invoice — order never confirmed by supplier.',
            ],
        ];
    }

    /**
     * Seed one balanced document to the general ledger and the party sub-ledger.
     *
     * Sales documents go to ar_ledgers and purchase documents to ap_ledgers, and
     * both ledgers receive the same two rows: the sub-ledger is a copy of the
     * document's general ledger rows, filed under the party, rather than only the
     * half of the entry that names them. A document that is not posted leaves no
     * row in either ledger.
     *
     * @param  array<string, mixed>  $document  The document blueprint, with its number, accounts, amount and status.
     * @param  Collection<string, stdClass>  $charts  The chart accounts, keyed by code.
     * @param  stdClass  $company  The company record for the document.
     * @param  stdClass  $product  The product record for the document.
     * @param  ?string  $currencyId  The default currency UUID.
     */
    private function seedDocument(
        array $document,
        Collection $charts,
        stdClass $company,
        stdClass $product,
        ?string $currencyId
    ): void {
        $debitChart = $charts->get($document['debit_code']);
        $creditChart = $charts->get($document['credit_code']);
        if ($debitChart === null || $creditChart === null) {
            throw new LogicException('Client ledger seeding requires all referenced charts.');
        }

        $now = now();
        $subtype = $document['subtype'];

        $isSales = $subtype === LedgerSubType::SALES_INVOICE;

        $debitLineId = $this->existingLineId($document['document_number'], $debitChart->id);
        $creditLineId = $this->existingLineId($document['document_number'], $creditChart->id);

        $common = [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'document_number' => $document['document_number'],
            'reference_number' => $document['reference_number'],
            'type' => $document['type']->value,
            'subtype' => $subtype->value,
            'date' => $document['date'],
            'description' => $document['notes'],
            'deleted_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($document['status'] === DocumentStatus::POSTED) {
            $debitRow = $common + [
                'id' => $debitLineId,
                'chart_id' => $debitChart->id,
                'currency_id' => $currencyId,
                'chart_code' => $debitChart->code,
                'chart_name' => $debitChart->name,
                'line_description' => null,
                'debit_amount' => $document['amount'],
                'credit_amount' => '0.00',
            ];
            $creditRow = $common + [
                'id' => $creditLineId,
                'chart_id' => $creditChart->id,
                'currency_id' => $currencyId,
                'chart_code' => $creditChart->code,
                'chart_name' => $creditChart->name,
                'line_description' => null,
                'debit_amount' => '0.00',
                'credit_amount' => $document['amount'],
            ];

            DB::table('ledgers')->updateOrInsert(['id' => $debitLineId], $debitRow);
            DB::table('ledgers')->updateOrInsert(['id' => $creditLineId], $creditRow);

            if ($isSales) {
                $partyTable = 'ar_ledgers';
                $otherTable = 'ap_ledgers';
            } else {
                $partyTable = 'ap_ledgers';
                $otherTable = 'ar_ledgers';
            }

            // The sub-ledger holds the same rows as the general ledger, each
            // against the account that row was booked to, so both sides of this
            // entry are written rather than only the side that names the party.
            $partyLines = [
                ['id' => $debitLineId, 'chart' => $debitChart, 'debit_amount' => $document['amount'], 'credit_amount' => '0.00'],
                ['id' => $creditLineId, 'chart' => $creditChart, 'debit_amount' => '0.00', 'credit_amount' => $document['amount']],
            ];

            /** @var array{id: string, chart: stdClass, debit_amount: string, credit_amount: string} $line */
            foreach ($partyLines as $line) {
                DB::table($partyTable)->updateOrInsert(
                    ['id' => $line['id']],
                    [
                        'chart_id' => $line['chart']->id,
                        'company_id' => $company->id,
                        'currency_id' => $currencyId,
                        'type' => $document['type']->value,
                        'subtype' => $subtype->value,
                        'document_number' => $document['document_number'],
                        'date' => $document['date'],
                        'reference_number' => $document['reference_number'],
                        'chart_code' => $line['chart']->code,
                        'chart_name' => $line['chart']->name,
                        'chart_description' => null,
                        'company_name' => $company->name,
                        'description' => $document['notes'],
                        'debit_amount' => $line['debit_amount'],
                        'credit_amount' => $line['credit_amount'],
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ]
                );
            }

            DB::table($otherTable)
                ->where('document_number', $document['document_number'])
                ->delete();
        } else {
            DB::table('ledgers')->whereIn('id', [$debitLineId, $creditLineId])->delete();
            DB::table('ar_ledgers')
                ->where('document_number', $document['document_number'])
                ->delete();
            DB::table('ap_ledgers')
                ->where('document_number', $document['document_number'])
                ->delete();
        }
    }

    /**
     * Keep each ledger row UUID stable across repeated seeding.
     */
    private function existingLineId(
        string $documentNumber,
        string $chartId
    ): string {
        return DB::table('ledgers')
            ->where('document_number', $documentNumber)
            ->where('chart_id', $chartId)
            ->value('id') ?? (string) Str::uuid7();
    }
}
