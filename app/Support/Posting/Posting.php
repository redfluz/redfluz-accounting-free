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

namespace App\Support\Posting;

use App\Enums\Bank\BankAccountKind;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\Bank\Bank;
use App\Models\Bank\BankLedger;
use App\Models\Bank\CashAccount;
use App\Models\Bank\CashLedger;
use App\Models\Bank\EWalletAccount;
use App\Models\Bank\EWalletLedger;
use App\Models\ChartOfAccount\Chart;
use App\Models\Crm\ApLedger;
use App\Models\Crm\ArLedger;
use App\Models\Crm\Company;
use App\Models\Finance\Ledger;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use App\Models\Inventory\StockLocation;
use App\Services\DecimalService;
use App\Support\Data\Inventory\ProductLedgerData;
use App\Support\PostingException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function count;
use function in_array;

/**
 * The one place ledger rows are written.
 *
 * Every method names the model it writes to and takes a batch of the DTO that
 * matches that table's columns exactly. The DTO is the contract: it carries
 * every column the table needs and nothing the table does not have.
 *
 * Each batch follows the same shape:
 *
 *   1. Load the reference information the batch needs ONCE — charts, companies,
 *      account ids — through the shared private helpers below.
 *   2. Loop the DTOs, checking each reference against the loaded information
 *      with an isset/in_array test, never a query per row, and map the DTO's
 *      fields onto the model's attributes one by one.
 *   3. Write the whole batch in a single model insert.
 *
 * Ledgers are immutable history: once written, they are never updated or deleted.
 * The models disable updated_at (const UPDATED_AT = null) and do not use SoftDeletes.
 * Only created_at is set, automatically by the model.
 *
 * Figures, balances and snapshots are the caller's decision, carried by the
 * DTO. This class validates that what the DTO names exists, then writes it.
 */
class Posting
{
    /**
     * Write a batch of rows to the bank ledger.
     *
     * @param  list<BankLedgerData>  $dtos  The rows, each matching bank_ledgers exactly.
     * @return int How many rows were written.
     *
     * @throws PostingException When a row names a bank account that does not exist.
     */
    public function bankLedger(array $dtos): int
    {
        if ($dtos === []) {
            return 0;
        }

        $accountIds = $this->bankAccountIds();
        $now = now()->format('Y-m-d H:i:s');

        $rows = [];
        foreach ($dtos as $dto) {
            if (! in_array($dto->bankAccountId, $accountIds, true)) {
                throw PostingException::unknownReference('bank account', $dto->bankAccountId, $dto->documentNumber);
            }

            $rows[] = [
                'id' => $dto->id,
                'bank_account_id' => $dto->bankAccountId,
                'currency_id' => $dto->currencyId,
                'source_id' => $dto->sourceId,
                'source_type' => $dto->sourceType,
                'document_number' => $dto->documentNumber,
                'date' => $dto->date,
                'reference_number' => $dto->referenceNumber,
                'debit_amount' => $dto->debitAmount,
                'credit_amount' => $dto->creditAmount,
                'balance' => $dto->balance,
                'bank_reference' => $dto->bankReference,
                'description' => $dto->description,
                'notes' => $dto->notes,
                'created_at' => $now,
            ];
        }

        BankLedger::insert($rows);

        return count($rows);
    }

