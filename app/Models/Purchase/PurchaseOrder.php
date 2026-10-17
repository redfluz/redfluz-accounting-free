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

use App\Enums\PurchaseOrderType;
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
 * @mixin Builder<PurchaseOrder>
 *
 * @property string $id
 * @property string|null $company_id
 * @property string|null $contact_id
 * @property PurchaseOrderType $purchase_type
 * @property bool $is_partial_received
 * @property bool $is_received
 * @property string $document_number
 * @property string|null $supplier_reference_number
 * @property string|null $supplier_sales_order_number
 * @property string|null $company_name
 * @property string|null $contact_name
 * @property CarbonInterface $date
 * @property CarbonInterface|null $expected_date
 * @property int $credit_term_days
 * @property CarbonInterface|null $due_date
 * @property numeric-string $subtotal
 * @property numeric-string $tax_amount
 * @property numeric-string $total_amount
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
 * @property-read Collection<int, PurchaseOrderLine> $lines
 * @property-read PurchaseInvoice|null $invoice
 * @property-read Collection<int, PurchaseOrderAttachment> $attachments
 * @property-read Company|null $company
 * @property-read Contact|null $contact
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 *
 * @noinspection PhpUnused
 */
#[Table('purchase_orders', key: 'id')]
#[Fillable([
    'company_id', 'contact_id',
    'purchase_type', 'is_partial_received', 'is_received',
    'document_number',
    'supplier_reference_number', 'supplier_sales_order_number',
    'company_name', 'contact_name',
    'date', 'expected_date', 'credit_term_days', 'due_date',
    'subtotal', 'tax_amount', 'total_amount', 'notes',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
])]
class PurchaseOrder extends Model
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
            'expected_date' => 'date',
            'credit_term_days' => 'integer',
            'due_date' => 'date',
            'is_partial_received' => 'boolean',
            'is_received' => 'boolean',
            'purchase_type' => PurchaseOrderType::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Define a one-to-many relationship.
     *
     * @return HasMany<PurchaseOrderLine, $this>
     */
    /**
     * The invoice raised against this order, if any.
     *
     * @return HasOne<PurchaseInvoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(PurchaseInvoice::class, 'purchase_order_id');
    }

    /**
     * The line items this order is made of.
     *
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('created_at');
    }

    /**
     * Define a one-to-many relationship.
     *
     * @return HasMany<PurchaseOrderAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseOrderAttachment::class)->orderBy('created_at');
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
}
