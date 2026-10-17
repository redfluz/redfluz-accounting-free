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

namespace Database\Seeders;

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\PaymentTransferType;
use App\Models\Bank\PaymentVoucher;
use App\Models\Bank\Receipt;
use App\Models\ChartOfAccount\Chart;
use App\Support\Posting\PaymentLedgerPoster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

/**
 * Seed movements through the cash drawers and the e-wallets.
 *
 * The cash and e-wallet ledgers were empty while the bank ledger had fifteen
 * rows in it, because every seeded document named a bank chart. That left the
 * two pages that report a drawer or a wallet permanently blank, which is the one
 * way a ledger cannot be tested: a balance of nothing looks exactly like a
 * balance that was never wired up.
 *
 * The movements are raised as real receipts and payment vouchers and then
 * posted, rather than being inserted into cash_ledgers and e_wallet_ledgers
 * directly. That is deliberate: a row written straight into a money ledger would
 * have no document behind it, no general ledger counterpart and no sub-ledger
 * counterpart, so the three would disagree and the seeded data would be teaching
 * a balance that could never arise. Posting real documents means every ledger
 * agrees, exactly as it does in use.
 *
 * Everything is seeded into one sequence after the existing documents so a new
 * receipt cannot be issued a number that a seeded one already holds.
 */
class MoneyLedgerSeeder extends Seeder
{
    use Concerns\PostsSeededDocuments;
    use Concerns\SeedsDocumentSequences;

    public function __construct(private readonly PaymentLedgerPoster $poster) {}

    /**
     * Seed the money ledger movements.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        // Posted in date order across both documents, not receipts-then-vouchers.
        // The running balance written onto each row is the account's balance
        // after that movement, which is only a running balance if the movements
        // arrived in the order they happened. Seeding a voucher dated before a
        // receipt and posting it second would leave every row between them
        // carrying a figure from the wrong day.
        $documents = [];

        foreach ($this->receipts() as $receipt) {
            $documents[] = ['kind' => 'receipt', 'data' => $receipt];
        }

        foreach ($this->vouchers() as $voucher) {
            $documents[] = ['kind' => 'voucher', 'data' => $voucher];
        }

        usort(
            $documents,
            fn (array $a, array $b): int => $a['data']['date'] <=> $b['data']['date']
        );

        // Every seeded document's rows are cleared before any of them is posted,
        // not as each one comes up. The balance written onto a row is the
        // account's balance after that movement, and it is computed from the rows
        // already in the ledger — so a document dated next month that has not
        // been cleared yet would already be counted into this one's balance.
        foreach ($documents as $document) {
            $this->purgePostedRows($document['data']['number']);
        }

        foreach ($documents as $document) {
            if ($document['kind'] === 'receipt') {
                $this->seedReceipt($document['data']);
            } else {
                $this->seedVoucher($document['data']);
            }
        }

        $this->seedDocumentSequence(
            LedgerSubType::RECEIPT->value,
            LedgerSubType::RECEIPT->prefix(),
            10021,
            'receipts',
            'document_number',
        );

        $this->seedDocumentSequence(
            LedgerSubType::PAYMENT_VOUCHER->value,
            LedgerSubType::PAYMENT_VOUCHER->prefix(),
            10021,
            'payment_vouchers',
            'document_number',
        );
    }

    /**
     * Money received into a drawer or a wallet.
     *
     * @return array<int, array<string, mixed>>
     */
    private function receipts(): array
    {
        return [
            [
                'number' => 'RCP-DRAWER-0101',
                'reference_number' => null,
                'date' => '2026-09-08',
                'debit_code' => '1140',
                'credit_code' => '1200',
                'amount' => '450.00',
                'party' => 'Horizon Wealth Management',
                'transfer_type' => PaymentTransferType::CASH,
                'notes' => 'Taken at the counter and left in drawer 1.',
            ],
            [
                'number' => 'RCP-DRAWER-0102',
                'reference_number' => null,
                'date' => '2026-09-12',
                'debit_code' => '1140',
                'credit_code' => '1200',
                'amount' => '275.00',
                'party' => 'Apex Capital Advisory Ltd',
                'transfer_type' => PaymentTransferType::CASH,
                'notes' => 'Second float counted into drawer 1 during the month.',
            ],
            [
                'number' => 'RCP-DRAWER-0103',
                'reference_number' => null,
                'date' => '2026-09-15',
                'debit_code' => '1141',
                'credit_code' => '1200',
                'amount' => '180.00',
                'party' => 'Khoo & Sons Private Investment',
                'transfer_type' => PaymentTransferType::CASH,
                'notes' => 'Cash received at the warehouse counter.',
            ],
            [
                // The float petty cash is counted out of. Seeded because a
                // drawer that only ever pays money out goes negative, and a
                // negative drawer reads as broken rather than as underfunded.
                'number' => 'RCP-DRAWER-0105',
                'reference_number' => null,
                'date' => '2026-09-07',
                'debit_code' => '1000',
                'credit_code' => '1200',
                'amount' => '500.00',
                'party' => 'Horizon Wealth Management',
                'transfer_type' => PaymentTransferType::CASH,
                'notes' => 'Petty cash float counted out to the office.',
            ],
            [
                'number' => 'RCP-WALLET-0101',
                'reference_number' => null,
                'date' => '2026-09-18',
                'debit_code' => '1150',
                'credit_code' => '1200',
                'amount' => '1200.00',
                'party' => 'Tradenet Financial Terminals',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Invoice settled through the Touch n Go wallet.',
            ],
            [
                'number' => 'RCP-WALLET-0102',
                'reference_number' => null,
                'date' => '2026-09-22',
                'debit_code' => '1151',
                'credit_code' => '1200',
                'amount' => '640.00',
                'party' => 'Khoo & Sons Private Investment',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Invoice settled through GrabPay.',
            ],
            // Held back deliberately so the drawer page shows a document still
            // waiting to be approved, which is the state a real drawer spends
            // most of its time in.
            [
                'number' => 'RCP-DRAWER-0104',
                'reference_number' => null,
                'date' => '2026-09-25',
                'debit_code' => '1140',
                'credit_code' => '1200',
                'amount' => '90.00',
                'party' => 'Horizon Wealth Management',
                'transfer_type' => PaymentTransferType::CASH,
                'notes' => 'Awaiting approval, so the drawer has not been credited yet.',
                'status' => LedgerStatus::PENDING,
            ],
        ];
    }

