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

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/**
 * Seed journal sources and post their balanced lines to the General Ledger.
 */
class JournalSeeder extends Seeder
{
    /**
     * Seed posted journals followed by their year-end closing journals.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->journalBlueprints() as $entry) {
                $this->seedPostedJournal($entry);
            }

            foreach ([2024, 2025] as $year) {
                $this->seedYearEndClosingJournal($year);
            }

            foreach ($this->cancelledJournalBlueprints() as $entry) {
                $this->seedNonPostedJournal($entry, LedgerStatus::CANCELLED);
            }

            foreach ($this->pendingJournalBlueprints() as $entry) {
                $this->seedNonPostedJournal($entry, LedgerStatus::PENDING);
            }
        });
    }

    /**
     * Seed one journal entry and use each item UUID for its posted GL row.
     *
     * @param  array{document_number: string, reference_number: string, date: string, notes: string, items: list<array{coa: string, description: string, debit_amount: float, credit_amount: float}>}  $entry
     */
    private function seedPostedJournal(array $entry): void
    {
        $totalDebit = round((float) collect($entry['items'])->sum('debit_amount'), 2);
        $totalCredit = round((float) collect($entry['items'])->sum('credit_amount'), 2);

        if ($totalDebit !== $totalCredit) {
            throw new LogicException('Unbalanced seeded journal: '.$entry['document_number']);
        }

        $now = now();
        $journalEntry = DB::table('journal_entries')
            ->where('document_number', $entry['document_number'])
            ->first(['id', 'created_at']);
        $journalEntryId = $journalEntry->id ?? (string) Str::uuid7();

        DB::table('journal_entries')->updateOrInsert(
            ['document_number' => $entry['document_number']],
            [
                'id' => $journalEntryId,
                'reference_number' => $entry['reference_number'],
                'date' => $entry['date'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => LedgerStatus::POSTED->value,
                'notes' => $entry['notes'],
                'created_name' => 'System Seeder',
                'created_at' => $journalEntry->created_at ?? $now,
                'approved_name' => 'Manager User',
                'approved_at' => $journalEntry->created_at ?? $now,
                'updated_at' => $now,
            ]
        );

        $existingItems = DB::table('journal_entry_charts')
            ->where('journal_entry_id', $journalEntryId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'created_at']);

        foreach ($entry['items'] as $index => $item) {
            $lineNumber = $index + 1;
            $ledgerFields = $this->journalLineFields($item);
            $journalItem = $existingItems->get($index);
            $journalItemId = $journalItem->id ?? (string) Str::uuid7();

            DB::table('journal_entry_charts')->updateOrInsert(
                ['id' => $journalItemId],
                [
                    'journal_entry_id' => $journalEntryId,
                    ...$ledgerFields,
                    'document_number' => $entry['document_number'],
                    'reference_number' => $entry['reference_number'] ?: $entry['document_number'],
                    'date' => $entry['date'],
                    'description' => $item['description'],
                    'debit_amount' => $item['debit_amount'],
                    'credit_amount' => $item['credit_amount'],
                    'deleted_at' => null,
                    'created_at' => $journalItem->created_at ?? $now,
                    'updated_at' => $now,
                ]
            );

            $this->postJournalItem(
                $journalItemId,
                $entry,
                $item,
                $now
            );
        }
    }

