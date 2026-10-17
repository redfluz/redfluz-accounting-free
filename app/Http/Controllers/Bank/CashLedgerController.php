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
use App\Models\Bank\CashAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

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
    use ReadsMoneyLedger;

    /** The table every cash movement is filed in. */
    private const TABLE = 'cash_ledgers';

    /** The column on that table naming which cash account moved. */
    private const ACCOUNT_COLUMN = 'cash_account_id';

    /**
     * Show the cash ledger.
     *
     * @param  Request  $request  The list filters.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::CASH_LEDGERS, FileAction::READ);

        $filters = $this->filters($request);

        return view('bank.money-ledger.index', [
            'ledger' => 'cash',
            'kindLabel' => BankAccountKind::CASH->label(),
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
     * The active cash accounts, default first.
     *
     * @return Collection<int, CashAccount>
     */
    protected function accountsOfKind(): Collection
    {
        return CashAccount::query()
            ->where('is_active', true)
            ->with('chart')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * The database table holding cash accounts.
     *
     * @return string The unqualified table name.
     */
    protected function accountTable(): string
    {
        return 'cash_accounts';
    }
}
