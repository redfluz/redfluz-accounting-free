<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @contact     corex2020@icloud.com
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

use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One row of the bank ledger.
 *
 * The bank ledger holds the balance at a financial institution. This is the one
 * money ledger that reconciles against a bank statement.
 *
 * @param  numeric-string  $debitAmount
 * @param  numeric-string  $creditAmount
 * @param  numeric-string  $balance
 */
class BankLedgerData extends Data
{
    /**
     * @param  string  $id  The row's key.
     * @param  string  $bankAccountId  The bank account the movement is against.
     * @param  string  $currencyId  The currency the movement is in.
     * @param  string|null  $sourceId  The document that moved the money.
     * @param  string|null  $sourceType  The kind of document, as the model's morph class.
     * @param  string  $documentNumber  The document's number.
     * @param  string  $documentDate  The document's date.
     * @param  string|null  $referenceNumber  What the movement settles, if anything.
     * @param  string  $debitAmount  The amount debited to the account.
     * @param  string  $creditAmount  The amount credited to the account.
     * @param  string  $balance  The account's balance after this movement.
     * @param  string|null  $bankReference  The bank's own reference for this movement.
     * @param  string|null  $description  What the movement was for.
     * @param  string|null  $notes  Additional notes.
     * @param  string|null  $createdAt  When the row was written.
     * @param  string|null  $updatedAt  When the row was last changed.
     * @param  string|null  $deletedAt  When the row was soft-deleted.
     */
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('bank_account_id')]
        public string $bankAccountId,

        #[MapName('currency_id')]
        public string $currencyId,

        #[MapName('source_id')]
        public ?string $sourceId,

        #[MapName('source_type')]
        public ?string $sourceType,

        #[MapName('document_number')]
        public string $documentNumber,

        #[MapName('document_date')]
        public string $documentDate,

        #[MapName('reference_number')]
        public ?string $referenceNumber,

        #[MapName('debit_amount')]
        public string $debitAmount,

        #[MapName('credit_amount')]
        public string $creditAmount,

        #[MapName('balance')]
        public string $balance,

        #[MapName('bank_reference')]
        public ?string $bankReference,

        #[MapName('description')]
        public ?string $description,

        #[MapName('notes')]
        public ?string $notes,

        #[MapName('created_at')]
        public ?string $createdAt,

        #[MapName('updated_at')]
        public ?string $updatedAt,

        #[MapName('deleted_at')]
        public ?string $deletedAt,
    ) {}

    /**
     * Build a bank ledger row.
     *
     * @param  string  $bankAccountId  The bank account.
     * @param  string  $currencyId  The currency.
     * @param  string|null  $sourceId  The document that moved the money.
     * @param  string|null  $sourceType  The document's morph class.
     * @param  string  $documentNumber  The document's number.
     * @param  string  $documentDate  The document's date.
     * @param  string|null  $referenceNumber  What the movement settles.
     * @param  numeric-string  $debitAmount  The amount debited.
     * @param  numeric-string  $creditAmount  The amount credited.
     * @param  numeric-string  $balance  The balance after this movement.
     * @param  string|null  $bankReference  The bank's own reference.
     * @param  string|null  $description  What the movement was for.
     */
    public static function build(
        string $bankAccountId,
        string $currencyId,
        ?string $sourceId,
        ?string $sourceType,
        string $documentNumber,
        string $documentDate,
        ?string $referenceNumber,
        string $debitAmount,
        string $creditAmount,
        string $balance,
        ?string $bankReference,
        ?string $description,
    ): self {
        $now = now();

        return new self(
            id: (string) Str::uuid7(),
            bankAccountId: $bankAccountId,
            currencyId: $currencyId,
            sourceId: $sourceId,
            sourceType: $sourceType,
            documentNumber: $documentNumber,
            documentDate: $documentDate,
            referenceNumber: $referenceNumber,
            debitAmount: $debitAmount,
            creditAmount: $creditAmount,
            balance: $balance,
            bankReference: $bankReference,
            description: $description,
            notes: null,
            createdAt: $now->format('Y-m-d H:i:s'),
            updatedAt: $now->format('Y-m-d H:i:s'),
            deletedAt: null,
        );
    }

    /**
     * The row as the bank_ledgers table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'bank_account_id' => $this->bankAccountId,
            'currency_id' => $this->currencyId,
            'source_id' => $this->sourceId,
            'source_type' => $this->sourceType,
            'document_number' => $this->documentNumber,
            'document_date' => $this->documentDate,
            'reference_number' => $this->referenceNumber,
            'debit_amount' => $this->debitAmount,
            'credit_amount' => $this->creditAmount,
            'balance' => $this->balance,
            'bank_reference' => $this->bankReference,
            'description' => $this->description,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
