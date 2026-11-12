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

namespace app\Http\Requests\Bank\Receipt;

use App\Enums\Finance\PaymentTransferType;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;
use Illuminate\Validation\Rule;

/**
 * The fields of a receipt, named.
 *
 * Creating and editing a receipt ask for the same fields but not quite the same
 * ones, so each states its own rules rather than one inheriting from the other: an
 * edit must not silently accept a new attachment, and a rule inherited and then
 * removed is a rule nobody can read in one place. What they do share is the naming
 * of the fields, which is here, so a controller asking for the amount gets the
 * same field whichever request it was handed.
 */
abstract class ReceiptRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function companyId(): ?string
    {
        return $this->stringOrNull('company_id');
    }

    public function documentDate(): string
    {
        return $this->stringOrEmpty('date');
    }

    public function amount(): float
    {
        return $this->floatOrZero('amount');
    }

    public function referenceNumber(): ?string
    {
        return $this->stringOrNull('reference_number');
    }

    public function notes(): ?string
    {
        return $this->stringOrNull('notes');
    }

    public function paymentTransferType(): ?string
    {
        return $this->stringOrNull('payment_transfer_type');
    }

    public function bankAccountId(): ?string
    {
        return $this->stringOrNull('bank_account_id');
    }

    public function cashAccountId(): ?string
    {
        return $this->stringOrNull('cash_account_id');
    }

    public function eWalletAccountId(): ?string
    {
        return $this->stringOrNull('e_wallet_account_id');
    }

    /**
     * Rules for the money account receiving the receipt, driven by transfer type.
     *
     * @return array<string, mixed>
     */
    protected function moneyAccountRules(): array
    {
        $chosen = PaymentTransferType::tryFrom($this->string('payment_transfer_type')->toString())?->accountColumn();

        $rules = [];

        foreach (
            [
                'bank_account_id' => 'banks',
                'cash_account_id' => 'cash_accounts',
                'e_wallet_account_id' => 'e_wallet_accounts',
            ] as $field => $table
        ) {
            $rules[$field] = [
                Rule::requiredIf($chosen === $field),
                'nullable',
                'uuid',
                Rule::exists($table, 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->whereNotNull('chart_id'),
            ];
        }

        return $rules;
    }
}
