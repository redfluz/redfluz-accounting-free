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

namespace app\Support\Traits;

use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\Bank\BankJournal;
use App\Models\Bank\PaymentVoucher;
use App\Models\Bank\Receipt;
use App\Models\Bank\Refund\CustomerRefund;
use App\Models\Bank\Refund\SupplierRefund;
use App\Models\Finance\JournalEntry;
use App\Models\Purchase\ManualPurchaseAdjustment;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Sales\ManualSalesAdjustment;
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
        $journalNumbers = $rows
            ->filter(fn (Model $row): bool => (
                $this->subtypeOf($row)
            ) === LedgerSubType::JOURNAL && (
                $this->typeOf($row)
            ) === LedgerType::JOURNAL)
            ->pluck('document_number')
            ->filter()
            ->unique();

        $journalEntries = JournalEntry::query()
            ->whereIn('document_number', $journalNumbers)
            ->pluck('document_number')
            ->flip();

        // COGS and other automatic postings are journal rows that carry the
        // source invoice number instead of a journal number. They have no
        // JournalEntry of their own, so resolve them back to that invoice.
        $journalSalesInvoices = SalesInvoice::query()
            ->whereIn('document_number', $journalNumbers)
            ->pluck('document_number')
            ->flip();

        $journalPurchaseInvoices = PurchaseInvoice::query()
            ->whereIn('document_number', $journalNumbers)
            ->pluck('document_number')
            ->flip();

        return $rows
            ->mapWithKeys(function (Model $row) use ($existing, $sources, $journalEntries, $journalSalesInvoices, $journalPurchaseInvoices): array {
                $documentNumber = $this->documentNumberOf($row);
                $rowKey = $this->rowKey($row);

                if ($documentNumber === '' || $rowKey === '') {
                    return [];
                }

                $subtype = $this->subtypeOf($row);

                // A journal entry drills into its own history page, keyed by the
                // journal number the ledger row already carries.
                if ($subtype === LedgerSubType::JOURNAL) {
                    if ($this->typeOf($row) !== LedgerType::JOURNAL) {
                        return [];
                    }

                    if ($journalEntries->has($documentNumber)) {
                        return [$rowKey => route('finance.journal-entries.history.show', $documentNumber)];
                    }

                    if ($journalSalesInvoices->has($documentNumber)) {
                        return [$rowKey => route('sales.invoices.history.show', $documentNumber)];
                    }

                    if ($journalPurchaseInvoices->has($documentNumber)) {
                        return [$rowKey => route('purchase.invoices.history.show', $documentNumber)];
                    }

                    return [];
                }

                $source = $subtype === null ? null : ($sources[$subtype->value] ?? null);

                if ($source === null) {
                    return [];
                }

                // Most families are keyed by the row's own document number; an
                // inventory deduction carries its own number and names the
                // invoice it came from in reference_number instead.
                $matchNumber = isset($source[2]) && $source[2] === 'reference_number'
                    ? $this->referenceNumberOf($row)
                    : $documentNumber;

                if ($matchNumber === '' || ! in_array($matchNumber, $existing[$subtype->value] ?? [], true)) {
                    return [];
                }

                return [$rowKey => route($source[1], $matchNumber)];
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
     * The optional third element names the column the document is matched on
     * when it is not the row's own document number: an inventory deduction has
     * a number of its own and keeps the source invoice in reference_number.
     *
     * @return array<string, array{0: class-string<Model>, 1: string, 2?: string}> Families keyed by subtype value.
     */
    private function documentSources(): array
    {
        return [
            LedgerSubType::SALES_INVOICE->value => [SalesInvoice::class, 'sales.invoices.history.show'],
            LedgerSubType::INVENTORY_DEDUCTION->value => [SalesInvoice::class, 'sales.invoices.history.show', 'reference_number'],
            LedgerSubType::MANUAL_SALES_INVOICE->value => [SalesInvoice::class, 'sales.invoices.history.show'],
            LedgerSubType::PURCHASE_INVOICE->value => [PurchaseInvoice::class, 'purchase.invoices.history.show'],
            LedgerSubType::RECEIPT->value => [Receipt::class, 'bank.receipts.history.show'],
            LedgerSubType::PAYMENT_VOUCHER->value => [PaymentVoucher::class, 'bank.payment-vouchers.history.show'],
            LedgerSubType::CUSTOMER_REFUND->value => [CustomerRefund::class, 'bank.customer-refunds.history.show'],
            LedgerSubType::REFUND->value => [CustomerRefund::class, 'bank.customer-refunds.history.show'],
            LedgerSubType::SUPPLIER_REFUND->value => [SupplierRefund::class, 'bank.supplier-refunds.history.show'],
            LedgerSubType::BANK_JOURNAL->value => [BankJournal::class, 'bank.bank-journals.show'],
            LedgerSubType::MANUAL_SALES_ADJUSTMENT->value => [
                ManualSalesAdjustment::class,
                'sales.manual-adjustments.history.show',
            ],
            LedgerSubType::MANUAL_PURCHASE_ADJUSTMENT->value => [
                ManualPurchaseAdjustment::class,
                'purchase.manual-adjustments.history.show',
            ],
            LedgerSubType::SALES_DEBIT_NOTE->value => [
                ManualSalesAdjustment::class,
                'sales.manual-adjustments.history.show',
            ],
            LedgerSubType::SALES_CREDIT_NOTE->value => [
                ManualSalesAdjustment::class,
                'sales.manual-adjustments.history.show',
            ],
            LedgerSubType::PURCHASE_DEBIT_NOTE->value => [
                ManualPurchaseAdjustment::class,
                'purchase.manual-adjustments.history.show',
            ],
            LedgerSubType::PURCHASE_CREDIT_NOTE->value => [
                ManualPurchaseAdjustment::class,
                'purchase.manual-adjustments.history.show',
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

            if ($subtype === null || ! isset($sources[$subtype->value])) {
                continue;
            }

            $matchNumber = isset($sources[$subtype->value][2]) && $sources[$subtype->value][2] === 'reference_number'
                ? $this->referenceNumberOf($row)
                : $this->documentNumberOf($row);

            if ($matchNumber !== '') {
                $wanted[$subtype->value][] = $matchNumber;
            }
        }

        /** @var array<string, array<int, string>> $existing */
        $existing = [];

        foreach ($wanted as $value => $documentNumbers) {
            /** @var class-string<Model> $model */
            $model = $sources[$value][0];
            $matchColumn = $sources[$value][2] ?? 'document_number';

            $existing[$value] = $model::query()
                ->whereIn($matchColumn, array_unique($documentNumbers))
                ->pluck($matchColumn)
                ->filter(fn (mixed $number): bool => is_scalar($number))
                ->map(fn (bool|float|int|string $number): string => (string) $number)
                ->values()
                ->all();
        }

        return $existing;
    }

    /**
     * Read a row's reference number as text.
     *
     * @param  Model  $row  The posted ledger row.
     * @return string The reference number, or an empty string when absent.
     */
    private function referenceNumberOf(Model $row): string
    {
        $referenceNumber = $row->getAttribute('reference_number');

        return is_scalar($referenceNumber) ? (string) $referenceNumber : '';
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
