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

namespace App\Models\Bank;

use App\Models\Finance\Currency;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One movement of money through an e-wallet account.
 *
 * The e-wallet ledger is immutable history: once a row is written, it is never
 * updated or deleted. The balance column is the running total at that point,
 * and the row is the audit trail of what happened.
 *
 * @property string $id
 * @property string $e_wallet_account_id
 * @property string $currency_id
 * @property string|null $source_id
 * @property string|null $source_type
 * @property string $document_number
 * @property CarbonInterface $date
 * @property string|null $reference_number
 * @property numeric-string $debit_amount
 * @property numeric-string $credit_amount
 * @property numeric-string $balance
 * @property string|null $wallet_reference
 * @property string|null $description
 * @property string|null $notes
 * @property CarbonInterface $created_at
 *
 * @mixin Builder<self>
 */
#[Table('e_wallet_ledgers', key: 'id')]
#[Fillable([
    'e_wallet_account_id',
    'currency_id',
    'source_id',
    'source_type',
    'document_number',
    'date',
    'reference_number',
    'debit_amount',
    'credit_amount',
    'balance',
    'wallet_reference',
    'description',
    'notes',
])]
class EWalletLedger extends Model
{
    use HasUuids;

    /**
     * Disable updated_at: ledgers are immutable history.
     */
    public const null UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * The e-wallet account this movement is against.
     *
     * @return BelongsTo<EWalletAccount, $this>
     */
    public function eWalletAccount(): BelongsTo
    {
        return $this->belongsTo(EWalletAccount::class, 'e_wallet_account_id', 'id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * The currency the movement is in.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }
}
