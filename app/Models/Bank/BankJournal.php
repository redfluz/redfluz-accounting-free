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

use App\Enums\BankAccountKind;
use App\Enums\LedgerStatus;
use App\Models\ChartOfAccount\Chart;
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
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A bank journal: money moved in or out of one money account.
 *
 * Bank journals are for first-time setup only (e.g. opening balances, initial
 * data migration). They are not intended for ongoing daily transactions. Regular
 * money movements come from posted documents: receipts, payment vouchers,
 * invoices, and other operational documents that write to the ledgers directly.
 *
 * The document and its double entry are separate things. Everything about the
 * journal itself — which account, which way, when, how much, what offsets it and
 * who approved it — is on this row. The accounting entry that follows from it is
 * written to the money ledgers and the general ledger when it is approved.
 *
 * A journal that has been posted has moved the money, so it is never edited and
 * never deleted, only looked at. A pending journal has moved nothing, so it can
 * still be changed, cancelled or removed.
 *
 * @property string $id
 * @property string|null $bank_account_id
 * @property string|null $cash_account_id
 * @property string|null $e_wallet_account_id
 * @property string $direction
 * @property string $document_number
 * @property CarbonInterface $document_date
 * @property string $offset_chart_id
 * @property numeric-string $amount
 * @property string|null $description
 * @property string|null $notes
 * @property LedgerStatus $status
 * @property string|null $created_by
 * @property string|null $created_name
 * @property string|null $updated_by
 * @property string|null $updated_name
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
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Bank|null $bankAccount
 * @property-read CashAccount|null $cashAccount
 * @property-read EWalletAccount|null $eWalletAccount
 * @property-read Chart $offsetChart
 *
 * @mixin Builder<self>
 *
 * @noinspection PhpUnused
 *
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('bank_journals', key: 'id')]
#[Fillable([
    'bank_account_id', 'cash_account_id', 'e_wallet_account_id',
    'direction',
    'document_number', 'document_date',
    'offset_chart_id', 'amount',
    'description', 'notes', 'status',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
    'id',
])]
class BankJournal extends Model
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
            'document_date' => 'date',
            'status' => LedgerStatus::class,
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * The bank account this journal moves money from or to.
     *
     * Only one of the three account relationships will be set on any given row;
     * the other two FK columns are null.
     *
     * @return BelongsTo<Bank, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_account_id');
    }

    /**
     * The cash account this journal moves money from or to.
     *
     * Only one of the three account relationships will be set on any given row;
     * the other two FK columns are null.
     *
     * @return BelongsTo<CashAccount, $this>
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'cash_account_id');
    }

    /**
     * The e-wallet account this journal moves money from or to.
     *
     * Only one of the three account relationships will be set on any given row;
     * the other two FK columns are null.
     *
     * @return BelongsTo<EWalletAccount, $this>
     */
    public function eWalletAccount(): BelongsTo
    {
        return $this->belongsTo(EWalletAccount::class, 'e_wallet_account_id');
    }

    /**
     * The money account this journal moves, whichever of the three is set.
     *
     * Returns the non-null account, or null when no FK has been filled in yet.
     */
    public function moneyAccount(): Bank|CashAccount|EWalletAccount|null
    {
        return $this->bankAccount ?? $this->cashAccount ?? $this->eWalletAccount;
    }

    /**
     * The kind of money account this journal moves, based on which FK is set.
     *
     * Returns null when no account FK has been filled in yet.
     */
    public function accountKind(): ?BankAccountKind
    {
        if ($this->bank_account_id !== null) {
            return BankAccountKind::BANK;
        }

        if ($this->cash_account_id !== null) {
            return BankAccountKind::CASH;
        }

        if ($this->e_wallet_account_id !== null) {
            return BankAccountKind::E_WALLET;
        }

        return null;
    }

    /**
     * The chart account on the other side of the movement.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function offsetChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'offset_chart_id');
    }

    /**
     * Get the options for logging activity using Spatie Activitylog.
     *
     * @return LogOptions Returns configured log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * Whether this journal has reached the ledgers.
     */
    public function isPosted(): bool
    {
        return $this->status === LedgerStatus::POSTED;
    }

    /**
     * Whether this journal can still be changed.
     *
     * A posted journal has already moved the money, so editing it would leave the
     * ledgers describing something the document no longer says.
     */
    public function isEditable(): bool
    {
        return ! $this->isPosted() && ! $this->isCancelled();
    }

    /**
     * Whether this journal has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === LedgerStatus::CANCELLED;
    }
}
