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

namespace App\Support\Posting\Concerns;

use App\Enums\BankAccountKind;
use App\Models\Bank\Bank;
use App\Models\Bank\CashAccount;
use App\Models\Bank\EWalletAccount;
use App\Services\DecimalService;
use App\Support\Posting\InvoiceLedgerPoster;
use App\Support\Posting\PaymentLedgerPoster;
use App\Support\PostingException;
use Illuminate\Support\Facades\DB;

/**
 * The parts of posting that do not care what is being posted.
 *
 * Looking up the currency, the bank/cash/e-wallet accounts and the chart accounts,
 * working out which table a ledger kind writes to, carrying an account's balance
 * forward, and writing a batch of rows — all of that is identical whether the
 * document is an invoice or a payment. Only the routing differs, and that is
 * what each poster owns.
 *
 * @see InvoiceLedgerPoster
 * @see PaymentLedgerPoster
 */
trait WritesLedgerTables
{
    /**
     * The currency every figure of this post is in, as a reference.
     *
     * @return array{id: string}
     */
    private function defaultCurrency(): array
    {
        $currency = DB::table('currencies')->where('is_default', true)->first(['id']);

        if ($currency === null) {
            throw PostingException::noDefaultCurrency();
        }

        $currencyId = $currency->id;

        return [
            'id' => is_string($currencyId) ? $currencyId : '',
        ];
    }

    /**
     * Every bank account, keyed by the chart account it posts to.
     *
     * @return array<string, Bank>
     */
    private function bankAccountsByChart(): array
    {
        /** @var array<string, Bank> $result */
        $result = Bank::query()
            ->whereNotNull('chart_id')
            ->where('is_active', true)
            ->get()
            ->keyBy('chart_id')
            ->all();

        return $result;
    }

    /**
     * Every cash account, keyed by the chart account it posts to.
     *
     * @return array<string, CashAccount>
     */
    private function cashAccountsByChart(): array
    {
        /** @var array<string, CashAccount> $result */
        $result = CashAccount::query()
            ->whereNotNull('chart_id')
            ->where('is_active', true)
            ->get()
            ->keyBy('chart_id')
            ->all();

        return $result;
    }

    /**
     * Every e-wallet account, keyed by the chart account it posts to.
     *
     * @return array<string, EWalletAccount>
     */
    private function eWalletAccountsByChart(): array
    {
        /** @var array<string, EWalletAccount> $result */
        $result = EWalletAccount::query()
            ->whereNotNull('chart_id')
            ->where('is_active', true)
            ->get()
            ->keyBy('chart_id')
            ->all();

        return $result;
    }

    /**
     * Every chart account, keyed by id, with the fields the poster snapshots.
     *
     * @return array<string, array{code: string|null, name: string|null, description: string|null, report_group: string|null, is_debtor: bool, is_creditor: bool}>
     */
    private function chartsById(): array
    {
        /** @var array<string, array{code: string|null, name: string|null, description: string|null, report_group: string|null, is_debtor: bool, is_creditor: bool}>|null $cache */
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = [];

        $columns = ['id', 'code', 'name', 'description', 'report_group', 'isDebtor', 'isCreditor'];

        foreach (DB::table('charts')->get($columns) as $chart) {
            /** @var string $id */
            $id = $chart->id;
            $cache[$id] = [
                'code' => is_string($chart->code) ? $chart->code : null,
                'name' => is_string($chart->name) ? $chart->name : null,
                'description' => is_string($chart->description) ? $chart->description : null,
                'report_group' => is_string($chart->report_group) ? $chart->report_group : null,
                'is_debtor' => (bool) $chart->isDebtor,
                'is_creditor' => (bool) $chart->isCreditor,
            ];
        }

        return $cache;
    }

    /**
     * The balance an account held before this posting.
     *
     * @return numeric-string The balance, exact to two decimal places.
     */
    private function priorBalance(BankAccountKind $kind, string $accountId): string
    {
        $balance = DB::table($this->ledgerTable($kind))
            ->where($this->ledgerAccountColumn($kind), $accountId)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(debit_amount) - SUM(credit_amount), 0) as balance')
            ->value('balance');

        return DecimalService::normalize(is_numeric($balance) ? (string) $balance : '0');
    }

    /**
     * The ledger table a kind writes to.
     */
    private function ledgerTable(BankAccountKind $kind): string
    {
        return match ($kind) {
            BankAccountKind::CASH => 'cash_ledgers',
            BankAccountKind::BANK => 'bank_ledgers',
            BankAccountKind::E_WALLET => 'e_wallet_ledgers',
            default => throw PostingException::unknownLedgerKind($kind->value),
        };
    }

    /**
     * The account column a ledger kind names its rows by.
     */
    private function ledgerAccountColumn(BankAccountKind $kind): string
    {
        return match ($kind) {
            BankAccountKind::CASH => 'cash_account_id',
            BankAccountKind::BANK => 'bank_account_id',
            BankAccountKind::E_WALLET => 'e_wallet_account_id',
            default => throw PostingException::unknownLedgerKind($kind->value),
        };
    }

    /**
     * Write a set of rows, ignoring an empty set.
     *
     * @param  string  $table  The table to write to.
     * @param  list<array<string, mixed>>  $rows  Rows to write.
     * @return int The number written.
     */
    private function writeMany(string $table, array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        DB::table($table)->insert($rows);

        return count($rows);
    }
}
