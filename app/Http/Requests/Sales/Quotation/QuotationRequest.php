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

namespace App\Http\Requests\Sales\Quotation;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a quotation, named.
 *
 * Creating and editing a quotation ask for the same fields but not quite the
 * same ones, so each states its own rules rather than one inheriting from the
 * other: an edit carries the quotation number and a rule inherited and then
 * removed is a rule nobody can read in one place. What they do share is the
 * naming of the fields, which is here, so a controller asking for the company
 * gets the same field whichever request it was handed.
 *
 * Each accessor returns null when the field was not supplied, rather than the
 * empty string or a zero. The caller needs to tell "not chosen" from "chosen as
 * nothing", because a quotation with no contact falls back to none and one with
 * an empty contact is a mistake worth reporting.
 */
abstract class QuotationRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function companyId(): ?string
    {
        return $this->stringOrNull('company_id');
    }

    public function contactId(): ?string
    {
        return $this->stringOrNull('contact_id');
    }

    public function referenceNumber(): ?string
    {
        return $this->stringOrNull('reference_number');
    }

    public function dateValue(): string
    {
        return $this->stringOrEmpty('date');
    }

    public function expiryDate(): ?string
    {
        return $this->stringOrNull('expiry_date');
    }

    public function status(): string
    {
        return $this->stringOrEmpty('status');
    }

    public function notes(): ?string
    {
        return $this->stringOrNull('notes');
    }
}
