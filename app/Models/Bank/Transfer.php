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

namespace App\Models\Bank;

use App\Enums\LedgerStatus;
use App\Models\ChartOfAccount\Chart;
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
 * One movement of money between a cash in transit account and one money account.
 *
 * A banker's deposit is two events, not one. The cash leaves the drawer on one
 * day and the bank credits it on another, and in between the money is in
 * neither place. Each half is a transfer here, in the direction that names which
 * side of that gap it closes: 'out' takes surplus out of a drawer into transit,
 * 'in' takes it out of transit into a bank account once the bank has it.
 *
 * A transfer names exactly one money account. Combining several into one row
 * would produce an entry that none of them individually agrees with, so a count
 * of any one drawer could not be reconciled against it.
 *
 * @mixin Builder<Transfer>
 *
 * @property string $id
 * @property string $document_number
 * @property string $direction
 * @property string|null $cash_account_id
 * @property string|null $bank_account_id
 * @property string $transit_chart_id
 * @property CarbonInterface $document_date
 * @property numeric-string $amount
 * @property string|null $reference_number
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property string|null $description
 * @property string|null $notes
 * @property LedgerStatus $status
 * @property string|null $created_by
 * @property string|null $created_name
 * @property string|null $updated_by
 * @property string|null $updated_name
 * @property string|null $deleted_by
 * @property string|null $deleted_name
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
 * @property CarbonInterface|null $deleted_at
 * @property-read CashAccount|null $cashAccount
 * @property-read Bank|null $bankAccount
 * @property-read Chart|null $transitChart
 *
 * @noinspection PhpUnused
 *
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('transfers', key: 'id')]
#[Fillable([
    'document_number', 'direction',
    'cash_account_id', 'bank_account_id', 'transit_chart_id',
    'document_date', 'amount', 'reference_number',
    'attachment_path', 'attachment_name',
    'description', 'notes', 'status',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
    'id',
])]
class Transfer extends Model
{
    use HasActivity, HasUuids, SoftDeletes;

    /**
     * Cash moving out of a drawer into cash in transit.
     */
    public const string DIRECTION_OUT = 'out';

    /**
     * Cash moving out of transit into a bank account.
     */
    public const string DIRECTION_IN = 'in';

    /**
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
     * The drawer this transfer empties, on a transfer out.
     *
     * Null on a transfer in, which is bankward and names a bank instead.
     *
     * @return BelongsTo<CashAccount, $this>
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'cash_account_id');
    }

    /**
     * The bank account this transfer fills, on a transfer in.
     *
     * Null on a transfer out, which is drawerward and names a cash account.
     *
     * @return BelongsTo<Bank, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_account_id');
    }

    /**
     * The account holding the cash while it is between the drawer and the bank.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function transitChart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'transit_chart_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * Whether this transfer moves cash into transit.
     */
    public function isOutbound(): bool
    {
        return $this->direction === self::DIRECTION_OUT;
    }

    /**
     * Whether this transfer moves cash out of transit into a bank.
     */
    public function isInbound(): bool
    {
        return $this->direction === self::DIRECTION_IN;
    }

    /**
     * Whether this transfer has reached the ledgers.
     */
    public function isPosted(): bool
    {
        return $this->status === LedgerStatus::POSTED;
    }

    /**
     * Whether this transfer has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === LedgerStatus::CANCELLED;
    }

    /**
     * Whether this transfer can still be changed.
     *
     * A posted transfer has moved the money, so editing it would leave the
     * ledgers describing something the document no longer says.
     */
    public function isEditable(): bool
    {
        return ! $this->isPosted() && ! $this->isCancelled();
    }
}
