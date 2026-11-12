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

namespace app\Traits;

use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Year\Period;
use App\Models\Year\Year;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

trait ChecksFinancialPostingDate
{
    /**
     * Get the client-side date bounds for a financial posting form.
     *
     * Normal posting permits dates within the current financial year. Strict
     * journal posting narrows the date picker to the current financial period.
     * Server-side validation remains authoritative.
     *
     * @return array{postingDateMin: string|null, postingDateMax: string|null}
     */
    protected function financialPostingDateBounds(): array
    {
        $chartSetting = ChartSetting::query()->first();

        if ($chartSetting === null) {
            return [
                'postingDateMin' => null,
                'postingDateMax' => null,
            ];
        }

        $today = Carbon::today()->toDateString();

        // Strict journal posting narrows the picker to the period that contains
        // today. Otherwise the whole financial year that contains today is open.
        $dateRange = $chartSetting->strict_journal_posting
            ? Period::query()
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first()
            : Year::query()
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

        if ($dateRange === null) {
            return [
                'postingDateMin' => null,
                'postingDateMax' => null,
            ];
        }

        return [
            'postingDateMin' => $dateRange->start_date->toDateString(),
            'postingDateMax' => $dateRange->end_date->toDateString(),
        ];
    }

    /**
     * Ensure a posting date belongs to an open accounting period.
     *
     * Closed years and periods always reject postings. When strict journal
     * posting is enabled, the date must also be inside the period currently
     * selected by chart settings.
     *
     * @param  string  $date  Validated posting date.
     * @param  string  $field  Request field that receives any validation error.
     * @return void No return value; throws on validation failure.
     *
     * @throws ValidationException
     */
    protected function ensureFinancialPostingDate(string $date, string $field = 'date'): void
    {
        $postingDate = Carbon::parse($date)->toDateString();
        $chartSetting = ChartSetting::query()->first();

        if ($chartSetting === null) {
            $this->throwPostingDateError($field, 'common.errors.posting_date_settings_missing');
        }

        $period = Period::query()
            ->with('year')
            ->whereDate('start_date', '<=', $postingDate)
            ->whereDate('end_date', '>=', $postingDate)
            ->first();

        if ($period === null) {
            $this->throwPostingDateError($field, 'common.errors.posting_date_unavailable');
        }

        if ($period->year->is_closed) {
            $this->throwPostingDateError($field, 'common.errors.posting_date_year_closed');
        }

        if ($period->is_closed) {
            $this->throwPostingDateError($field, 'common.errors.posting_date_period_closed');
        }

        if (! $chartSetting->strict_journal_posting) {
            return;
        }

        $currentPeriod = Period::query()
            ->whereDate('start_date', '<=', Carbon::today()->toDateString())
            ->whereDate('end_date', '>=', Carbon::today()->toDateString())
            ->first();

        if ($currentPeriod === null) {
            $this->throwPostingDateError($field, 'common.errors.posting_date_current_period_missing');
        }

        if ($period->year_id !== $currentPeriod->year_id || $period->id !== $currentPeriod->id) {
            $this->throwPostingDateError($field, 'common.errors.posting_date_not_current_period');
        }
    }

    /**
     * Throw a translated validation error for the supplied request field.
     *
     * @param  string  $field  Request field that receives the error.
     * @param  string  $translationKey  Translation key in the common language file.
     * @return never This method always throws and never returns.
     *
     * @throws ValidationException
     */
    private function throwPostingDateError(string $field, string $translationKey): never
    {
        throw ValidationException::withMessages([
            $field => __($translationKey),
        ]);
    }
}
