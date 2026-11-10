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

namespace App\Http\Requests\Bank\SupplierRefund;

use App\Enums\Finance\LedgerType;
use App\Enums\Finance\PaymentTransferType;
use App\Http\Requests\BaseFormRequest;
use App\Models\Crm\ApLedger;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Traits\ChecksFilePermission;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * The fields of a supplier refund, named.
 *
 * Creating and editing a supplier refund ask for the same fields but not quite the
 * same ones, so each states its own rules rather than one inheriting from the
 * other: an edit must not silently accept a new attachment, and a rule inherited
 * and then removed is a rule nobody can read in one place. What they do share is
 * the naming of the fields, which is here, so a controller asking for the amount
 * gets the same field whichever request it was handed.
 *
 * Each accessor returns null when the field was not supplied, rather than the
 * empty string or a zero. The caller needs to tell "not chosen" from "chosen as
 * nothing", because a refund with no company falls back to the configured
 * default and one with an empty company is a mistake worth reporting.
 */
abstract class SupplierRefundRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The supplier company the refund is raised against, if the user named one.
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
     * How the money was returned, if the user said. Null means not stated, which
     * the posting code treats differently from a stated transfer type.
     */
    public function paymentTransferType(): ?string
    {
        return $this->stringOrNull('payment_transfer_type');
    }

    /**
     * The bank account the money came back into, when the transfer type named a bank.
     */
    public function bankAccountId(): ?string
    {
        return $this->stringOrNull('bank_account_id');
    }

    /**
     * The e-wallet the money came back into, when the transfer type named a wallet.
     */
    public function eWalletAccountId(): ?string
    {
        return $this->stringOrNull('e_wallet_account_id');
    }

    /**
     * The rules for the account the money moves through, whichever kind it is.
     *
     * Stated once here because creation and edit ask for the same thing: an account
     * is required when, and only when, the transfer type names that kind of account.
     * A cheque or a bank transfer points at a bank and a wallet payment at an
     * e-wallet, and that mapping belongs to
     * {@see PaymentTransferType::accountColumn()} rather than being restated in
     * each request, where the two could quietly disagree.
     *
     * A refund names two accounts and not three. Cash is not one of them, so there
     * is no register column on this document and no rule to fill it: money taken
     * back has to arrive in an account that can be recorded, which is what
     * {@see PaymentTransferType::refundable()} is for.
     *
     * The account also has to be live and have a chart behind it, because the chart
     * is what the money posts against: an account with no chart cannot move money,
     * however deliberately it was chosen.
     *
     * @return array<string, mixed>
     */
    protected function moneyAccountRules(): array
    {
        $chosen = PaymentTransferType::tryFrom((string) $this->input('payment_transfer_type'))?->accountColumn();

        $rules = [];

        foreach ([
            'bank_account_id' => 'banks',
            'e_wallet_account_id' => 'e_wallet_accounts',
        ] as $field => $table) {
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

    /**
     * The money still recoverable from the payments recorded against the invoice.
     *
     * A payment voucher debits the payable and a supplier refund credits it again,
     * so what is left on the debit side of the payables ledger is what the supplier
     * was actually paid and has not been given back. The figure is read the same way
     * the invoice options of the supplier refund controller read it, so the amount
     * the screen offers and the amount the server allows cannot drift apart.
     *
     * Null is returned when the refund names no invoice, because there is then no
     * payment to recover and nothing to measure against.
     *
     * @return string|null The refundable balance as a decimal string, or null when no invoice is named.
     */
    protected function refundableBalance(): ?string
    {
        $reference = $this->referenceNumber();

        if (blank($reference)) {
            return null;
        }

        /** @var Collection<int, ApLedger> $entries */
        $entries = ApLedger::query()
            ->where('type', LedgerType::PAYMENT)
            ->where('reference_number', $reference)
            ->get(['debit_amount', 'credit_amount']);

        return bcsub(
            bcadd((string) $entries->sum('debit_amount'), '0', 2),
            bcadd((string) $entries->sum('credit_amount'), '0', 2),
            2,
        );
    }

    /**
     * The rule that refuses a refund larger than the money still recoverable.
     *
     * The form caps the amount on the client, but a cap only the browser enforces is
     * a suggestion: the ledger is written from the amount the request carried, so the
     * same ceiling has to be checked where the request is accepted. A refund with no
     * invoice behind it is left to the invoice rule rather than being refused here for
     * a balance that was never named.
     *
     * @return Closure The validation rule to run against the amount.
     */
    protected function refundableCap(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_numeric($value)) {
                return;
            }

            $available = $this->refundableBalance();

            if ($available === null) {
                return;
            }

            if (bccomp(bcadd(DecimalService::normalize((string) $value), '0', 2), $available, 2) > 0) {
                $fail(__('refunds.validation.amount_exceeds_paid', [
                    'currency' => Setting::getCurrencySymbol(),
                    'amount' => number_format((float) $available, 2),
                ]));
            }
        };
    }
}
