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

use App\Enums\AcquisitionType;
use App\Models\Crm\Company;
use App\Models\Finance\Discount;
use App\Models\Finance\Tax;
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
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<Product>
 *
 * @property string $id
 * @property string|null $parent_product_id
 * @property string|null $brand_id
 * @property string|null $supplier_company_id
 * @property string|null $product_category_id
 * @property string|null $product_type_id
 * @property string $name
 * @property string $sku
 * @property string|null $barcode
 * @property string|null $mpn
 * @property string|null $variant_name
 * @property string|null $variant_details
 * @property bool $inherits_selling_price
 * @property string|null $image_path
 * @property numeric-string $base_price
 * @property numeric-string $selling_price
 * @property int|null $warranty_months
 * @property string|null $default_tax_id
 * @property string|null $default_discount_id
 * @property string|null $description
 * @property bool $is_service
 * @property bool $is_product
 * @property AcquisitionType $default_acquisition_type
 * @property numeric-string $reorder_level
 * @property numeric-string $reorder_quantity
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read string|null $image_url
 * @property-read Collection<int, ProductLedger> $productLedgers
 * @property-read Product|null $parent
 * @property-read Brand|null $brand
 * @property-read ProductCategory|null $category
 * @property-read ProductType|null $type
 * @property-read Company|null $supplier
 * @property-read Collection<int, Product> $variants
 * @property-read Collection<int, ProductAttachment> $attachments
 * @property-read Tax|null $defaultTax
 * @property-read Discount|null $defaultDiscount
 *
 * @noinspection PhpUnused
 *
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('products', key: 'id')]
#[Fillable([
    'parent_product_id',
    'brand_id',
    'supplier_company_id',
    'product_category_id',
    'product_type_id',
    'name',
    'sku',
    'barcode',
    'mpn',
    'variant_name',
    'variant_details',
    'inherits_selling_price',
    'image_path',
    'base_price',
    'selling_price',
    'warranty_months',
    'default_tax_id',
    'default_discount_id',
    'description',
    'is_service',
    'is_product',
    'default_acquisition_type',
    'reorder_level',
    'reorder_quantity',
])]
class Product extends Model
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
            'parent_product_id' => 'string',
            'brand_id' => 'string',
            'supplier_company_id' => 'string',
            'product_category_id' => 'string',
            'product_type_id' => 'string',
            'name' => 'string',
            'sku' => 'string',
            'barcode' => 'string',
            'mpn' => 'string',
            'variant_name' => 'string',
            'variant_details' => 'string',
            'inherits_selling_price' => 'boolean',
            'image_path' => 'string',
            'base_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'warranty_months' => 'integer',
            'default_tax_id' => 'string',
            'default_discount_id' => 'string',
            'description' => 'string',
            'is_service' => 'boolean',
            'is_product' => 'boolean',
            'default_acquisition_type' => AcquisitionType::class,
            'reorder_level' => 'decimal:4',
            'reorder_quantity' => 'decimal:4',
        ];
    }

    /**
     * Define a one-to-many relationship.
     * Get all movement history rows for this product.
     *
     * @return HasMany<ProductLedger, $this>
     */
    public function productLedgers(): HasMany
    {
        return $this->hasMany(ProductLedger::class, 'product_id', 'id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the main product for this variant.
     *
     * @return BelongsTo<Product, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_product_id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the brand assigned to this product.
     *
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the default supplier for this product.
     *
     * @return BelongsTo<Company, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the first-level product category.
     *
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the second-level product type.
     *
     * @return BelongsTo<ProductType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    /**
     * Define a one-to-many relationship.
     * Get all variants registered below this main product.
     *
     * @return HasMany<Product, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_product_id')->orderBy('variant_name');
    }

    /**
     * Define a one-to-many relationship.
     * Get all images attached to this main product.
     *
     * @return HasMany<ProductAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ProductAttachment::class, 'product_id')->oldest();
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the default tax copied to new transaction lines.
     *
     * @return BelongsTo<Tax, $this>
     */
    public function defaultTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'default_tax_id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the default discount copied to new transaction lines.
     *
     * @return BelongsTo<Discount, $this>
     */
    public function defaultDiscount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'default_discount_id');
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
