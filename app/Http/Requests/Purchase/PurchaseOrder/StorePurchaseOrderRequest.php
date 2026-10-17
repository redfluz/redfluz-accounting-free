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

namespace App\Http\Requests\Purchase\PurchaseOrder;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\PurchaseOrderType;
use App\Enums\UploadType;
use Illuminate\Validation\Rule;

/**
 * The fields a purchase order is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked, and can ask for a named field
 * instead of reaching into an array it built itself. A rule stated once cannot be
 * stated twice in two places that then disagree.
 *
 * Attachments are offered on creation: the files belong to the order from the
 * moment it is raised. The edit request also accepts them, because new files may
 * be attached to an existing order.
 */
class StorePurchaseOrderRequest extends PurchaseOrderRequest
{
    /**
     * Who may raise a purchase order.
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
        return $this->allows(FileResources::PURCHASE_ORDERS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('is_creditor', true)],
            'contact_id' => ['nullable', 'uuid', 'exists:contacts,id'],
            'supplier_reference_number' => ['nullable', 'string', 'max:255'],
            'supplier_sales_order_number' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'purchase_type' => ['required', Rule::enum(PurchaseOrderType::class)],
            'expected_date' => ['nullable', 'date', 'after_or_equal:date'],
            'credit_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:10'],
            // extensions: validates the uploaded file name. mimes: ignores the
            // name and guesses the type from the content, so it accepted an .exe:
            // the bytes "MZ" read as text/plain, which mapped to the then-allowed
            // txt. csv and txt are now refused app-wide for that reason, and
            // extensions: is used so the gate cannot be defeated by content.
            'attachments.*' => ['file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:10240'],
            'lines' => ['required', 'array', 'size:10'],
            'lines.*' => ['array'],
            'lines.*.product_id' => ['prohibited'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
