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

namespace App\Http\Requests\Purchase\GoodsReceipt;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\UploadType;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * The fields a goods receipt is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked, and can ask for a named field
 * instead of reaching into an array it built itself. A rule stated once cannot be
 * stated twice in two places that then disagree.
 *
 * Attachments are offered on creation: the files belong to the receipt from the
 * moment it is raised. The edit request also accepts them, because new files may
 * be attached to an existing receipt.
 */
class StoreGoodsReceiptRequest extends GoodsReceiptRequest
{
    /**
     * Who may raise a goods receipt.
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
        return $this->allows(FileResources::GOODS_RECEIPTS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'purchase_order_number' => [
                'required',
                'string',
                Rule::exists('purchase_orders', 'document_number')->where(
                    fn (Builder $query) => $query->whereNotNull('approved_at')
                        ->whereNull('deleted_at')
                ),
            ],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array'],
            'lines.*' => ['array'],
            'lines.*.product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array', 'max:10'],
            // extensions:, not mimes: — mimes: guesses the type from the content
            // rather than the file name, so a shaped executable was accepted.
            // csv and txt are refused app-wide; see PurchaseOrderController.
            'attachments.*' => ['file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:5120'],
        ];
    }
}
