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

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a purchase invoice, named.
 *
 * Creating and editing a purchase invoice ask for the same fields but not quite
 * the same ones, so each states its own rules rather than one inheriting from the
 * other: an edit must not silently accept a new attachment, and a rule inherited
 * and then removed is a rule nobody can read in one place. What they do share is
 * the naming of the fields, which is here, so a controller asking for a named
 * field gets the same thing whichever request it was handed.
 *
 * Each accessor returns null when the field was not supplied, rather than the
 * empty string or a zero. The caller needs to tell "not chosen" from "chosen as
 * nothing", because an invoice with no debit account falls back to the configured
 * default and one with an empty debit account is a mistake worth reporting.
 */
abstract class PurchaseInvoiceRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The supplier company the invoice is raised against, if the user named one.
     */
    public function companyId(): ?string
    {
        return $this->stringOrNull('company_id');
    }

    /**
     * The supplier contact, if the user chose one.
     */
    public function contactId(): ?string
    {
        return $this->stringOrNull('contact_id');
    }

    /**
     * The debit account for the invoice, when the user chose one.
     */
    public function debitId(): ?string
    {
        return $this->stringOrNull('debit_id');
    }

    /**
     * The credit account for the invoice, when the user chose one.
     */
    public function creditId(): ?string
    {
        return $this->stringOrNull('credit_id');
    }

    public function dateValue(): string
    {
        return $this->stringOrEmpty('date');
    }

    /**
     * The number of days the supplier allows for payment.
     */
    public function creditTermDays(): int
    {
        return $this->intOrZero('credit_term_days');
    }

    /**
     * The invoice amount, as a float for the arithmetic that follows.
     */
    public function amount(): float
    {
        return $this->floatOrZero('amount');
    }

    /**
     * The debit account for the payment portion, when the user chose one.
     */
    public function paymentDebitId(): ?string
    {
        return $this->stringOrNull('payment_debit_id');
    }

    /**
     * The credit account for the payment portion, when the user chose one.
     */
    public function paymentCreditId(): ?string
    {
        return $this->stringOrNull('payment_credit_id');
    }

    /**
     * Free-text notes for the invoice, if the user wrote any.
     */
    public function invoiceNotes(): ?string
    {
        return $this->stringOrNull('invoice_notes');
    }

    /**
     * Free-text notes for the payment, if the user wrote any.
     */
    public function paymentNotes(): ?string
    {
        return $this->stringOrNull('payment_notes');
    }
}
