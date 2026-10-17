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

namespace App\Http\Requests\Sales\Quotation;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\QuotationStatus;
use App\Enums\UploadType;
use Illuminate\Validation\Rule;

/**
 * The fields a quotation is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked, and can ask for a named field
 * instead of reaching into an array it built itself. A rule stated once cannot be
 * stated twice in two places that then disagree.
 *
 * The attachment is offered on creation and not on edit, because the file is
 * attached to the quotation when it is raised; replacing it later is a separate
 * act with its own history.
 */
class StoreQuotationRequest extends QuotationRequest
{
    /**
     * Who may raise a quotation.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::QUOTATIONS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('is_debtor', true)],
            'contact_id' => [
                'nullable',
                'uuid',
                Rule::exists('contacts', 'id')
                    ->where('company_id', $this->inputStringOrEmpty('company_id'))
                    ->whereNull('deleted_at'),
            ],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:date'],
            'status' => ['required', Rule::enum(QuotationStatus::class)],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:5120'],
            'lines' => ['required', 'array', 'max:20'],
            'lines.*.product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'lines.*.description' => ['nullable', 'string', 'max:2000'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
