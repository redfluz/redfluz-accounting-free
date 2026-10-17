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

use App\Enums\LedgerSourceType;
use App\Enums\LedgerStatus;
use App\Enums\PaymentTransferType;
use App\Models\Crm\ArLedger;
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
 * A receipt: money received from a customer.
 *
 * The document and its double entry are separate things. Everything about the
 * receipt itself, who paid, when, how much, what it settles and who approved it,
 * is on this row. The accounting entry that follows from it is on its lines.
 *
 * Keeping them apart is what makes a receipt readable on its own. When the two
 * were one row, the document number appeared once per accounting leg, so every
 * listing had to group by it to avoid showing the same receipt twice, and a
 * receipt split across several accounts could not be expressed at all.
 *
 * A receipt that has been posted has written to the general ledger and to
 * whichever money ledger its account belongs to, and to the receivables ledger
 * for the customer side. Those rows point back here through source_id and
 * source_type, so the entries a receipt produced can always be found.
 *
 * @property string $id
 * @property string $company_id
 * @property string|null $contact_id
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
 * @property-read Collection<int, ReceiptLine> $lines
 * @property-read Collection<int, ReceiptChart> $charts
 * @property-read Collection<int, Activity> $activities
 *
 * @mixin Builder<self>
 *
 * @noinspection PhpUnused
 */
#[Table('receipts')]
#[Fillable([
    'company_id', 'contact_id',
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
class Receipt extends Model
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
            'date' => 'date',
            'status' => LedgerStatus::class,
            'payment_transfer_type' => PaymentTransferType::class,
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * The double entry this receipt carries.
     *
     * @return HasMany<ReceiptLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ReceiptLine::class);
    }

    /**
     * The chart of accounts this receipt posts to.
     *
     * @return HasMany<ReceiptChart, $this>
     */
    public function charts(): HasMany
    {
        return $this->hasMany(ReceiptChart::class);
    }

    /**
     * The customer the money was received from.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The receivable entries this receipt raised when it was posted.
     *
     * Matched on the subtype rather than a polymorphic type, because the receivable
     * ledger stores the LedgerSourceType that raised the row, not the model class
     * that did. Without the subtype half of the match every receipt would claim
     * every other receipt's entries too, since they all share one source_id space
     * only by coincidence.
     *
     * @return HasMany<ArLedger, $this>
     */
    public function receivableEntries(): HasMany
    {
        return $this->hasMany(ArLedger::class, 'source_id', 'id')
            ->where('source_type', LedgerSourceType::RECEIPT->value);
    }

    /**
     * Whether this receipt has reached the general ledger.
     */
    public function isPosted(): bool
    {
        return $this->status === LedgerStatus::POSTED;
    }

    /**
     * Whether this receipt can still be changed.
     *
     * A posted receipt has already moved the money, so editing it would leave the
     * ledgers describing something the document no longer says.
     */
    public function isEditable(): bool
    {
        return ! $this->isPosted() && ! $this->isCancelled();
    }

    /**
     * Whether this receipt has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === LedgerStatus::CANCELLED;
    }
}
