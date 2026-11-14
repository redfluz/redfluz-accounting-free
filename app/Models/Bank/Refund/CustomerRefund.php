<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

namespace App\Models\Bank\Refund;

use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\PaymentTransferType;
use App\Models\Bank\Bank;
use App\Models\Bank\EWalletAccount;
use App\Models\Crm\ArLedger;
use App\Models\Crm\Company;
use App\Support\Traits\AttributesDocuments;
use App\Support\Traits\RecordsAuthor;
use App\Support\Traits\RecordsRefundStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;

/**
 * A refund given back to a customer who paid us.
 *
 * The document and its double entry are separate things. Everything about the
 * refund itself, who it is with, when, how much, what it settles and who approved
 * it, is on this row. The accounting entry that follows from it is on its lines.
 *
 * A posted customer refund has written to the general ledger, to whichever money
 * ledger its account belongs to, and to the receivable ledger. Those rows point
 * back here through the document number and subtype, so the entries a refund
 * produced can always be found.
 *
 * The kind is the table, not a column. A customer refund cannot be a supplier one
 * because there is no column left to say so, and every query against this model
 * needs no filter to stay on the right side. That is the whole reason this is a
 * separate model from {@see SupplierRefund}: sharing one would put both kinds
 * back in reach of the same query, which is the mistake the split exists to make
 * impossible.
 *
 * @property string $id
 * @property string $company_id
 * @property string|null $contact_id
 * @property string|null $bank_account_id
 * @property string|null $e_wallet_account_id
 * @property DocumentStatus $status
 * @property PaymentTransferType|null $payment_transfer_type
 * @property string $document_number
 * @property string $company_name
 * @property string|null $contact_name
 * @property CarbonInterface $date
 * @property string|null $reference_number
 * @property string|null $attachment_path
 * @property string|null $notes
 * @property numeric-string $total_amount
 * @property string|null $created_by
 * @property string|null $created_name
 * @property CarbonInterface|null $created_at
 * @property string|null $updated_by
 * @property string|null $updated_name
 * @property CarbonInterface|null $updated_at
 * @property string|null $deleted_by
 * @property string|null $deleted_name
 * @property CarbonInterface|null $deleted_at
 * @property string|null $approved_by
 * @property string|null $approved_name
 * @property CarbonInterface|null $approved_at
 * @property string|null $cancelled_by
 * @property string|null $cancelled_name
 * @property CarbonInterface|null $cancelled_at
 * @property string|null $undone_by
 * @property string|null $undone_name
 * @property CarbonInterface|null $undone_at
 * @property-read Collection<int, Activity> $activities
 *
 * @method Bank|EWalletAccount|null moneyAccount()
 *
 * @mixin Builder<self>
 */
#[Table('customer_refunds')]
#[Fillable([
    'company_id',
    'contact_id',
    'bank_account_id',
    'e_wallet_account_id',
    'status',
    'payment_transfer_type',
    'document_number',
    'company_name',
    'contact_name',
    'date',
    'reference_number',
    'attachment_path',
    'notes',
    'total_amount',
    'created_by',
    'created_name',
    'created_at',
    'updated_by',
    'updated_name',
    'updated_at',
    'deleted_by',
    'deleted_name',
    'deleted_at',
    'approved_by',
    'approved_name',
    'approved_at',
    'cancelled_by',
    'cancelled_name',
    'cancelled_at',
    'undone_by',
    'undone_name',
    'undone_at',
    'id',
])]
class CustomerRefund extends Model
{
    use AttributesDocuments, HasActivity, HasUuids, RecordsAuthor, RecordsRefundStatus, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'payment_transfer_type' => PaymentTransferType::class,
            'date' => 'date',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * The company this refund is with.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * The bank account the money left, when the transfer type named a bank.
     *
     * @return BelongsTo<Bank, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_account_id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * The e-wallet the money left, when the transfer type named a wallet.
     *
     * @return BelongsTo<EWalletAccount, $this>
     */
    public function eWalletAccount(): BelongsTo
    {
        return $this->belongsTo(EWalletAccount::class, 'e_wallet_account_id');
    }

    /**
     * The account this refund was paid from, whichever kind it is.
     *
     * Exactly one of the two account columns is ever filled, because a refund
     * travels by bank or by wallet and the transfer type decides which one the form
     * was allowed to set. Reading the account through here means a caller never has
     * to repeat that choice, and the chart the money posts against comes off the
     * account itself: every account row owns one chart, which is what puts the
     * movement in that account's ledger rather than in whichever account the
     * settings happen to name today.
     */
    public function moneyAccount(): Bank|EWalletAccount|null
    {
        return $this->bankAccount ?? $this->eWalletAccount;
    }

    /**
     * Define a one-to-many relationship.
     * The receivable entries this refund raised when it was posted.
     *
     * Matched on the document number plus the subtype: the receivable ledger
     * stores the LedgerSubType that raised the row. A supplier refund posts to
     * the payable ledger instead, so the two never meet here.
     *
     * @return HasMany<ArLedger, $this>
     */
    public function arLedgers(): HasMany
    {
        return $this->hasMany(ArLedger::class, 'document_number', 'document_number')
            ->where('subtype', LedgerSubType::CUSTOMER_REFUND->value);
    }
}
