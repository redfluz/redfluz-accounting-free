<?php

/**
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace Database\Seeders\Concerns;

use App\Enums\Finance\LedgerStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\Bank\Bank;
use App\Models\Bank\CashAccount;
use App\Models\Bank\EWalletAccount;
use App\Models\Bank\PaymentVoucher;
use App\Models\Bank\Receipt;
use App\Models\Bank\Refund\CustomerRefund;
use App\Models\Bank\Refund\SupplierRefund;
use App\Support\Posting\Posting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Post seeded documents through Posting (no legs).
 */
trait PostsSeededDocuments
{
    /**
     * Remove every ledger row a seeded document has already written.
     */
    protected function purgePostedRows(string $number): void
    {
        foreach (['ledgers', 'ar_ledgers', 'ap_ledgers', 'cash_ledgers', 'bank_ledgers', 'e_wallet_ledgers'] as $table) {
            DB::table($table)->where('document_number', $number)->delete();
        }
    }

    /**
     * The account columns of a voucher, read from the chart the money left.
     *
     * Every money account owns exactly one chart, so the credit chart a seeded
     * voucher is booked against already says which account paid. Recording it on
     * the document is what makes a seeded voucher one the form could have raised,
     * rather than a row whose ledger appears from a chart nobody chose.
     *
     * @param  string  $moneyChartId  The chart id the money side was booked against.
     * @return array{bank_account_id: string|null, cash_account_id: string|null, e_wallet_account_id: string|null} The three account columns, one of them filled.
     */
    protected function moneyAccountColumns(string $moneyChartId): array
    {
        $columns = [
            'bank_account_id' => null,
            'cash_account_id' => null,
            'e_wallet_account_id' => null,
        ];

        foreach ([
            'bank_account_id' => Bank::class,
            'cash_account_id' => CashAccount::class,
            'e_wallet_account_id' => EWalletAccount::class,
        ] as $column => $accountClass) {
            $accountId = $accountClass::query()->where('chart_id', $moneyChartId)->value('id');

            if ($accountId !== null) {
                $columns[$column] = $accountId;

                break;
            }
        }

        return $columns;
    }

    /**
     * The account columns of a refund, read from the chart the money moved through.
     *
     * A refund table carries no register column, because money handed back has to
     * leave an account the document can name, so a seeded refund booked against a
     * cash register fails here instead of quietly losing its money account.
     *
     * @param  string  $moneyChartId  The chart id the money side was booked against.
     * @param  string  $documentNumber  The refund document number, named in the failure message.
     * @return array{bank_account_id: string|null, e_wallet_account_id: string|null} The two refund account columns, one of them filled.
     *
     * @throws LogicException Thrown when the chart belongs to a register, or to no money account at all.
     */
    protected function refundAccountColumns(string $moneyChartId, string $documentNumber): array
    {
        $columns = $this->moneyAccountColumns($moneyChartId);

        if ($columns['cash_account_id'] !== null) {
            throw new LogicException('Refund '.$documentNumber.' is booked through a cash register, which a refund cannot pay out of.');
        }

        if ($columns['bank_account_id'] === null && $columns['e_wallet_account_id'] === null) {
            throw new LogicException('Refund '.$documentNumber.' is booked through a chart that no bank or e-wallet account owns.');
        }

        return [
            'bank_account_id' => $columns['bank_account_id'],
            'e_wallet_account_id' => $columns['e_wallet_account_id'],
        ];
    }

    /**
     * Post one seeded document if it is marked posted (direct, no legs).
     */
    protected function postSeededDocument(
        Receipt|PaymentVoucher|CustomerRefund|SupplierRefund $document,
        LedgerSubType $subtype,
        string $numberColumn,
        Posting $posting,
        string $debitChartId,
        string $creditChartId,
        string $amount,
    ): void {
        if ($document->status !== LedgerStatus::POSTED) {
            return;
        }

        $date = $document->date instanceof \DateTimeInterface
            ? $document->date
            : CarbonImmutable::parse($document->date);

        $posting->postPayment(
            $document,
            (string) $document->{$numberColumn},
            $date,
            LedgerType::PAYMENT,
            $subtype,
            $debitChartId,
            $creditChartId,
            $amount,
            companyId: $document->company_id,
            companyName: $document->company_name ?? null,
            referenceNumber: $document->reference_number ?? null,
            description: $document->notes ?? null,
        );
    }
}
