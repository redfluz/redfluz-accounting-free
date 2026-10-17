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

namespace App\Http\Requests\Inventory\Product;

use App\Enums\AcquisitionType;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\UploadType;
use App\Models\Inventory\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * The fields a product is edited from.
 *
 * The rules are stated in full rather than taken from the create request, because
 * an edit names the product being changed and carries removed attachment
 * identifiers, and a rule inherited and then changed is a rule nobody can read in
 * one place.
 */
class UpdateProductRequest extends ProductRequest
{
    /**
     * Who may edit a product.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * It refuses by redirecting with an explanation and writing the attempt to
     * the log, which is what this check has always done and what returning a
     * plain false would lose.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::PRODUCTS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
        $productId = $product->id;

        return [
            'id' => ['required', 'uuid', Rule::in([$productId])],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'barcode' => ['nullable', 'string', 'max:100'],
            'mpn' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
            'supplier_company_id' => [
                'nullable', 'uuid',
                Rule::exists('companies', 'id')->where(fn (Builder $query) => $query->where('is_creditor', true)),
            ],
            'product_category_id' => ['required', 'uuid', 'exists:product_categories,id'],
            'product_type_id' => [
                'required', 'uuid',
                Rule::exists('product_types', 'id')
                    ->where('product_category_id', $this->inputStringOrEmpty('product_category_id')),
            ],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', UploadType::IMAGES->rule(), 'max:2048'],
            'removed_attachment_ids' => ['nullable', 'array', 'max:10'],
            'removed_attachment_ids.*' => ['required', 'uuid', 'distinct'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'warranty_months' => ['required', 'integer', 'min:-1', 'max:120'],
            'default_tax_id' => ['nullable', 'uuid', 'exists:taxes,id'],
            'default_discount_id' => ['nullable', 'uuid', 'exists:discounts,id'],
            'description' => ['nullable', 'string'],
            'is_service' => ['nullable', 'boolean'],
            'default_acquisition_type' => ['required', Rule::enum(AcquisitionType::class)],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'variants' => ['nullable', 'array', 'max:10'],
            'variants.*.id' => ['nullable', 'uuid', 'distinct'],
            'variants.*.variant_name' => ['nullable', 'string', 'max:255', 'required_with:variants.*.sku'],
            'variants.*.sku' => [
                'nullable', 'string', 'max:100', 'distinct:ignore_case',
                'required_with:variants.*.variant_name',
            ],
            'variants.*.variant_details' => ['nullable', 'string'],
            'variants.*.base_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.inherits_selling_price' => ['nullable', 'boolean'],
            'variants.*.selling_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
