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

namespace App\Support\Data\ChartOfAccount;

use App\Models\ChartOfAccount\ChartSetting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * Which accounts the system posts to by default, and how strict it is.

    These are the accounts a document falls back to when the user does not choose
    one, so they are settings rather than defaults on the columns they serve: a
    document that picks its own account must not be overwritten by a change here,
    and one that relies on the fallback must follow the change.

    The retained earnings formula is stored rather than computed, because the
    treatment of opening balances is a decision the business makes and it has to
    survive the software being upgraded.

    Strict posting decides whether a journal may be dated outside the current
    period. It is a flag rather than a fixed rule because whether that is
    permitted is a matter of how closely the books are being kept, not of what the
    entry means.
 */
class ChartSettingData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('cogs_chart_uuid')]
        public ?string $cogsChartUuid = null,

        #[MapName('income_chart_uuid')]
        public ?string $incomeChartUuid = null,

        #[MapName('bank_chart_uuid')]
        public ?string $bankChartUuid = null,

        #[MapName('cash_chart_uuid')]
        public ?string $cashChartUuid = null,

        #[MapName('e_wallet_chart_uuid')]
        public ?string $eWalletChartUuid = null,

        #[MapName('client_chart_uuid')]
        public ?string $clientChartUuid = null,

        #[MapName('vendor_chart_uuid')]
        public ?string $vendorChartUuid = null,

        #[MapName('inventory_chart_uuid')]
        public ?string $inventoryChartUuid = null,

        #[MapName('stock_shortage_chart_uuid')]
        public ?string $stockShortageChartUuid = null,

        #[MapName('stock_surplus_chart_uuid')]
        public ?string $stockSurplusChartUuid = null,

        #[MapName('deposit_chart_uuid')]
        public ?string $depositChartUuid = null,

        #[MapName('input_tax_chart_uuid')]
        public ?string $inputTaxChartUuid = null,

        #[MapName('output_tax_chart_uuid')]
        public ?string $outputTaxChartUuid = null,

        #[MapName('retained_earnings_chart_uuid')]
        public ?string $retainedEarningsChartUuid = null,

        #[MapName('disposal_gain_chart_uuid')]
        public ?string $disposalGainChartUuid = null,

        #[MapName('disposal_loss_chart_uuid')]
        public ?string $disposalLossChartUuid = null,

        #[MapName('employee_advance_chart_uuid')]
        public ?string $employeeAdvanceChartUuid = null,

        #[MapName('retained_earnings_formula')]
        public ?string $retainedEarningsFormula = null,

        #[MapName('closing_date')]
        public ?CarbonImmutable $closingDate = null,

        #[MapName('start_finance_period_month')]
        public ?int $startFinancePeriodMonth = null,

        #[MapName('strict_journal_posting')]
        public ?bool $strictJournalPosting = null,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt = null,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt = null,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt = null,
    ) {}

    /**
     * The same moment, as the type this object holds.
     *
     * @param  CarbonInterface|null  $value  The moment to normalise.
     * @return CarbonImmutable|null The moment as an immutable, or null.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the model
     * holds, and the two are written in different formats. Taking the model's
     * attributes instead means the cast is what is mapped.
     */
    public static function fromModel(ChartSetting $model): self
    {
        return new self(
            id: $model->id,
            cogsChartUuid: $model->cogs_chart_uuid,
            incomeChartUuid: $model->income_chart_uuid,
            bankChartUuid: $model->bank_chart_uuid,
            cashChartUuid: $model->cash_chart_uuid,
            eWalletChartUuid: $model->e_wallet_chart_uuid,
            clientChartUuid: $model->client_chart_uuid,
            vendorChartUuid: $model->vendor_chart_uuid,
            inventoryChartUuid: $model->inventory_chart_uuid,
            stockShortageChartUuid: $model->stock_shortage_chart_uuid,
            stockSurplusChartUuid: $model->stock_surplus_chart_uuid,
            depositChartUuid: $model->deposit_chart_uuid,
            inputTaxChartUuid: $model->input_tax_chart_uuid,
            outputTaxChartUuid: $model->output_tax_chart_uuid,
            retainedEarningsChartUuid: $model->retained_earnings_chart_uuid,
            disposalGainChartUuid: $model->disposal_gain_chart_uuid,
            disposalLossChartUuid: $model->disposal_loss_chart_uuid,
            employeeAdvanceChartUuid: $model->employee_advance_chart_uuid,
            retainedEarningsFormula: $model->retained_earnings_formula,
            closingDate: self::moment($model->closing_date),
            startFinancePeriodMonth: $model->start_finance_period_month,
            strictJournalPosting: $model->strict_journal_posting,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a chart setting is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made. Creating from the full row
     * would insert null for timestamps the model fills in for itself.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'cogs_chart_uuid' => $this->cogsChartUuid,
            'income_chart_uuid' => $this->incomeChartUuid,
            'bank_chart_uuid' => $this->bankChartUuid,
            'cash_chart_uuid' => $this->cashChartUuid,
            'e_wallet_chart_uuid' => $this->eWalletChartUuid,
            'client_chart_uuid' => $this->clientChartUuid,
            'vendor_chart_uuid' => $this->vendorChartUuid,
            'inventory_chart_uuid' => $this->inventoryChartUuid,
            'stock_shortage_chart_uuid' => $this->stockShortageChartUuid,
            'stock_surplus_chart_uuid' => $this->stockSurplusChartUuid,
            'deposit_chart_uuid' => $this->depositChartUuid,
            'input_tax_chart_uuid' => $this->inputTaxChartUuid,
            'output_tax_chart_uuid' => $this->outputTaxChartUuid,
            'retained_earnings_chart_uuid' => $this->retainedEarningsChartUuid,
            'disposal_gain_chart_uuid' => $this->disposalGainChartUuid,
            'disposal_loss_chart_uuid' => $this->disposalLossChartUuid,
            'employee_advance_chart_uuid' => $this->employeeAdvanceChartUuid,
            'retained_earnings_formula' => $this->retainedEarningsFormula,
            'closing_date' => $this->closingDate?->format('Y-m-d'),
            'start_finance_period_month' => $this->startFinancePeriodMonth,
            'strict_journal_posting' => $this->strictJournalPosting,
        ];
    }

    /**
     * The row as the chart_settings table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'cogs_chart_uuid' => $this->cogsChartUuid,
            'income_chart_uuid' => $this->incomeChartUuid,
            'bank_chart_uuid' => $this->bankChartUuid,
            'cash_chart_uuid' => $this->cashChartUuid,
            'e_wallet_chart_uuid' => $this->eWalletChartUuid,
            'client_chart_uuid' => $this->clientChartUuid,
            'vendor_chart_uuid' => $this->vendorChartUuid,
            'inventory_chart_uuid' => $this->inventoryChartUuid,
            'stock_shortage_chart_uuid' => $this->stockShortageChartUuid,
            'stock_surplus_chart_uuid' => $this->stockSurplusChartUuid,
            'deposit_chart_uuid' => $this->depositChartUuid,
            'input_tax_chart_uuid' => $this->inputTaxChartUuid,
            'output_tax_chart_uuid' => $this->outputTaxChartUuid,
            'retained_earnings_chart_uuid' => $this->retainedEarningsChartUuid,
            'disposal_gain_chart_uuid' => $this->disposalGainChartUuid,
            'disposal_loss_chart_uuid' => $this->disposalLossChartUuid,
            'employee_advance_chart_uuid' => $this->employeeAdvanceChartUuid,
            'retained_earnings_formula' => $this->retainedEarningsFormula,
            'closing_date' => $this->closingDate?->format('Y-m-d'),
            'start_finance_period_month' => $this->startFinancePeriodMonth,
            'strict_journal_posting' => $this->strictJournalPosting,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
