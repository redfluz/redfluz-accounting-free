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
 * Posts an invoice, and everything shaped like one, to the ledgers.
 *
 * An invoice bills somebody and moves no money of its own. Its rows go to the
 * general ledger, and the one leg naming the customer's or supplier's account
 * goes to that sub-ledger as well, so the statement for that company can be
 * read on its own and drilled into by document number.
 *
 * So an invoice writes two places:
 *
 *   ledgers      the double entry itself
 *   ar_ledgers   a sales invoice — what the customer now owes
 *   ap_ledgers   a purchase invoice — what is now owed to the supplier
 *
 * A money ledger is only written if the document names a bank, drawer or wallet
 * account, which an ordinary invoice does not. An invoice carrying its own
 * payment is not that: the payment is raised as its own receipt or voucher and
 * posted by {@see PaymentLedgerPoster}, so this poster stays the one thing
 * that handles billing.
 *
 * The rows are handed over already built as {@see GeneralLedgerData}, because
 * an invoice carries line detail the financial columns alone would lose. The
 * legs used for routing are derived from those same rows, so the row a document
 * was approved into and the leg a sub-ledger was filed under cannot disagree.
 */
class InvoiceLedgerPoster
{
    use WritesLedgerTables;

    /**
     * Post an invoice's double entry to the ledgers it belongs in.
     *
     * @param  Model  $document  The invoice being posted. Its key is what the
     *                           rows drill back to.
     * @param  string  $documentNumber  Its number, recorded on every row.
     * @param  DateTimeInterface  $documentDate  Its date.
     * @param  LedgerType  $type  Recorded on the general ledger rows, so a
     *                            payment and an invoice stay distinguishable
     *                            even when they post to the same account.
     * @param  LedgerSubType  $subtype  The finer kind, and the one that says
     *                                  whether this bills a customer or a
     *                                  supplier.
     * @param  list<GeneralLedgerData>  $generalRows  The invoice's rows, built
     *                                                before posting so they can
     *                                                carry line detail.
     * @param  string|null  $companyId  The customer or supplier the sub-ledger
     *                                  rows belong to.
     * @param  string|null  $contactId  The person at that company, recorded on
     *                                  the general ledger rows.
     * @param  string|null  $companyName  Snapshotted onto the rows; taken from
     *                                    the document when not given.
     * @param  string|null  $referenceNumber  What the invoice corrects, recorded
     *                                        on the sub-ledger rows.
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
        array $generalRows,
        ?string $companyId = null,
        ?string $contactId = null,
        ?string $companyName = null,
        ?string $referenceNumber = null,
    ): PostingResult {
        if ($generalRows === []) {
            throw PostingException::postingInputInvalid($documentNumber);
        }

        $sourceType = GeneralLedgerData::sourceTypeFor($subtype);
        $date = Carbon::instance(DateTime::createFromInterface($documentDate));
        $key = $document->getKey();
        $documentKey = is_string($key) ? $key : '';

        $currency = $this->defaultCurrency();
        $cashAccounts = $this->cashAccountsByChart();
        $bankAccounts = $this->bankAccountsByChart();
        $eWalletAccounts = $this->eWalletAccountsByChart();
        $charts = $this->chartsById();

        $documentCompanyName = $document->company_name ?? null;
        $companyName ??= is_string($documentCompanyName) ? $documentCompanyName : null;

        $generalRowArrays = [];

        foreach ($generalRows as $row) {
            $generalRowArrays[] = $row->toRow();
        }

        /** @var list<PostingLeg> $legs */
        $legs = array_map(
            fn (GeneralLedgerData $row): PostingLeg => $row->toLeg(),
            $generalRows
        );

        // Money rows and sub ledger rows, both derived from the same legs, so
        // the ledgers cannot disagree about what the document did.
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
                $balances[$accountId] ??= $this->priorBalance(BankAccountKind::CASH, $accountId);
                $balances[$accountId] = bcsub(
                    bcadd($balances[$accountId], $leg->debit, 2),
                    $leg->credit,
                    2
                );

                $documentDate = $date->format('Y-m-d');

                $cashRows[] = CashLedgerData::build(
                    cashAccountId: $accountId,
                    locationName: null,
                    assetLocationId: null,
                    currencyId: $currency['id'],
                    sourceId: $documentKey,
                    sourceType: $document->getMorphClass(),
                    documentNumber: $documentNumber,
                    documentDate: $documentDate,
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
                $balances[$accountId] ??= $this->priorBalance(BankAccountKind::BANK, $accountId);
                $balances[$accountId] = bcsub(
                    bcadd($balances[$accountId], $leg->debit, 2),
                    $leg->credit,
                    2
                );

                $documentDate = $date->format('Y-m-d');

                $bankRows[] = BankLedgerData::build(
                    bankAccountId: $accountId,
                    currencyId: $currency['id'],
                    sourceId: $documentKey,
                    sourceType: $document->getMorphClass(),
                    documentNumber: $documentNumber,
                    documentDate: $documentDate,
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
                $balances[$accountId] ??= $this->priorBalance(BankAccountKind::E_WALLET, $accountId);
                $balances[$accountId] = bcsub(
                    bcadd($balances[$accountId], $leg->debit, 2),
                    $leg->credit,
                    2
                );

                $documentDate = $date->format('Y-m-d');

                $eWalletRows[] = EWalletLedgerData::build(
                    eWalletAccountId: $accountId,
                    currencyId: $currency['id'],
                    sourceId: $documentKey,
                    sourceType: $document->getMorphClass(),
                    documentNumber: $documentNumber,
                    documentDate: $documentDate,
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

            // A sub ledger row answers what one company owes or is owed, and a
            // row with no company answers nothing, so the document must name one
            // for the movement to be filed at all.
            if ($companyId === null) {
                throw PostingException::noCounterpartyCompany($documentNumber);
            }

            // The account as a report prints it, taken at the moment the figure
            // was raised.
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

        // An invoice that moved nothing receivable or payable is a misconfigured
        // counterparty chart, and misconfiguration fails loudly here rather than
        // as a balance that quietly never moved.
        if ($arRows === [] && $apRows === []) {
            throw PostingException::noCounterpartyRow($documentNumber, $sourceType->value);
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
     * Which sub-ledger an invoice bills into.
     *
     * The document says who it is dealing with, so the document decides:
     * sales invoices and sales notes raise what the customer owes, purchase
     * invoices and purchase notes raise what is owed to the supplier.
     *
     * @return string|null 'receivable', 'payable', or null for neither.
     */
    private function subLedgerSideFor(LedgerSubType $subtype): ?string
    {
        return match ($subtype) {
            LedgerSubType::SALES_INVOICE,
            LedgerSubType::MANUAL_SALES_INVOICE,
            LedgerSubType::INCREASE_ADJUSTMENT,
            LedgerSubType::DECREASE_ADJUSTMENT => 'receivable',

            LedgerSubType::PURCHASE_INVOICE,
            LedgerSubType::PURCHASE_DEBIT_NOTE,
            LedgerSubType::PURCHASE_CREDIT_NOTE => 'payable',

            default => null,
        };
    }
}
