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
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Models\Bank\CashAccount;
use App\Services\DecimalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What is in the cash drawer.
 *
 * The general ledger says what the business earned and owed. This says what is
 * physically in each cash account, and it is a separate question because the two
 * answers can differ for a reason no ledger records: money that was in the drawer
 * at the close is not there this morning. A drawer is counted by a person
 * standing in front of it, not derived from a posting rule, so it gets its own
 * page rather than a line in the general ledger.
 *
 * It is separate from the bank and wallet pages because a cash balance is a
 * float someone is responsible for, where a bank balance is confirmed against
 * a statement and a wallet against a phone. Listing them together would put a
 * drawer in the same column as a current account, which is the comparison that
 * cannot be made.
 *
 * Nothing here writes. Rows reach this table only by posting a document whose
 * money account is a cash one, so a balance shown here cannot disagree with the
 * ledger unless the posting itself was wrong.
 */
class CashLedgerController extends Controller
{
    /**
     * Show the cash ledger.
     *
     * @param  Request  $request  The list filters.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::CASH_LEDGERS);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $accountId = $request->string('account_id')->toString();

        $accounts = CashAccount::query()
            ->where('is_active', true)
            ->with('chart')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('bank.money-ledger.index', [
            'ledger' => 'cash',
            'kindLabel' => BankAccountKind::CASH->label(),
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
     * The totals for each cash account, calculated from ledger rows.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, CashAccount>  $accounts
     * @return Collection<int, object{account: CashAccount, money_in: string, money_out: string, balance: numeric-string, isFiltered: bool}>
     */
    private function balancesFor($accounts, string $accountId): Collection
    {
        /** @var Collection<int, object{account: CashAccount, money_in: string, money_out: string, balance: numeric-string, isFiltered: bool}> $balances */
        $balances = $accounts->map(function (CashAccount $account) use ($accountId): object {
            $totals = DB::table('cash_ledgers')
                ->where('cash_account_id', $account->id)
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

            /** @var object{account: CashAccount, money_in: string, money_out: string, balance: numeric-string, isFiltered: bool}&\stdClass $row */
            $row = (object) [
                'account' => $account,
                'money_in' => (string) $moneyIn,
                'money_out' => (string) $moneyOut,
                'balance' => (string) bcsub($moneyIn, $moneyOut, 2),
                'isFiltered' => $accountId !== '' && $accountId === (string) $account->id,
            ];

            return $row;
        });

        return $balances;
    }

    /**
     * The cash ledger rows, filtered and paginated.
     *
     * @return LengthAwarePaginator<int, \stdClass>
     */
    private function ledgerRows(
        string $search,
        string $startDate,
        string $endDate,
        string $accountId
    ): LengthAwarePaginator {
        return DB::table('cash_ledgers')
            ->leftJoin('cash_accounts', 'cash_accounts.id', '=', 'cash_ledgers.cash_account_id')
            ->select('cash_ledgers.*', 'cash_accounts.name as account_name')
            ->when($accountId !== '', fn (QueryBuilder $query): QueryBuilder => $query->where('cash_ledgers.cash_account_id', $accountId))
            ->when($search !== '', fn (QueryBuilder $query): QueryBuilder => $query->where(function (QueryBuilder $query) use ($search): void {
                $query->where('cash_ledgers.document_number', 'like', "%$search%")
                    ->orWhere('cash_ledgers.reference_number', 'like', "%$search%")
                    ->orWhere('cash_ledgers.description', 'like', "%$search%");
            }))
            ->when($startDate !== '', fn (QueryBuilder $query): QueryBuilder => $query->whereDate('cash_ledgers.date', '>=', $startDate))
            ->when($endDate !== '', fn (QueryBuilder $query): QueryBuilder => $query->whereDate('cash_ledgers.date', '<=', $endDate))
            ->whereNull('cash_ledgers.deleted_at')
            ->orderByDesc('cash_ledgers.date')
            ->orderByDesc('cash_ledgers.created_at')
            ->paginate(self::PER_PAGE_GRID_STANDARD)
            ->withQueryString();
    }
}
