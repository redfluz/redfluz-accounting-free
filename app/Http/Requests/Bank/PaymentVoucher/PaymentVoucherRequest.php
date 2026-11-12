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

namespace app\Http\Requests\Bank\PaymentVoucher;

use App\Enums\Finance\PaymentTransferType;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;
use Illuminate\Validation\Rule;

/**
 * The fields of a payment voucher, named.
 *
 * Creating and editing a payment voucher ask for the same fields but not quite the
 * same ones, so each states its own rules rather than one inheriting from the
 * other: an edit must not silently accept a new attachment, and a rule inherited
 * and then removed is a rule nobody can read in one place. What they do share is
 * the naming of the fields, which is here, so a controller asking for the amount
 * gets the same field whichever request it was handed.
 *
 * Each accessor returns null when the field was not supplied, rather than the
 * empty string or a zero. The caller needs to tell "not chosen" from "chosen as
 * nothing", because a voucher with no company falls back to the configured
 * default and one with an empty company is a mistake worth reporting.
 */
abstract class PaymentVoucherRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The company the voucher is raised against, if the user named one.
     *
     * The rest of the document falls back to the chart of accounts setting when
     * this is null, so the default belongs to the posting code rather than being
     * filled in during validation: making it a default here would hide the fact
     * that the account was never chosen.
     */
    public function companyId(): ?string
    {
        return $this->stringOrNull('company_id');
    }

    /**
     * The document date, as supplied by the user.
     */
    public function documentDate(): string
    {
        return $this->stringOrEmpty('date');
    }

    /**
     * The amount, as a float for the arithmetic that follows.
     *
     * The exact figure is recomputed from the decimal string before it is written,
     * because a float cannot hold every amount exactly and a ledger that balances
     * to the cent needs the cent.
     */
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

    /**
     * How the payment was made, if the user said. Null means not stated, which
     * the posting code treats differently from a stated transfer type.
     */
    public function paymentTransferType(): ?string
    {
        return $this->stringOrNull('payment_transfer_type');
    }

    /**
     * The bank account the money left, when the transfer type named a bank.
     */
    public function bankAccountId(): ?string
    {
        return $this->stringOrNull('bank_account_id');
    }

    /**
     * The cash register the money left, when the transfer type named cash.
     */
    public function cashAccountId(): ?string
    {
        return $this->stringOrNull('cash_account_id');
    }

    /**
     * The e-wallet the money left, when the transfer type named a wallet.
     */
    public function eWalletAccountId(): ?string
    {
        return $this->stringOrNull('e_wallet_account_id');
    }

    /**
     * The rules for the account the money leaves, whichever kind of account it is.
     *
     * Stated once here because creation and edit ask for the same thing: an account
     * is required when, and only when, the transfer type names that kind of
     * account. A cheque or a bank transfer points at a bank, a wallet payment at an
     * e-wallet and a cash payment at a register, and that mapping belongs to
     * {@see PaymentTransferType::accountColumn()} rather than being restated in
     * each request, where the two could quietly disagree.
     *
     * The account also has to be live and have a chart behind it, because the chart
     * is what the money posts against: an account with no chart cannot move money,
     * however deliberately it was chosen.
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
