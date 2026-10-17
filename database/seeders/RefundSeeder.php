<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\PaymentTransferType;
use App\Models\Bank\Refund\CustomerRefund;
use App\Models\Bank\Refund\SupplierRefund;
use App\Models\ChartOfAccount\Chart;
use App\Support\Posting\PaymentLedgerPoster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Seed payment refunds in the new document structure.
 *
 * A payment refund is a document with its own row and its own lines, rather than a
 * pair of ledger rows sharing a document number. Seeded through the
 * models so the rows are exactly what the application would write, which means a
 * seeded refund can be approved, cancelled and printed by the same code that
 * handles a real one.
 *
 * Documents are seeded across all three statuses on purpose: pending is what the
 * working list shows, and a payment refund system is only believable if the cancelled
 * and history lists have something in them too.
 *
 * Idempotent by document number, so reseeding replaces a payment refund rather than
 * accumulating copies, and the sequence is advanced past every number issued so
 * a new refund cannot collide with a seeded one.
 */
class RefundSeeder extends Seeder
{
    use Concerns\PostsSeededDocuments;
    use Concerns\SeedsDocumentSequences;

    /**
     * The poster that writes a seeded document's double entry to every ledger it
     * belongs in, injected so the trait does not have to reach for the container.
     */
    public function __construct(private readonly PaymentLedgerPoster $poster) {}

    /**
     * Seed the refunds.
     */
    public function run(): void
    {
        $charts = DB::table('charts')->pluck('name', 'code');

        foreach ($this->refunds() as $refund) {
            $debitChart = $charts->get($refund['debit_code']);
            $creditChart = $charts->get($refund['credit_code']);

            if ($debitChart === null || $creditChart === null) {
                throw new LogicException(sprintf(
                    'Refund %s names a chart account that does not exist: %s / %s',
                    $refund['number'],
                    $refund['debit_code'],
                    $refund['credit_code'],
                ));
            }

            $company = DB::table('companies')
                ->where('name', $refund['party'])
                ->first();

            if ($company === null) {
                throw new LogicException('Refund names a company that does not exist: '.$refund['party']);
            }

            $debitId = Chart::query()->where('name', $debitChart)->value('id');
            $creditId = Chart::query()->where('name', $creditChart)->value('id');

            // The kind picks the table, so each seed writes to the model that owns
            // it. The model is named by the seed's own type field rather than
            // inferred from the party, because the table is the only thing that
            // makes a refund a customer or a supplier refund now.
            $documentClass = $refund['type'] === LedgerSubType::SUPPLIER_REFUND->value
                ? SupplierRefund::class
                : CustomerRefund::class;

            DB::transaction(function () use ($refund, $company, $debitId, $creditId, $debitChart, $creditChart, $documentClass): void {
                $existing = $documentClass::query()
                    ->where('document_number', $refund['number'])
                    ->first();

                if ($existing !== null) {
                    $this->purgePostedRows($refund['number']);

                    $existing->lines()->delete();
                    $existing->charts()->delete();
                    $existing->forceDelete();
                }

                $model = $documentClass::query()->create([
                    'document_number' => $refund['number'],
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'date' => $refund['date'],
                    'status' => $refund['status'],
                    'payment_transfer_type' => $refund['transfer_type'],
                    'reference_number' => $refund['reference_number'],
                    'notes' => $refund['notes'],
                    'total_amount' => $refund['amount'],
                    // Seeded approvals are dated relative to the document rather
                    // than left null, so a seeded history row reads like a real
                    // one rather than a posted refund nobody approved.
                    'created_name' => 'System Seeder',
                    'created_at' => $refund['date'],
                    'approved_name' => $refund['status'] === LedgerStatus::POSTED ? 'Manager User' : null,
                    'approved_at' => $refund['status'] === LedgerStatus::POSTED ? $refund['date'] : null,
                    'cancelled_name' => $refund['status'] === LedgerStatus::CANCELLED ? 'Manager User' : null,
                    'cancelled_at' => $refund['status'] === LedgerStatus::CANCELLED ? $refund['date'] : null,
                ]);

                $model->lines()->create([
                    'amount' => $refund['amount'],
                    'description' => $refund['notes'],
                ]);

                $model->lines()->create([
                    'amount' => $refund['amount'],
                    'description' => $refund['notes'],
                ]);

                $model->charts()->create([
                    'debit_id' => $debitId,
                    'debit_acc' => $refund['debit_code'],
                    'debit_acc_desc' => $debitChart,
                    'document_number' => $refund['number'],
                    'reference_number' => $refund['reference_number'],
                    'date' => $refund['date'],
                    'description' => $refund['notes'],
                    'debit_amount' => $refund['amount'],
                    'credit_amount' => '0.00',
                    'total_amount' => $refund['amount'],
                ]);

                $model->charts()->create([
                    'credit_id' => $creditId,
                    'credit_acc' => $refund['credit_code'],
                    'credit_acc_desc' => $creditChart,
                    'document_number' => $refund['number'],
                    'reference_number' => $refund['reference_number'],
                    'date' => $refund['date'],
                    'description' => $refund['notes'],
                    'debit_amount' => '0.00',
                    'credit_amount' => $refund['amount'],
                    'total_amount' => $refund['amount'],
                ]);

                // The subtype is the kind the seed asked for, which is the same
                // kind the model it was written to represents. The model reports
                // its own source type, so the posting cannot disagree with the
                // table the row landed in.
                $this->postSeededDocument(
                    $model,
                    $refund['type'] === LedgerSubType::SUPPLIER_REFUND->value
                        ? LedgerSubType::SUPPLIER_REFUND
                        : LedgerSubType::RECEIPT_REFUND,
                    'document_number',
                    $this->poster,
                );
            });
        }

        // Seeded per kind, not for the legacy kindless REFUND subtype: the
        // controller generates a number from the kind of refund being created, so
        // seeding the other one would leave that prefix at its first number and
        // a new refund would be issued a number that already exists.
        //
        // Each kind has its own table now, so each is read from its own table for the
        // highest number already issued. Reading a shared table for both would
        // let one kind's numbers push the other kind's sequence forward, and a
        // new document would be issued a number the other kind already holds.
        foreach ([
            [LedgerSubType::RECEIPT_REFUND, 'customer_refunds'],
            [LedgerSubType::SUPPLIER_REFUND, 'supplier_refunds'],
        ] as [$subtype, $table]) {
            $this->seedDocumentSequence(
                $subtype->value,
                $subtype->prefix(),
                10001,
                $table,
                'document_number',
            );
        }
    }