    /**
     * Write a batch of rows to the cash ledger.
     *
     * @param  list<CashLedgerData>  $dtos  The rows, each matching cash_ledgers exactly.
     * @return int How many rows were written.
     *
     * @throws PostingException When a row names a cash account that does not exist.
     */
    public function cashLedger(array $dtos): int
    {
        if ($dtos === []) {
            return 0;
        }

        $accountIds = $this->cashAccountIds();
        $now = now()->format('Y-m-d H:i:s');

        $rows = [];
        foreach ($dtos as $dto) {
            if (! in_array($dto->cashAccountId, $accountIds, true)) {
                throw PostingException::unknownReference('cash account', $dto->cashAccountId, $dto->documentNumber);
            }

            $rows[] = [
                'id' => $dto->id,
                'cash_account_id' => $dto->cashAccountId,
                'location_name' => $dto->locationName,
                'asset_location_id' => $dto->assetLocationId,
                'currency_id' => $dto->currencyId,
                'source_id' => $dto->sourceId,
                'source_type' => $dto->sourceType,
                'document_number' => $dto->documentNumber,
                'date' => $dto->date,
                'reference_number' => $dto->referenceNumber,
                'debit_amount' => $dto->debitAmount,
                'credit_amount' => $dto->creditAmount,
                'balance' => $dto->balance,
                'description' => $dto->description,
                'notes' => $dto->notes,
                'created_at' => $now,
            ];
        }

        CashLedger::insert($rows);

        return count($rows);
    }

    /**
     * Write a batch of rows to the e-wallet ledger.
     *
     * @param  list<EWalletLedgerData>  $dtos  The rows, each matching e_wallet_ledgers exactly.
     * @return int How many rows were written.
     *
     * @throws PostingException When a row names an e-wallet account that does not exist.
     */
    public function eWalletLedger(array $dtos): int
    {
        if ($dtos === []) {
            return 0;
        }

        $accountIds = $this->eWalletAccountIds();
        $now = now()->format('Y-m-d H:i:s');

        $rows = [];
        foreach ($dtos as $dto) {
            if (! in_array($dto->eWalletAccountId, $accountIds, true)) {
                throw PostingException::unknownReference('e-wallet account', $dto->eWalletAccountId, $dto->documentNumber);
            }

            $rows[] = [
                'id' => $dto->id,
                'e_wallet_account_id' => $dto->eWalletAccountId,
                'currency_id' => $dto->currencyId,
                'source_id' => $dto->sourceId,
                'source_type' => $dto->sourceType,
                'document_number' => $dto->documentNumber,
                'date' => $dto->date,
                'reference_number' => $dto->referenceNumber,
                'debit_amount' => $dto->debitAmount,
                'credit_amount' => $dto->creditAmount,
                'balance' => $dto->balance,
                'wallet_reference' => $dto->walletReference,
                'description' => $dto->description,
                'notes' => $dto->notes,
                'created_at' => $now,
            ];
        }

        EWalletLedger::insert($rows);

        return count($rows);
    }

    /**
     * Write a batch of rows to the accounts receivable sub-ledger.
     *
     * @param  list<ArLedgerData>  $dtos  The rows, each matching ar_ledgers exactly.
     * @return int How many rows were written.
     *
     * @throws PostingException When a row names a chart or company that does not exist.
     */
    public function accountReceivableLedger(array $dtos): int
    {
        if ($dtos === []) {
            return 0;
        }

        $charts = $this->chartInformation();
        $companies = $this->companyInformation();
        $now = now()->format('Y-m-d H:i:s');

        $rows = [];
        foreach ($dtos as $dto) {
            if (! isset($charts[$dto->chartId])) {
                throw PostingException::unknownReference('chart', $dto->chartId, $dto->documentNumber);
            }

            if (! isset($companies[$dto->companyId])) {
                throw PostingException::unknownReference('company', $dto->companyId, $dto->documentNumber);
            }

            $rows[] = [
                'id' => $dto->id,
                'chart_id' => $dto->chartId,
                'company_id' => $dto->companyId,
                'currency_id' => $dto->currencyId,
                'type' => $dto->type,
                'subtype' => $dto->subtype,
                'document_number' => $dto->documentNumber,
                'date' => $dto->date,
                'reference_number' => $dto->referenceNumber,
                'chart_code' => $dto->chartCode,
                'chart_name' => $dto->chartName,
                'chart_description' => $dto->chartDescription,
                'company_name' => $dto->companyName,
                'description' => $dto->description,
                'debit_amount' => $dto->debitAmount,
                'credit_amount' => $dto->creditAmount,
                'created_at' => $now,
            ];
        }

        ArLedger::insert($rows);

        return count($rows);
    }

