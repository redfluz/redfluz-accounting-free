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

namespace App\Models\Bank;

use App\Enums\Finance\LedgerStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\PaymentTransferType;
use App\Models\Crm\ApLedger;
use App\Models\Crm\Company;
use App\Support\Traits\AttributesDocuments;
use App\Support\Traits\RecordsAuthor;
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
 * A payment voucher: money paid to a supplier.
 *
 * The document and its double entry are separate things. Everything about the
 * voucher itself, who it is with, when, how much, what it settles and who
 * approved it, is on this row. The accounting entry that follows from it is on
 * its lines.
 *
 * A posted voucher has written to the general ledger, to whichever money ledger
 * its account belongs to, and to the payable ledger for the
 * supplier side. Those rows point back here through the document number and
 * subtype, so the entries a voucher produced can always be found.
 *
 * @property string $id
 * @property string $company_id
 * @property string|null $contact_id
 * @property string|null $bank_account_id
 * @property string|null $cash_account_id
 * @property string|null $e_wallet_account_id
 * @property LedgerStatus $status
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
 * @method Bank|CashAccount|EWalletAccount|null moneyAccount()
 *
 * @mixin Builder<self>
 */
#[Table('payment_vouchers')]
#[Fillable([
    'company_id', 'contact_id',
    'bank_account_id', 'cash_account_id', 'e_wallet_account_id',
    'status', 'payment_transfer_type',
    'document_number', 'company_name', 'contact_name',
    'date', 'reference_number',
    'attachment_path', 'notes', 'total_amount',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
    'id',
])]
class PaymentVoucher extends Model
{
    use AttributesDocuments, HasActivity, HasUuids, RecordsAuthor, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LedgerStatus::class,
            'payment_transfer_type' => PaymentTransferType::class,
            'date' => 'date',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * The company this voucher is with.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The bank account the money left, when the transfer type named a bank.
     *
     * @return BelongsTo<Bank, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_account_id');
    }

    /**
     * The cash register the money left, when the transfer type named cash.
     *
     * @return BelongsTo<CashAccount, $this>
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'cash_account_id');
    }

    /**
     * The e-wallet the money left, when the transfer type named a wallet.
     *
     * @return BelongsTo<EWalletAccount, $this>
     */
    public function eWalletAccount(): BelongsTo
    {
        return $this->belongsTo(EWalletAccount::class, 'e_wallet_account_id');
    }

    /**
     * The account this voucher was paid from, whichever kind it is.
     *
     * Exactly one of the three account columns is ever filled, because the
     * transfer type decides which one the form was allowed to set. Reading the
     * account through here means a caller never has to repeat that choice, and
     * the chart the money posts against comes off the account itself: every
     * account row owns one chart, which is what puts the movement in that
     * account's ledger rather than in whichever account the settings happen to
     * name today.
     */
    public function moneyAccount(): Bank|CashAccount|EWalletAccount|null
    {
        return $this->bankAccount ?? $this->cashAccount ?? $this->eWalletAccount;
    }

    /**
     * The payable entries this voucher raised when it was posted.
     *
     * Matched on the document number plus the subtype: the payable ledger
     * stores the LedgerSubType that raised the row, so a number raised by a
     * different kind of document never matches here.
     *
     * @return HasMany<ApLedger, $this>
     */
    public function apLedgers(): HasMany
    {
        return $this->hasMany(ApLedger::class, 'document_number', 'document_number')
            ->where('subtype', LedgerSubType::PAYMENT_VOUCHER->value);
    }

    /**
     * Whether this voucher has reached the general ledger.
     */
    public function isPosted(): bool
    {
        return $this->status === LedgerStatus::POSTED;
    }

    /**
     * Whether this voucher can still be changed.
     *
     * A posted voucher has already moved money, so editing it would leave the
     * ledgers describing something the document no longer says.
     */
    public function isEditable(): bool
    {
        return ! $this->isPosted() && ! $this->isCancelled();
    }

    /**
     * Whether this voucher has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === LedgerStatus::CANCELLED;
    }
}
