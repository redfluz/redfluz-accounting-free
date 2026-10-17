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

use App\Enums\BankAccountKind;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Bank\Concerns\ReadsMoneyLedger;
use App\Http\Controllers\Controller;
use App\Models\Bank\Bank;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

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
    use ReadsMoneyLedger;

    /** The table every bank movement is filed in. */
    private const TABLE = 'bank_ledgers';

    /** The column on that table naming which bank account moved. */
    private const ACCOUNT_COLUMN = 'bank_account_id';

    /**
     * Show the bank ledger.
     *
     * @param  Request  $request  The list filters.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::BANK_LEDGERS, FileAction::READ);

        $filters = $this->filters($request);

        return view('bank.money-ledger.index', [
            'ledger' => 'bank',
            'kindLabel' => BankAccountKind::BANK->label(),
            'rows' => $this->ledgerRows($request, self::TABLE, self::ACCOUNT_COLUMN),
            'accounts' => $this->accountsOfKind(),
            'balances' => $this->balancesFor(
                self::TABLE,
                self::ACCOUNT_COLUMN,
                $filters['accountId']
            ),
            ...$filters,
        ]);
    }

    /**
     * The active bank accounts, default first.
     *
     * @return Collection<int, Bank>
     */
    protected function accountsOfKind(): Collection
    {
        return Bank::query()
            ->where('is_active', true)
            ->with('chart')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * The database table holding bank accounts.
     *
     * @return string The unqualified table name.
     */
    protected function accountTable(): string
    {
        return 'banks';
    }
}
