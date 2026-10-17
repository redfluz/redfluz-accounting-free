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

namespace App\Http\Requests\Inventory\ProductType;

use App\Enums\FileAction;
use App\Enums\FileResources;
use Illuminate\Validation\Rule;

/**
 * The fields a product type is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked. A rule stated once cannot be
 * stated twice in two places that then disagree.
 *
 * The name is unique within its category, so the category is named in the rule
 * and a type in one category may share its name with a type in another.
 */
class StoreProductTypeRequest extends ProductTypeRequest
{
    /**
     * Who may create a product type.
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
        return $this->allows(FileResources::PRODUCT_TYPES, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categoryId = $this->string('product_category_id')->toString();

        return [
            'id' => ['nullable'],
            'product_category_id' => ['required', 'uuid', Rule::exists('product_categories', 'id')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:100', 'unique:product_types,code'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_types', 'name')->where('product_category_id', $categoryId),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
