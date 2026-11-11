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

namespace App\Support\Traits;

use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\Bank\BankJournal;
use App\Models\Bank\PaymentVoucher;
use App\Models\Bank\Receipt;
use App\Models\Bank\Refund\CustomerRefund;
use App\Models\Bank\Refund\SupplierRefund;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\ManualPurchaseAdjustment;
use App\Models\Finance\ManualSalesAdjustment;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Sales\SalesInvoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Link a posted ledger row back to the document that produced it.
 *
 * A ledger row is a figure, not a document, but it always names the document it
 * came from. Reading a grid of figures without that link means the only way to
 * find out why a number is there is to hunt for the document by hand, so every
 * posted grid resolves its rows to their source instead.
 *
 * The mapping lives here rather than in each controller because the general
 * ledger and a party statement are the same rows seen from two sides: a supplier
 * statement row and the ledger row behind it must drill down to the same
 * document. Two copies would drift, and the statement is the one that would
 * silently lose its links.
 *
 * Rows are read either as enums or as plain strings. The general ledger casts
 * type and subtype to enums; the party sub-ledgers store the same values as text,
 * so both are normalised before matching.
 *
 * A row is only linked when its document is actually there. A link that opens a
 * missing document is worse than no link at all, and a ledger row can outlive its
 * document in both directions: a document can be deleted after posting, and the
 * demonstration data deliberately carries posted figures for payables that were
 * never issued as documents of their own. Each family is therefore checked in one
 * query per page, and an unrecognised subtype resolves to no link rather than to
 * a guessed route.
 */
trait ResolvesLedgerSources
{
    /**
     * Resolve each posted row to its originating document when one exists.
     *
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $rows  The posted ledger rows, general ledger or party sub-ledger.
     * @return array<string, string> Returns the resolved source URLs keyed by row id.
     */
    protected function sourceUrls(Collection $rows): array
    {
        $existing = $this->existingDocuments($rows);
        $sources = $this->documentSources();

        // A journal number is only a journal entry when the ledger says it is,
        // so the entries are looked up in one pass and matched per row below.
        $journalEntries = JournalEntry::query()
            ->whereIn(
                'document_number',
                $rows
                    ->filter(fn (Model $row): bool => (
                        $this->subtypeOf($row)
                    ) === LedgerSubType::JOURNAL && (
                        $this->typeOf($row)
                    ) === LedgerType::JOURNAL)
                    ->pluck('document_number')
                    ->filter()
                    ->unique()
            )
            ->pluck('id', 'document_number');

        return $rows
            ->mapWithKeys(function (Model $row) use ($existing, $sources, $journalEntries): array {
                $documentNumber = $this->documentNumberOf($row);
                $rowKey = $this->rowKey($row);

                if ($documentNumber === '' || $rowKey === '') {
                    return [];
                }

                $subtype = $this->subtypeOf($row);

                // A journal entry is the one document family keyed by its own id
                // rather than by its number, so it resolves separately.
                if ($subtype === LedgerSubType::JOURNAL) {
                    return $this->typeOf($row) === LedgerType::JOURNAL && filled($journalEntries->get($documentNumber))
                        ? [$rowKey => route('finance.journal-entries.show', $journalEntries->get($documentNumber))]
                        : [];
                }

                $source = $subtype === null ? null : ($sources[$subtype->value] ?? null);

                if ($source === null || ! in_array($documentNumber, $existing[$subtype->value] ?? [], true)) {
                    return [];
                }

                return [$rowKey => route($source[1], $documentNumber)];
            })
            ->all();
    }