    /**
     * Money paid out of a drawer or a wallet.
     *
     * @return array<int, array<string, mixed>>
     */
    private function vouchers(): array
    {
        return [
            [
                'number' => 'PV-DRAWER-0101',
                'reference_number' => null,
                'date' => '2026-09-10',
                'debit_code' => '2100',
                'credit_code' => '1000',
                'amount' => '120.00',
                'party' => 'Lembaga Hasil Dalam Negeri Malaysia (LHDN)',
                'transfer_type' => PaymentTransferType::CASH,
                'notes' => 'Cleaning and supplies paid from petty cash.',
            ],
            [
                'number' => 'PV-DRAWER-0102',
                'reference_number' => null,
                'date' => '2026-09-14',
                'debit_code' => '2000',
                'credit_code' => '1140',
                'amount' => '300.00',
                'party' => 'VSTECS Berhad',
                'transfer_type' => PaymentTransferType::CASH,
                'notes' => 'Freight paid out of drawer 1 in notes.',
            ],
            [
                'number' => 'PV-DRAWER-0103',
                'reference_number' => null,
                'date' => '2026-09-19',
                'debit_code' => '1100',
                'credit_code' => '1140',
                'amount' => '400.00',
                'party' => 'Horizon Wealth Management',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                // Cash banked up: money leaves the drawer and arrives in the
                // bank, which is the only movement that touches both money
                // ledgers at once and so is the one that proves both agree.
                'notes' => 'Drawer 1 banked up into the Maybank current account.',
            ],
            [
                'number' => 'PV-WALLET-0101',
                'reference_number' => null,
                'date' => '2026-09-21',
                'debit_code' => '2100',
                'credit_code' => '1151',
                'amount' => '250.00',
                'party' => 'DHL Express Malaysia',
                'transfer_type' => PaymentTransferType::BANK_TRANSFER,
                'notes' => 'Office supplies paid through GrabPay.',
            ],
        ];
    }

