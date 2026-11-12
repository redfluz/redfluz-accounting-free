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

namespace app\Http\Requests\Sales\SalesInvoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DiscountType;
use App\Enums\Finance\TaxMode;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;

/**
 * The fields a sales invoice is edited from.
 *
 * The rules are stated in full rather than taken from the store request and
 * adjusted, because the fields a reader would want to see must be visible in one
 * place. A rule inherited and then deleted is a rule that no longer exists
 * anywhere to be read.
 */
class UpdateSalesInvoiceRequest extends SalesInvoiceRequest
{
    /**
     * Who may edit a sales invoice.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::SALES_INVOICES, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')->where('is_debtor', true)],
            'contact_id' => ['nullable', 'uuid', 'exists:contacts,id'],
            'date' => ['required', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'invoice_notes' => ['nullable', 'string'],
            'payment_notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'size:'.Controller::detailGridRows()],
            'lines.*.product_id' => ['nullable', 'exists:products,id'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_option' => ['nullable', 'string'],
            'lines.*.discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_option' => ['nullable', 'string'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_mode' => ['nullable', Rule::enum(TaxMode::class)],
        ];
    }
}
