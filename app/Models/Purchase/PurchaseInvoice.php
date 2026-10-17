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

namespace App\Models\Purchase;

use App\Enums\LedgerStatus;
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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A purchase invoice: a request for payment received from a supplier.
 *
 * A purchase invoice is the document a supplier sends requesting payment for
 * goods or services received. Once posted, the amounts flow into the general
 * ledger and the supplier's outstanding balance increases.
 *
 * Unlike the shared client ledger that previously held every document type in
 * one table, an invoice here is a single row with its own number. What it is
 * made of is on {@see PurchaseInvoiceLine}.
 *
 * @property string $id
 * @property string|null $company_id
 * @property string|null $contact_id
 * @property PurchaseOrderType $purchase_type
 * @property LedgerStatus $status
 * @property string $document_number
 * @property string|null $reference_number
 * @property string|null $purchase_order_id
 * @property string|null $company_name
 * @property string|null $contact_name
 * @property CarbonInterface $date
 * @property CarbonInterface|null $due_date
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
 * @property-read Collection<int, PurchaseInvoiceLine> $lines
 *
 * @mixin Builder<self>
 */
#[Table('purchase_invoices', key: 'id')]
#[Fillable([
    'company_id', 'contact_id',
    'purchase_type', 'status',
    'document_number', 'reference_number', 'purchase_order_id',
    'company_name', 'contact_name',
    'date', 'due_date', 'attachment_path', 'subtotal', 'total_amount',
    'notes',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
])]
class PurchaseInvoice extends Model
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
            'purchase_type' => PurchaseOrderType::class,
            'status' => LedgerStatus::class,
            'date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * The purchase order this invoice was raised against, if any.
     *
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * The line items this invoice is made of.
     *
     * @return HasMany<PurchaseInvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class)->orderBy('created_at');
    }

    /**
     * The company this invoice is received from.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * The contact person at the supplier.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    /**
     * Whether this invoice has reached the general ledger.
     */
    public function isPosted(): bool
    {
        return $this->status === LedgerStatus::POSTED;
    }

    /**
     * Whether this invoice has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === LedgerStatus::CANCELLED;
    }

    /**
     * Whether this invoice can still be changed.
     *
     * A posted invoice has already moved money, so editing it would leave the
     * ledgers describing something the document no longer says.
     */
    public function isEditable(): bool
    {
        return ! $this->isPosted() && ! $this->isCancelled();
    }
}
