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

use App\Enums\LedgerSourceType;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
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
 * Sales invoices and sales adjustments write their counterparty leg to
 * ar_ledgers; purchase invoices and purchase notes write theirs to ap_ledgers.
 * Both sides of every posted entry go to ledgers.
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
        $creditorContactIds = DB::table('contacts')
            ->whereIn('company_id', $creditorCompanies->pluck('id'))
            ->orderBy('created_at')
            ->get(['company_id', 'id'])
            ->groupBy('company_id')
            ->map(fn (Collection $contacts): ?string => $contacts->first()?->id);
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

        DB::transaction(function () use ($charts, $company, $contactId, $product, $creditorCompanies, $creditorContactIds, $currencyId): void {
            foreach ($this->documents() as $document) {
                $party = $company;
                $partyContactId = $contactId;
                if (isset($document['party_name'])) {
                    $party = $creditorCompanies->get($document['party_name']);
                    if ($party === null) {
                        throw new LogicException("Creditor {$document['party_name']} is required for client ledger flow seeding.");
                    }
                    $partyContactId = $creditorContactIds->get($party->id);
                }

                $this->seedDocument(
                    $document,
                    $charts,
                    $party,
                    $partyContactId,
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

        foreach ([
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
        ] as $invoice) {
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

        foreach ([
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
        ] as $invoice) {
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
            LedgerSubType::INCREASE_ADJUSTMENT,
            LedgerSubType::DECREASE_ADJUSTMENT,
            LedgerSubType::PURCHASE_DEBIT_NOTE,
            LedgerSubType::PURCHASE_CREDIT_NOTE,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::PENDING,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::POSTED,
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
                'status' => LedgerStatus::CANCELLED,
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
                'status' => LedgerStatus::CANCELLED,
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
                'status' => LedgerStatus::CANCELLED,
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
                'status' => LedgerStatus::CANCELLED,
                'use_product' => true,
                'party_name' => 'VSTECS Berhad',
                'notes' => 'Cancelled purchase invoice — order never confirmed by supplier.',
            ],
            [
                'document_number' => 'DN-ADJ-0001',
                'reference_number' => 'IV-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::INCREASE_ADJUSTMENT,
                'date' => '2026-08-10',
                'debit_code' => '1200',
                'credit_code' => '4100',
                'quantity' => 1,
                'unit_price' => '750.00',
                'amount' => '750.00',
                'status' => LedgerStatus::POSTED,
                'use_product' => true,
                'notes' => 'Posted sales increase adjustment — additional charge for warranty upgrade.',
            ],
            [
                'document_number' => 'CN-ADJ-0001',
                'reference_number' => 'IV-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::DECREASE_ADJUSTMENT,
                'date' => '2026-08-12',
                'debit_code' => '4100',
                'credit_code' => '1200',
                'quantity' => 1,
                'unit_price' => '350.00',
                'amount' => '350.00',
                'status' => LedgerStatus::POSTED,
                'use_product' => true,
                'notes' => 'Posted sales decrease adjustment — partial refund for damaged packaging.',
            ],
            [
                'document_number' => 'DN-ADJ-0002',
                'reference_number' => 'IV-RMA-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::INCREASE_ADJUSTMENT,
                'date' => '2026-09-05',
                'debit_code' => '1200',
                'credit_code' => '4100',
                'quantity' => 1,
                'unit_price' => '1200.00',
                'amount' => '1200.00',
                'status' => LedgerStatus::CANCELLED,
                'use_product' => true,
                'notes' => 'Cancelled sales increase adjustment — approved then reversed.',
            ],
            [
                'document_number' => 'CN-ADJ-0002',
                'reference_number' => 'IV-RMA-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::DECREASE_ADJUSTMENT,
                'date' => '2026-09-08',
                'debit_code' => '4100',
                'credit_code' => '1200',
                'quantity' => 1,
                'unit_price' => '480.00',
                'amount' => '480.00',
                'status' => LedgerStatus::CANCELLED,
                'use_product' => true,
                'notes' => 'Cancelled sales decrease adjustment — voided before posting.',
            ],
            [
                'document_number' => 'PDN-ADJ-0001',
                'reference_number' => 'PI-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_DEBIT_NOTE,
                'date' => '2026-08-18',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 1,
                'unit_price' => '560.00',
                'amount' => '560.00',
                'status' => LedgerStatus::POSTED,
                'use_product' => true,
                'party_name' => 'Ingram Micro Malaysia Sdn Bhd',
                'notes' => 'Posted purchase debit note — additional cost for expedited delivery.',
            ],
            [
                'document_number' => 'PCN-ADJ-0001',
                'reference_number' => 'PI-VSTECS-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_CREDIT_NOTE,
                'date' => '2026-08-20',
                'debit_code' => '2000',
                'credit_code' => '5000',
                'quantity' => 1,
                'unit_price' => '420.00',
                'amount' => '420.00',
                'status' => LedgerStatus::POSTED,
                'use_product' => true,
                'party_name' => 'VSTECS Berhad',
                'notes' => 'Posted purchase credit note — supplier returned defective items.',
            ],
            [
                'document_number' => 'PDN-ADJ-0002',
                'reference_number' => 'PI-SNS-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_DEBIT_NOTE,
                'date' => '2026-09-10',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 1,
                'unit_price' => '890.00',
                'amount' => '890.00',
                'status' => LedgerStatus::CANCELLED,
                'use_product' => true,
                'party_name' => 'SNS Network (M) Sdn Bhd',
                'notes' => 'Cancelled purchase debit note — disputed charge not approved.',
            ],
            [
                'document_number' => 'PCN-ADJ-0002',
                'reference_number' => 'PI-INGRAM-DEMO-0002',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_CREDIT_NOTE,
                'date' => '2026-09-12',
                'debit_code' => '2000',
                'credit_code' => '5000',
                'quantity' => 1,
                'unit_price' => '310.00',
                'amount' => '310.00',
                'status' => LedgerStatus::CANCELLED,
                'use_product' => true,
                'party_name' => 'Ingram Micro Malaysia Sdn Bhd',
                'notes' => 'Cancelled purchase credit note — supplier rejected return request.',
            ],
            [
                'document_number' => 'PDN-ADJ-0003',
                'reference_number' => 'PI-VSTECS-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_DEBIT_NOTE,
                'date' => '2026-09-18',
                'debit_code' => '5000',
                'credit_code' => '2000',
                'quantity' => 1,
                'unit_price' => '650.00',
                'amount' => '650.00',
                'status' => LedgerStatus::PENDING,
                'use_product' => true,
                'party_name' => 'VSTECS Berhad',
                'notes' => 'Pending purchase debit note — awaiting approval before posting.',
            ],
            [
                'document_number' => 'PCN-ADJ-0003',
                'reference_number' => 'PI-SNS-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::PURCHASE_CREDIT_NOTE,
                'date' => '2026-09-20',
                'debit_code' => '2000',
                'credit_code' => '5000',
                'quantity' => 1,
                'unit_price' => '280.00',
                'amount' => '280.00',
                'status' => LedgerStatus::PENDING,
                'use_product' => true,
                'party_name' => 'SNS Network (M) Sdn Bhd',
                'notes' => 'Pending purchase credit note — awaiting approval before posting.',
            ],
            [
                'document_number' => 'DN-ADJ-0003',
                'reference_number' => 'IV-RMA-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::INCREASE_ADJUSTMENT,
                'date' => '2026-09-22',
                'debit_code' => '1200',
                'credit_code' => '4100',
                'quantity' => 1,
                'unit_price' => '520.00',
                'amount' => '520.00',
                'status' => LedgerStatus::PENDING,
                'use_product' => true,
                'notes' => 'Pending sales increase adjustment — awaiting approval before posting.',
            ],
            [
                'document_number' => 'CN-ADJ-0003',
                'reference_number' => 'IV-RMA-DEMO-0001',
                'type' => LedgerType::INVOICE,
                'subtype' => LedgerSubType::DECREASE_ADJUSTMENT,
                'date' => '2026-09-24',
                'debit_code' => '4100',
                'credit_code' => '1200',
                'quantity' => 1,
                'unit_price' => '390.00',
                'amount' => '390.00',
                'status' => LedgerStatus::PENDING,
                'use_product' => true,
                'notes' => 'Pending sales decrease adjustment — awaiting approval before posting.',
            ],
        ];
    }

    /**
     * Seed one balanced document to the general ledger and the appropriate
     * sub-ledger (ar_ledgers for sales, ap_ledgers for purchases).
     *
     * @param  array<string, mixed>  $document
     * @param  Collection<string, stdClass>  $charts
     * @param  stdClass  $company  The company record for the document.
     * @param  ?string  $contactId  The contact UUID, if any.
     * @param  stdClass  $product  The product record for the document.
     * @param  ?string  $currencyId  The default currency UUID.
     */
    private function seedDocument(
        array $document,
        Collection $charts,
        stdClass $company,
        ?string $contactId,
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

        $sourceType = match (true) {
            $subtype === LedgerSubType::SALES_INVOICE => LedgerSourceType::SALES_INVOICE->value,
            $subtype === LedgerSubType::PURCHASE_INVOICE => LedgerSourceType::PURCHASE_INVOICE->value,
            $subtype === LedgerSubType::INCREASE_ADJUSTMENT,
            $subtype === LedgerSubType::DECREASE_ADJUSTMENT => LedgerSourceType::SALES_ADJUSTMENT->value,
            $subtype === LedgerSubType::PURCHASE_DEBIT_NOTE,
            $subtype === LedgerSubType::PURCHASE_CREDIT_NOTE => LedgerSourceType::PURCHASE_ADJUSTMENT->value,
            default => null,
        };

        $isSales = in_array($subtype, [
            LedgerSubType::SALES_INVOICE,
            LedgerSubType::INCREASE_ADJUSTMENT,
            LedgerSubType::DECREASE_ADJUSTMENT,
        ], true);

        $debitId = $this->existingLineId(
            $document['document_number'],
            'debit_id',
            $debitChart->id
        );
        $creditId = $this->existingLineId(
            $document['document_number'],
            'credit_id',
            $creditChart->id
        );

        $common = [
            'source_type' => $sourceType,
            'company_id' => $company->id,
            'contact_id' => $contactId,
            'company_name' => $company->name,
            'document_number' => $document['document_number'],
            'reference_number' => $document['reference_number'],
            'type' => $document['type']->value,
            'subtype' => $subtype->value,
            'date' => $document['date'],
            'document_date' => $document['date'],
            'total_amount' => $document['amount'],
            'status' => $document['status']->value,
            'description' => $document['notes'],
            'deleted_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($document['status'] === LedgerStatus::POSTED) {
            $debitRow = $common + [
                'id' => $debitId,
                'source_id' => $debitId,
                'debit_id' => $debitChart->id,
                'credit_id' => null,
                'debit_acc' => $debitChart->code,
                'credit_acc' => null,
                'debit_acc_desc' => $debitChart->name,
                'credit_acc_desc' => null,
                'debit_amount' => $document['amount'],
                'credit_amount' => '0.00',
            ];
            $creditRow = $common + [
                'id' => $creditId,
                'source_id' => $creditId,
                'debit_id' => null,
                'credit_id' => $creditChart->id,
                'debit_acc' => null,
                'credit_acc' => $creditChart->code,
                'debit_acc_desc' => null,
                'credit_acc_desc' => $creditChart->name,
                'debit_amount' => '0.00',
                'credit_amount' => $document['amount'],
            ];

            DB::table('ledgers')->updateOrInsert(['id' => $debitId], $debitRow);
            DB::table('ledgers')->updateOrInsert(['id' => $creditId], $creditRow);

            if ($isSales) {
                DB::table('ar_ledgers')->updateOrInsert(
                    ['id' => $debitId],
                    [
                        'chart_id' => $debitChart->id,
                        'company_id' => $company->id,
                        'currency_id' => $currencyId,
                        'type' => $document['type']->value,
                        'subtype' => $subtype->value,
                        'document_number' => $document['document_number'],
                        'date' => $document['date'],
                        'reference_number' => $document['reference_number'],
                        'chart_code' => $debitChart->code,
                        'chart_name' => $debitChart->name,
                        'chart_description' => null,
                        'company_name' => $company->name,
                        'description' => $document['notes'],
                        'debit_amount' => $document['amount'],
                        'credit_amount' => '0.00',
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ]
                );
                DB::table('ap_ledgers')
                    ->where('document_number', $document['document_number'])
                    ->delete();
            } else {
                DB::table('ap_ledgers')->updateOrInsert(
                    ['id' => $creditId],
                    [
                        'chart_id' => $creditChart->id,
                        'company_id' => $company->id,
                        'currency_id' => $currencyId,
                        'type' => $document['type']->value,
                        'subtype' => $subtype->value,
                        'document_number' => $document['document_number'],
                        'date' => $document['date'],
                        'reference_number' => $document['reference_number'],
                        'chart_code' => $creditChart->code,
                        'chart_name' => $creditChart->name,
                        'chart_description' => null,
                        'company_name' => $company->name,
                        'description' => $document['notes'],
                        'debit_amount' => '0.00',
                        'credit_amount' => $document['amount'],
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ]
                );
                DB::table('ar_ledgers')
                    ->where('document_number', $document['document_number'])
                    ->delete();
            }
        } else {
            DB::table('ledgers')->whereIn('id', [$debitId, $creditId])->delete();
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
        string $accountColumn,
        string $chartId
    ): string {
        return DB::table('ledgers')
            ->where('document_number', $documentNumber)
            ->where($accountColumn, $chartId)
            ->value('id') ?? (string) Str::uuid7();
    }
}
