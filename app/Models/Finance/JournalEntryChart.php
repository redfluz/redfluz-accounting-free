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

use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\ChartOfAccount\Chart;
use App\Models\Crm\Company;
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

/**
 * @mixin Builder<JournalEntryChart>
 *
 * @property string $id
 * @property string $journal_entry_id
 * @property string|null $chart_id
 * @property string|null $company_id
 * @property string|null $currency_id
 * @property LedgerType $type
 * @property LedgerSubType $subtype
 * @property string $document_number
 * @property string|null $reference_number
 * @property CarbonInterface $date
 * @property string|null $chart_code
 * @property string|null $chart_name
 * @property string|null $company_name
 * @property string|null $description
 * @property string|null $line_description
 * @property numeric-string $debit_amount
 * @property numeric-string $credit_amount
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property CarbonInterface|null $deleted_at
 *
 * @noinspection PhpUnused
 *
 * @property-read JournalEntry|null $journalEntry
 * @property-read Chart|null $chart
 * @property-read Company|null $company
 * @property-read Currency|null $currency
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('journal_entry_charts', key: 'id')]
#[Fillable([
    'journal_entry_id',
    'chart_id',
    'company_id',
    'currency_id',
    'type',
    'subtype',
    'document_number',
    'reference_number',
    'date',
    'chart_code',
    'chart_name',
    'company_name',
    'description',
    'line_description',
    'debit_amount',
    'credit_amount',
])]
class JournalEntryChart extends Model
{
    use HasActivity,HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'journal_entry_id' => 'string',
            'chart_id' => 'string',
            'company_id' => 'string',
            'currency_id' => 'string',
            'type' => LedgerType::class,
            'subtype' => LedgerSubType::class,
            'document_number' => 'string',
            'reference_number' => 'string',
            'date' => 'date',
            'chart_code' => 'string',
            'chart_name' => 'string',
            'company_name' => 'string',
            'description' => 'string',
            'line_description' => 'string',
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define an inverse one-to-one or many relationship with JournalEntry.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id', 'id');
    }

    /**
     * The chart account this single-sided line posts against.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'chart_id', 'id');
    }

    /**
     * The company the posting belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * The currency the amounts are stated in.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }
}
