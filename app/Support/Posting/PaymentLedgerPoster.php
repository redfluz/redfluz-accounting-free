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

namespace App\Support\Posting;

use App\Enums\BankAccountKind;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Models\Bank\Bank;
use App\Models\Bank\CashAccount;
use App\Models\Bank\EWalletAccount;
use App\Support\Posting\Concerns\WritesLedgerTables;
use App\Support\PostingException;
use Carbon\Carbon;
use DateTime;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Posts a receipt, a payment voucher or a refund — anything that moves money.
 *
 * These documents are given as legs rather than as built rows, because a payment
 * has no line detail worth keeping: it is one figure crossing from an account
 * to an account. The general ledger rows are derived from the legs here.
 *
 * A payment writes three places, and which is which is the whole of it:
 *
 *   ledgers            the double entry itself
 *   cash_ledgers       the drawer or petty cash tin it came from
 *   bank_ledgers       the account at a financial institution
 *   e_wallet_ledgers   the balance at a wallet provider
 *   ar_ledgers         a receipt or customer refund — what the customer owes less
 *   ap_ledgers         a voucher or supplier refund — what is owed less
 *
 * The ledger is chosen by which account table the leg names, not by the caller.
 */
class PaymentLedgerPoster
{
    use WritesLedgerTables;