    /**
     * Raise and post one receipt.
     *
     * @param  array<string, mixed>  $receipt
     *
     * @throws Throwable
     */
    private function seedReceipt(array $receipt): void
    {
        $debitId = $this->chartId($receipt['debit_code'], $receipt['number']);
        $creditId = $this->chartId($receipt['credit_code'], $receipt['number']);
        $company = $this->company($receipt['party'], $receipt['number']);

        DB::transaction(function () use ($receipt, $company, $debitId, $creditId): void {
            $existing = Receipt::query()->where('document_number', $receipt['number'])->first();

            if ($existing !== null) {
                // The ledger rows go first. Deleting only the document would
                // leave its postings behind, and posting it again would add a
                // second set on top of them — so a re-seed would quietly double
                // every drawer balance instead of replacing it.
                $this->purgePostedRows($receipt['number']);

                $existing->lines()->delete();
                $existing->forceDelete();
            }

            $model = Receipt::query()->create([
                'document_number' => $receipt['number'],
                'company_id' => $company->id,
                'company_name' => $company->name,
                'date' => $receipt['date'],
                'status' => $receipt['status'] ?? LedgerStatus::POSTED,
                'payment_transfer_type' => $receipt['transfer_type'],
                'reference_number' => $receipt['reference_number'],
                'description' => $receipt['notes'],
                'notes' => $receipt['notes'],
                'total_amount' => $receipt['amount'],
                'created_name' => 'System Seeder',
                'created_at' => $receipt['date'],
                'approved_name' => ($receipt['status'] ?? LedgerStatus::POSTED) === LedgerStatus::POSTED ? 'Manager User' : null,
                'approved_at' => ($receipt['status'] ?? LedgerStatus::POSTED) === LedgerStatus::POSTED ? $receipt['date'] : null,
            ]);

            $model->lines()->create([
                'debit_id' => $debitId,
                'debit_acc' => $receipt['debit_code'],
                'amount' => $receipt['amount'],
                'description' => $receipt['notes'],
            ]);

            $model->lines()->create([
                'credit_id' => $creditId,
                'credit_acc' => $receipt['credit_code'],
                'amount' => $receipt['amount'],
                'description' => $receipt['notes'],
            ]);

            $this->postSeededDocument($model, LedgerSubType::RECEIPT, 'document_number', $this->poster);
        });
    }

    /**
     * Raise and post one payment voucher.
     *
     * @param  array<string, mixed>  $voucher
     *
     * @throws Throwable
     */
    private function seedVoucher(array $voucher): void
    {
        $debitId = $this->chartId($voucher['debit_code'], $voucher['number']);
        $creditId = $this->chartId($voucher['credit_code'], $voucher['number']);
        $company = $this->company($voucher['party'], $voucher['number']);

        DB::transaction(function () use ($voucher, $company, $debitId, $creditId): void {
            $existing = PaymentVoucher::query()
                ->where('document_number', $voucher['number'])
                ->first();

            if ($existing !== null) {
                // See seedReceipt(): the document alone is not what a re-seed
                // leaves behind, its postings are.
                $this->purgePostedRows($voucher['number']);

                $existing->lines()->delete();
                $existing->forceDelete();
            }

            $model = PaymentVoucher::query()->create([
                'document_number' => $voucher['number'],
                'company_id' => $company->id,
                'company_name' => $company->name,
                'date' => $voucher['date'],
                'status' => LedgerStatus::POSTED,
                'payment_transfer_type' => $voucher['transfer_type'],
                'reference_number' => $voucher['reference_number'],
                'description' => $voucher['notes'],
                'notes' => $voucher['notes'],
                'total_amount' => $voucher['amount'],
                'created_name' => 'System Seeder',
                'created_at' => $voucher['date'],
                'approved_name' => 'Manager User',
                'approved_at' => $voucher['date'],
            ]);

            $model->lines()->create([
                'debit_id' => $debitId,
                'debit_acc' => $voucher['debit_code'],
                'amount' => $voucher['amount'],
                'description' => $voucher['notes'],
            ]);

            $model->lines()->create([
                'credit_id' => $creditId,
                'credit_acc' => $voucher['credit_code'],
                'amount' => $voucher['amount'],
                'description' => $voucher['notes'],
            ]);

            $this->postSeededDocument(
                $model,
                LedgerSubType::PAYMENT_VOUCHER,
                'document_number',
                $this->poster,
            );
        });
    }

    /**
     * The chart a movement is booked against.
     *
     * @param  string  $code  The chart code.
     * @param  string  $number  The document, for the failure message.
     */
    private function chartId(string $code, string $number): ?string
    {
        $id = Chart::query()->where('code', $code)->value('id');

        if ($id === null) {
            throw new LogicException("Money ledger seed names a chart that does not exist: {$code} ({$number})");
        }

        return $id;
    }

    /**
     * The company a movement belongs to.
     *
     * @param  string  $name  The company name.
     * @param  string  $number  The document, for the failure message.
     */
    private function company(string $name, string $number): object
    {
        $company = DB::table('companies')->where('name', $name)->first();

        if ($company === null) {
            throw new LogicException("Money ledger seed names a company that does not exist: {$name} ({$number})");
        }

        return $company;
    }
}
