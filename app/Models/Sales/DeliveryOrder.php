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

use App\Enums\DeliveryOrderStatus;
use App\Enums\ShippingStatus;
use App\Models\Crm\Company;
use App\Models\Crm\CompanyAddress;
use App\Models\Inventory\StockLocation;
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
 * @mixin Builder<DeliveryOrder>
 *
 * @property string $id
 * @property string $sales_order_id
 * @property string|null $company_address_id
 * @property string $stock_location_id
 * @property string|null $courier_company_id
 * @property ShippingStatus $shipping_status
 * @property DeliveryOrderStatus $status
 * @property string $document_number
 * @property string $stock_location_name
 * @property CarbonInterface $delivery_date
 * @property string $recipient_name
 * @property string|null $recipient_phone
 * @property string $delivery_address
 * @property string|null $courier
 * @property string|null $tracking_number
 * @property CarbonInterface|null $shipping_date
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
 * @property string|null $posted_by
 * @property string|null $posted_name
 * @property CarbonInterface|null $posted_at
 * @property-read SalesOrder|null $salesOrder
 * @property-read CompanyAddress|null $companyAddress
 * @property-read Company|null $courierCompany
 * @property-read StockLocation|null $stockLocation
 * @property-read Collection<int, DeliveryOrderLine> $lines
 *
 * @noinspection PhpUnused
 */
#[Table('delivery_orders', key: 'id')]
#[Fillable([
    'sales_order_id', 'company_address_id', 'stock_location_id', 'courier_company_id',
    'shipping_status', 'status',
    'document_number',
    'stock_location_name', 'delivery_date', 'recipient_name', 'recipient_phone', 'delivery_address',
    'courier', 'tracking_number', 'shipping_date',
    'attachment_path', 'notes',
    'created_by', 'created_name', 'created_at',
    'updated_by', 'updated_name', 'updated_at',
    'deleted_by', 'deleted_name', 'deleted_at',
    'approved_by', 'approved_name', 'approved_at',
    'cancelled_by', 'cancelled_name', 'cancelled_at',
    'undone_by', 'undone_name', 'undone_at',
    'posted_by', 'posted_name', 'posted_at',
])]
class DeliveryOrder extends Model
{
    use AttributesDocuments, HasUuids, RecordsAuthor, SoftDeletes;

    /**
     * Get the route key for the model.
     *
     * @return string The Delivery Order number column.
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
            'delivery_date' => 'date',
            'shipping_date' => 'date',
            'shipping_status' => ShippingStatus::class,
            'status' => DeliveryOrderStatus::class,
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'undone_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the sales order for this delivery order.
     *
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the company address for this delivery order.
     *
     * @return BelongsTo<CompanyAddress, $this>
     */
    public function companyAddress(): BelongsTo
    {
        return $this->belongsTo(CompanyAddress::class);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the courier company for this delivery order.
     *
     * @return BelongsTo<Company, $this>
     */
    public function courierCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'courier_company_id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the stock location for this delivery order.
     *
     * @return BelongsTo<StockLocation, $this>
     */
    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }

    /**
     * Define a one-to-many relationship.
     * Get the lines for this delivery order.
     *
     * @return HasMany<DeliveryOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryOrderLine::class)->orderBy('created_at');
    }
}
