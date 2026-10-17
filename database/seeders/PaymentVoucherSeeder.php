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
use App\Models\Bank\PaymentVoucher;
use App\Models\ChartOfAccount\Chart;
use App\Support\Posting\PaymentLedgerPoster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Seed payment vouchers in the new document structure.
 *
 * A payment voucher is a document with its own row and its own lines, rather than a
 * pair of ledger rows sharing a document number. Seeded through the
 * models so the rows are exactly what the application would write, which means a
 * seeded voucher can be approved, cancelled and printed by the same code that
 * handles a real one.
 *
 * Documents are seeded across all three statuses on purpose: pending is what the
 * working list shows, and a payment voucher system is only believable if the cancelled
 * and history lists have something in them too.
 *
 * Idempotent by document number, so reseeding replaces a payment voucher rather than
 * accumulating copies, and the sequence is advanced past every number issued so
 * a new voucher cannot collide with a seeded one.
 */
class PaymentVoucherSeeder extends Seeder
{
    use Concerns\PostsSeededDocuments;
    use Concerns\SeedsDocumentSequences;

    /**
     * The poster that writes a seeded document's double entry to every ledger it
     * belongs in, injected so the trait does not have to reach for the container.
     */
    public function __construct(private readonly PaymentLedgerPoster $poster) {}

    /**
     * Seed the vouchers.
     */
    public function run(): void
    {
        $charts = DB::table('charts')->pluck('name', 'code');

        foreach ($this->vouchers() as $voucher) {
            $debitChart = $charts->get($voucher['debit_code']);
            $creditChart = $charts->get($voucher['credit_code']);

            if ($debitChart === null || $creditChart === null) {
                throw new LogicException(sprintf(
                    'Voucher %s names a chart account that does not exist: %s / %s',
                    $voucher['number'],
                    $voucher['debit_code'],
                    $voucher['credit_code'],
                ));
            }

            $company = DB::table('companies')
                ->where('name', $voucher['party'])
                ->first();

            if ($company === null) {
                throw new LogicException('Voucher names a company that does not exist: '.$voucher['party']);
            }

            $debitId = Chart::query()->where('name', $debitChart)->value('id');
            $creditId = Chart::query()->where('name', $creditChart)->value('id');

            DB::transaction(function () use ($voucher, $company, $debitId, $creditId, $debitChart, $creditChart): void {
                $existing = PaymentVoucher::query()
                    ->where('document_number', $voucher['number'])
                    ->first();

                if ($existing !== null) {
                    $this->purgePostedRows($voucher['number']);

                    $existing->lines()->delete();
                    $existing->charts()->delete();
                    $existing->forceDelete();
                }

                $model = PaymentVoucher::query()->create([
                    'document_number' => $voucher['number'],
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'date' => $voucher['date'],
                    'status' => $voucher['status'],
                    'payment_transfer_type' => $voucher['transfer_type'],
                    'reference_number' => $voucher['reference_number'],
                    'notes' => $voucher['notes'],
                    'total_amount' => $voucher['amount'],
                    // Seeded approvals are dated relative to the document rather
                    // than left null, so a seeded history row reads like a real
                    // one rather than a posted voucher nobody approved.
                    'created_name' => 'System Seeder',
                    'created_at' => $voucher['date'],
                    'approved_name' => $voucher['status'] === LedgerStatus::POSTED ? 'Manager User' : null,
                    'approved_at' => $voucher['status'] === LedgerStatus::POSTED ? $voucher['date'] : null,
                    'cancelled_name' => $voucher['status'] === LedgerStatus::CANCELLED ? 'Manager User' : null,
                    'cancelled_at' => $voucher['status'] === LedgerStatus::CANCELLED ? $voucher['date'] : null,
                ]);

                $model->lines()->create([
                    'amount' => $voucher['amount'],
                    'description' => $voucher['notes'],
                ]);

                $model->lines()->create([
                    'amount' => $voucher['amount'],
                    'description' => $voucher['notes'],
                ]);

                $model->charts()->create([
                    'debit_id' => $debitId,
                    'debit_acc' => $voucher['debit_code'],
                    'debit_acc_desc' => $debitChart,
                    'document_number' => $voucher['number'],
                    'reference_number' => $voucher['reference_number'],
                    'date' => $voucher['date'],
                    'description' => $voucher['notes'],
                    'debit_amount' => $voucher['amount'],
                    'credit_amount' => '0.00',
                    'total_amount' => $voucher['amount'],
                ]);

                $model->charts()->create([
                    'credit_id' => $creditId,
                    'credit_acc' => $voucher['credit_code'],
                    'credit_acc_desc' => $creditChart,
                    'document_number' => $voucher['number'],
                    'reference_number' => $voucher['reference_number'],
                    'date' => $voucher['date'],
                    'description' => $voucher['notes'],
                    'debit_amount' => '0.00',
                    'credit_amount' => $voucher['amount'],
                    'total_amount' => $voucher['amount'],
                ]);

                $this->postSeededDocument(
                    $model,
                    LedgerSubType::PAYMENT_VOUCHER,
                    'document_number',
                    $this->poster,
                );
            });
        }

        // Read from vouchers rather than passing the numbers: the helper takes
        // the highest issued number from the table itself, which is what keeps a
        // new voucher from colliding with a seeded one.
        $this->seedDocumentSequence(
            LedgerSubType::PAYMENT_VOUCHER->value,
            LedgerSubType::PAYMENT_VOUCHER->prefix(),
            10001,
            'payment_vouchers',
            'document_number',
        );
    }