    /**
     * The posted subtypes that name a document of their own, each with the model
     * holding it and the grid it drills down into.
     *
     * A receipt and a payment voucher, or a customer refund and a supplier refund,
     * are separate document families, so each subtype links into its own grid: a
     * supplier refund sent to the customer refund grid would land on a document
     * that is not there. A subtype absent from this map has no document of its own
     * to drill into — a transfer or a withdrawal is a movement between money
     * accounts, and the commercial, stock and payroll subtypes never reach the
     * financial ledger. Stock takes are not in this edition, so a stock variance
     * has no grid either.
     *
     * @return array<string, array{0: class-string<Model>, 1: string}> Families keyed by subtype value.
     */
    private function documentSources(): array
    {
        return [
            LedgerSubType::SALES_INVOICE->value => [SalesInvoice::class, 'sales.invoices.show'],
            LedgerSubType::MANUAL_SALES_INVOICE->value => [SalesInvoice::class, 'sales.invoices.show'],
            LedgerSubType::PURCHASE_INVOICE->value => [PurchaseInvoice::class, 'purchase.invoices.show'],
            LedgerSubType::RECEIPT->value => [Receipt::class, 'bank.receipts.show'],
            LedgerSubType::PAYMENT_VOUCHER->value => [PaymentVoucher::class, 'bank.payment-vouchers.show'],
            LedgerSubType::CUSTOMER_REFUND->value => [CustomerRefund::class, 'bank.customer-refunds.show'],
            LedgerSubType::REFUND->value => [CustomerRefund::class, 'bank.customer-refunds.show'],
            LedgerSubType::SUPPLIER_REFUND->value => [SupplierRefund::class, 'bank.supplier-refunds.show'],
            LedgerSubType::BANK_JOURNAL->value => [BankJournal::class, 'bank.bank-journals.show'],
            LedgerSubType::MANUAL_SALES_ADJUSTMENT->value => [
                ManualSalesAdjustment::class,
                'sales.manual-adjustments.show',
            ],
            LedgerSubType::MANUAL_PURCHASE_ADJUSTMENT->value => [
                ManualPurchaseAdjustment::class,
                'purchase.manual-adjustments.show',
            ],
        ];
    }

    /**
     * Find which of the documents named by these rows are actually present.
     *
     * One query per document family on the page, not one per row: a grid page is
     * dozens of rows over a handful of families. Deleted documents are absent
     * because every family is read through its own default scope, which is what
     * the destination page would do too.
     *
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $rows  The posted ledger rows.
     * @return array<string, array<int, string>> The present document numbers, keyed by subtype value.
     */
    private function existingDocuments(Collection $rows): array
    {
        $sources = $this->documentSources();
        $wanted = [];

        foreach ($rows as $row) {
            /** @var Model $row */
            $subtype = $this->subtypeOf($row);
            $documentNumber = $this->documentNumberOf($row);

            if ($subtype !== null && $documentNumber !== '' && isset($sources[$subtype->value])) {
                $wanted[$subtype->value][] = $documentNumber;
            }
        }

        /** @var array<string, array<int, string>> $existing */
        $existing = [];

        foreach ($wanted as $value => $documentNumbers) {
            /** @var class-string<Model> $model */
            $model = $sources[$value][0];

            $existing[$value] = $model::query()
                ->whereIn('document_number', array_unique($documentNumbers))
                ->pluck('document_number')
                ->filter(fn (mixed $number): bool => is_scalar($number))
                ->map(fn (bool|float|int|string $number): string => (string) $number)
                ->values()
                ->all();
        }

        return $existing;
    }

    /**
     * Read a row's document number as text.
     *
     * @param  Model  $row  The posted ledger row.
     * @return string The document number, or an empty string when absent.
     */
    private function documentNumberOf(Model $row): string
    {
        $documentNumber = $row->getAttribute('document_number');

        return is_scalar($documentNumber) ? (string) $documentNumber : '';
    }

    /**
     * Read a row's primary key as text.
     *
     * @param  Model  $row  The posted ledger row.
     * @return string The row key, or an empty string when absent.
     */
    private function rowKey(Model $row): string
    {
        $key = $row->getKey();

        return is_scalar($key) ? (string) $key : '';
    }

    /**
     * Read a row's subtype as an enum, whether it is cast or stored as text.
     *
     * @param  Model  $row  The posted ledger row.
     * @return LedgerSubType|null The resolved subtype, or null when unrecognised.
     */
    private function subtypeOf(Model $row): ?LedgerSubType
    {
        $subtype = $row->getAttribute('subtype');

        if ($subtype instanceof LedgerSubType) {
            return $subtype;
        }

        return is_string($subtype) ? LedgerSubType::tryFrom($subtype) : null;
    }

    /**
     * Read a row's type as an enum, whether it is cast or stored as text.
     *
     * @param  Model  $row  The posted ledger row.
     * @return LedgerType|null The resolved type, or null when unrecognised.
     */
    private function typeOf(Model $row): ?LedgerType
    {
        $type = $row->getAttribute('type');

        if ($type instanceof LedgerType) {
            return $type;
        }

        return is_string($type) ? LedgerType::tryFrom($type) : null;
    }
}
