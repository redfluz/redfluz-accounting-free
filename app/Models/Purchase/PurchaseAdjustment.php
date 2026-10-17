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

namespace App\Models\Purchase;

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
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

/**
 * A purchase adjustment: a correction raised against a purchase invoice.
 *
 * The mirror of a sales adjustment. A purchase invoice that turns out to be
 * wrong is not edited, because the supplier already has it; a note is raised
 * against it instead, which is why this is a document with its own number.
 *
 * The two kinds are the same document reversed, so both live here and the
 * direction is a column rather than a second table: a debit note raises what is
 * owed to the supplier, a credit note reduces it. What the note is made of is
 * on {@see PurchaseAdjustmentLine}.
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
 * @property-read Collection<int, PurchaseAdjustmentLine> $lines
 *
 * @mixin Builder<self>
 */
#[Table('purchase_adjustments', key: 'id')]
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
    'description',
])]
class PurchaseAdjustment extends Model
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
     * The legs this adjustment is made of.
     *
     * @return HasMany<PurchaseAdjustmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseAdjustmentLine::class)->orderBy('created_at');
    }

    /**
     * The supplier the correction is for.
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
     * A posted note has already moved a payable, so editing it would leave the
     * ledgers describing something the document no longer says.
     */
    public function isEditable(): bool
    {
        return ! $this->isPosted() && ! $this->isCancelled();
    }
}