    /**
     * Write a batch of rows to the accounts payable sub-ledger.
     *
     * @param  list<ApLedgerData>  $dtos  The rows, each matching ap_ledgers exactly.
     * @return int How many rows were written.
     *
     * @throws PostingException When a row names a chart or company that does not exist.
     */
    public function accountPayableLedger(array $dtos): int
    {
        if ($dtos === []) {
            return 0;
        }

        $charts = $this->chartInformation();
        $companies = $this->companyInformation();
        $now = now()->format('Y-m-d H:i:s');

        $rows = [];
        foreach ($dtos as $dto) {
            if (! isset($charts[$dto->chartId])) {
                throw PostingException::unknownReference('chart', $dto->chartId, $dto->documentNumber);
            }

            if (! isset($companies[$dto->companyId])) {
                throw PostingException::unknownReference('company', $dto->companyId, $dto->documentNumber);
            }

            $rows[] = [
                'id' => $dto->id,
                'chart_id' => $dto->chartId,
                'company_id' => $dto->companyId,
                'currency_id' => $dto->currencyId,
                'type' => $dto->type,
                'subtype' => $dto->subtype,
                'document_number' => $dto->documentNumber,
                'date' => $dto->date,
                'reference_number' => $dto->referenceNumber,
                'chart_code' => $dto->chartCode,
                'chart_name' => $dto->chartName,
                'chart_description' => $dto->chartDescription,
                'company_name' => $dto->companyName,
                'description' => $dto->description,
                'debit_amount' => $dto->debitAmount,
                'credit_amount' => $dto->creditAmount,
                'created_at' => $now,
            ];
        }

        ApLedger::insert($rows);

        return count($rows);
    }

    /**
     * Write a batch of rows to the general ledger.
     *
     * Both general-ledger DTOs describe the same ledgers table: LedgerData for
     * rows built from a payment leg, GeneralLedgerData for rows built from an
     * invoice line. Either is accepted in the batch.
     *
     * @param  list<LedgerData|GeneralLedgerData>  $dtos  The rows, each matching ledgers exactly.
     * @return int How many rows were written.
     *
     * @throws PostingException When a row names a chart or company that does not exist.
     */
    public function generalLedger(array $dtos): int
    {
        if ($dtos === []) {
            return 0;
        }

        $charts = $this->chartInformation();
        $companies = $this->companyInformation();
        $now = now()->format('Y-m-d H:i:s');

        $rows = [];
        foreach ($dtos as $dto) {
            if ($dto->chartId !== null && ! isset($charts[$dto->chartId])) {
                throw PostingException::unknownReference('chart', $dto->chartId, $dto->documentNumber ?? $dto->id);
            }

            if ($dto->companyId !== null && ! isset($companies[$dto->companyId])) {
                throw PostingException::unknownReference('company', $dto->companyId, $dto->documentNumber ?? $dto->id);
            }

            $rows[] = [
                'id' => $dto->id,
                'chart_id' => $dto->chartId,
                'company_id' => $dto->companyId,
                'currency_id' => $dto->currencyId,
                'type' => $dto->type instanceof LedgerType ? $dto->type->value : $dto->type,
                'subtype' => $dto->subtype instanceof LedgerSubType ? $dto->subtype->value : $dto->subtype,
                'document_number' => $dto->documentNumber,
                'reference_number' => $dto->referenceNumber,
                'date' => $dto->date->format('Y-m-d'),
                'chart_code' => $dto->chartCode,
                'chart_name' => $dto->chartName,
                'company_name' => $dto->companyName,
                'description' => $dto->description,
                'line_description' => $dto->lineDescription,
                'debit_amount' => $dto->debitAmount,
                'credit_amount' => $dto->creditAmount,
                'created_at' => $now,
            ];
        }

        Ledger::insert($rows);

        return count($rows);
    }

