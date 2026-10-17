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

namespace App\Http\Requests\ChartOfAccount\ChartSetting;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The fields a chart setting is edited from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked, and can ask for a named field
 * instead of reaching into an array it built itself. A rule stated once cannot be
 * stated twice in two places that then disagree.
 */
class UpdateChartSettingRequest extends ChartSettingRequest
{
    /**
     * Who may edit chart settings.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * @return bool Whether the authenticated user may perform this action.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::CHART_SETTINGS, FileAction::UPDATE);
    }

    /**
     * The validation rules for editing chart settings.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $chartTable = (new Chart)->getTable();

        return [
            'cogs_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'income_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'bank_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'cash_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'e_wallet_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'client_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'vendor_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'inventory_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'stock_shortage_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'stock_surplus_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'deposit_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'input_tax_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'output_tax_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'retained_earnings_chart_uuid' => [
                'required',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'disposal_gain_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'disposal_loss_chart_uuid' => [
                'nullable',
                'uuid',
                Rule::exists($chartTable, 'id'),
            ],

            'retained_earnings_formula' => [
                'required',
                'string',
                'max:2000',
            ],

            'closing_date' => [
                'nullable',
                'date',
            ],

            'start_finance_period_month' => [
                'required',
                'integer',
                'min:1',
                'max:12',
            ],

            'strict_journal_posting' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Validate the retained earnings formula after the basic rules pass.
     *
     * The formula is checked against the chart setting row that is about to be
     * updated, so a formula that no longer evaluates is rejected before the
     * controller writes it.
     *
     * @param  Validator  $validator  The validator instance.
     * @return \Closure(Validator): void
     */
    public function after(Validator $validator): \Closure
    {
        /** @var ChartSetting $chartSetting */
        $chartSetting = $this->route('chartSetting');

        return function (Validator $v) use ($chartSetting): void {
            $formula = $this->input('retained_earnings_formula');

            if (is_string($formula) && ! $chartSetting->validateRetainedEarningsFormula($formula)) {
                $v->errors()->add(
                    'retained_earnings_formula',
                    __('chart_settings.validation.invalid_retained_earnings_formula')
                );
            }

            // Each money-account dropdown is scoped to the charts that have
            // a row in the corresponding account table. A chart selected for
            // the bank dropdown must exist in `banks`, not just in `charts`.
            $this->validateMoneyAccountChart(
                $v,
                'bank_chart_uuid',
                'banks',
                'chart_settings.validation.bank_chart_not_in_banks',
            );

            $this->validateMoneyAccountChart(
                $v,
                'cash_chart_uuid',
                'cash_accounts',
                'chart_settings.validation.cash_chart_not_in_cash_accounts',
            );

            $this->validateMoneyAccountChart(
                $v,
                'e_wallet_chart_uuid',
                'e_wallet_accounts',
                'chart_settings.validation.e_wallet_chart_not_in_e_wallet_accounts',
            );
        };
    }

    /**
     * Verify that a chart UUID belongs to the expected money-account table.
     *
     * The dropdown on the edit page only offers charts that have a row in the
     * account table, so a valid submission always passes. The check exists to
     * reject a tampered request before it writes a wrong default.
     *
     * @param  Validator  $v  The validator with accumulated errors.
     * @param  string  $field  The request field being validated.
     * @param  string  $table  The account table the chart must belong to.
     * @param  string  $messageKey  The translation key for the error message.
     */
    private function validateMoneyAccountChart(
        Validator $v,
        string $field,
        string $table,
        string $messageKey,
    ): void {
        $chartId = $this->input($field);

        if (blank($chartId)) {
            return;
        }

        $exists = DB::table($table)
            ->where('chart_id', $chartId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $exists) {
            $v->errors()->add($field, __($messageKey));
        }
    }
}
