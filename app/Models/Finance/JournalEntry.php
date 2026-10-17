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

namespace App\Models\Finance;

use App\Enums\LedgerStatus;
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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<JournalEntry>
 *
 * @property string $id
 * @property string|null $reversal_of_id
 * @property string $document_number
 * @property string|null $reference_number
 * @property CarbonInterface $date
 * @property numeric-string $total_debit
 * @property numeric-string $total_credit
 * @property LedgerStatus $status
 * @property string|null $notes
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
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 *
 * @noinspection PhpUnused
 *
 * @property-read Collection<int, JournalEntryChart> $charts
 * @property-read JournalEntry|null $reversalOf
 * @property-read JournalEntry|null $reversal
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('journal_entries', key: 'id')]
#[Fillable([
    'reversal_of_id',
    'document_number',
    'reference_number',
    'date',
    'total_debit',
    'total_credit',
    'status',
    'notes',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
])]
class JournalEntry extends Model
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
            'id' => 'string',
            'reversal_of_id' => 'string',
            'document_number' => 'string',
            'reference_number' => 'string',
            'date' => 'date',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'status' => LedgerStatus::class,
            'notes' => 'string',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * Define a one-to-many relationship.
     * Define a one-to-many relationship with JournalEntryChart.
     *
     * @return HasMany<JournalEntryChart, $this>
     */
    public function charts(): HasMany
    {
        return $this->hasMany(JournalEntryChart::class, 'journal_entry_id', 'id')
            ->oldest()
            ->orderBy('id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the original posted journal entry reversed by this entry.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id', 'id');
    }

    /**
     * Define a one-to-one relationship.
     * Get the posted reversal created for this journal entry.
     *
     * @return HasOne<JournalEntry, $this>
     */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id', 'id');
    }

    /**
     * Get the options for logging activity using Spatie Activitylog.
     *
     * @return LogOptions Returns configured log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }
}
