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

namespace App\Models\Sales;

use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerSubType;
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
 * A manual sales adjustment: a credit or debit note keyed against the chart of
 * accounts the old way, correcting an invoice that has gone out.
 *
 * The user picks the accounts and the sides themselves; nothing about the lines
 * is tied to a product, which is what makes it a different document from
 * a COA-based note on a customer's account (no products).
 *
 * Both are raised against an invoice that has gone out. Here the link is the
 * `sales_invoice_id` foreign key to that invoice; `reference_number` keeps the
 * invoice's document number as the snapshot the grids print.
 *
 * The subtype is the direction: an increase raises the customer's balance, a
 * decrease reduces it. The posting itself follows whichever way the user keyed
 * the accounts.
 *
 * What it is made of is on {@see ManualSalesAdjustmentChart}.
 *
 * @property string $id
 * @property string|null $company_id
 * @property string|null $contact_id
 * @property string $document_number
 * @property LedgerSubType $subtype
 * @property string|null $reference_number
 * @property string|null $document_original
 * @property string|null $sales_invoice_id
 * @property string|null $company_name
 * @property string|null $contact_name
 * @property CarbonInterface $date
 * @property DocumentStatus $status
 * @property string|null $attachment_path
 * @property numeric-string $subtotal
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
 * @property-read Collection<int, ManualSalesAdjustmentChart> $charts
 * @property-read SalesInvoice|null $salesInvoice
 *
 * @mixin Builder<self>
 */
#[Table('manual_sales_adjustments', key: 'id')]
#[Fillable([
    'company_id',
    'contact_id',
    'document_number',
    'subtype',
    'reference_number',
    'document_original',
    'sales_invoice_id',
    'company_name',
    'contact_name',
    'date',
    'status',
    'attachment_path',
    'subtotal',
    'total_amount',
    'notes',
    'created_by',
    'created_name',
    'created_at',
    'updated_by',
    'updated_name',
    'updated_at',
    'deleted_by',
    'deleted_name',
    'deleted_at',
    'approved_by',
    'approved_name',
    'approved_at',
    'cancelled_by',
    'cancelled_name',
    'cancelled_at',
    'undone_by',
    'undone_name',
    'undone_at',
])]
class ManualSalesAdjustment extends Model
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
            'status' => DocumentStatus::class,
            'date' => 'date',
            'subtotal' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * The invoice this note corrects, if it was linked to one.
     *
     * @return BelongsTo<SalesInvoice, $this>
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'id');
    }

    /**
     * Define a one-to-many relationship.
     * The chart of accounts this note is keyed against.
     *
     * @return HasMany<ManualSalesAdjustmentChart, $this>
     */
    public function charts(): HasMany
    {
        return $this->hasMany(ManualSalesAdjustmentChart::class)->orderBy('created_at');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * The company the note is for.
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
        return $this->status === DocumentStatus::POSTED;
    }

    /**
     * Whether this adjustment has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === DocumentStatus::CANCELLED;
    }

    /**
     * Whether this adjustment can still be changed.
     *
     * A posted note has already moved money, so editing it would leave the
     * ledgers describing something the document no longer says.
     */
    public function isEditable(): bool
    {
        return ! $this->isPosted() && ! $this->isCancelled();
    }
}
