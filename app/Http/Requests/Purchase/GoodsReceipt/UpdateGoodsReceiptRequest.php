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
 * The fields a goods receipt is edited from.
 *
 * The rules are stated in full rather than taken from the create request and
 * adjusted, because the field that differs is exactly the field a reader would
 * want to see. An edit accepts delete_attachments, which a creation does not,
 * and a rule that was inherited and then deleted is a rule that no longer exists
 * anywhere to be read.
 */
class UpdateGoodsReceiptRequest extends GoodsReceiptRequest
{
    /**
     * Who may edit a goods receipt.
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
        return $this->allows(FileResources::GOODS_RECEIPTS, FileAction::UPDATE);
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
            'delete_attachments' => ['nullable', 'array'],
            'delete_attachments.*' => ['uuid', 'exists:goods_receipt_attachments,id'],
        ];
    }
}
