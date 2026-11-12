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

namespace App\Http\Controllers\Bank;

use App\Enums\Bank\BankAccountKind;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Models\Bank\Bank;
use App\Services\DecimalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What is in each bank account.
 *
 * Where the cash ledger answers a question somebody has to go and look at, this
 * one answers a question somebody can look up: the figure is confirmed against a
 * statement rather than counted on the spot, and the difference between what this
 * page says and what the statement says is the reconciliation.
 *
 * Each bank account is its own column of figures rather than one total, because
 * money sitting in a fixed deposit cannot pay a supplier and a current account
 * can. A single number across them would answer no question anyone actually has.
 *
 * Nothing here writes. Rows reach this table only by posting a document whose
 * money account is a bank one.
 */
class BankLedgerController extends Controller
{
    /**
     * Show the bank ledger.
     *
     * @param  Request  $request  The list filters.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::BANK_LEDGERS, FileAction::READ);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $accountId = $request->string('account_id')->toString();

        $accounts = Bank::query()
            ->where('is_active', true)
            ->with('chart')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('bank.money-ledger.index', [
            'ledger' => 'bank',
            'kindLabel' => BankAccountKind::BANK->label(),
            'rows' => $this->ledgerRows($search, $startDate, $endDate, $accountId),
            'accounts' => $accounts,
            'balances' => $this->balancesFor($accounts, $accountId),
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'accountId' => $accountId,
        ]);
    }

    /**
     * The totals for each bank account, calculated from ledger rows.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Bank>  $accounts
     * @return Collection<int, object{account: Bank, money_in: string, money_out: string, balance: string, isFiltered: bool}>
     */
    private function balancesFor($accounts, string $accountId): Collection
    {
        return $accounts->map(function (Bank $account) use ($accountId): object {
            $totals = DB::table('bank_ledgers')
                ->where('bank_account_id', $account->id)
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
                'isFiltered' => $accountId !== '' && $accountId === (string) $account->id,
            ];
        });
    }

    /**
     * The bank ledger rows, filtered and paginated.
     *
     * @return LengthAwarePaginator<int, \stdClass>
     */
    private function ledgerRows(
        string $search,
        string $startDate,
        string $endDate,
        string $accountId
    ): LengthAwarePaginator {
        return DB::table('bank_ledgers')
            ->leftJoin('banks', 'banks.id', '=', 'bank_ledgers.bank_account_id')
            ->select('bank_ledgers.*', 'banks.name as account_name')
            ->when($accountId !== '', fn (QueryBuilder $query): QueryBuilder => $query->where('bank_ledgers.bank_account_id', $accountId))
            ->when($search !== '', fn (QueryBuilder $query): QueryBuilder => $query->where(function (QueryBuilder $query) use ($search): void {
                $query->where('bank_ledgers.document_number', 'like', "%$search%")
                    ->orWhere('bank_ledgers.reference_number', 'like', "%$search%")
                    ->orWhere('bank_ledgers.description', 'like', "%$search%");
            }))
            ->when($startDate !== '', fn (QueryBuilder $query): QueryBuilder => $query->whereDate('bank_ledgers.date', '>=', $startDate))
            ->when($endDate !== '', fn (QueryBuilder $query): QueryBuilder => $query->whereDate('bank_ledgers.date', '<=', $endDate))
            ->whereNull('bank_ledgers.deleted_at')
            ->orderByDesc('bank_ledgers.date')
            ->orderByDesc('bank_ledgers.created_at')
            ->paginate(self::PER_PAGE_GRID_STANDARD)
            ->withQueryString();
    }
}