    /**
     * The refunds to seed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function refunds(): array
    {
        return [
            [
                'number' => 'RR-DEMO-0101',
                'type' => LedgerSubType::RECEIPT_REFUND->value,
                'reference_number' => 'RCP-DEMO-0001',
                'date' => '2026-09-15',
                // Giving money back to a customer debits the debtor, because what
                // they owe falls, and credits the money that left.
                'debit_code' => '1200',
                'credit_code' => '1100',
                'amount' => '450.00',
                'status' => LedgerStatus::PENDING,
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Partial refund to a customer, awaiting posting.',
            ],
            [
                'number' => 'RR-DEMO-0001',
                'type' => LedgerSubType::RECEIPT_REFUND->value,
                'reference_number' => 'RCP-DEMO-0101',
                'date' => '2026-08-28',
                'debit_code' => '1200',
                'credit_code' => '1110',
                'amount' => '275.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'Tradenet Financial Terminals',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Customer refund posted against RCP-DEMO-0101.',
            ],
            [
                'number' => 'RR-DEMO-0102',
                'type' => LedgerSubType::RECEIPT_REFUND->value,
                'reference_number' => 'RCP-DEMO-0001',
                'date' => '2026-09-16',
                'debit_code' => '1200',
                'credit_code' => '1100',
                'amount' => '120.00',
                'status' => LedgerStatus::CANCELLED,
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::CHEQUE,
                'notes' => 'Cancelled refund: the cheque was never presented.',
            ],
            [
                // A supplier refund takes money back rather than giving it, so
                // the money account is debited and the creditor credited. Both
                // kinds share one table; refund_type is what keeps them apart.
                'number' => 'SR-DEMO-0102',
                'type' => LedgerSubType::SUPPLIER_REFUND->value,
                'reference_number' => 'PV-DEMO-0101',
                'date' => '2026-09-17',
                'debit_code' => '1100',
                'credit_code' => '2000',
                'amount' => '610.00',
                'status' => LedgerStatus::PENDING,
                'party' => 'VSTECS Berhad',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Money recovered from a supplier, awaiting posting.',
            ],
            [
                'number' => 'SR-DEMO-0101',
                'type' => LedgerSubType::SUPPLIER_REFUND->value,
                'reference_number' => 'PV-DEMO-0001',
                'date' => '2026-08-30',
                'debit_code' => '1110',
                'credit_code' => '2000',
                'amount' => '900.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'SNS Network (M) Sdn Bhd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Supplier refund posted against PV-DEMO-0001.',
            ],
            [
                'number' => 'SR-DEMO-0103',
                'type' => LedgerSubType::SUPPLIER_REFUND->value,
                'reference_number' => 'PV-DEMO-0102',
                'date' => '2026-09-18',
                'debit_code' => '1100',
                'credit_code' => '2000',
                'amount' => '340.00',
                'status' => LedgerStatus::CANCELLED,
                'party' => 'VSTECS Berhad',
                'transfer_type' => PaymentTransferType::CHEQUE,
                'notes' => 'Cancelled supplier refund: raised against the wrong invoice.',
            ],
            [
                'number' => 'RR-JUL-DEMO-0001',
                'type' => LedgerSubType::RECEIPT_REFUND->value,
                'reference_number' => 'RCP-JUL-DEMO-0001',
                'date' => '2026-07-19',
                'debit_code' => '1200',
                'credit_code' => '1120',
                'amount' => '900.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Customer refund paid out of Public Bank.',
            ],
        ];
    }
}
