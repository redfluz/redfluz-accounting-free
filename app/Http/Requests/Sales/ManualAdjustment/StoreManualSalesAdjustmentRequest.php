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

namespace App\Http\Requests\Sales\ManualAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\LedgerStatus;
use App\Enums\Finance\LedgerSubType;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;

/**
 * The fields a manual sales adjustment is created from.
 *
 * The rules are stated in full rather than taken from the update request, because
 * a reader must not have to consult the other request to know what this one
 * demands. The note corrects an invoice that has gone out, so the sales
 * invoice is required and must be a posted invoice of the selected company.
 * The lines are ten fixed rows the form always posts, empty ones included.
 */
class StoreManualSalesAdjustmentRequest extends ManualSalesAdjustmentRequest
{
    /**
     * Who may raise a manual sales adjustment.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')->where('is_debtor', true)],
            'contact_id' => ['nullable', 'uuid', 'exists:contacts,id'],
            'subtype' => [
                'required',
                'string',
                Rule::in([
                    LedgerSubType::INCREASE_ADJUSTMENT->value,
                    LedgerSubType::DECREASE_ADJUSTMENT->value,
                ]),
            ],
            'date' => ['required', 'date'],
            'sales_invoice_id' => [
                'required',
                'uuid',
                Rule::exists('sales_invoices', 'id')
                    ->where('status', LedgerStatus::POSTED->value)
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
