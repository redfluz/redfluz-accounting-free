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

namespace App\Models\Inventory;

use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
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
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<ProductLedger>
 *
 * @property string $id
 * @property string $product_id
 * @property string $stock_location_id
 * @property string $stock_location_name
 * @property string|null $company_id
 * @property string|null $source_entry_id
 * @property string|null $cogs_ledger_id
 * @property string|null $inventory_ledger_id
 * @property string|null $variance_ledger_id
 * @property string $product_sku
 * @property string $product_name
 * @property string|null $company_name
 * @property string $document_number
 * @property string|null $reference_number
 * @property ProductMovementSource $source_type
 * @property ProductMovementDirection $direction
 * @property ProductStockBucket $stock_bucket
 * @property CarbonInterface $date
 * @property numeric-string $quantity
 * @property numeric-string $unit_cost
 * @property numeric-string $total_cost
 * @property ProductMovementStatus $status
 * @property CarbonInterface|null $activated_at
 * @property string|null $notes
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read numeric-string $total_in
 * @property-read numeric-string $total_out
 *
 * @noinspection PhpUnused
 *
 * @property-read Product|null $product
 * @property-read Company|null $company
 * @property-read StockLocation|null $stockLocation
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('product_ledgers', key: 'id')]
#[Fillable([
    'product_id',
    'stock_location_id',
    'stock_location_name',
    'company_id',
    'source_entry_id',
    'cogs_ledger_id',
    'inventory_ledger_id',
    'variance_ledger_id',
    'product_sku',
    'product_name',
    'company_name',
    'document_number',
    'reference_number',
    'source_type',
    'direction',
    'stock_bucket',
    'date',
    'quantity',
    'unit_cost',
    'total_cost',
    'status',
    'activated_at',
    'notes',
])]
class ProductLedger extends Model
{
    use HasActivity, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'string',
            'stock_location_id' => 'string',
            'company_id' => 'string',
            'source_entry_id' => 'string',
            'cogs_ledger_id' => 'string',
            'inventory_ledger_id' => 'string',
            'variance_ledger_id' => 'string',
            'source_type' => ProductMovementSource::class,
            'direction' => ProductMovementDirection::class,
            'stock_bucket' => ProductStockBucket::class,
            'date' => 'date',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'status' => ProductMovementStatus::class,
            'activated_at' => 'datetime',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the product associated with this ledger entry.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the company associated with this ledger entry.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the stock location associated with this ledger entry.
     *
     * @return BelongsTo<StockLocation, $this>
     */
    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
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