    /**
     * The vouchers to seed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function vouchers(): array
    {
        return [
            [
                'number' => 'PV-DEMO-0101',
                'reference_number' => 'PI-DEMO-0001',
                'date' => '2026-09-10',
                // A voucher debits what is owed to the supplier and credits the
                // money it left, so the creditor is the debit side here. That is
                // the mirror of a receipt and the reason the two feed opposite
                // ledgers.
                'debit_code' => '2000',
                'credit_code' => '1100',
                'amount' => '8400.00',
                'status' => LedgerStatus::PENDING,
                'party' => 'VSTECS Berhad',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Supplier payment recorded and awaiting posting.',
            ],
            [
                // Paid out of the petty cash tin rather than a bank, so the
                // routing to the cash ledger is exercised by seeded data too.
                'number' => 'PV-CASH-0101',
                'reference_number' => null,
                'date' => '2026-09-11',
                'debit_code' => '2000',
                'credit_code' => '1130',
                'amount' => '320.00',
                'status' => LedgerStatus::PENDING,
                'party' => 'Ingram Micro Malaysia Sdn Bhd',
                'transfer_type' => PaymentTransferType::CASH,
                'notes' => 'Small supplier payment settled from the till.',
            ],
            [
                'number' => 'PV-DEMO-0001',
                'reference_number' => 'PI-DEMO-0002',
                'date' => '2026-08-20',
                'debit_code' => '2000',
                'credit_code' => '1110',
                'amount' => '5200.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'SNS Network (M) Sdn Bhd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Supplier payment posted against PI-DEMO-0002.',
            ],
            [
                'number' => 'PV-DEMO-0102',
                'reference_number' => 'PI-DEMO-0003',
                'date' => '2026-09-12',
                'debit_code' => '2000',
                'credit_code' => '1100',
                'amount' => '1750.00',
                'status' => LedgerStatus::PENDING,
                'party' => 'VSTECS Berhad',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Second supplier payment recorded and awaiting posting.',
            ],
            [
                'number' => 'PV-DEMO-0103',
                'reference_number' => 'PI-DEMO-0004',
                'date' => '2026-09-14',
                'debit_code' => '2000',
                'credit_code' => '1100',
                'amount' => '640.00',
                'status' => LedgerStatus::CANCELLED,
                'party' => 'SNS Network (M) Sdn Bhd',
                'transfer_type' => PaymentTransferType::CHEQUE,
                'notes' => 'Cancelled voucher: the cheque was returned unpaid.',
            ],
            [
                // One posted voucher per seeded bank account, so the payable
                // money ledgers are exercised across more than one account.
                'number' => 'PV-CIMB-DEMO-0001',
                'reference_number' => 'PI-CIMB-DEMO-0001',
                'date' => '2026-08-22',
                'debit_code' => '2000',
                'credit_code' => '1110',
                'amount' => '4200.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'Ingram Micro Malaysia Sdn Bhd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Supplier payment made from CIMB Bank.',
            ],
            [
                'number' => 'PV-PBB-DEMO-0001',
                'reference_number' => 'PI-PBB-DEMO-0001',
                'date' => '2026-08-28',
                'debit_code' => '2000',
                'credit_code' => '1120',
                'amount' => '2500.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'VSTECS Berhad',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Supplier payment made from Public Bank.',
            ],
            [
                'number' => 'PV-MAY-DEMO-0001',
                'reference_number' => 'PI-MAY-DEMO-0001',
                'date' => '2026-05-25',
                'debit_code' => '2000',
                'credit_code' => '1100',
                'amount' => '3100.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'SNS Network (M) Sdn Bhd',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Supplier payment made from Maybank.',
            ],
            [
                'number' => 'PV-JUN-DEMO-0001',
                'reference_number' => 'PI-JUN-DEMO-0001',
                'date' => '2026-06-20',
                'debit_code' => '2000',
                'credit_code' => '1110',
                'amount' => '5600.00',
                'status' => LedgerStatus::POSTED,
                'party' => 'VSTECS Berhad',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Supplier payment made from CIMB Bank.',
            ],
        ];
    }
}
