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

use App\Enums\QuotationStatus;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
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
 * A quotation: an offer, before anything has been promised.
 *
 * A quotation is a document with a number, a customer, a date it expires and a
 * state it has reached. What it is offering is its lines, which are what
 * {@see QuotationLine} holds.
 *
 * The two were one flat table once, with the header repeated on every line. That
 * made a quotation with more than one line impossible to reason about: expiry,
 * status and the converted-to numbers were stored once per line and could
 * disagree, and there was no way to ask which lines belonged to a quotation
 * without grouping on what is meant to be a document reference. Splitting them is
 * the same shape every other document in the system already has.
 *
 * @property string $id
 * @property string|null $company_id
 * @property string|null $contact_id
 * @property QuotationStatus $status
 * @property string $document_number
 * @property string $company_name
 * @property string|null $contact_name
 * @property string|null $reference_number
 * @property CarbonInterface $date
 * @property CarbonInterface|null $expiry_date
 * @property string|null $attachment_path
 * @property bool $is_converted
 * @property CarbonInterface|null $converted_at
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
 * @property-read Collection<int, QuotationLine> $lines
 * @property-read Collection<int, Activity> $activities
 *
 * @mixin Builder<self>
 */
#[Table('quotations', key: 'id')]
#[Fillable([
    'company_id', 'contact_id',
    'status',
    'document_number', 'company_name', 'contact_name',
    'reference_number', 'date', 'expiry_date',
    'attachment_path', 'is_converted', 'converted_at',
    'notes',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
])]
class Quotation extends Model
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
            'status' => QuotationStatus::class,
            'date' => 'date',
            'expiry_date' => 'date',
            'is_converted' => 'boolean',
            'converted_at' => 'datetime',
        ];
    }

    /**
     * The lines this quotation offers.
     *
     * @return HasMany<QuotationLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class)->orderBy('created_at');
    }

    /**
     * The company the offer is made to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * The sales order this quotation became, if it became one.
     *
     * @return HasOne<SalesOrder, $this>
     */
    public function convertedSalesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class, 'quotation_id');
    }

    /**
     * The sales invoice this quotation became, if it converted directly.
     *
     * @return HasOne<SalesInvoice, $this>
     */
    public function convertedInvoice(): HasOne
    {
        return $this->hasOne(SalesInvoice::class, 'quotation_id');
    }

    /**
     * The person at that company.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    /**
     * Get the options for logging activity using Spatie Activitylog.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }
}
