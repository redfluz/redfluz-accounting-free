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

namespace App\Models\Sales;

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Models\Crm\Company;
use App\Models\Finance\JournalEntry;
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

/**
 * A sales adjustment: a correction raised against an invoice that has gone out.
 *
 * A sales invoice that turns out to be wrong is not edited, because the customer
 * already has it. A correction is raised against it instead, which is why this
 * is a document with its own number rather than an edit to the invoice.
 *
 * The two kinds are the same document reversed: an increase raises the invoice, a
 * decrease reduces it. Both live here because both are answered by the same grid,
 * and the direction is a column rather than a second table. What it is made of is
 * on {@see SalesAdjustmentLine}.
 *
 * @property string $id
 * @property string|null $company_id
 * @property string|null $contact_id
 * @property LedgerSubType $subtype
 * @property LedgerStatus $status
 * @property string $document_number
 * @property string|null $reference_number
 * @property string|null $company_name
 * @property string|null $contact_name
 * @property CarbonInterface $date
 * @property string|null $attachment_path
 * @property numeric-string|null $subtotal
 * @property numeric-string|null $total_amount
 * @property string|null $notes
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
 * @property-read Collection<int, SalesAdjustmentLine> $lines
 *
 * @mixin Builder<self>
 */
#[Table('sales_adjustments', key: 'id')]
#[Fillable([
    'company_id', 'contact_id',
    'subtype', 'status',
    'document_number', 'reference_number',
    'company_name', 'contact_name',
    'date', 'attachment_path', 'subtotal', 'total_amount',
    'notes',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
    'reversal_journal_id',
    'description',
    'debit_id',
    'debit_acc',
    'debit_acc_desc',
    'debit_amount',
    'credit_amount',
    'id',
    'credit_id',
    'credit_acc',
    'credit_acc_desc',
])]
class SalesAdjustment extends Model
{
    use AttributesDocuments, HasUuids, RecordsAuthor, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtype' => LedgerSubType::class,
            'status' => LedgerStatus::class,
            'date' => 'date',
            'subtotal' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * The journal that reversed this document, if it was reversed.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function reversalJournal(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_id');
    }

    /**
     * The legs this adjustment is made of.
     *
     * @return HasMany<SalesAdjustmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SalesAdjustmentLine::class)->orderBy('created_at');
    }

    /**
     * The chart of accounts this note posts to.
     *
     * @return HasMany<SalesAdjustmentChart, $this>
     */
    public function charts(): HasMany
    {
        return $this->hasMany(SalesAdjustmentChart::class)->orderBy('created_at');
    }

    /**
     * The company the correction is for.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Whether this adjustment has reached the general ledger.
     */
    public function isPosted(): bool
    {
        return $this->status === LedgerStatus::POSTED;
    }

    /**
     * Whether this adjustment has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === LedgerStatus::CANCELLED;
    }

    /**
     * Whether this adjustment can still be changed.
     *
     * A posted correction has already moved money, so editing it would leave the
     * ledgers describing something the document no longer says.
     */
    public function isEditable(): bool
    {
        return ! $this->isPosted() && ! $this->isCancelled();
    }
}