    /**
     * Write a batch of stock movements to the product ledger.
     *
     * @param  list<ProductLedgerData>  $dtos  The rows, each matching product_ledgers exactly.
     * @return int How many rows were written.
     *
     * @throws PostingException When a row names a product, stock location or company that does not exist.
     */
    public function productLedger(array $dtos): int
    {
        if ($dtos === []) {
            return 0;
        }

        $productIds = $this->productIds();
        $locationIds = $this->stockLocationIds();
        $companies = $this->companyInformation();
        $now = now()->format('Y-m-d H:i:s');

        $rows = [];
        foreach ($dtos as $dto) {
            $identifier = $dto->documentNumber ?? $dto->id;

            if ($dto->productId !== null && ! in_array($dto->productId, $productIds, true)) {
                throw PostingException::unknownReference('product', $dto->productId, $identifier);
            }

            if ($dto->stockLocationId !== null && ! in_array($dto->stockLocationId, $locationIds, true)) {
                throw PostingException::unknownReference('stock location', $dto->stockLocationId, $identifier);
            }

            if ($dto->companyId !== null && ! isset($companies[$dto->companyId])) {
                throw PostingException::unknownReference('company', $dto->companyId, $identifier);
            }

            $rows[] = [
                'id' => $dto->id,
                'product_id' => $dto->productId,
                'stock_location_id' => $dto->stockLocationId,
                'stock_location_name' => $dto->stockLocationName,
                'company_id' => $dto->companyId,
                'source_entry_id' => $dto->sourceEntryId,
                'cogs_ledger_id' => $dto->cogsLedgerId,
                'inventory_ledger_id' => $dto->inventoryLedgerId,
                'variance_ledger_id' => $dto->varianceLedgerId,
                'product_sku' => $dto->productSku,
                'product_name' => $dto->productName,
                'company_name' => $dto->companyName,
                'document_number' => $dto->documentNumber,
                'reference_number' => $dto->referenceNumber,
                'source_type' => $dto->sourceType->value,
                'direction' => $dto->direction->value,
                'stock_bucket' => $dto->stockBucket->value,
                'date' => $dto->date->format('Y-m-d'),
                'quantity' => $dto->quantity,
                'unit_cost' => $dto->unitCost,
                'total_cost' => $dto->totalCost,
                'status' => $dto->status->value,
                'activated_at' => $dto->activatedAt?->format('Y-m-d H:i:s'),
                'notes' => $dto->notes,
                'created_at' => $now,
            ];
        }

        ProductLedger::insert($rows);

        return count($rows);
    }

    /**
     * Every chart account, keyed by id, loaded once for the whole batch.
     *
     * The loop checks membership against this instead of querying the charts
     * table once per row.
     *
     * @return array<string, Chart> Chart id to its model.
     */
    private function chartInformation(): array
    {
        /** @var array<string, Chart> $charts */
        $charts = Chart::query()
            ->get(['id', 'code', 'name', 'description'])
            ->keyBy('id')
            ->all();

        return $charts;
    }

    /**
     * Every company's name, keyed by id, loaded once for the whole batch.
     *
     * @return array<string, string> Company id to its name.
     */
    private function companyInformation(): array
    {
        /** @var array<string, string> $companies */
        $companies = Company::query()->pluck('name', 'id')->all();

        return $companies;
    }

    /**
     * Every bank account id, loaded once for the whole batch.
     *
     * @return list<string> The ids that exist.
     */
    private function bankAccountIds(): array
    {
        /** @var list<string> $ids */
        $ids = Bank::query()->pluck('id')->all();

        return $ids;
    }

    /**
     * Every cash account id, loaded once for the whole batch.
     *
     * @return list<string> The ids that exist.
     */
    private function cashAccountIds(): array
    {
        /** @var list<string> $ids */
        $ids = CashAccount::query()->pluck('id')->all();

        return $ids;
    }

    /**
     * Every e-wallet account id, loaded once for the whole batch.
     *
     * @return list<string> The ids that exist.
     */
    private function eWalletAccountIds(): array
    {
        /** @var list<string> $ids */
        $ids = EWalletAccount::query()->pluck('id')->all();

        return $ids;
    }