    /**
     * Build the account-side fields shared by journal items and ledger rows.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function journalLineFields(array $item): array
    {
        [$accountCode] = array_pad(explode(' - ', $item['coa'], 2), 1, '');
        $chart = DB::table('charts')
            ->where('code', trim($accountCode))
            ->first(['id', 'code', 'name']);

        if ($chart === null) {
            throw new LogicException('Journal account not found in charts: '.$item['coa']);
        }

        $isDebit = (float) $item['debit_amount'] > 0;

        return [
            'debit_id' => $isDebit ? $chart->id : null,
            'credit_id' => $isDebit ? null : $chart->id,
            'debit_acc' => $isDebit ? $chart->code : null,
            'credit_acc' => $isDebit ? null : $chart->code,
            'debit_acc_desc' => $isDebit ? $chart->name : null,
            'credit_acc_desc' => $isDebit ? null : $chart->name,
            'total_amount' => round(max(
                (float) $item['debit_amount'],
                (float) $item['credit_amount']
            ), 2),
        ];
    }

    /**
     * Post one journal item to GL using the source journal item UUID.
     *
     * @param  string  $journalItemId  The journal item UUID to post.
     * @param  array{document_number: string, reference_number: string, date: string, notes: string, items: list<array{coa: string, description: string, debit_amount: float, credit_amount: float}>}  $entry
     * @param  array<string, mixed>  $item
     * @param  mixed  $now  The current timestamp for seeding records.
     */
    private function postJournalItem(
        string $journalItemId,
        array $entry,
        array $item,
        mixed $now
    ): void {
        [$accountCode, $fallbackDescription] = array_pad(
            explode(' - ', $item['coa'], 2),
            2,
            ''
        );
        $chart = DB::table('charts')
            ->where('code', trim($accountCode))
            ->first(['id', 'code', 'name']);

        if ($chart === null) {
            throw new LogicException('Journal account not found in charts: '.$item['coa']);
        }

        $isDebit = (float) $item['debit_amount'] > 0;
        $amount = round(max(
            (float) $item['debit_amount'],
            (float) $item['credit_amount']
        ), 2);
        $referenceNumber = $entry['reference_number'] ?: $entry['document_number'];

        DB::table('ledgers')->updateOrInsert(
            ['id' => $journalItemId],
            [
                'source_id' => $journalItemId,
                'source_type' => null,
                'company_id' => null,
                'contact_id' => null,
                'debit_id' => $isDebit ? $chart->id : null,
                'credit_id' => $isDebit ? null : $chart->id,
                'debit_acc' => $isDebit ? $chart->code : null,
                'credit_acc' => $isDebit ? null : $chart->code,
                'debit_acc_desc' => $isDebit ? $chart->name : null,
                'credit_acc_desc' => $isDebit ? null : $chart->name,
                'company_name' => null,
                'document_number' => $entry['document_number'],
                'reference_number' => $referenceNumber,
                'type' => LedgerType::JOURNAL->value,
                'subtype' => LedgerSubType::JOURNAL->value,
                'date' => $entry['date'],
                'document_date' => $entry['date'],
                'debit_amount' => $item['debit_amount'],
                'credit_amount' => $item['credit_amount'],
                'total_amount' => $amount,
                'status' => LedgerStatus::POSTED->value,
                'description' => $item['description'] ?: $fallbackDescription,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    /**
     * Build and post the closing journal for one completed financial year.
     */
    private function seedYearEndClosingJournal(int $year): void
    {
        $balances = $this->profitAndLossBalancesForYear($year);
        $items = [];
        $profit = 0.00;

        ksort($balances);

        foreach ($balances as $accountCode => $balance) {
            $netDebit = round(
                $balance['debit'] - $balance['credit'],
                2
            );

            if ($netDebit > 0) {
                $items[] = [
                    'coa' => $accountCode,
                    'description' => 'Close expense account for FY '.$year,
                    'debit_amount' => 0.00,
                    'credit_amount' => $netDebit,
                ];
                $profit -= $netDebit;
            } elseif ($netDebit < 0) {
                $items[] = [
                    'coa' => $accountCode,
                    'description' => 'Close income account for FY '.$year,
                    'debit_amount' => abs($netDebit),
                    'credit_amount' => 0.00,
                ];
                $profit += abs($netDebit);
            }
        }

        $items[] = [
            'coa' => '3100',
            'description' => $profit >= 0
                ? 'Transfer FY '.$year.' net profit to retained earnings'
                : 'Transfer FY '.$year.' net loss to retained earnings',
            'debit_amount' => $profit >= 0 ? 0.00 : round(abs($profit), 2),
            'credit_amount' => $profit >= 0 ? round($profit, 2) : 0.00,
        ];

        $this->seedPostedJournal([
            'document_number' => 'YEC'.$year,
            'reference_number' => 'YEAR-END-SEED:'.$year,
            'date' => $year.'-12-31',
            'notes' => 'Year-end closing entry for FY '.$year.'.',
            'items' => $items,
        ]);
    }

    /**
     * Calculate P&L account movements from posted GL rows only.
     *
     * @param  int  $year  The financial year to calculate balances for.
     * @return array<string, array{debit: float, credit: float}>
     */
    private function profitAndLossBalancesForYear(int $year): array
    {
        $balances = [];
        $ledgerRows = DB::table('ledgers')
            ->where('status', LedgerStatus::POSTED->value)
            ->whereYear('date', $year)
            ->where('document_number', 'not like', 'YEC%')
            ->get(['debit_acc', 'credit_acc', 'debit_amount', 'credit_amount']);

        foreach ($ledgerRows as $row) {
            if ($this->isProfitAndLossAccount($row->debit_acc)) {
                $balances[$row->debit_acc]['debit'] =
                    ($balances[$row->debit_acc]['debit'] ?? 0) + (float) $row->debit_amount;
                $balances[$row->debit_acc]['credit'] =
                    $balances[$row->debit_acc]['credit'] ?? 0;
            }

            if ($this->isProfitAndLossAccount($row->credit_acc)) {
                $balances[$row->credit_acc]['credit'] =
                    ($balances[$row->credit_acc]['credit'] ?? 0) + (float) $row->credit_amount;
                $balances[$row->credit_acc]['debit'] =
                    $balances[$row->credit_acc]['debit'] ?? 0;
            }
        }

        return $balances;
    }

    /**
     * Determine whether an account code belongs to income or expenses.
     */
    private function isProfitAndLossAccount(?string $accountCode): bool
    {
        if ($accountCode === null || $accountCode === '') {
            return false;
        }

        $code = (int) trim(strtok($accountCode, ' '));

        return $code >= 4000 && $code < 7000;
    }

    /**
     * Return normal and adjustment journals before year-end closing.
     *
     * @return list<array{document_number: string, reference_number: string, date: string, notes: string, items: list<array{coa: string, description: string, debit_amount: float, credit_amount: float}>}>
     */
    private function journalBlueprints(): array
    {
        return [
            [
                'document_number' => 'JV-24001',
                'reference_number' => 'OB-2024-001',
                'date' => '2024-01-01',
                'notes' => 'Opening capital introduced to start FY 2024 operations.',
                'items' => [
                    [
                        'coa' => '1100',
                        'description' => 'Initial working capital deposited',
                        'debit_amount' => 150000.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '3200',
                        'description' => 'Owner capital contribution',
                        'debit_amount' => 0.00,
                        'credit_amount' => 150000.00,
                    ],
                ],
            ],
            [
                'document_number' => 'SIM-2024',
                'reference_number' => 'RE-SIMULATION-2024',
                'date' => '2024-12-15',
                'notes' => 'First-year activity with zero opening retained earnings.',
                'items' => [
                    [
                        'coa' => '1100',
                        'description' => 'FY 2024 revenue received',
                        'debit_amount' => 175000.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '4000',
                        'description' => 'FY 2024 sales revenue',
                        'debit_amount' => 0.00,
                        'credit_amount' => 175000.00,
                    ],
                    [
                        'coa' => '5000',
                        'description' => 'FY 2024 operating expenses',
                        'debit_amount' => 105000.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1100',
                        'description' => 'Payment of FY 2024 operating expenses',
                        'debit_amount' => 0.00,
                        'credit_amount' => 105000.00,
                    ],
                    [
                        'coa' => '3100',
                        'description' => 'FY 2024 owner distributions and equity adjustments',
                        'debit_amount' => 23000.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1100',
                        'description' => 'Payment of FY 2024 distributions and adjustments',
                        'debit_amount' => 0.00,
                        'credit_amount' => 23000.00,
                    ],
                ],
            ],
            [
                'document_number' => 'JV-25002',
                'reference_number' => 'CAPEX-2025-001',
                'date' => '2025-02-01',
                'notes' => 'Capital expenditure and financing for FY 2025.',
                'items' => [
                    [
                        'coa' => '1500',
                        'description' => 'Workstations and office setup',
                        'debit_amount' => 18000.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1600',
                        'description' => 'Company vehicle financed through bank term loan',
                        'debit_amount' => 48000.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1400',
                        'description' => 'Annual insurance paid in advance',
                        'debit_amount' => 6600.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1100',
                        'description' => 'Cash paid for office equipment and insurance',
                        'debit_amount' => 0.00,
                        'credit_amount' => 24600.00,
                    ],
                    [
                        'coa' => '2200',
                        'description' => 'Vehicle financing arranged with bank',
                        'debit_amount' => 0.00,
                        'credit_amount' => 48000.00,
                    ],
                ],
            ],
            [
                'document_number' => 'JV-25004',
                'reference_number' => 'ADJ-2025-012',
                'date' => '2025-12-31',
                'notes' => 'Year-end accruals, amortisation, depreciation, and distribution.',
                'items' => [
                    [
                        'coa' => '6400',
                        'description' => 'Full-year depreciation charge',
                        'debit_amount' => 8400.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '5800',
                        'description' => 'Insurance expense recognised for the year',
                        'debit_amount' => 6600.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '5600',
                        'description' => 'December utilities accrued but unpaid',
                        'debit_amount' => 1250.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '3100',
                        'description' => 'Distribution paid directly from retained earnings',
                        'debit_amount' => 12000.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1900',
                        'description' => 'Accumulated depreciation on fixed assets',
                        'debit_amount' => 0.00,
                        'credit_amount' => 8400.00,
                    ],
                    [
                        'coa' => '1400',
                        'description' => 'Insurance prepayment amortised',
                        'debit_amount' => 0.00,
                        'credit_amount' => 6600.00,
                    ],
                    [
                        'coa' => '2100',
                        'description' => 'Utility accrual at year-end',
                        'debit_amount' => 0.00,
                        'credit_amount' => 1250.00,
                    ],
                    [
                        'coa' => '1100',
                        'description' => 'Cash distribution paid',
                        'debit_amount' => 0.00,
                        'credit_amount' => 12000.00,
                    ],
                ],
            ],
            [
                'document_number' => 'JV-26003',
                'reference_number' => 'PAYROLL-2026-Q1',
                'date' => '2026-03-31',
                'notes' => 'Q1 2026 payroll accrual and employer EPF/SOCSO contributions.',
                'items' => [
                    [
                        'coa' => '5100',
                        'description' => 'Staff salaries for Q1 2026',
                        'debit_amount' => 45000.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '5200',
                        'description' => 'Employer EPF contribution (13%)',
                        'debit_amount' => 5850.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '5300',
                        'description' => 'Employer SOCSO/EIS contribution',
                        'debit_amount' => 450.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1100',
                        'description' => 'Net payroll and statutory payments disbursed',
                        'debit_amount' => 0.00,
                        'credit_amount' => 51300.00,
                    ],
                ],
            ],
            [
                'document_number' => 'JV-26005',
                'reference_number' => 'PREPAY-2026-Q3',
                'date' => '2026-07-01',
                'notes' => 'Q3 2026 prepaid rental and insurance coverage.',
                'items' => [
                    [
                        'coa' => '1400',
                        'description' => 'Prepaid rental for Jul–Sep 2026',
                        'debit_amount' => 28800.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1400',
                        'description' => 'Prepaid insurance for Jul–Dec 2026',
                        'debit_amount' => 6600.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1100',
                        'description' => 'Cash outflow for prepaid coverage',
                        'debit_amount' => 0.00,
                        'credit_amount' => 35400.00,
                    ],
                ],
            ],
        ];
    }

    /**
     * Return cancelled journal entries for demonstration.
     *
     * @return list<array{document_number: string, reference_number: string, date: string, notes: string, items: list<array{coa: string, description: string, debit_amount: float, credit_amount: float}>}>
     */
    private function cancelledJournalBlueprints(): array
    {
        return [
            [
                'document_number' => 'JV-CANCEL-001',
                'reference_number' => 'CANCEL-REF-001',
                'date' => '2026-07-15',
                'notes' => 'Cancelled journal entry — incorrect account selection.',
                'items' => [
                    [
                        'coa' => '1100',
                        'description' => 'Cash receipt reversed',
                        'debit_amount' => 0.00,
                        'credit_amount' => 2500.00,
                    ],
                    [
                        'coa' => '1200',
                        'description' => 'Accounts receivable adjustment',
                        'debit_amount' => 2500.00,
                        'credit_amount' => 0.00,
                    ],
                ],
            ],
            [
                'document_number' => 'JV-CANCEL-002',
                'reference_number' => 'CANCEL-REF-002',
                'date' => '2026-08-22',
                'notes' => 'Cancelled journal entry — duplicate entry voided.',
                'items' => [
                    [
                        'coa' => '5000',
                        'description' => 'Expense reversal',
                        'debit_amount' => 0.00,
                        'credit_amount' => 1800.00,
                    ],
                    [
                        'coa' => '1100',
                        'description' => 'Cash payment reversed',
                        'debit_amount' => 1800.00,
                        'credit_amount' => 0.00,
                    ],
                ],
            ],
            [
                'document_number' => 'JV-CANCEL-003',
                'reference_number' => 'CANCEL-REF-003',
                'date' => '2026-09-05',
                'notes' => 'Cancelled journal entry — wrong vendor amount entered.',
                'items' => [
                    [
                        'coa' => '6000',
                        'description' => 'Office supplies expense reversed',
                        'debit_amount' => 0.00,
                        'credit_amount' => 3200.00,
                    ],
                    [
                        'coa' => '2000',
                        'description' => 'Accounts payable adjustment',
                        'debit_amount' => 3200.00,
                        'credit_amount' => 0.00,
                    ],
                ],
            ],
            [
                'document_number' => 'JV-CANCEL-004',
                'reference_number' => 'CANCEL-REF-004',
                'date' => '2026-09-12',
                'notes' => 'Cancelled journal entry — project code misallocated.',
                'items' => [
                    [
                        'coa' => '6100',
                        'description' => 'Travel expense reversal',
                        'debit_amount' => 0.00,
                        'credit_amount' => 4500.00,
                    ],
                    [
                        'coa' => '1100',
                        'description' => 'Bank payment reversed',
                        'debit_amount' => 4500.00,
                        'credit_amount' => 0.00,
                    ],
                ],
            ],
        ];
    }

    /**
     * Return pending journal entries awaiting approval.
     *
     * @return list<array{document_number: string, reference_number: string, date: string, notes: string, items: list<array{coa: string, description: string, debit_amount: float, credit_amount: float}>}>
     */
    private function pendingJournalBlueprints(): array
    {
        return [
            [
                'document_number' => 'JV-PENDING-001',
                'reference_number' => 'PENDING-REF-001',
                'date' => '2026-09-18',
                'notes' => 'Pending journal entry — awaiting manager approval.',
                'items' => [
                    [
                        'coa' => '1400',
                        'description' => 'Prepaid insurance adjustment',
                        'debit_amount' => 1200.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1100',
                        'description' => 'Cash payment pending approval',
                        'debit_amount' => 0.00,
                        'credit_amount' => 1200.00,
                    ],
                ],
            ],
            [
                'document_number' => 'JV-PENDING-002',
                'reference_number' => 'PENDING-REF-002',
                'date' => '2026-09-20',
                'notes' => 'Pending journal entry — accrual awaiting review.',
                'items' => [
                    [
                        'coa' => '5600',
                        'description' => 'Utilities accrual for September',
                        'debit_amount' => 850.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '2100',
                        'description' => 'Accrued utilities liability',
                        'debit_amount' => 0.00,
                        'credit_amount' => 850.00,
                    ],
                ],
            ],
            [
                'document_number' => 'JV-PENDING-003',
                'reference_number' => 'PENDING-REF-003',
                'date' => '2026-09-22',
                'notes' => 'Pending journal entry — depreciation schedule pending approval.',
                'items' => [
                    [
                        'coa' => '6400',
                        'description' => 'Monthly depreciation charge',
                        'debit_amount' => 700.00,
                        'credit_amount' => 0.00,
                    ],
                    [
                        'coa' => '1900',
                        'description' => 'Accumulated depreciation',
                        'debit_amount' => 0.00,
                        'credit_amount' => 700.00,
                    ],
                ],
            ],
        ];
    }

    /**
     * Seed one journal entry without posting to GL (for CANCELLED or PENDING status).
     *
     * @param  array{document_number: string, reference_number: string, date: string, notes: string, items: list<array{coa: string, description: string, debit_amount: float, credit_amount: float}>}  $entry
     * @param  LedgerStatus  $status  The ledger status for the journal entry.
     */
    private function seedNonPostedJournal(array $entry, LedgerStatus $status): void
    {
        $totalDebit = round((float) collect($entry['items'])->sum('debit_amount'), 2);
        $totalCredit = round((float) collect($entry['items'])->sum('credit_amount'), 2);

        if ($totalDebit !== $totalCredit) {
            throw new LogicException('Unbalanced seeded journal: '.$entry['document_number']);
        }

        $now = now();
        $journalEntry = DB::table('journal_entries')
            ->where('document_number', $entry['document_number'])
            ->first(['id', 'created_at']);
        $journalEntryId = $journalEntry->id ?? (string) Str::uuid7();

        DB::table('journal_entries')->updateOrInsert(
            ['document_number' => $entry['document_number']],
            [
                'id' => $journalEntryId,
                'reference_number' => $entry['reference_number'],
                'date' => $entry['date'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => $status->value,
                'notes' => $entry['notes'],
                'created_name' => 'System Seeder',
                'created_at' => $journalEntry->created_at ?? $now,
                'cancelled_name' => $status === LedgerStatus::CANCELLED ? 'Manager User' : null,
                'cancelled_at' => $status === LedgerStatus::CANCELLED ? ($journalEntry->created_at ?? $now) : null,
                'updated_at' => $now,
            ]
        );

        $existingItems = DB::table('journal_entry_charts')
            ->where('journal_entry_id', $journalEntryId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'created_at']);

        foreach ($entry['items'] as $index => $item) {
            $lineNumber = $index + 1;
            $ledgerFields = $this->journalLineFields($item);
            $journalItem = $existingItems->get($index);
            $journalItemId = $journalItem->id ?? (string) Str::uuid7();

            DB::table('journal_entry_charts')->updateOrInsert(
                ['id' => $journalItemId],
                [
                    'journal_entry_id' => $journalEntryId,
                    ...$ledgerFields,
                    'document_number' => $entry['document_number'],
                    'reference_number' => $entry['reference_number'] ?: $entry['document_number'],
                    'date' => $entry['date'],
                    'description' => $item['description'],
                    'debit_amount' => $item['debit_amount'],
                    'credit_amount' => $item['credit_amount'],
                    'deleted_at' => null,
                    'created_at' => $journalItem->created_at ?? $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('ledgers')->where('id', $journalItemId)->delete();
        }
    }
}
