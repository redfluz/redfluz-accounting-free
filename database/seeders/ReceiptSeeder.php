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
use App\Models\Bank\Receipt;
use App\Models\ChartOfAccount\Chart;
use App\Support\Posting\PaymentLedgerPoster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Seed receipts in the new document structure.
 *
 * A receipt is a document with its own row and its own lines, rather than a
 * pair of ledger rows sharing a document number. Seeded through the
 * models so the rows are exactly what the application would write, which means a
 * seeded receipt can be approved, cancelled and printed by the same code that
 * handles a real one.
 *
 * Documents are seeded across all three statuses on purpose: pending is what the
 * working list shows, and a receipt system is only believable if the cancelled
 * and history lists have something in them too.
 *
 * Idempotent by document number, so reseeding replaces a receipt rather than
 * accumulating copies, and the sequence is advanced past every number issued so
 * a new receipt cannot collide with a seeded one.
 */
class ReceiptSeeder extends Seeder
{
    use Concerns\PostsSeededDocuments;
    use Concerns\SeedsDocumentSequences;

    /**
     * The poster that writes a seeded document's double entry to every ledger it
     * belongs in, injected so the trait does not have to reach for the container.
     */
    public function __construct(private readonly PaymentLedgerPoster $poster) {}

    /**
     * Seed the receipts.
     */
    public function run(): void
    {
        $charts = DB::table('charts')->pluck('name', 'code');

        foreach ($this->receipts() as $receipt) {
            $debitChart = $charts->get($receipt['debit_code']);
            $creditChart = $charts->get($receipt['credit_code']);

            if ($debitChart === null || $creditChart === null) {
                throw new LogicException(sprintf(
                    'Receipt %s names a chart account that does not exist: %s / %s',
                    $receipt['number'],
                    $receipt['debit_code'],
                    $receipt['credit_code'],
                ));
            }

            $company = DB::table('companies')
                ->where('name', $receipt['party'])
                ->first();

            if ($company === null) {
                throw new LogicException('Receipt names a company that does not exist: '.$receipt['party']);
            }

            $debitId = Chart::query()->where('name', $debitChart)->value('id');
            $creditId = Chart::query()->where('name', $creditChart)->value('id');

            DB::transaction(function () use ($receipt, $company, $debitId, $creditId, $debitChart, $creditChart): void {
                $existing = Receipt::query()
                    ->where('document_number', $receipt['number'])
                    ->first();

                if ($existing !== null) {
                    $this->purgePostedRows($receipt['number']);

                    $existing->lines()->delete();
                    $existing->charts()->delete();
                    $existing->forceDelete();
                }

                $model = Receipt::query()->create([
                    'document_number' => $receipt['number'],
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'date' => $receipt['date'],
                    'status' => $receipt['status'],
                    'payment_transfer_type' => $receipt['transfer_type'],
                    'reference_number' => $receipt['reference_number'],
                    'notes' => $receipt['notes'],
                    'total_amount' => $receipt['amount'],
                    // Seeded approvals are dated relative to the document rather
                    // than left null, so a seeded history row reads like a real
                    // one rather than a posted receipt nobody approved.
                    'created_name' => 'System Seeder',
                    'created_at' => $receipt['date'],
                    'approved_name' => $receipt['status'] === LedgerStatus::POSTED ? 'Manager User' : null,
                    'approved_at' => $receipt['status'] === LedgerStatus::POSTED ? $receipt['date'] : null,
                    'cancelled_name' => $receipt['status'] === LedgerStatus::CANCELLED ? 'Manager User' : null,
                    'cancelled_at' => $receipt['status'] === LedgerStatus::CANCELLED ? $receipt['date'] : null,
                ]);

                $model->lines()->create([
                    'amount' => $receipt['amount'],
                    'description' => $receipt['notes'],
                ]);

                $model->lines()->create([
                    'amount' => $receipt['amount'],
                    'description' => $receipt['notes'],
                ]);

                $model->charts()->create([
                    'debit_id' => $debitId,
                    'debit_acc' => $receipt['debit_code'],
                    'debit_acc_desc' => $debitChart,
                    'document_number' => $receipt['number'],
                    'reference_number' => $receipt['reference_number'],
                    'date' => $receipt['date'],
                    'description' => $receipt['notes'],
                    'debit_amount' => $receipt['amount'],
                    'credit_amount' => '0.00',
                    'total_amount' => $receipt['amount'],
                ]);

                $model->charts()->create([
                    'credit_id' => $creditId,
                    'credit_acc' => $receipt['credit_code'],
                    'credit_acc_desc' => $creditChart,
                    'document_number' => $receipt['number'],
                    'reference_number' => $receipt['reference_number'],
                    'date' => $receipt['date'],
                    'description' => $receipt['notes'],
                    'debit_amount' => '0.00',
                    'credit_amount' => $receipt['amount'],
                    'total_amount' => $receipt['amount'],
                ]);

                $this->postSeededDocument(
                    $model,
                    LedgerSubType::RECEIPT,
                    'document_number',
                    $this->poster,
                );
            });
        }

        // Read from receipts rather than passing the numbers: the helper takes
        // the highest issued number from the table itself, which is what keeps a
        // new receipt from colliding with a seeded one.
        $this->seedDocumentSequence(
            LedgerSubType::RECEIPT->value,
            LedgerSubType::RECEIPT->prefix(),
            10001,
            'receipts',
            'document_number',
        );
    }

