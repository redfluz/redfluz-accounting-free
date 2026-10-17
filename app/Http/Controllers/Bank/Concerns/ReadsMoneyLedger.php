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

namespace App\Http\Controllers\Bank\Concerns;

use App\Http\Controllers\Bank\BankLedgerController;
use App\Http\Controllers\Bank\CashLedgerController;
use App\Http\Controllers\Bank\EWalletLedgerController;
use App\Services\DecimalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * The reading half of a money ledger page.
 *
 * Cash, bank and e-wallet each get their own controller and their own page, but
 * the question each page answers is the same one asked of a different table:
 * what is in each account, and what moved through it. Only the table and the
 * account column change. So the query lives here once, and each controller says
 * which two names it owns.
 *
 * This trait only reads. Nothing in it writes, because a money ledger is written
 * by posting a document and nothing else — a page that could add a row would let
 * a figure be counted into a drawer that no document ever moved, and the balance
 * would stop meaning what it says.
 *
 * @see CashLedgerController
 * @see BankLedgerController
 * @see EWalletLedgerController
 */
trait ReadsMoneyLedger
{
    /**
     * The money accounts of one kind, with what each holds.
     *
     * The totals are added up from the ledger rows rather than read from a
     * stored balance, because a stored balance is only as fresh as the last time
     * something remembered to update it, and this page is what someone opens to
     * find out whether it is fresh.
     *
     * @param  string  $table  The ledger table holding the rows.
     * @param  string  $column  The column naming the account on each row.
     * @param  string  $accountId  Restrict the rows to one account, when filtering.
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    private function balancesFor(
        string $table,
        string $column,
        string $accountId = ''
    ): \Illuminate\Support\Collection {
        // @phpstan-ignore return.type
        return $this->accountsOfKind()
            ->map(function (Model $account) use ($table, $column, $accountId): object {
                $totals = DB::table($table)
                    ->where($column, $account->id)
                    ->whereNull('deleted_at')
                    ->selectRaw(
                        'COALESCE(SUM(debit_amount), 0) as money_in, '
                        .'COALESCE(SUM(credit_amount), 0) as money_out'
                    )
                    ->first();

                $moneyInRaw = $totals->money_in ?? '0';
                $moneyOutRaw = $totals->money_out ?? '0';

                $moneyIn = DecimalService::normalize(is_numeric($moneyInRaw) ? (string) $moneyInRaw : '0');
                $moneyOut = DecimalService::normalize(is_numeric($moneyOutRaw) ? (string) $moneyOutRaw : '0');

                return (object) [
                    'account' => $account,
                    'money_in' => $moneyIn,
                    'money_out' => $moneyOut,
                    'balance' => bcsub($moneyIn, $moneyOut, 2),
                    'isFiltered' => $accountId !== '' && $accountId === $account->id,
                ];
            });
    }

    /**
     * The active money accounts for this ledger, default first.
     *
     * Each controller provides its own implementation that queries the correct
     * account model: CashLedgerController reads CashAccount, BankLedgerController
     * reads Bank, and EWalletLedgerController reads EWalletAccount.
     */
    abstract protected function accountsOfKind(): Collection;

    /**
     * The database table holding the accounts for this ledger.
     *
     * Used to join account names onto the ledger rows so the grid can show
     * which account each movement belongs to without a separate query per row.
     *
     * @return string The unqualified table name (e.g. "banks", "cash_accounts").
     */
    abstract protected function accountTable(): string;

    /**
     * The ledger rows this page lists, filtered and paginated.
     *
     * Each row includes the account name via a join to the account table, so the
     * grid can show which account each movement belongs to. Adding a new account
     * is automatic: the join picks up whatever rows exist in the account table.
     *
     * @param  Request  $request  The list filters.
     * @param  string  $table  The ledger table to read.
     * @param  string  $column  The column naming the account on each row.
     * @return LengthAwarePaginator<int, stdClass>
     */
    private function ledgerRows(Request $request, string $table, string $column): LengthAwarePaginator
    {
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $accountId = $request->string('account_id')->toString();

        $accountTable = $this->accountTable();

        return DB::table($table)
            ->leftJoin($accountTable, "$accountTable.id", '=', "$table.$column")
            ->select("$table.*", "$accountTable.name as account_name")
            ->when($accountId !== '', fn (QueryBuilder $query): QueryBuilder => $query->where("$table.$column", $accountId))
            ->when($search !== '', fn (QueryBuilder $query): QueryBuilder => $query->where(function (QueryBuilder $query) use ($search, $table): void {
                $query->where("$table.document_number", 'like', "%$search%")
                    ->orWhere("$table.reference_number", 'like', "%$search%")
                    ->orWhere("$table.description", 'like', "%$search%");
            }))
            ->when(filled($startDate), fn (QueryBuilder $query): QueryBuilder => $query->whereDate("$table.document_date", '>=', $startDate))
            ->when(filled($endDate), fn (QueryBuilder $query): QueryBuilder => $query->whereDate("$table.document_date", '<=', $endDate))
            ->whereNull("$table.deleted_at")
            ->orderByDesc("$table.document_date")
            ->orderByDesc("$table.created_at")
            ->paginate(static::PER_PAGE_GRID_STANDARD)
            ->withQueryString();
    }

    /**
     * The filter values, read once so the form and the query cannot disagree.
     *
     * @param  Request  $request  The list filters.
     * @return array{search: string, startDate: string, endDate: string, accountId: string}
     */
    private function filters(Request $request): array
    {
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $accountId = $request->string('account_id')->toString();

        return compact('search', 'startDate', 'endDate', 'accountId');
    }
}
