<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://github.com/redfluz
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

namespace App\Http\Requests\Purchase\GoodsReceiptAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use Illuminate\Validation\Rule;

class StoreGoodsReceiptAdjustmentRequest extends GoodsReceiptAdjustmentRequest
{
    /**
     * Who may book quantity corrections on a posted goods receipt.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * @return bool Whether the authenticated user may perform this action.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::GOODS_RECEIPTS, FileAction::UPDATE);
    }

    /**
     * The validation rules for booking quantity corrections.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array'],
            'lines.*' => ['array'],
            'lines.*.entry_id' => ['required', 'uuid', Rule::exists('product_ledgers', 'id')],
            'lines.*.quantity' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
        ];
    }
}