    /**
     * Every product id, loaded once for the whole batch.
     *
     * @return list<string> The ids that exist.
     */
    private function productIds(): array
    {
        /** @var list<string> $ids */
        $ids = Product::query()->pluck('id')->all();

        return $ids;
    }

    /**
     * Every stock location id, loaded once for the whole batch.
     *
     * @return list<string> The ids that exist.
     */
    private function stockLocationIds(): array
    {
        /** @var list<string> $ids */
        $ids = StockLocation::query()->pluck('id')->all();

        return $ids;
    }

    /**
     * Post a simple 2-sided payment/receipt/refund/transfer/journal (no legs).
     *
     * Direct from the document's accounts and amount: builds GL + money ledger
     * (cash/bank/e-wallet, with balance) + counterparty AR/AP, then writes via
     * Posting's ledger writers. Controllers pass chart IDs and amount only.
     *
     * @param  numeric-string  $amount
     */
    public function postPayment(
        Model $document,
        string $documentNumber,
        \DateTimeInterface $date,
        LedgerType $type,
        LedgerSubType $subtype,
        string $debitChartId,
        string $creditChartId,
        string $amount,
        ?string $companyId = null,
        ?string $companyName = null,
        ?string $referenceNumber = null,
        ?string $description = null,
        bool $writeCounterparty = true,
    ): void {
        $amount = bcadd(DecimalService::normalize((string) $amount), '0', 2);
        if (bccomp($amount, '0.00', 2) === 0) {
            throw PostingException::postingInputInvalid($documentNumber);
        }

        $date = Carbon::instance(\DateTime::createFromInterface($date));
        $key = $document->getKey();
        $documentKey = is_string($key) ? $key : '';
        $documentCompanyName = $document->company_name ?? null;
        $companyName ??= is_string($documentCompanyName) ? $documentCompanyName : null;
        $notes = is_string($document->notes ?? null) ? $document->notes : null;
        $currencyId = $this->defaultCurrencyIdValue();
        $charts = $this->chartsByIdFull();
        $sides = [
            ['chartId' => $debitChartId, 'debit' => $amount, 'credit' => '0.00'],
            ['chartId' => $creditChartId, 'debit' => '0.00', 'credit' => $amount],
        ];

        // GL: 2 rows, direct
        $glDtos = [];
        foreach ($sides as $side) {
            $chart = $charts[$side['chartId']] ?? null;
            $glDtos[] = new LedgerData(
                id: (string) Str::uuid7(),
                chartId: $side['chartId'],
                companyId: $companyId,
                currencyId: $currencyId !== '' ? $currencyId : null,
                type: $type,
                subtype: $subtype,
                documentNumber: $documentNumber,
                referenceNumber: $referenceNumber,
                date: $date,
                chartCode: $chart['code'] ?? null,
                chartName: $chart['name'] ?? null,
                companyName: $companyName,
                description: $notes,
                lineDescription: $description,
                debitAmount: $side['debit'],
                creditAmount: $side['credit'],
                createdAt: null,
                updatedAt: null,
            );
        }
        $this->generalLedger($glDtos);

        // Money ledgers (cash/bank/e-wallet), with running balance
        $bankAccounts = $this->bankAccountsByChartMap();
        $cashAccounts = $this->cashAccountsByChartMap();
        $eWalletAccounts = $this->eWalletAccountsByChartMap();
        $balances = [];
        $cashDtos = [];
        $bankDtos = [];
        $eWalletDtos = [];
        $counterpartySide = $this->subLedgerSideForSubtype($subtype);
        $arDtos = [];
        $apDtos = [];

        foreach ($sides as $side) {
            $chartId = $side['chartId'];
            $debit = $side['debit'];
            $credit = $side['credit'];

            if (isset($cashAccounts[$chartId])) {
                $acct = $cashAccounts[$chartId];
                $acctId = (string) $acct->id;
                $kind = BankAccountKind::CASH;
                $balances[$acctId] ??= $this->priorBalanceFor($kind, $acctId);
                $balances[$acctId] = bcsub(bcadd($balances[$acctId], $debit, 2), $credit, 2);
                $cashDtos[] = CashLedgerData::build(
                    cashAccountId: $acctId,
                    locationName: null,
                    assetLocationId: null,
                    currencyId: $currencyId,
                    sourceId: $documentKey,
                    sourceType: $document->getMorphClass(),
                    documentNumber: $documentNumber,
                    date: $date->format('Y-m-d'),
                    referenceNumber: $referenceNumber,
                    debitAmount: $debit,
                    creditAmount: $credit,
                    balance: $balances[$acctId],
                    description: $description,
                );

                continue;
            }
            if (isset($bankAccounts[$chartId])) {
                $acct = $bankAccounts[$chartId];
                $acctId = (string) $acct->id;
                $kind = BankAccountKind::BANK;
                $balances[$acctId] ??= $this->priorBalanceFor($kind, $acctId);
                $balances[$acctId] = bcsub(bcadd($balances[$acctId], $debit, 2), $credit, 2);
                $bankDtos[] = BankLedgerData::build(
                    bankAccountId: $acctId,
                    currencyId: $currencyId,
                    sourceId: $documentKey,
                    sourceType: $document->getMorphClass(),
                    documentNumber: $documentNumber,
                    date: $date->format('Y-m-d'),
                    referenceNumber: $referenceNumber,
                    debitAmount: $debit,
                    creditAmount: $credit,
                    balance: $balances[$acctId],
                    bankReference: null,
                    description: $description,
                );

                continue;
            }
            if (isset($eWalletAccounts[$chartId])) {
                $acct = $eWalletAccounts[$chartId];
                $acctId = (string) $acct->id;
                $kind = BankAccountKind::E_WALLET;
                $balances[$acctId] ??= $this->priorBalanceFor($kind, $acctId);
                $balances[$acctId] = bcsub(bcadd($balances[$acctId], $debit, 2), $credit, 2);
                $eWalletDtos[] = EWalletLedgerData::build(
                    eWalletAccountId: $acctId,
                    currencyId: $currencyId,
                    sourceId: $documentKey,
                    sourceType: $document->getMorphClass(),
                    documentNumber: $documentNumber,
                    date: $date->format('Y-m-d'),
                    referenceNumber: $referenceNumber,
                    debitAmount: $debit,
                    creditAmount: $credit,
                    balance: $balances[$acctId],
                    walletReference: null,
                    description: $description,
                );

                continue;
            }

            if ($counterpartySide === null || ! $writeCounterparty) {
                continue;
            }
            $chart = $charts[$chartId] ?? null;
            $isCounterparty = $counterpartySide === 'receivable'
                ? ($chart['is_debtor'] ?? false)
                : ($chart['is_creditor'] ?? false);
            if (! $isCounterparty) {
                continue;
            }
            if ($companyId === null) {
                throw PostingException::noCounterpartyCompany($documentNumber);
            }
            $rowId = (string) Str::uuid7();
            if ($counterpartySide === 'receivable') {
                $arDtos[] = new ArLedgerData(
                    id: $rowId,
                    chartId: $chartId,
                    companyId: $companyId,
                    currencyId: $currencyId,
                    type: $type->value,
                    subtype: $subtype->value,
                    documentNumber: $documentNumber,
                    date: $date->format('Y-m-d'),
                    referenceNumber: $referenceNumber,
                    chartCode: $chart['code'] ?? null,
                    chartName: $chart['name'] ?? null,
                    chartDescription: $chart['description'] ?? null,
                    companyName: $companyName,
                    description: $description,
                    debitAmount: $debit,
                    creditAmount: $credit,
                );
            } else {
                $apDtos[] = new ApLedgerData(
                    id: $rowId,
                    chartId: $chartId,
                    companyId: $companyId,
                    currencyId: $currencyId,
                    type: $type->value,
                    subtype: $subtype->value,
                    documentNumber: $documentNumber,
                    date: $date->format('Y-m-d'),
                    referenceNumber: $referenceNumber,
                    chartCode: $chart['code'] ?? null,
                    chartName: $chart['name'] ?? null,
                    chartDescription: $chart['description'] ?? null,
                    companyName: $companyName,
                    description: $description,
                    debitAmount: $debit,
                    creditAmount: $credit,
                    createdAt: null,
                    updatedAt: null,
                    deletedAt: null,
                );
            }
        }

        $this->cashLedger($cashDtos);
        $this->bankLedger($bankDtos);
        $this->eWalletLedger($eWalletDtos);
        $this->accountReceivableLedger($arDtos);
        $this->accountPayableLedger($apDtos);
    }

