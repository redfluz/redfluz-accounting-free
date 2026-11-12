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

namespace app\Http\Requests\Purchase\ManualAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerSubType;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;

/**
 * The fields a manual purchase adjustment is created from.
 *
 * The rules are stated in full rather than taken from the update request, because
 * a reader must not have to consult the other request to know what this one
 * demands. The note corrects a bill that has come in, so the purchase
 * invoice is required and must be a posted invoice of the selected supplier.
 * The lines are ten fixed rows the form always posts, empty ones included.
 */
class StoreManualPurchaseAdjustmentRequest extends ManualPurchaseAdjustmentRequest
{
    /**
     * Who may raise a manual purchase adjustment.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')->where('is_creditor', true)],
            'contact_id' => ['nullable', 'uuid', 'exists:contacts,id'],
            'subtype' => [
                'required',
                'string',
                Rule::in([
                    LedgerSubType::PURCHASE_DEBIT_NOTE->value,
                    LedgerSubType::PURCHASE_CREDIT_NOTE->value,
                    LedgerSubType::INCREASE_ADJUSTMENT->value,
                    LedgerSubType::DECREASE_ADJUSTMENT->value,
                ]),
            ],
            'date' => ['required', 'date'],
            'purchase_invoice_id' => [
                'required',
                'uuid',
                Rule::exists('purchase_invoices', 'id')
                    ->where('status', DocumentStatus::POSTED->value)
                    ->where('company_id', $this->inputStringOrEmpty('company_id')),
            ],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,png,jpeg', 'max:5120'],
            'lines' => ['required', 'array', 'size:'.Controller::detailGridRows()],
            'lines.*.chart_id' => ['nullable', 'uuid', 'exists:charts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.debit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