    /**
     * Post a payment's double entry to the ledgers it belongs in.
     *
     * @param  Model  $document  The receipt, voucher or refund.
     * @param  string  $documentNumber  Its number, recorded on every row.
     * @param  DateTimeInterface  $documentDate  Its date.
     * @param  LedgerType  $type  Recorded on the general ledger rows.
     * @param  LedgerSubType  $subtype  The finer kind.
     * @param  list<PostingLeg>  $legs  The double entry, one side per leg.
     * @param  string|null  $companyId  The customer or supplier.
     * @param  string|null  $contactId  The person at that company.
     * @param  string|null  $companyName  Snapshotted onto the rows.
     * @param  string|null  $referenceNumber  What the payment settles or corrects.
     * @return PostingResult How many rows each ledger received.
     *
     * @throws PostingException
     */
    public function post(
        Model $document,
        string $documentNumber,
        DateTimeInterface $documentDate,
        LedgerType $type,
        LedgerSubType $subtype,
        array $legs,
        ?string $companyId = null,
        ?string $contactId = null,
        ?string $companyName = null,
        ?string $referenceNumber = null,
    ): PostingResult {
        if ($legs === []) {
            throw PostingException::postingInputInvalid($documentNumber);
        }

        $date = Carbon::instance(DateTime::createFromInterface($documentDate));
        $key = $document->getKey();
        $documentKey = is_string($key) ? $key : '';

        $currency = $this->defaultCurrency();
        $bankAccounts = $this->bankAccountsByChart();
        $cashAccounts = $this->cashAccountsByChart();
        $eWalletAccounts = $this->eWalletAccountsByChart();
        $charts = $this->chartsById();

        $documentCompanyName = $document->company_name ?? null;
        $companyName ??= is_string($documentCompanyName) ? $documentCompanyName : null;

        $generalRowArrays = [];
        $sourceType = GeneralLedgerData::sourceTypeFor($subtype);

        foreach ($legs as $leg) {
            $chart = $charts[$leg->chartId] ?? null;

            $generalRowArrays[] = LedgerData::fromLeg(
                $leg,
                $documentKey,
                $type,
                $subtype,
                $sourceType,
                [
                    'code' => $chart['code'] ?? null,
                    'name' => $chart['name'] ?? null,
                ],
                $companyId,
                $companyName,
                $contactId,
                $documentNumber,
                $date,
                $referenceNumber,
            )->toRow();
        }

        $cashRows = [];
        $bankRows = [];
        $eWalletRows = [];
        $arRows = [];
        $apRows = [];
        $balances = [];

        $side = $this->subLedgerSideFor($subtype);

        foreach ($legs as $leg) {
            $cashAccount = $cashAccounts[$leg->chartId] ?? null;
            $bankAccount = $bankAccounts[$leg->chartId] ?? null;
            $eWalletAccount = $eWalletAccounts[$leg->chartId] ?? null;

            if ($cashAccount instanceof CashAccount) {
                $accountId = $cashAccount->id;
                $kind = BankAccountKind::CASH;
                $balances[$accountId] ??= $this->priorBalance($kind, $accountId);
                $balances[$accountId] = bcsub(bcadd($balances[$accountId], $leg->debit, 2), $leg->credit, 2);

                $cashRows[] = CashLedgerData::build(
                    cashAccountId: $accountId,
                    locationName: null,
                    assetLocationId: null,
                    currencyId: $currency['id'],
                    sourceId: $documentKey,
                    sourceType: $document->getMorphClass(),
                    documentNumber: $documentNumber,
                    documentDate: $date->format('Y-m-d'),
                    referenceNumber: $leg->referenceNumber,
                    debitAmount: $leg->debit,
                    creditAmount: $leg->credit,
                    balance: $balances[$accountId],
                    description: $leg->description,
                )->toRow();

                continue;
            }

            if ($bankAccount instanceof Bank) {
                $accountId = $bankAccount->id;
                $kind = BankAccountKind::BANK;
                $balances[$accountId] ??= $this->priorBalance($kind, $accountId);
                $balances[$accountId] = bcsub(bcadd($balances[$accountId], $leg->debit, 2), $leg->credit, 2);

                $bankRows[] = BankLedgerData::build(
                    bankAccountId: $accountId,
                    currencyId: $currency['id'],
                    sourceId: $documentKey,
                    sourceType: $document->getMorphClass(),
                    documentNumber: $documentNumber,
                    documentDate: $date->format('Y-m-d'),
                    referenceNumber: $leg->referenceNumber,
                    debitAmount: $leg->debit,
                    creditAmount: $leg->credit,
                    balance: $balances[$accountId],
                    bankReference: null,
                    description: $leg->description,
                )->toRow();

                continue;
            }

            if ($eWalletAccount instanceof EWalletAccount) {
                $accountId = $eWalletAccount->id;
                $kind = BankAccountKind::E_WALLET;
                $balances[$accountId] ??= $this->priorBalance($kind, $accountId);
                $balances[$accountId] = bcsub(bcadd($balances[$accountId], $leg->debit, 2), $leg->credit, 2);

                $eWalletRows[] = EWalletLedgerData::build(
                    eWalletAccountId: $accountId,
                    currencyId: $currency['id'],
                    sourceId: $documentKey,
                    sourceType: $document->getMorphClass(),
                    documentNumber: $documentNumber,
                    documentDate: $date->format('Y-m-d'),
                    referenceNumber: $leg->referenceNumber,
                    debitAmount: $leg->debit,
                    creditAmount: $leg->credit,
                    balance: $balances[$accountId],
                    walletReference: null,
                    description: $leg->description,
                )->toRow();

                continue;
            }

            if ($side === null) {
                continue;
            }

            $chart = $charts[$leg->chartId] ?? null;

            $isCounterparty = $side === 'receivable'
                ? ($chart['is_debtor'] ?? false)
                : ($chart['is_creditor'] ?? false);

            if (! $isCounterparty) {
                continue;
            }

            if ($companyId === null) {
                throw PostingException::noCounterpartyCompany($documentNumber);
            }

            $chartSnapshot = [
                'code' => $chart['code'] ?? null,
                'name' => $chart['name'] ?? null,
                'description' => $chart['description'] ?? null,
            ];

            if ($side === 'receivable') {
                $arRows[] = ArLedgerData::fromLeg(
                    $leg,
                    $chartSnapshot,
                    $companyId,
                    $currency['id'],
                    $type,
                    $subtype,
                    $documentNumber,
                    $date->format('Y-m-d'),
                    $referenceNumber,
                    $companyName,
                )->toRow();
            } else {
                $apRows[] = ApLedgerData::fromLeg(
                    $leg,
                    $chartSnapshot,
                    $companyId,
                    $currency['id'],
                    $type,
                    $subtype,
                    $documentNumber,
                    $date->format('Y-m-d'),
                    $referenceNumber,
                    $companyName,
                )->toRow();
            }
        }

        return new PostingResult(
            general: $this->writeMany('ledgers', $generalRowArrays),
            cash: $this->writeMany('cash_ledgers', $cashRows),
            bank: $this->writeMany('bank_ledgers', $bankRows),
            eWallet: $this->writeMany('e_wallet_ledgers', $eWalletRows),
            ar: $this->writeMany('ar_ledgers', $arRows),
            ap: $this->writeMany('ap_ledgers', $apRows),
        );
    }

    /**
     * Which sub-ledger a payment is filed in.
     *
     * @return string|null 'receivable', 'payable', or null for neither.
     */
    private function subLedgerSideFor(LedgerSubType $subtype): ?string
    {
        return match ($subtype) {
            LedgerSubType::RECEIPT,
            LedgerSubType::RECEIPT_REFUND,
            LedgerSubType::REFUND => 'receivable',

            LedgerSubType::PAYMENT_VOUCHER,
            LedgerSubType::SUPPLIER_REFUND => 'payable',

            default => null,
        };
    }
}