    /** @return array<string, Bank> */
    private function bankAccountsByChartMap(): array
    {
        /** @var array<string, Bank> $r */
        $r = Bank::query()->whereNotNull('chart_id')->where('is_active', true)->get()->keyBy('chart_id')->all();

        return $r;
    }

    /** @return array<string, CashAccount> */
    private function cashAccountsByChartMap(): array
    {
        /** @var array<string, CashAccount> $r */
        $r = CashAccount::query()->whereNotNull('chart_id')->where('is_active', true)->get()->keyBy('chart_id')->all();

        return $r;
    }

    /** @return array<string, EWalletAccount> */
    private function eWalletAccountsByChartMap(): array
    {
        /** @var array<string, EWalletAccount> $r */
        $r = EWalletAccount::query()->whereNotNull('chart_id')->where('is_active', true)->get()->keyBy('chart_id')->all();

        return $r;
    }

    /** @return array<string, array{code: ?string, name: ?string, description: ?string, report_group: ?string, is_debtor: bool, is_creditor: bool}> */
    private function chartsByIdFull(): array
    {
        $cache = [];
        foreach (DB::table('charts')->get(['id', 'code', 'name', 'description', 'report_group', 'isDebtor', 'isCreditor']) as $c) {
            $id = (string) $c->id;
            $cache[$id] = [
                'code' => is_string($c->code) ? $c->code : null,
                'name' => is_string($c->name) ? $c->name : null,
                'description' => is_string($c->description) ? $c->description : null,
                'report_group' => is_string($c->report_group) ? $c->report_group : null,
                'is_debtor' => (bool) $c->isDebtor,
                'is_creditor' => (bool) $c->isCreditor,
            ];
        }

        return $cache;
    }

