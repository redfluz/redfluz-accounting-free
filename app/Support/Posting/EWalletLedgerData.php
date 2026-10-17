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
 * One row of the e-wallet ledger.
 *
 * The e-wallet ledger holds money in a third-party wallet: Touch 'n Go, Boost,
 * GrabPay, etc. Funds settle immediately and a receipt is final the moment it lands.
 *
 * @param  numeric-string  $debitAmount
 * @param  numeric-string  $creditAmount
 * @param  numeric-string  $balance
 */
class EWalletLedgerData extends Data
{
    /**
     * @param  string  $id  The row's key.
     * @param  string  $eWalletAccountId  The e-wallet account the movement is against.
     * @param  string  $currencyId  The currency the movement is in.
     * @param  string|null  $sourceId  The document that moved the money.
     * @param  string|null  $sourceType  The kind of document, as the model's morph class.
     * @param  string  $documentNumber  The document's number.
     * @param  string  $documentDate  The document's date.
     * @param  string|null  $referenceNumber  What the movement settles, if anything.
     * @param  string  $debitAmount  The amount debited to the account.
     * @param  string  $creditAmount  The amount credited to the account.
     * @param  string  $balance  The account's balance after this movement.
     * @param  string|null  $walletReference  The provider's own reference for this movement.
     * @param  string|null  $description  What the movement was for.
     * @param  string|null  $notes  Additional notes.
     * @param  string|null  $createdAt  When the row was written.
     * @param  string|null  $updatedAt  When the row was last changed.
     * @param  string|null  $deletedAt  When the row was soft-deleted.
     */
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('e_wallet_account_id')]
        public string $eWalletAccountId,

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

        #[MapName('wallet_reference')]
        public ?string $walletReference,

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
     * Build an e-wallet ledger row.
     *
     * @param  string  $eWalletAccountId  The e-wallet account.
     * @param  string  $currencyId  The currency.
     * @param  string|null  $sourceId  The document that moved the money.
     * @param  string|null  $sourceType  The document's morph class.
     * @param  string  $documentNumber  The document's number.
     * @param  string  $documentDate  The document's date.
     * @param  string|null  $referenceNumber  What the movement settles.
     * @param  numeric-string  $debitAmount  The amount debited.
     * @param  numeric-string  $creditAmount  The amount credited.
     * @param  numeric-string  $balance  The balance after this movement.
     * @param  string|null  $walletReference  The provider's own reference.
     * @param  string|null  $description  What the movement was for.
     */
    public static function build(
        string $eWalletAccountId,
        string $currencyId,
        ?string $sourceId,
        ?string $sourceType,
        string $documentNumber,
        string $documentDate,
        ?string $referenceNumber,
        string $debitAmount,
        string $creditAmount,
        string $balance,
        ?string $walletReference,
        ?string $description,
    ): self {
        $now = now();

        return new self(
            id: (string) Str::uuid7(),
            eWalletAccountId: $eWalletAccountId,
            currencyId: $currencyId,
            sourceId: $sourceId,
            sourceType: $sourceType,
            documentNumber: $documentNumber,
            documentDate: $documentDate,
            referenceNumber: $referenceNumber,
            debitAmount: $debitAmount,
            creditAmount: $creditAmount,
            balance: $balance,
            walletReference: $walletReference,
            description: $description,
            notes: null,
            createdAt: $now->format('Y-m-d H:i:s'),
            updatedAt: $now->format('Y-m-d H:i:s'),
            deletedAt: null,
        );
    }

    /**
     * The row as the e_wallet_ledgers table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'e_wallet_account_id' => $this->eWalletAccountId,
            'currency_id' => $this->currencyId,
            'source_id' => $this->sourceId,
            'source_type' => $this->sourceType,
            'document_number' => $this->documentNumber,
            'document_date' => $this->documentDate,
            'reference_number' => $this->referenceNumber,
            'debit_amount' => $this->debitAmount,
            'credit_amount' => $this->creditAmount,
            'balance' => $this->balance,
            'wallet_reference' => $this->walletReference,
            'description' => $this->description,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
