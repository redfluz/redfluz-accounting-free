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

declare(strict_types=1);

namespace App\Http\Controllers\Bank\Concerns;

use App\Models\Bank\Bank;
use App\Models\Bank\CashAccount;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Services\DecimalService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The parts of moving cash in and out of transit that both directions share.
 *
 * There are two screens — a drawer handing its surplus to transit, and transit
 * handing to a bank — and they are deliberately not one screen. They happen on
 * different days, are approved by different people, and mean different things: a
 * transfer out is a cash count, a transfer in is a bank credit. What they share
 * is arithmetic and the one account they share, and that is what lives here.
 */
trait HandlesTransfers
{
    /**
     * The chart holding cash between the drawer and the bank.
     *
     * Taken from chart settings each time rather than cached, so that changing the
     * setting takes effect immediately. Refuses rather than falling back to some
     * default chart: without a transit account the cash would be either counted
     * twice or not at all, and quietly picking a different account would hide
     * that behind a working screen.
     *
     * @throws \RuntimeException When no transit account has been configured.
     */
    private function transitChart(): Chart
    {
        $chartId = ChartSetting::query()->value('cash_in_transit_chart_uuid');

        /** @var Chart|null $chart */
        $chart = is_string($chartId) && $chartId !== ''
            ? Chart::query()->whereKey($chartId)->whereNull('deleted_at')->first()
            : null;

        if (! $chart instanceof Chart) {
            throw new \RuntimeException(
                'No cash in transit account is configured. Set one in Chart Settings before using transfers.'
            );
        }

        return $chart;
    }

    /**
     * What a cash ledger holds, to the cent.
     *
     * Read from the money ledger rather than kept in a column, because the
     * ledger is the record: a balance held elsewhere would be a second answer to
     * the same question, free to disagree with the first. Soft-deleted rows are
     * left out, as a row reversed out of the ledger no longer says anything about
     * the account.
     *
     * @return numeric-string
     */
    private function cashBalance(CashAccount $account): string
    {
        return $this->ledgerBalance('cash_ledgers', 'cash_account_id', $account->id);
    }

    /**
     * What a bank ledger holds, to the cent.
     *
     * @return numeric-string
     */
    private function bankBalance(Bank $account): string
    {
        return $this->ledgerBalance('bank_ledgers', 'bank_account_id', $account->id);
    }

    /**
     * What cash in transit holds, to the cent.
     *
     * Transit is an ordinary chart with no money ledger of its own, so its balance
     * is read from the general ledger the same way a financial report reads it:
     * everything debited to it less everything credited to it.
     *
     * @return numeric-string
     */
    private function transitBalance(Chart $chart): string
    {
        $balance = DB::table('ledgers')
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($chart): void {
                $query->where('debit_id', $chart->id)
                    ->orWhere('credit_id', $chart->id);
            })
            ->selectRaw('COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0) as balance')
            ->value('balance');

        return $this->toAmount(is_numeric($balance) ? (string) $balance : '0');
    }

    /**
     * How much of a drawer is surplus: what it holds above the float it must keep.
     *
     * Never negative. A drawer that has already fallen below its float has no
     * surplus to bank, and reporting a negative one would offer to move money out
     * of a drawer that needs topping up instead.
     *
     * @return numeric-string
     */
    private function surplusFor(CashAccount $account): string
    {
        $balance = $this->cashBalance($account);
        $float = $account->float_amount;

        if ($float === null) {
            return $this->toAmount($balance);
        }

        $surplus = bcsub($balance, DecimalService::normalize($float), 2);

        return bccomp($surplus, '0', 2) < 0 ? $this->toAmount('0') : $this->toAmount($surplus);
    }

    /**
     * Read a debit-less-credit balance out of one of the money ledgers.
     *
     * @return numeric-string
     */
    private function ledgerBalance(string $table, string $column, string $accountId): string
    {
        $balance = DB::table($table)
            ->where($column, $accountId)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(debit_amount) - SUM(credit_amount), 0) as balance')
            ->value('balance');

        return $this->toAmount(is_numeric($balance) ? (string) $balance : '0');
    }

    /**
     * An exact two-place decimal string.
     *
     * The SUM comes back from the database as a string, an int or a float
     * depending on the driver; whichever it is, the number itself is exact enough
     * that its string form is what bcmath expects.
     *
     * @return numeric-string
     */
    private function toAmount(string $value): string
    {
        return bcadd(DecimalService::normalize($value), '0', 2);
    }
}
