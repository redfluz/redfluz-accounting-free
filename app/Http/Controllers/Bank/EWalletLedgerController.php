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
use App\Models\Bank\EWalletAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

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
    use ReadsMoneyLedger;

    /** The table every e-wallet movement is filed in. */
    private const TABLE = 'e_wallet_ledgers';

    /** The column on that table naming which wallet account moved. */
    private const ACCOUNT_COLUMN = 'e_wallet_account_id';

    /**
     * Show the e-wallet ledger.
     *
     * @param  Request  $request  The list filters.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::E_WALLET_LEDGERS, FileAction::READ);

        $filters = $this->filters($request);

        return view('bank.money-ledger.index', [
            'ledger' => 'e-wallet',
            'kindLabel' => BankAccountKind::E_WALLET->label(),
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
     * The active e-wallet accounts, default first.
     *
     * @return Collection<int, EWalletAccount>
     */
    protected function accountsOfKind(): Collection
    {
        return EWalletAccount::query()
            ->where('is_active', true)
            ->with('chart')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * The database table holding e-wallet accounts.
     *
     * @return string The unqualified table name.
     */
    protected function accountTable(): string
    {
        return 'e_wallet_accounts';
    }
}
