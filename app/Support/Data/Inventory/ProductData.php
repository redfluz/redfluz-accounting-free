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

declare(strict_types=1);

namespace App\Support\Data\Inventory;

use App\Enums\AcquisitionType;
use App\Models\Inventory\Product;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A product the business holds or sells.
 *
 * A product may be a variant of another, and the variant takes its selling price
 * from the parent unless it states one. That is why the price is on the row and
 * not resolved through the parent on read: a variant priced differently from its
 * parent has to say so, and a price that was inherited silently stops being
 * inherited the moment someone changes the parent.
 *
 * A row can be a product, a service, or both flags false. The flags are
 * independent rather than one kind column, because the two questions are asked
 * separately: a service can be stocked for later use and a product can be
 * provided as a service.
 *
 * All properties are nullable with defaults so the same object is built two ways:
 *
 *   ProductData::from($request->validated())   a form, to create or edit
 *   ProductData::fromModel($product)            a saved one, to read it
 *
 * Images and variants are not carried here: they are validated by the form
 * request but processed separately by the controller, because each image becomes
 * its own attachment row and each variant becomes its own product row.
 */
class ProductData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('sku')]
        public ?string $sku = null,

        #[MapName('barcode')]
        public ?string $barcode = null,

        #[MapName('mpn')]
        public ?string $mpn = null,

        #[MapName('brand_id')]
        public ?string $brandId = null,

        #[MapName('supplier_company_id')]
        public ?string $supplierCompanyId = null,

        #[MapName('product_category_id')]
        public ?string $productCategoryId = null,

        #[MapName('product_type_id')]
        public ?string $productTypeId = null,

        #[MapName('base_price')]
        public ?string $basePrice = null,

        #[MapName('selling_price')]
        public ?string $sellingPrice = null,

        #[MapName('warranty_months')]
        public ?int $warrantyMonths = null,

        #[MapName('default_tax_id')]
        public ?string $defaultTaxId = null,

        #[MapName('default_discount_id')]
        public ?string $defaultDiscountId = null,

        #[MapName('description')]
        public ?string $description = null,

        #[MapName('is_service')]
        public ?bool $isService = null,

        #[MapName('is_product')]
        public ?bool $isProduct = null,

        #[MapName('default_acquisition_type')]
        public ?AcquisitionType $defaultAcquisitionType = null,

        #[MapName('reorder_level')]
        public ?string $reorderLevel = null,

        #[MapName('reorder_quantity')]
        public ?string $reorderQuantity = null,

        // ---- model-only columns ----

        #[MapName('parent_product_id')]
        public ?string $parentProductId = null,

        #[MapName('variant_name')]
        public ?string $variantName = null,

        #[MapName('variant_details')]
        public ?string $variantDetails = null,

        #[MapName('inherits_selling_price')]
        public ?bool $inheritsSellingPrice = null,

        #[MapName('image_path')]
        public ?string $imagePath = null,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt = null,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt = null,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt = null,
    ) {}

    /**
     * The same moment, as the type this object holds.
     *
     * A model hands back whatever Carbon its cast produced; this object holds one
     * concrete type so that a form and a model produce the same thing. The value
     * is the same either way, and only its class differs.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the model
     * holds, and the two are written in different formats. Taking the model's
     * attributes instead means the cast is what is mapped.
     *
     * @param  Product  $model  The product model instance.
     * @return self The populated DTO.
     */
    public static function fromModel(Product $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            sku: $model->sku,
            barcode: $model->barcode,
            mpn: $model->mpn,
            brandId: $model->brand_id,
            supplierCompanyId: $model->supplier_company_id,
            productCategoryId: $model->product_category_id,
            productTypeId: $model->product_type_id,
            basePrice: $model->base_price,
            sellingPrice: $model->selling_price,
            warrantyMonths: $model->warranty_months,
            defaultTaxId: $model->default_tax_id,
            defaultDiscountId: $model->default_discount_id,
            description: $model->description,
            isService: $model->is_service,
            isProduct: $model->is_product,
            defaultAcquisitionType: $model->default_acquisition_type,
            reorderLevel: $model->reorder_level,
            reorderQuantity: $model->reorder_quantity,
            parentProductId: $model->parent_product_id,
            variantName: $model->variant_name,
            variantDetails: $model->variant_details,
            inheritsSellingPrice: $model->inherits_selling_price,
            imagePath: $model->image_path,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a product is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made. Variant-only columns are absent
     * because a parent product has no variant name, details or inheritance flag.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'mpn' => $this->mpn,
            'brand_id' => $this->brandId,
            'supplier_company_id' => $this->supplierCompanyId,
            'product_category_id' => $this->productCategoryId,
            'product_type_id' => $this->productTypeId,
            'base_price' => $this->basePrice,
            'selling_price' => $this->sellingPrice,
            'warranty_months' => $this->warrantyMonths,
            'default_tax_id' => $this->defaultTaxId,
            'default_discount_id' => $this->defaultDiscountId,
            'description' => $this->description,
            'is_service' => $this->isService === null ? null : (int) $this->isService,
            'is_product' => $this->isProduct === null ? null : (int) $this->isProduct,
            'default_acquisition_type' => $this->defaultAcquisitionType?->value,
            'reorder_level' => $this->reorderLevel,
            'reorder_quantity' => $this->reorderQuantity,
        ];
    }

    /**
     * The row as the products table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'parent_product_id' => $this->parentProductId,
            'brand_id' => $this->brandId,
            'supplier_company_id' => $this->supplierCompanyId,
            'product_category_id' => $this->productCategoryId,
            'product_type_id' => $this->productTypeId,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'mpn' => $this->mpn,
            'variant_name' => $this->variantName,
            'variant_details' => $this->variantDetails,
            'inherits_selling_price' => $this->inheritsSellingPrice === null ? null : (int) $this->inheritsSellingPrice,
            'image_path' => $this->imagePath,
            'base_price' => $this->basePrice,
            'selling_price' => $this->sellingPrice,
            'warranty_months' => $this->warrantyMonths,
            'description' => $this->description,
            'is_service' => $this->isService === null ? null : (int) $this->isService,
            'is_product' => $this->isProduct === null ? null : (int) $this->isProduct,
            'default_acquisition_type' => $this->defaultAcquisitionType?->value,
            'default_tax_id' => $this->defaultTaxId,
            'default_discount_id' => $this->defaultDiscountId,
            'reorder_level' => $this->reorderLevel,
            'reorder_quantity' => $this->reorderQuantity,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