    private function priorBalanceFor(BankAccountKind $kind, string $accountId): string
    {
        $table = match ($kind) {
            BankAccountKind::CASH => 'cash_ledgers',
            BankAccountKind::BANK => 'bank_ledgers',
            BankAccountKind::E_WALLET => 'e_wallet_ledgers',
            default => throw PostingException::unknownLedgerKind($kind->value),
        };
        $col = match ($kind) {
            BankAccountKind::CASH => 'cash_account_id',
            BankAccountKind::BANK => 'bank_account_id',
            BankAccountKind::E_WALLET => 'e_wallet_account_id',
            default => throw PostingException::unknownLedgerKind($kind->value),
        };
        $balance = DB::table($table)->where($col, $accountId)->selectRaw('COALESCE(SUM(debit_amount) - SUM(credit_amount), 0) as balance')->value('balance');

        return DecimalService::normalize(is_numeric($balance) ? (string) $balance : '0');
    }

    private function defaultCurrencyIdValue(): string
    {
        $currency = DB::table('currencies')->where('is_default', true)->first(['id']);

        return is_string($currency?->id) ? $currency->id : '';
    }

    private function subLedgerSideForSubtype(LedgerSubType $subtype): ?string
    {
        return match ($subtype) {
            LedgerSubType::RECEIPT, LedgerSubType::CUSTOMER_REFUND, LedgerSubType::REFUND => 'receivable',
            LedgerSubType::PAYMENT_VOUCHER, LedgerSubType::SUPPLIER_REFUND => 'payable',
            default => null,
        };
    }
}