    /**
     * The receipts to seed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function receipts(): array
    {
        return [
            [
                'number' => 'RCP-DEMO-0101',
                'reference_number' => 'IV-DEMO-0001',
                'date' => '2026-09-05',
                'debit_code' => '1100',
                'credit_code' => '1200',
                'amount' => '1500.00',
                'status' => LedgerStatus::PENDING,
                'party' => 'Tradenet Financial Terminals',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Customer receipt recorded and awaiting posting.',
            ],
            [
                // Paid into the drawer rather than a bank. Seeded deliberately:
                // a receipt into physical cash is a different ledger from a
                // receipt into a bank account, and both have to be real for the
                // routing to be worth anything. Numbered apart from the DEMO
                // series so it cannot be confused with those.
                'number' => 'RCP-CASH-0101',
                'reference_number' => null,
                'date' => '2026-09-06',
                'debit_code' => '1000',
                'credit_code' => '1200',
                'amount' => '260.00',
                'status' => LedgerStatus::PENDING,
                'party' => 'Horizon Wealth Management',
                'transfer_type' => PaymentTransferType::CASH,
                'notes' => 'Cash taken at the counter and held in the drawer.',
            ],
            [
                // Posted, so the history grid has something in it. The approval
                // columns are recorded rather than left null: a posted receipt
                // with nobody recorded as having approved it is exactly the gap
                // the history grid exists to close.
                'number' => 'RCP-DEMO-0001',
                'reference_number' => 'IV-DEMO-0001',
                'date' => '2026-08-05',
                'debit_code' => '1100',
                'credit_code' => '1200',
                'amount' => '3000.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Customer receipt posted against IV-DEMO-0001.',
            ],
            [
                'number' => 'RCP-DEMO-0102',
                'reference_number' => 'IV-DEMO-0002',
                'date' => '2026-09-08',
                'debit_code' => '1110',
                'credit_code' => '1200',
                'amount' => '425.00',
                'status' => LedgerStatus::CANCELLED,
                'party' => 'Horizon Wealth Management',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Cancelled receipt: the deposit was reversed by the bank.',
            ],
            [
                // One posted receipt per seeded bank account. Seeded so the
                // money ledgers have something in more than one account, which
                // is what makes the per-account routing worth testing.
                'number' => 'RCP-CIMB-DEMO-0001',
                'reference_number' => 'IV-CIMB-DEMO-0001',
                'date' => '2026-08-20',
                'debit_code' => '1110',
                'credit_code' => '1200',
                'amount' => '12500.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Customer receipt deposited into CIMB Bank.',
            ],
            [
                'number' => 'RCP-PBB-DEMO-0001',
                'reference_number' => 'IV-PBB-DEMO-0001',
                'date' => '2026-08-26',
                'debit_code' => '1120',
                'credit_code' => '1200',
                'amount' => '18000.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Customer receipt deposited into Public Bank.',
            ],
            [
                'number' => 'RCP-MAY-DEMO-0001',
                'reference_number' => 'IV-MAY-DEMO-0001',
                'date' => '2026-05-18',
                'debit_code' => '1100',
                'credit_code' => '1200',
                'amount' => '8200.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Customer receipt deposited into Maybank.',
            ],
            [
                'number' => 'RCP-JUN-DEMO-0001',
                'reference_number' => 'IV-JUN-DEMO-0001',
                'date' => '2026-06-14',
                'debit_code' => '1110',
                'credit_code' => '1200',
                'amount' => '14600.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Customer receipt deposited into CIMB Bank.',
            ],
            [
                'number' => 'RCP-JUL-DEMO-0001',
                'reference_number' => 'IV-JUL-DEMO-0001',
                'date' => '2026-07-11',
                'debit_code' => '1120',
                'credit_code' => '1200',
                'amount' => '9800.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Customer receipt deposited into Public Bank.',
            ],
        ];
    }
}
