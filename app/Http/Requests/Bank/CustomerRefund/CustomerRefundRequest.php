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

namespace App\Http\Requests\Bank\CustomerRefund;

use App\Enums\Finance\LedgerType;
use App\Enums\Finance\PaymentTransferType;
use App\Http\Requests\BaseFormRequest;
use App\Models\Crm\ArLedger;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Traits\ChecksFilePermission;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * The fields of a customer refund, named.
 *
 * Creating and editing a customer refund ask for the same fields but not quite the
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
abstract class CustomerRefundRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The company the refund is raised against, if the user named one.
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
     * The bank account the money left, when the transfer type named a bank.
     */
    public function bankAccountId(): ?string
    {
        return $this->stringOrNull('bank_account_id');
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
     * is required when, and only when, the transfer type names that kind of account.
     * A cheque or a bank transfer points at a bank and a wallet payment at an
     * e-wallet, and that mapping belongs to
     * {@see PaymentTransferType::accountColumn()} rather than being restated in
     * each request, where the two could quietly disagree.
     *
     * A refund names two accounts and not three. Cash is not one of them, so there
     * is no register column on this document and no rule to fill it: money handed
     * back has to leave an account that can be recorded, which is what
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
        $chosen = PaymentTransferType::tryFrom($this->string('payment_transfer_type')->toString())?->accountColumn();

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
     * The money still returnable to the customer from the receipts on the invoice.
     *
     * A receipt credits the receivable and a customer refund debits it again, so what
     * is left on the credit side of the receivable account is what the customer
     * actually paid and has not been given back. The receivable ledger holds a row of
     * every payment, the account the money moved among them, so the sum is read on the
     * receivable account alone. The figure is read the same way the invoice options of
     * the customer refund controller read it, so the amount the screen offers and the
     * amount the server allows cannot drift apart.
     *
     * Null is returned when the refund names no invoice, because there is then no
     * payment to return and nothing to measure against.
     *
     * @return numeric-string|null The refundable balance as a decimal string, or null when no invoice is named.
     */
    protected function refundableBalance(): ?string
    {
        $reference = $this->referenceNumber();

        if (blank($reference)) {
            return null;
        }

        /** @var Collection<int, ArLedger> $entries */
        $entries = ArLedger::query()
            ->againstReceivableAccount()
            ->where('type', LedgerType::PAYMENT)
            ->where('reference_number', $reference)
            ->get(['debit_amount', 'credit_amount']);

        $credit = '0';
        $debit = '0';
        foreach ($entries as $entry) {
            $credit = bcadd($credit, $entry->credit_amount, 2);
            $debit = bcadd($debit, $entry->debit_amount, 2);
        }

        return bcsub($credit, $debit, 2);
    }

    /**
     * The rule that refuses a refund larger than the money still returnable.
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
