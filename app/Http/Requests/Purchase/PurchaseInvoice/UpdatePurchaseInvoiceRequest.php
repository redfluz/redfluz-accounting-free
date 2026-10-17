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

namespace App\Http\Requests\Purchase\PurchaseInvoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\PurchaseOrderType;
use App\Models\ChartOfAccount\Chart;
use Closure;
use Illuminate\Validation\Rule;

/**
 * The fields a purchase invoice is edited from.
 *
 * The rules are stated in full rather than taken from the create request and
 * adjusted, because the one field that differs is exactly the field a reader
 * would want to see. The attachment is absent here and not merely removed: an edit
 * does not take a new file, and a rule that was inherited and then deleted is a
 * rule that no longer exists anywhere to be read.
 */
class UpdatePurchaseInvoiceRequest extends PurchaseInvoiceRequest
{
    /**
     * Who may edit a purchase invoice.
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
        return $this->allows(FileResources::PURCHASE_INVOICES, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')->where('is_creditor', true)],
            'contact_id' => ['nullable', 'uuid', 'exists:contacts,id'],
            'debit_id' => [
                'nullable',
                'exists:charts,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value) {
                        return;
                    }

                    $chart = Chart::query()->with('chartType')->whereKey($value)->first();
                    if ($chart === null) {
                        return;
                    }

                    if (! $chart->chartType?->isExpenses && (! $chart->chartType?->isAsset || $chart->isBank)) {
                        $fail(__('ledgers.validation.debit_purchase_must_be_expense_or_asset'));
                    }
                },
            ],
            'credit_id' => [
                'nullable',
                'exists:charts,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value) {
                        return;
                    }

                    $chart = Chart::query()->with('chartType')->whereKey($value)->first();
                    if ($chart === null) {
                        return;
                    }

                    if (! $chart->chartType?->isLiability || ! $chart->isCreditor) {
                        $fail(__('ledgers.validation.credit_purchase_must_be_liability'));
                    }
                },
            ],
            'date' => ['required', 'date'],
            'purchase_type' => ['required', Rule::enum(PurchaseOrderType::class)],
            'credit_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_debit_id' => ['nullable', 'exists:charts,id'],
            'payment_credit_id' => ['nullable', 'exists:charts,id'],
            'invoice_notes' => ['nullable', 'string'],
            'payment_notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'size:10'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
