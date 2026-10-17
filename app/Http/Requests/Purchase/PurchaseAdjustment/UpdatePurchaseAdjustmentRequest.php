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

namespace App\Http\Requests\Purchase\PurchaseAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerSubType;
use Illuminate\Validation\Rule;

/**
 * The fields a purchase adjustment is edited from.
 *
 * The rules are stated in full rather than taken from the create request and
 * adjusted, because the one field that differs is exactly the field a reader
 * would want to see. The attachment is absent here and not merely removed: an edit
 * does not take a new file, and a rule that was inherited and then deleted is a
 * rule that no longer exists anywhere to be read.
 */
class UpdatePurchaseAdjustmentRequest extends PurchaseAdjustmentRequest
{
    /**
     * Who may edit a purchase adjustment.
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
        return $this->allows(FileResources::PURCHASE_ADJUSTMENTS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('is_creditor', true)],
            'debit_id' => ['required', 'uuid', 'different:credit_id', 'exists:charts,id'],
            'credit_id' => ['required', 'uuid', 'different:debit_id', 'exists:charts,id'],
            'subtype' => [
                'required',
                'string',
                Rule::in([
                    LedgerSubType::PURCHASE_DEBIT_NOTE->value,
                    LedgerSubType::PURCHASE_CREDIT_NOTE->value,
                ]),
            ],
            'date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
