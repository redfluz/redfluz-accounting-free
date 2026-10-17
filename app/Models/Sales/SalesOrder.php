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

use App\Enums\SalesOrderStatus;
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

/**
 * @mixin Builder<SalesOrder>
 *
 * @property string $id
 * @property string|null $company_id
 * @property string|null $contact_id
 * @property SalesOrderStatus $status
 * @property string|null $quotation_id
 * @property string $document_number
 * @property string|null $customer_reference_number
 * @property string $company_name
 * @property string|null $contact_name
 * @property CarbonInterface $order_date
 * @property CarbonInterface|null $expected_delivery_date
 * @property numeric-string $subtotal
 * @property numeric-string $total_amount
 * @property string|null $attachment_path
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
 * @property-read Company|null $company
 * @property-read Contact|null $contact
 * @property-read Quotation|null $quotation
 * @property-read SalesInvoice|null $invoice
 * @property-read Collection<int, SalesOrderLine> $lines
 * @property-read Collection<int, DeliveryOrder> $deliveryOrders
 *
 * @noinspection PhpUnused
 */
#[Table('sales_orders', key: 'id')]
#[Fillable([
    'company_id', 'contact_id',
    'status',
    'quotation_id',
    'document_number',
    'customer_reference_number', 'company_name', 'contact_name',
    'order_date', 'expected_delivery_date',
    'subtotal', 'total_amount', 'attachment_path', 'notes',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
])]
class SalesOrder extends Model
{
    use AttributesDocuments, HasUuids, RecordsAuthor, SoftDeletes;

    /**
     * Get the route key for the model.
     *
     * @return string The Sales Order number column.
     */
    public function getRouteKeyName(): string
    {
        return 'document_number';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'status' => SalesOrderStatus::class,
            'subtotal' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the company for this sales order.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the contact for this sales order.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * The quotation this order was converted from, if any.
     *
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * The invoice raised against this order, if any.
     *
     * @return HasOne<SalesInvoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(SalesInvoice::class, 'sales_order_id');
    }

    /**
     * Define a one-to-many relationship.
     * Get all lines associated with this sales order.
     *
     * @return HasMany<SalesOrderLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class)->orderBy('created_at');
    }

    /**
     * Define a one-to-many relationship.
     * Get all delivery orders associated with this sales order.
     *
     * @return HasMany<DeliveryOrder, $this>
     */
    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class)->orderBy('created_at');
    }
}
