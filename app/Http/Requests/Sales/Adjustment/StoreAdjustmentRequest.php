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

declare(strict_types=1);

namespace App\Http\Requests\Sales\Adjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerSubType;
use App\Enums\UploadType;
use Illuminate\Validation\Rule;

/**
 * The fields a sales adjustment is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked, and can ask for a named field
 * instead of reaching into an array it built itself. A rule stated once cannot be
 * stated twice in two places that then disagree.
 */
class StoreAdjustmentRequest extends AdjustmentRequest
{
    /**
     * Who may raise a sales adjustment.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::SALES_ADJUSTMENTS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('is_debtor', true)],
            'debit_id' => ['required', 'uuid', 'different:credit_id', 'exists:charts,id'],
            'credit_id' => ['required', 'uuid', 'different:debit_id', 'exists:charts,id'],
            'subtype' => [
                'required',
                'string',
                Rule::in([
                    LedgerSubType::INCREASE_ADJUSTMENT->value,
                    LedgerSubType::DECREASE_ADJUSTMENT->value,
                ]),
            ],
            'date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0.01'],
            'attachment' => ['nullable', 'file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:5120'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
