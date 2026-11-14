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

namespace App\Http\Controllers\Bank\Ledger;

use App\Enums\Bank\BankAccountKind;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Models\Bank\EWalletAccount;
use App\Services\DecimalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What is on each e-wallet.
 *
 * A wallet is neither a drawer nor a bank account, and the difference matters
 * here: a drawer is counted, a bank balance is confirmed against a statement,
 * and a wallet is checked on a phone whenever its holder asks. They are three
 * different acts of verification and one combined figure would hide which of
 * them had actually been done.
 *
 * The account belongs to a provider — Touch 'n Go, GrabPay, Boost — and a
 * provider issues many accounts, so this page is per account and the provider is
 * shown as context rather than treated as the unit of account.
 *
 * Nothing here writes. Rows reach this table only by posting a document whose
 * money account is a wallet one.
 */
class EWalletLedgerController extends Controller
{
    /**
     * Show the e-wallet ledger.
     *
     * @param  Request  $request  The list filters.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::E_WALLET_LEDGERS, FileAction::READ);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $accountId = $request->string('account_id')->toString();

        $accounts = EWalletAccount::query()
            ->where('is_active', true)
            ->with('chart')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('bank.money-ledger.index', [
            'ledger' => 'e-wallet',
            'kindLabel' => BankAccountKind::E_WALLET->label(),
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
     * The totals for each e-wallet account, calculated from ledger rows.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, EWalletAccount>  $accounts
     * @return Collection<int, object{account: EWalletAccount, money_in: string, money_out: string, balance: numeric-string, isFiltered: bool}>
     */
    private function balancesFor($accounts, string $accountId): Collection
    {
        /** @var Collection<int, object{account: EWalletAccount, money_in: string, money_out: string, balance: numeric-string, isFiltered: bool}> $balances */
        $balances = $accounts->map(function (EWalletAccount $account) use ($accountId): object {
            $totals = DB::table('e_wallet_ledgers')
                ->where('e_wallet_account_id', $account->id)
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

            /** @var object{account: EWalletAccount, money_in: string, money_out: string, balance: numeric-string, isFiltered: bool}&\stdClass $row */
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
     * The e-wallet ledger rows, filtered and paginated.
     *
     * @return LengthAwarePaginator<int, \stdClass>
     */
    private function ledgerRows(
        string $search,
        string $startDate,
        string $endDate,
        string $accountId
    ): LengthAwarePaginator {
        return DB::table('e_wallet_ledgers')
            ->leftJoin('e_wallet_accounts', 'e_wallet_accounts.id', '=', 'e_wallet_ledgers.e_wallet_account_id')
            ->select('e_wallet_ledgers.*', 'e_wallet_accounts.name as account_name')
            ->when($accountId !== '', fn (QueryBuilder $query): QueryBuilder => $query->where('e_wallet_ledgers.e_wallet_account_id', $accountId))
            ->when($search !== '', fn (QueryBuilder $query): QueryBuilder => $query->where(function (QueryBuilder $query) use ($search): void {
                $query->where('e_wallet_ledgers.document_number', 'like', "%$search%")
                    ->orWhere('e_wallet_ledgers.reference_number', 'like', "%$search%")
                    ->orWhere('e_wallet_ledgers.description', 'like', "%$search%");
            }))
            ->when($startDate !== '', fn (QueryBuilder $query): QueryBuilder => $query->whereDate('e_wallet_ledgers.date', '>=', $startDate))
            ->when($endDate !== '', fn (QueryBuilder $query): QueryBuilder => $query->whereDate('e_wallet_ledgers.date', '<=', $endDate))
            ->whereNull('e_wallet_ledgers.deleted_at')
            ->orderByDesc('e_wallet_ledgers.date')
            ->orderByDesc('e_wallet_ledgers.created_at')
            ->paginate(self::PER_PAGE_GRID_STANDARD)
            ->withQueryString();
    }
}
