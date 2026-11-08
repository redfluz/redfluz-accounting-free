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

namespace App\Enums\Bank;

/**
 * What a payment voucher is settling.
 *
 * A supplier voucher pays a company's bill; the other modes pay people the
 * business owes outside the payables ledger — an employee's claim, salary or
 * advance, or a payroll statutory body. Which one it is decides which account
 * the voucher debits and whether a company is attached to the document.
 */
enum PaymentFor: string
{
    case SUPPLIER = 'supplier';
    case CLAIM = 'claim';
    case SALARY = 'salary';
    case STATUTORY = 'statutory';
    case ADVANCE = 'advance';
    case LOAN = 'loan';

    /**
     * The translated name shown on the form.
     *
     * @return string The label for this payment mode.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPPLIER => __('payments.form.supplier'),
            self::CLAIM => __('payments.form.claim'),
            self::SALARY => __('payments.form.salary'),
            self::STATUTORY => __('payments.form.statutory'),
            self::ADVANCE => __('payments.form.advance'),
            self::LOAN => __('payments.form.loan'),
        };
    }

    /**
     * Whether the payee is a company from the payables ledger.
     *
     * @return bool True when a company is paid, false when a person or body is.
     */
    public function paysCompany(): bool
    {
        return $this === self::SUPPLIER;
    }
}
