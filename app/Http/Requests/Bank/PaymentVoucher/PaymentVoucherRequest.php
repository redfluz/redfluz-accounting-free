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

namespace App\Http\Requests\Bank\PaymentVoucher;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

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
 * nothing", because a voucher with no debit account falls back to the configured
 * default and one with an empty debit account is a mistake worth reporting.
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
     * The account the voucher is debited against, when the user chose one.
     */
    public function debitId(): ?string
    {
        return $this->stringOrNull('debit_id');
    }

    /**
     * The account the creditor is credited against, when the user chose one.
     */
    public function creditId(): ?string
    {
        return $this->stringOrNull('credit_id');
    }

    public function documentDate(): string
    {
        return $this->stringOrEmpty('document_date');
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
}
