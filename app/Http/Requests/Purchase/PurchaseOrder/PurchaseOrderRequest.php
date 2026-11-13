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

namespace App\Http\Requests\Purchase\PurchaseOrder;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a purchase order, named.
 *
 * Creating and editing a purchase order ask for the same fields but not quite the
 * same ones, so each states its own rules rather than one inheriting from the
 * other: an edit carries the order number and a rule inherited and then removed is
 * a rule nobody can read in one place. What they do share is the naming of the
 * fields, which is here, so a controller asking for the company gets the same
 * field whichever request it was handed.
 *
 * Each accessor returns null when the field was not supplied, rather than the
 * empty string or a zero. The caller needs to tell "not chosen" from "chosen as
 * nothing", because a purchase order with no contact falls back to none and one
 * with an empty contact is a mistake worth reporting.
 */
abstract class PurchaseOrderRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The supplier company the order is raised against.
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
     * The supplier's own reference number for the order, if supplied.
     */
    public function supplierReferenceNumber(): ?string
    {
        return $this->stringOrNull('supplier_reference_number');
    }

    /**
     * The supplier's sales order number, if supplied.
     */
    public function supplierSalesOrderNumber(): ?string
    {
        return $this->stringOrNull('supplier_sales_order_number');
    }

    public function dateValue(): string
    {
        return $this->stringOrEmpty('date');
    }

    public function expectedDate(): ?string
    {
        return $this->stringOrNull('expected_date');
    }

    /**
     * The number of days the supplier allows for payment.
     */
    public function creditTermDays(): int
    {
        return $this->intOrZero('credit_term_days');
    }

    /**
     * Free-text notes, if the user wrote any.
     */
    public function notes(): ?string
    {
        return $this->stringOrNull('notes');
    }
}
